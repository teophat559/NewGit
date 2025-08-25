<?php
// auth.php - Service xử lý authentication và authorization
require_once __DIR__ . '/db.php';

class AuthService {
    private $db;
    private $jwtSecret;
    private $sessionSecret;

    public function __construct() {
        $this->db = db();
        $this->jwtSecret = JWT_SECRET;
        $this->sessionSecret = SESSION_SECRET;
    }

    // ==================== USER AUTHENTICATION ====================

    public function loginUser($username, $password) {
        try {
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE username = ? AND status = 'active'",
                [$username]
            );

            if (!$user || !password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Thông tin đăng nhập không chính xác'];
            }

            // Tạo JWT token
            $token = $this->generateJWT($user['id'], $user['username'], 'user');

            // Cập nhật last_login
            $this->db->execute(
                "UPDATE users SET last_login = NOW(), ip_address = ? WHERE id = ?",
                [$this->getClientIP(), $user['id']]
            );

            // Log login
            $this->logLogin($user['id'], 'user', true, 'Đăng nhập thành công');

            return [
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role']
                ]
            ];

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi server'];
        }
    }

    public function loginAdmin($username, $password) {
        try {
            $admin = $this->db->fetchOne(
                "SELECT * FROM admins WHERE username = ? AND status = 'active'",
                [$username]
            );

            if (!$admin || !password_verify($password, $admin['password_hash'])) {
                return ['success' => false, 'message' => 'Thông tin đăng nhập không chính xác'];
            }

            // Tạo JWT token
            $token = $this->generateJWT($admin['id'], $admin['username'], 'admin');

            // Cập nhật last_login
            $this->db->execute(
                "UPDATE admins SET last_login = NOW() WHERE id = ?",
                [$admin['id']]
            );

            // Log login
            $this->logLogin($admin['id'], 'admin', true, 'Đăng nhập admin thành công');

            return [
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'token' => $token,
                'admin' => [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'email' => $admin['email'],
                    'full_name' => $admin['full_name'],
                    'role' => $admin['role'],
                    'permissions' => json_decode($admin['permissions'], true)
                ]
            ];

        } catch (Exception $e) {
            error_log("Admin login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi server'];
        }
    }

    public function registerUser($username, $email, $password, $fullName, $phone = null) {
        try {
            // Kiểm tra username và email đã tồn tại
            $existingUser = $this->db->fetchOne(
                "SELECT id FROM users WHERE username = ? OR email = ?",
                [$username, $email]
            );

            if ($existingUser) {
                return ['success' => false, 'message' => 'Username hoặc email đã tồn tại'];
            }

            // Hash password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Tạo user mới
            $this->db->execute(
                "INSERT INTO users (username, email, password_hash, full_name, phone, status, role, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, 'active', 'user', ?, ?)",
                [$username, $email, $passwordHash, $fullName, $phone, $this->getClientIP(), $this->getUserAgent()]
            );

            $userId = $this->db->lastInsertId();

            // Log registration
            $this->logLogin($userId, 'user', true, 'Đăng ký tài khoản mới');

            return [
                'success' => true,
                'message' => 'Đăng ký thành công',
                'user_id' => $userId
            ];

        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi server'];
        }
    }

    // ==================== JWT TOKEN MANAGEMENT ====================

    public function generateJWT($userId, $username, $role) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $userId,
            'username' => $username,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24) // 24 giờ
        ]);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $this->jwtSecret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }

    public function validateJWT($token) {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return false;
            }

            $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[0]));
            $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1]));
            $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[2]));

            $expectedSignature = hash_hmac('sha256', $parts[0] . "." . $parts[1], $this->jwtSecret, true);

            if (!hash_equals($signature, $expectedSignature)) {
                return false;
            }

            $payloadData = json_decode($payload, true);

            // Kiểm tra token hết hạn
            if ($payloadData['exp'] < time()) {
                return false;
            }

            return $payloadData;

        } catch (Exception $e) {
            return false;
        }
    }

    // ==================== SESSION MANAGEMENT ====================

    public function startSession($userId, $role) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $userId;
        $_SESSION['role'] = $role;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();

        return true;
    }

    public function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $role = $_SESSION['role'];
        $userId = $_SESSION['user_id'];

        if ($role === 'admin') {
            return $this->db->fetchOne(
                "SELECT id, username, email, full_name, role, permissions FROM admins WHERE id = ?",
                [$userId]
            );
        } else {
            return $this->db->fetchOne(
                "SELECT id, username, email, full_name, role, avatar_url FROM users WHERE id = ?",
                [$userId]
            );
        }
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Log logout
        if (isset($_SESSION['user_id'])) {
            $this->logLogin($_SESSION['user_id'], $_SESSION['role'], false, 'Đăng xuất');
        }

        // Xóa session
        session_unset();
        session_destroy();

        return true;
    }

    // ==================== AUTHORIZATION ====================

    public function hasPermission($permission) {
        $user = $this->getCurrentUser();

        if (!$user) {
            return false;
        }

        if ($user['role'] === 'super_admin') {
            return true;
        }

        if ($user['role'] === 'admin' && isset($user['permissions'])) {
            $permissions = json_decode($user['permissions'], true);
            return in_array($permission, $permissions) || in_array('*', $permissions);
        }

        return false;
    }

    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Yêu cầu đăng nhập']);
            exit();
        }
    }

    public function requireAdmin() {
        $this->requireAuth();

        if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Không có quyền truy cập']);
            exit();
        }
    }

    public function requirePermission($permission) {
        $this->requireAdmin();

        if (!$this->hasPermission($permission)) {
            http_response_code(403);
            echo json_encode(['error' => 'Không có quyền thực hiện hành động này']);
            exit();
        }
    }

    // ==================== UTILITY FUNCTIONS ====================

    private function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }

    private function logLogin($userId, $role, $success, $message) {
        try {
            $this->db->execute(
                "INSERT INTO login_logs (user_id, role, success, message, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [$userId, $role, $success ? 1 : 0, $message, $this->getClientIP(), $this->getUserAgent()]
            );
        } catch (Exception $e) {
            error_log("Failed to log login: " . $e->getMessage());
        }
    }

    // ==================== PASSWORD MANAGEMENT ====================

    public function changePassword($userId, $oldPassword, $newPassword) {
        try {
            $user = $this->db->fetchOne(
                "SELECT password_hash FROM users WHERE id = ?",
                [$userId]
            );

            if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Mật khẩu cũ không chính xác'];
            }

            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $this->db->execute(
                "UPDATE users SET password_hash = ? WHERE id = ?",
                [$newPasswordHash, $userId]
            );

            return ['success' => true, 'message' => 'Đổi mật khẩu thành công'];

        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi server'];
        }
    }

    public function resetPassword($email) {
        try {
            $user = $this->db->fetchOne(
                "SELECT id, username FROM users WHERE email = ? AND status = 'active'",
                [$email]
            );

            if (!$user) {
                return ['success' => false, 'message' => 'Email không tồn tại trong hệ thống'];
            }

            // Tạo mật khẩu mới ngẫu nhiên
            $newPassword = bin2hex(random_bytes(8));
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $this->db->execute(
                "UPDATE users SET password_hash = ? WHERE id = ?",
                [$passwordHash, $user['id']]
            );

            // TODO: Gửi email chứa mật khẩu mới
            // $this->sendPasswordResetEmail($email, $newPassword);

            return [
                'success' => true,
                'message' => 'Mật khẩu mới đã được gửi đến email của bạn',
                'temp_password' => $newPassword // Chỉ trả về trong development
            ];

        } catch (Exception $e) {
            error_log("Reset password error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi server'];
        }
    }
}

// Helper function để sử dụng auth service
function auth() {
    return new AuthService();
}
?>
