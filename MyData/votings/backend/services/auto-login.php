<?php
/**
 * Auto Login Service - BVOTE
 * Xử lý hệ thống đăng nhập tự động với phê duyệt admin
 */

require_once __DIR__ . '/db.php';

class AutoLoginService {
    private $db;
    private $settings;

    public function __construct() {
        $this->db = getConnection();
        $this->loadSettings();
    }

    /**
     * Tải cài đặt hệ thống
     */
    private function loadSettings() {
        $stmt = $this->db->prepare("SELECT setting_key, setting_value, setting_type FROM system_settings");
        $stmt->execute();
        $this->settings = [];

        while ($row = $stmt->fetch()) {
            $value = $row['setting_value'];
            switch ($row['setting_type']) {
                case 'number':
                    $value = (int)$value;
                    break;
                case 'boolean':
                    $value = $value === 'true';
                    break;
                case 'json':
                    $value = json_decode($value, true);
                    break;
            }
            $this->settings[$row['setting_key']] = $value;
        }
    }

    /**
     * Khởi tạo yêu cầu đăng nhập
     */
    public function createLoginRequest($userHint, $platform, $ipAddress = null, $userAgent = null) {
        try {
            // Kiểm tra rate limit
            if (!$this->checkRateLimit($ipAddress)) {
                return [
                    'success' => false,
                    'error' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau.'
                ];
            }

            // Tạo request ID duy nhất
            $requestId = $this->generateRequestId();

            // Tính thời gian hết hạn
            $ttl = $this->settings['login_request_ttl'] ?? 120;
            $expiresAt = date('Y-m-d H:i:s', time() + $ttl);

            // Lưu yêu cầu vào database
            $stmt = $this->db->prepare("
                INSERT INTO login_requests (
                    request_id, user_hint, platform, status,
                    ttl_expires_at, ip_address, user_agent, meta_json
                ) VALUES (?, ?, ?, 'PENDING_REVIEW', ?, ?, ?, ?)
            ");

            $meta = [
                'created_at' => time(),
                'user_hint' => $userHint,
                'platform' => $platform
            ];

            $stmt->execute([
                $requestId,
                $userHint,
                $platform,
                $expiresAt,
                $ipAddress,
                $userAgent,
                json_encode($meta)
            ]);

            // Ghi log
            $this->logAction('system', null, 'login_request_created', 'login_requests', $requestId, [
                'platform' => $platform,
                'user_hint' => $userHint,
                'ip' => $ipAddress
            ]);

            return [
                'success' => true,
                'request_id' => $requestId,
                'status' => 'PENDING_REVIEW',
                'expires_in' => $ttl,
                'message' => 'Yêu cầu đăng nhập đã được tạo và chờ phê duyệt'
            ];

        } catch (Exception $e) {
            error_log("AutoLogin createLoginRequest error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Lỗi tạo yêu cầu đăng nhập'
            ];
        }
    }

    /**
     * Kiểm tra trạng thái yêu cầu
     */
    public function getRequestStatus($requestId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM login_requests
                WHERE request_id = ? AND ttl_expires_at > NOW()
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                return [
                    'success' => false,
                    'error' => 'Yêu cầu không tồn tại hoặc đã hết hạn'
                ];
            }

            // Kiểm tra nếu hết hạn
            if (strtotime($request['ttl_expires_at']) < time()) {
                $this->updateRequestStatus($requestId, 'EXPIRED');
                $request['status'] = 'EXPIRED';
            }

            return [
                'success' => true,
                'request' => $request
            ];

        } catch (Exception $e) {
            error_log("AutoLogin getRequestStatus error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Lỗi kiểm tra trạng thái'
            ];
        }
    }

    /**
     * Cập nhật trạng thái yêu cầu
     */
    public function updateRequestStatus($requestId, $status, $adminId = null, $reason = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE login_requests
                SET status = ?, updated_at = NOW()
                WHERE request_id = ?
            ");
            $stmt->execute([$status, $requestId]);

            // Ghi log
            $this->logAction(
                $adminId ? 'admin' : 'system',
                $adminId,
                'login_request_status_updated',
                'login_requests',
                $requestId,
                [
                    'new_status' => $status,
                    'reason' => $reason
                ]
            );

            return [
                'success' => true,
                'message' => 'Trạng thái đã được cập nhật'
            ];

        } catch (Exception $e) {
            error_log("AutoLogin updateRequestStatus error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Lỗi cập nhật trạng thái'
            ];
        }
    }

    /**
     * Yêu cầu OTP
     */
    public function requireOTP($requestId, $adminId, $otpLength = null) {
        try {
            $otpLength = $otpLength ?? ($this->settings['otp_length'] ?? 6);
            $otpCode = $this->generateOTP($otpLength);

            $stmt = $this->db->prepare("
                UPDATE login_requests
                SET status = 'OTP_REQUIRED', otp_required = TRUE,
                    otp_code = ?, otp_length = ?, otp_retries = 0,
                    updated_at = NOW()
                WHERE request_id = ?
            ");
            $stmt->execute([$otpCode, $otpLength, $requestId]);

            // Ghi log
            $this->logAction('admin', $adminId, 'otp_required', 'login_requests', $requestId, [
                'otp_length' => $otpLength
            ]);

            return [
                'success' => true,
                'message' => 'OTP đã được yêu cầu',
                'otp_code' => $otpCode // Chỉ trả về cho admin
            ];

        } catch (Exception $e) {
            error_log("AutoLogin requireOTP error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Lỗi yêu cầu OTP'
            ];
        }
    }

    /**
     * Xác thực OTP
     */
    public function verifyOTP($requestId, $otpInput) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM login_requests
                WHERE request_id = ? AND status = 'OTP_REQUIRED'
                AND ttl_expires_at > NOW()
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                return [
                    'success' => false,
                    'error' => 'Yêu cầu OTP không hợp lệ'
                ];
            }

            // Kiểm tra số lần thử
            if ($request['otp_retries'] >= $request['max_otp_retries']) {
                $this->updateRequestStatus($requestId, 'REJECTED');
                return [
                    'success' => false,
                    'error' => 'Quá số lần nhập OTP. Yêu cầu bị từ chối.'
                ];
            }

            // Kiểm tra OTP
            if ($request['otp_code'] === $otpInput) {
                $this->updateRequestStatus($requestId, 'APPROVED');
                return [
                    'success' => true,
                    'message' => 'OTP xác thực thành công',
                    'status' => 'APPROVED'
                ];
            } else {
                // Tăng số lần thử sai
                $stmt = $this->db->prepare("
                    UPDATE login_requests
                    SET otp_retries = otp_retries + 1, updated_at = NOW()
                    WHERE request_id = ?
                ");
                $stmt->execute([$requestId]);

                $remaining = $request['max_otp_retries'] - ($request['otp_retries'] + 1);

                return [
                    'success' => false,
                    'error' => 'OTP không đúng. Còn ' . $remaining . ' lần thử.',
                    'remaining_attempts' => $remaining
                ];
            }

        } catch (Exception $e) {
            error_log("AutoLogin verifyOTP error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Lỗi xác thực OTP'
            ];
        }
    }

    /**
     * Phê duyệt yêu cầu
     */
    public function approveRequest($requestId, $adminId) {
        try {
            $result = $this->updateRequestStatus($requestId, 'APPROVED', $adminId, 'Admin approved');

            if ($result['success']) {
                // Tạo session cho user
                $sessionData = $this->createUserSession($requestId);
                if ($sessionData['success']) {
                    return [
                        'success' => true,
                        'message' => 'Yêu cầu đã được phê duyệt',
                        'session' => $sessionData['session']
                    ];
                }
            }

            return $result;

        } catch (Exception $e) {
            error_log("AutoLogin approveRequest error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Lỗi phê duyệt yêu cầu'
            ];
        }
    }

    /**
     * Từ chối yêu cầu
     */
    public function rejectRequest($requestId, $adminId, $reason = 'Admin rejected') {
        return $this->updateRequestStatus($requestId, 'REJECTED', $adminId, $reason);
    }

    /**
     * Lấy danh sách yêu cầu chờ xử lý
     */
    public function getPendingRequests($filters = []) {
        try {
            $where = "WHERE status IN ('PENDING_REVIEW', 'OTP_REQUIRED')";
            $params = [];

            if (!empty($filters['platform'])) {
                $where .= " AND platform = ?";
                $params[] = $filters['platform'];
            }

            if (!empty($filters['status'])) {
                $where .= " AND status = ?";
                $params[] = $filters['status'];
            }

            $stmt = $this->db->prepare("
                SELECT * FROM login_requests
                {$where}
                ORDER BY created_at DESC
                LIMIT 100
            ");
            $stmt->execute($params);

            return [
                'success' => true,
                'requests' => $stmt->fetchAll()
            ];

        } catch (Exception $e) {
            error_log("AutoLogin getPendingRequests error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Lỗi lấy danh sách yêu cầu'
            ];
        }
    }

    /**
     * Tạo session cho user
     */
    private function createUserSession($requestId) {
        try {
            // Lấy thông tin request
            $stmt = $this->db->prepare("SELECT * FROM login_requests WHERE request_id = ?");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if (!$request) {
                return ['success' => false, 'error' => 'Request không tồn tại'];
            }

            // Tìm hoặc tạo user
            $user = $this->findOrCreateUser($request);
            if (!$user['success']) {
                return $user;
            }

            // Tạo session token
            $sessionToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 giờ

            $stmt = $this->db->prepare("
                INSERT INTO auth_sessions (
                    user_id, session_token, expires_at, token_hash,
                    ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");

            $tokenHash = hash('sha256', $sessionToken);
            $stmt->execute([
                $user['user_id'],
                $sessionToken,
                $expiresAt,
                $tokenHash,
                $request['ip_address'],
                $request['user_agent']
            ]);

            return [
                'success' => true,
                'session' => [
                    'token' => $sessionToken,
                    'user_id' => $user['user_id'],
                    'expires_at' => $expiresAt
                ]
            ];

        } catch (Exception $e) {
            error_log("AutoLogin createUserSession error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Lỗi tạo session'];
        }
    }

    /**
     * Tìm hoặc tạo user
     */
    private function findOrCreateUser($request) {
        try {
            // Tìm user theo user_hint
            $stmt = $this->db->prepare("
                SELECT id FROM users WHERE email = ? OR username = ?
            ");
            $stmt->execute([$request['user_hint'], $request['user_hint']]);
            $user = $stmt->fetch();

            if ($user) {
                // Cập nhật last_login
                $stmt = $this->db->prepare("
                    UPDATE users SET last_login_at = NOW() WHERE id = ?
                ");
                $stmt->execute([$user['id']]);

                return ['success' => true, 'user_id' => $user['id']];
            }

            // Tạo user mới
            $username = $this->generateUsername($request['user_hint']);
            $email = filter_var($request['user_hint'], FILTER_VALIDATE_EMAIL) ? $request['user_hint'] : null;

            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, status) VALUES (?, ?, 'active')
            ");
            $stmt->execute([$username, $email]);
            $userId = $this->db->lastInsertId();

            // Gán role user
            $stmt = $this->db->prepare("
                INSERT INTO user_roles (user_id, role_id)
                SELECT ?, id FROM roles WHERE name = 'user'
            ");
            $stmt->execute([$userId]);

            return ['success' => true, 'user_id' => $userId];

        } catch (Exception $e) {
            error_log("AutoLogin findOrCreateUser error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Lỗi tìm/tạo user'];
        }
    }

    /**
     * Kiểm tra rate limit
     */
    private function checkRateLimit($ipAddress) {
        if (!$ipAddress) return true;

        $rateLimit = $this->settings['rate_limit_login'] ?? 5;
        $timeWindow = 60; // 1 phút

        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM login_requests
            WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$ipAddress, $timeWindow]);
        $result = $stmt->fetch();

        return $result['count'] < $rateLimit;
    }

    /**
     * Tạo request ID duy nhất
     */
    private function generateRequestId() {
        return bin2hex(random_bytes(32));
    }

    /**
     * Tạo mã OTP
     */
    private function generateOTP($length) {
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= rand(0, 9);
        }
        return $otp;
    }

    /**
     * Tạo username duy nhất
     */
    private function generateUsername($hint) {
        $base = preg_replace('/[^a-zA-Z0-9]/', '', $hint);
        $username = $base;
        $counter = 1;

        while ($this->usernameExists($username)) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Kiểm tra username đã tồn tại
     */
    private function usernameExists($username) {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() !== false;
    }

    /**
     * Ghi log
     */
    private function logAction($actorType, $actorId, $action, $targetType, $targetId, $details = []) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (
                    actor_type, actor_id, action, target_type, target_id,
                    details_json, ip_address
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $actorType,
                $actorId,
                $action,
                $targetType,
                $targetId,
                json_encode($details),
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);

        } catch (Exception $e) {
            error_log("AutoLogin logAction error: " . $e->getMessage());
        }
    }

    /**
     * Dọn dẹp yêu cầu hết hạn
     */
    public function cleanupExpiredRequests() {
        try {
            $stmt = $this->db->prepare("
                UPDATE login_requests
                SET status = 'EXPIRED'
                WHERE ttl_expires_at < NOW() AND status IN ('PENDING_REVIEW', 'OTP_REQUIRED')
            ");
            $stmt->execute();

            $affected = $stmt->rowCount();
            if ($affected > 0) {
                $this->logAction('system', null, 'cleanup_expired_requests', null, null, [
                    'expired_count' => $affected
                ]);
            }

            return ['success' => true, 'expired_count' => $affected];

        } catch (Exception $e) {
            error_log("AutoLogin cleanupExpiredRequests error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Lỗi dọn dẹp'];
        }
    }
}
