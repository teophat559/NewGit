<?php
/**
 * BVOTE 2025 - User Authentication Handler
 * Xử lý đăng nhập bằng email/SĐT và mật khẩu
 *
 * 🔒 INTERFACE LOCKED - Backend processing only
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

class UserAuth {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Handle login request
     */
    public function handleLogin() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || !isset($input['email']) || !isset($input['password'])) {
                throw new Exception('Missing required fields');
            }

            $email = trim($input['email']);
            $password = $input['password'];
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            // Check if user exists
            $user = $this->findUser($email);

            if (!$user) {
                $this->logLoginAttempt($email, 'failed', 'user_not_found', $ipAddress, $userAgent);
                return $this->sendResponse(false, 'Tài khoản không tồn tại');
            }

            // Verify password
            if (!password_verify($password, $user['password'])) {
                $this->logLoginAttempt($email, 'failed', 'invalid_password', $ipAddress, $userAgent);
                return $this->sendResponse(false, 'Mật khẩu không chính xác');
            }

            // Check if account is active
            if ($user['status'] !== 'active') {
                $this->logLoginAttempt($email, 'failed', 'account_inactive', $ipAddress, $userAgent);
                return $this->sendResponse(false, 'Tài khoản đã bị khóa hoặc chưa được kích hoạt');
            }

            // Check for suspicious login (new device/IP)
            $deviceCheck = $this->checkDeviceSecurity($user['id'], $ipAddress, $userAgent);

            if ($deviceCheck['requires_approval']) {
                $this->logLoginAttempt($email, 'pending_approval', 'new_device', $ipAddress, $userAgent);
                return $this->sendResponse(false, 'Đăng nhập từ thiết bị mới cần được phê duyệt', [
                    'requires_approval' => true,
                    'device_info' => $deviceCheck['device_info']
                ]);
            }

            if ($deviceCheck['requires_otp']) {
                // Generate and send OTP
                $otpCode = $this->generateOTP($user['id']);
                $this->sendOTP($user, $otpCode);

                $this->logLoginAttempt($email, 'otp_required', 'two_factor', $ipAddress, $userAgent);
                return $this->sendResponse(false, 'Yêu cầu xác minh OTP', [
                    'requires_otp' => true,
                    'otp_sent_to' => $this->maskSensitiveData($user['email'])
                ]);
            }

            // Successful login
            $sessionData = $this->createUserSession($user, $ipAddress, $userAgent);
            $this->logLoginAttempt($email, 'success', null, $ipAddress, $userAgent);

            return $this->sendResponse(true, 'Đăng nhập thành công', [
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['full_name'],
                    'email' => $user['email'],
                    'avatar' => $user['avatar']
                ],
                'session' => $sessionData
            ]);

        } catch (Exception $e) {
            return $this->sendResponse(false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    /**
     * Handle OTP verification
     */
    public function handleOTPVerification() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || !isset($input['user_id']) || !isset($input['otp_code'])) {
                throw new Exception('Missing required fields');
            }

            $userId = $input['user_id'];
            $otpCode = $input['otp_code'];
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            // Verify OTP
            if (!$this->verifyOTP($userId, $otpCode)) {
                return $this->sendResponse(false, 'Mã OTP không chính xác hoặc đã hết hạn');
            }

            // Get user data
            $user = $this->getUserById($userId);
            if (!$user) {
                return $this->sendResponse(false, 'Người dùng không tồn tại');
            }

            // Create session after successful OTP verification
            $sessionData = $this->createUserSession($user, $ipAddress, $userAgent);
            $this->logLoginAttempt($user['email'], 'success', 'otp_verified', $ipAddress, $userAgent);

            return $this->sendResponse(true, 'Xác minh OTP thành công', [
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['full_name'],
                    'email' => $user['email'],
                    'avatar' => $user['avatar']
                ],
                'session' => $sessionData
            ]);

        } catch (Exception $e) {
            return $this->sendResponse(false, 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    /**
     * Find user by email or phone
     */
    private function findUser($emailOrPhone) {
        $stmt = $this->pdo->prepare("
            SELECT id, email, phone, password, full_name, avatar, status, created_at
            FROM users
            WHERE email = ? OR phone = ?
            LIMIT 1
        ");

        $stmt->execute([$emailOrPhone, $emailOrPhone]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get user by ID
     */
    private function getUserById($userId) {
        $stmt = $this->pdo->prepare("
            SELECT id, email, phone, full_name, avatar, status
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check device security
     */
    private function checkDeviceSecurity($userId, $ipAddress, $userAgent) {
        // Check if this device/IP has been used before
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count
            FROM login_attempts
            WHERE user_id = ?
            AND ip_address = ?
            AND status = 'success'
            AND attempt_time > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");

        $stmt->execute([$userId, $ipAddress]);
        $knownDevice = $stmt->fetchColumn() > 0;

        // Check for suspicious activity
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as failed_attempts
            FROM login_attempts
            WHERE ip_address = ?
            AND status = 'failed'
            AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");

        $stmt->execute([$ipAddress]);
        $failedAttempts = $stmt->fetchColumn();

        $result = [
            'requires_approval' => false,
            'requires_otp' => false,
            'device_info' => [
                'ip' => $ipAddress,
                'device' => $this->parseUserAgent($userAgent),
                'is_known' => $knownDevice
            ]
        ];

        // New device from unknown location
        if (!$knownDevice) {
            $result['requires_otp'] = true;
        }

        // Too many failed attempts
        if ($failedAttempts >= 5) {
            $result['requires_approval'] = true;
        }

        return $result;
    }

    /**
     * Generate OTP code
     */
    private function generateOTP($userId) {
        $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        // Store OTP in database
        $stmt = $this->pdo->prepare("
            INSERT INTO user_otps (user_id, otp_code, expires_at, created_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            otp_code = VALUES(otp_code),
            expires_at = VALUES(expires_at),
            created_at = NOW()
        ");

        $stmt->execute([$userId, $otpCode, $expiresAt]);

        return $otpCode;
    }

    /**
     * Send OTP to user
     */
    private function sendOTP($user, $otpCode) {
        // In a real implementation, you would send SMS or email here
        // For now, we'll just log it
        error_log("OTP for user {$user['email']}: $otpCode");

        // You can integrate with SMS/Email services here
        // Example: Twilio, SendGrid, etc.
    }

    /**
     * Verify OTP code
     */
    private function verifyOTP($userId, $otpCode) {
        $stmt = $this->pdo->prepare("
            SELECT id FROM user_otps
            WHERE user_id = ?
            AND otp_code = ?
            AND expires_at > NOW()
            AND used_at IS NULL
            ORDER BY created_at DESC
            LIMIT 1
        ");

        $stmt->execute([$userId, $otpCode]);
        $otp = $stmt->fetch();

        if ($otp) {
            // Mark OTP as used
            $stmt = $this->pdo->prepare("
                UPDATE user_otps
                SET used_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$otp['id']]);

            return true;
        }

        return false;
    }

    /**
     * Create user session
     */
    private function createUserSession($user, $ipAddress, $userAgent) {
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

        // Store session in database
        $stmt = $this->pdo->prepare("
            INSERT INTO user_sessions (
                user_id, session_token, ip_address, user_agent,
                expires_at, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $user['id'],
            $sessionToken,
            $ipAddress,
            $userAgent,
            $expiresAt
        ]);

        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['session_token'] = $sessionToken;
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];

        return [
            'token' => $sessionToken,
            'expires_at' => $expiresAt
        ];
    }

    /**
     * Log login attempt
     */
    private function logLoginAttempt($email, $status, $errorType, $ipAddress, $userAgent) {
        $stmt = $this->pdo->prepare("
            INSERT INTO login_attempts (
                email, status, error_type, ip_address,
                user_agent, attempt_time
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([$email, $status, $errorType, $ipAddress, $userAgent]);
    }

    /**
     * Parse user agent for device info
     */
    private function parseUserAgent($userAgent) {
        if (strpos($userAgent, 'Mobile') !== false) {
            return 'Mobile Device';
        } elseif (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome Browser';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox Browser';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari Browser';
        } else {
            return 'Unknown Device';
        }
    }

    /**
     * Mask sensitive data
     */
    private function maskSensitiveData($data) {
        if (strpos($data, '@') !== false) {
            // Email
            $parts = explode('@', $data);
            $username = $parts[0];
            $domain = $parts[1];

            if (strlen($username) > 3) {
                $username = substr($username, 0, 2) . str_repeat('*', strlen($username) - 2);
            }

            return $username . '@' . $domain;
        } else {
            // Phone number
            if (strlen($data) > 6) {
                return substr($data, 0, 3) . str_repeat('*', strlen($data) - 6) . substr($data, -3);
            }
        }

        return $data;
    }

    /**
     * Send JSON response
     */
    private function sendResponse($success, $message, $data = null) {
        $response = [
            'success' => $success,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response);
        exit;
    }
}

// Handle requests
$auth = new UserAuth();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        $auth->handleLogin();
        break;

    case 'verify_otp':
        $auth->handleOTPVerification();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>
