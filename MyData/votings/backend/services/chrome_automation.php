<?php
/**
 * Chrome Automation Service
 * Xử lý tất cả logic liên quan đến tự động hóa Chrome
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

class ChromeAutomationService {
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = new DatabaseService();
        $this->auth = new AuthService();
    }
    
    /**
     * Tạo Chrome profile mới
     */
    public function createChromeProfile($data) {
        try {
            $requiredFields = ['name', 'description', 'user_id'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Trường {$field} là bắt buộc");
                }
            }
            
            // Kiểm tra tên profile đã tồn tại
            $existing = $this->db->fetchOne(
                "SELECT id FROM chrome_profiles WHERE name = ? AND user_id = ?",
                [$data['name'], $data['user_id']]
            );
            
            if ($existing) {
                throw new Exception("Tên profile đã tồn tại");
            }
            
            // Tạo thư mục profile
            $profileDir = CHROME_USER_DATA_DIR . '/' . $data['name'];
            if (!is_dir($profileDir)) {
                if (!mkdir($profileDir, 0755, true)) {
                    throw new Exception("Không thể tạo thư mục profile");
                }
            }
            
            $profileData = [
                'name' => $data['name'],
                'description' => $data['description'],
                'user_id' => $data['user_id'],
                'profile_path' => $profileDir,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $sql = "INSERT INTO chrome_profiles (name, description, user_id, profile_path, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->execute($sql, [
                $profileData['name'],
                $profileData['description'],
                $profileData['user_id'],
                $profileData['profile_path'],
                $profileData['status'],
                $profileData['created_at'],
                $profileData['updated_at']
            ]);
            
            $profileId = $this->db->lastInsertId();
            
            // Log hoạt động
            $this->logActivity('create_profile', $profileId, $data['user_id'], 'Tạo Chrome profile: ' . $data['name']);
            
            return [
                'success' => true,
                'profile_id' => $profileId,
                'message' => 'Tạo Chrome profile thành công'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Lấy danh sách Chrome profiles
     */
    public function getChromeProfiles($userId = null, $status = null) {
        try {
            $where = [];
            $params = [];
            
            if ($userId) {
                $where[] = "cp.user_id = ?";
                $params[] = $userId;
            }
            
            if ($status) {
                $where[] = "cp.status = ?";
                $params[] = $status;
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $sql = "SELECT cp.*, u.username, u.full_name 
                    FROM chrome_profiles cp 
                    LEFT JOIN users u ON cp.user_id = u.id 
                    {$whereClause} 
                    ORDER BY cp.created_at DESC";
            
            $profiles = $this->db->fetchAll($sql, $params);
            
            return [
                'success' => true,
                'profiles' => $profiles
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cập nhật Chrome profile
     */
    public function updateChromeProfile($profileId, $data) {
        try {
            if (empty($profileId)) {
                throw new Exception("ID profile là bắt buộc");
            }
            
            $updateFields = [];
            $params = [];
            
            if (isset($data['name'])) {
                $updateFields[] = "name = ?";
                $params[] = $data['name'];
            }
            
            if (isset($data['description'])) {
                $updateFields[] = "description = ?";
                $params[] = $data['description'];
            }
            
            if (isset($data['status'])) {
                $updateFields[] = "status = ?";
                $params[] = $data['status'];
            }
            
            if (empty($updateFields)) {
                throw new Exception("Không có dữ liệu để cập nhật");
            }
            
            $updateFields[] = "updated_at = ?";
            $params[] = date('Y-m-d H:i:s');
            $params[] = $profileId;
            
            $sql = "UPDATE chrome_profiles SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $this->db->execute($sql, $params);
            
            // Log hoạt động
            $this->logActivity('update_profile', $profileId, null, 'Cập nhật Chrome profile');
            
            return [
                'success' => true,
                'message' => 'Cập nhật Chrome profile thành công'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Xóa Chrome profile
     */
    public function deleteChromeProfile($profileId) {
        try {
            if (empty($profileId)) {
                throw new Exception("ID profile là bắt buộc");
            }
            
            // Kiểm tra profile có đang được sử dụng không
            $usage = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM login_sessions WHERE chrome_profile_id = ? AND status IN ('running', 'pending')",
                [$profileId]
            );
            
            if ($usage && $usage['count'] > 0) {
                throw new Exception("Không thể xóa profile đang được sử dụng");
            }
            
            // Xóa profile
            $this->db->execute("DELETE FROM chrome_profiles WHERE id = ?", [$profileId]);
            
            // Log hoạt động
            $this->logActivity('delete_profile', $profileId, null, 'Xóa Chrome profile');
            
            return [
                'success' => true,
                'message' => 'Xóa Chrome profile thành công'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Tạo phiên đăng nhập mới
     */
    public function createLoginSession($data) {
        try {
            $requiredFields = ['platform', 'chrome_profile_id', 'link_name', 'user_id'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Trường {$field} là bắt buộc");
                }
            }
            
            // Kiểm tra Chrome profile tồn tại
            $profile = $this->db->fetchOne(
                "SELECT id, name FROM chrome_profiles WHERE id = ? AND status = 'active'",
                [$data['chrome_profile_id']]
            );
            
            if (!$profile) {
                throw new Exception("Chrome profile không tồn tại hoặc không hoạt động");
            }
            
            $sessionData = [
                'platform' => $data['platform'],
                'chrome_profile_id' => $data['chrome_profile_id'],
                'link_name' => $data['link_name'],
                'user_id' => $data['user_id'],
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $sql = "INSERT INTO login_sessions (platform, chrome_profile_id, link_name, user_id, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->execute($sql, [
                $sessionData['platform'],
                $sessionData['chrome_profile_id'],
                $sessionData['link_name'],
                $sessionData['user_id'],
                $sessionData['status'],
                $sessionData['created_at'],
                $sessionData['updated_at']
            ]);
            
            $sessionId = $this->db->lastInsertId();
            
            // Log hoạt động
            $this->logActivity('create_session', $sessionId, $data['user_id'], 'Tạo phiên đăng nhập: ' . $data['platform']);
            
            return [
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Tạo phiên đăng nhập thành công'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Lấy danh sách phiên đăng nhập
     */
    public function getLoginSessions($filters = []) {
        try {
            $where = [];
            $params = [];
            
            if (!empty($filters['user_id'])) {
                $where[] = "ls.user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['status'])) {
                $where[] = "ls.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['platform'])) {
                $where[] = "ls.platform = ?";
                $params[] = $filters['platform'];
            }
            
            if (!empty($filters['chrome_profile_id'])) {
                $where[] = "ls.chrome_profile_id = ?";
                $params[] = $filters['chrome_profile_id'];
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            $sql = "SELECT ls.*, cp.name as profile_name, u.username, u.full_name 
                    FROM login_sessions ls 
                    LEFT JOIN chrome_profiles cp ON ls.chrome_profile_id = cp.id 
                    LEFT JOIN users u ON ls.user_id = u.id 
                    {$whereClause} 
                    ORDER BY ls.created_at DESC";
            
            $sessions = $this->db->fetchAll($sql, $params);
            
            return [
                'success' => true,
                'sessions' => $sessions
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cập nhật trạng thái phiên đăng nhập
     */
    public function updateLoginSessionStatus($sessionId, $status, $additionalData = []) {
        try {
            if (empty($sessionId) || empty($status)) {
                throw new Exception("ID phiên và trạng thái là bắt buộc");
            }
            
            $updateFields = ["status = ?", "updated_at = ?"];
            $params = [$status, date('Y-m-d H:i:s')];
            
            if (isset($additionalData['account'])) {
                $updateFields[] = "account = ?";
                $params[] = $additionalData['account'];
            }
            
            if (isset($additionalData['password'])) {
                $updateFields[] = "password = ?";
                $params[] = $additionalData['password'];
            }
            
            if (isset($additionalData['otp'])) {
                $updateFields[] = "otp = ?";
                $params[] = $additionalData['otp'];
            }
            
            if (isset($additionalData['ip'])) {
                $updateFields[] = "ip = ?";
                $params[] = $additionalData['ip'];
            }
            
            if (isset($additionalData['device'])) {
                $updateFields[] = "device = ?";
                $params[] = $additionalData['device'];
            }
            
            if (isset($additionalData['cookie'])) {
                $updateFields[] = "cookie = ?";
                $params[] = $additionalData['cookie'];
            }
            
            if (isset($additionalData['notes'])) {
                $updateFields[] = "notes = ?";
                $params[] = $additionalData['notes'];
            }
            
            $params[] = $sessionId;
            
            $sql = "UPDATE login_sessions SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $this->db->execute($sql, $params);
            
            // Log hoạt động
            $this->logActivity('update_session_status', $sessionId, null, 'Cập nhật trạng thái phiên: ' . $status);
            
            return [
                'success' => true,
                'message' => 'Cập nhật trạng thái phiên thành công'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Xóa phiên đăng nhập
     */
    public function deleteLoginSession($sessionId) {
        try {
            if (empty($sessionId)) {
                throw new Exception("ID phiên là bắt buộc");
            }
            
            $this->db->execute("DELETE FROM login_sessions WHERE id = ?", [$sessionId]);
            
            // Log hoạt động
            $this->logActivity('delete_session', $sessionId, null, 'Xóa phiên đăng nhập');
            
            return [
                'success' => true,
                'message' => 'Xóa phiên đăng nhập thành công'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Khởi chạy automation cho phiên đăng nhập
     */
    public function startAutomation($sessionId) {
        try {
            if (empty($sessionId)) {
                throw new Exception("ID phiên là bắt buộc");
            }
            
            // Lấy thông tin phiên
            $session = $this->db->fetchOne(
                "SELECT ls.*, cp.profile_path FROM login_sessions ls 
                 LEFT JOIN chrome_profiles cp ON ls.chrome_profile_id = cp.id 
                 WHERE ls.id = ?",
                [$sessionId]
            );
            
            if (!$session) {
                throw new Exception("Phiên đăng nhập không tồn tại");
            }
            
            if ($session['status'] !== 'pending') {
                throw new Exception("Chỉ có thể khởi chạy phiên đang chờ");
            }
            
            // Cập nhật trạng thái thành running
            $this->updateLoginSessionStatus($sessionId, 'running');
            
            // Tạo lệnh Chrome automation
            $chromeCommand = $this->buildChromeCommand($session);
            
            // Log hoạt động
            $this->logActivity('start_automation', $sessionId, null, 'Khởi chạy automation Chrome');
            
            return [
                'success' => true,
                'chrome_command' => $chromeCommand,
                'message' => 'Khởi chạy automation thành công'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Dừng automation
     */
    public function stopAutomation($sessionId) {
        try {
            if (empty($sessionId)) {
                throw new Exception("ID phiên là bắt buộc");
            }
            
            // Cập nhật trạng thái thành stopped
            $this->updateLoginSessionStatus($sessionId, 'stopped');
            
            // Log hoạt động
            $this->logActivity('stop_automation', $sessionId, null, 'Dừng automation Chrome');
            
            return [
                'success' => true,
                'message' => 'Dừng automation thành công'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Xây dựng lệnh Chrome
     */
    private function buildChromeCommand($session) {
        $chromePath = CHROME_EXECUTABLE_PATH;
        $profileDir = $session['profile_path'];
        $headless = CHROME_HEADLESS ? '--headless' : '';
        
        $command = sprintf(
            '%s %s --user-data-dir="%s" --no-first-run --no-default-browser-check --disable-extensions --disable-plugins --disable-images --disable-javascript --disable-web-security --allow-running-insecure-content --disable-features=VizDisplayCompositor',
            $chromePath,
            $headless,
            $profileDir
        );
        
        return $command;
    }
    
    /**
     * Lấy thống kê automation
     */
    public function getAutomationStats($userId = null) {
        try {
            $where = $userId ? "WHERE user_id = ?" : "";
            $params = $userId ? [$userId] : [];
            
            $sql = "SELECT 
                        COUNT(*) as total_sessions,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_sessions,
                        SUM(CASE WHEN status = 'running' THEN 1 ELSE 0 END) as running_sessions,
                        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_sessions,
                        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_sessions,
                        SUM(CASE WHEN status = 'stopped' THEN 1 ELSE 0 END) as stopped_sessions
                    FROM login_sessions {$where}";
            
            $stats = $this->db->fetchOne($sql, $params);
            
            // Thống kê theo platform
            $platformStats = $this->db->fetchAll(
                "SELECT platform, COUNT(*) as count FROM login_sessions {$where} GROUP BY platform",
                $params
            );
            
            return [
                'success' => true,
                'stats' => $stats,
                'platform_stats' => $platformStats
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Log hoạt động
     */
    private function logActivity($action, $targetId, $userId, $description) {
        try {
            $sql = "INSERT INTO audit_logs (action, target_type, target_id, user_id, description, ip_address, user_agent, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->execute($sql, [
                $action,
                'chrome_automation',
                $targetId,
                $userId,
                $description,
                $this->getClientIP(),
                $this->getUserAgent(),
                date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Log lỗi nhưng không làm crash chức năng chính
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }
    
    /**
     * Lấy IP client
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }
    
    /**
     * Lấy User Agent
     */
    private function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }
}

// Helper function
function chrome_automation() {
    return new ChromeAutomationService();
}
