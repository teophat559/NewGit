<?php
// User service - Quản lý người dùng, profile và thống kê
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

class UserService {
    private $db;
    private $auth;

    public function __construct() {
        $this->db = db();
        $this->auth = auth();
    }

    // ==================== USER CRUD OPERATIONS ====================

    public function createUser($userData) {
        try {
            // Validation
            $validation = $this->validateUserData($userData);
            if (!$validation['valid']) {
                return $validation;
            }

            // Kiểm tra username và email đã tồn tại
            $existingUser = $this->db->fetchOne(
                "SELECT id FROM users WHERE username = ? OR email = ?",
                [$userData['username'], $userData['email']]
            );

            if ($existingUser) {
                return ['success' => false, 'message' => 'Username hoặc email đã tồn tại'];
            }

            // Hash password
            $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);

            // Tạo user mới
            $this->db->execute(
                "INSERT INTO users (username, email, password_hash, full_name, phone, avatar_url, status, role, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $userData['username'],
                    $userData['email'],
                    $passwordHash,
                    $userData['full_name'] ?? null,
                    $userData['phone'] ?? null,
                    $userData['avatar_url'] ?? null,
                    $userData['status'] ?? 'active',
                    $userData['role'] ?? 'user',
                    $this->getClientIP(),
                    $this->getUserAgent()
                ]
            );

            $userId = $this->db->lastInsertId();

            return [
                'success' => true,
                'message' => 'Tạo người dùng thành công',
                'user_id' => $userId
            ];

        } catch (Exception $e) {
            error_log("User creation failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi tạo người dùng: ' . $e->getMessage()];
        }
    }

    public function updateUser($userId, $userData) {
        try {
            // Kiểm tra user tồn tại
            $existingUser = $this->db->fetchOne("SELECT id FROM users WHERE id = ?", [$userId]);
            if (!$existingUser) {
                return ['success' => false, 'message' => 'Người dùng không tồn tại'];
            }

            // Build update fields
            $updateFields = [];
            $params = [];

            $fields = ['username', 'email', 'full_name', 'phone', 'avatar_url', 'status', 'role'];

            foreach ($fields as $field) {
                if (isset($userData[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $userData[$field];
                }
            }

            if (empty($updateFields)) {
                return ['success' => false, 'message' => 'Không có dữ liệu để cập nhật'];
            }

            $params[] = $userId;

            $this->db->execute(
                "UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                $params
            );

            return [
                'success' => true,
                'message' => 'Cập nhật người dùng thành công'
            ];

        } catch (Exception $e) {
            error_log("User update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi cập nhật người dùng: ' . $e->getMessage()];
        }
    }

    public function deleteUser($userId) {
        try {
            // Kiểm tra user tồn tại
            $existingUser = $this->db->fetchOne("SELECT id FROM users WHERE id = ?", [$userId]);
            if (!$existingUser) {
                return ['success' => false, 'message' => 'Người dùng không tồn tại'];
            }

            // Soft delete - chỉ cập nhật status
            $this->db->execute(
                "UPDATE users SET status = 'deleted', updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$userId]
            );

            return [
                'success' => true,
                'message' => 'Xóa người dùng thành công'
            ];

        } catch (Exception $e) {
            error_log("User deletion failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi xóa người dùng: ' . $e->getMessage()];
        }
    }

    public function getUserById($userId) {
        try {
            $user = $this->db->fetchOne(
                "SELECT id, username, email, full_name, avatar_url, phone, status, role, created_at, updated_at, last_login
                 FROM users WHERE id = ? AND status != 'deleted'",
                [$userId]
            );

            if (!$user) {
                return null;
            }

            // Thêm thống kê
            $user['stats'] = $this->getUserStats($userId);

            return $user;

        } catch (Exception $e) {
            error_log("Get user by ID failed: " . $e->getMessage());
            return null;
        }
    }

    public function getUsers($filters = [], $page = 1, $limit = 10) {
        try {
            $whereConditions = ["status != 'deleted'"];
            $params = [];

            // Apply filters
            if (isset($filters['status']) && $filters['status']) {
                $whereConditions[] = "status = ?";
                $params[] = $filters['status'];
            }

            if (isset($filters['role']) && $filters['role']) {
                $whereConditions[] = "role = ?";
                $params[] = $filters['role'];
            }

            if (isset($filters['search']) && $filters['search']) {
                $whereConditions[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            $whereClause = "WHERE " . implode(" AND ", $whereConditions);

            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM users $whereClause";
            $countResult = $this->db->fetchOne($countSql, $params);
            $total = $countResult['total'];

            // Get users
            $offset = ($page - 1) * $limit;
            $params[] = $limit;
            $params[] = $offset;

            $users = $this->db->fetchAll(
                "SELECT id, username, email, full_name, avatar_url, phone, status, role, created_at, last_login
                 FROM users $whereClause
                 ORDER BY created_at DESC
                 LIMIT ? OFFSET ?",
                $params
            );

            // Add stats for each user
            foreach ($users as &$user) {
                $user['stats'] = $this->getUserStats($user['id']);
            }

            return [
                'users' => $users,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ];

        } catch (Exception $e) {
            error_log("Get users failed: " . $e->getMessage());
            return ['users' => [], 'pagination' => ['page' => 1, 'limit' => 10, 'total' => 0, 'pages' => 0]];
        }
    }

    // ==================== USER PROFILE MANAGEMENT ====================

    public function updateProfile($userId, $profileData) {
        try {
            // Kiểm tra user tồn tại
            $existingUser = $this->db->fetchOne("SELECT id FROM users WHERE id = ?", [$userId]);
            if (!$existingUser) {
                return ['success' => false, 'message' => 'Người dùng không tồn tại'];
            }

            // Build update fields
            $updateFields = [];
            $params = [];

            $fields = ['full_name', 'phone', 'avatar_url'];

            foreach ($fields as $field) {
                if (isset($profileData[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $profileData[$field];
                }
            }

            if (empty($updateFields)) {
                return ['success' => false, 'message' => 'Không có dữ liệu để cập nhật'];
            }

            $params[] = $userId;

            $this->db->execute(
                "UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                $params
            );

            return [
                'success' => true,
                'message' => 'Cập nhật profile thành công'
            ];

        } catch (Exception $e) {
            error_log("Profile update failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi cập nhật profile: ' . $e->getMessage()];
        }
    }

    public function changePassword($userId, $oldPassword, $newPassword) {
        try {
            // Lấy user hiện tại
            $user = $this->db->fetchOne(
                "SELECT password_hash FROM users WHERE id = ?",
                [$userId]
            );

            if (!$user) {
                return ['success' => false, 'message' => 'Người dùng không tồn tại'];
            }

            // Kiểm tra password cũ
            if (!password_verify($oldPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Mật khẩu cũ không đúng'];
            }

            // Hash password mới
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

            // Cập nhật password
            $this->db->execute(
                "UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$newPasswordHash, $userId]
            );

            return ['success' => true, 'message' => 'Đổi mật khẩu thành công'];

        } catch (Exception $e) {
            error_log("Password change failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi đổi mật khẩu: ' . $e->getMessage()];
        }
    }

    // ==================== USER STATISTICS ====================

    public function getUserStats($userId) {
        try {
            $stats = [
                'total_contests' => 0,
                'active_contests' => 0,
                'total_votes_received' => 0,
                'total_uploads' => 0,
                'last_activity' => null
            ];

            // Count contests participated
            $contestStats = $this->db->fetchOne(
                "SELECT COUNT(*) as total,
                        SUM(CASE WHEN c.status = 'active' OR c.status = 'voting' THEN 1 ELSE 0 END) as active
                 FROM contestants ct
                 JOIN contests c ON ct.contest_id = c.id
                 WHERE ct.user_id = ?",
                [$userId]
            );

            if ($contestStats) {
                $stats['total_contests'] = $contestStats['total'];
                $stats['active_contests'] = $contestStats['active'];
            }

            // Count total votes received
            $voteStats = $this->db->fetchOne(
                "SELECT SUM(total_votes) as total FROM contestants WHERE user_id = ?",
                [$userId]
            );

            if ($voteStats && $voteStats['total']) {
                $stats['total_votes_received'] = $voteStats['total'];
            }

            // Count uploads
            $uploadStats = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM uploads WHERE user_id = ?",
                [$userId]
            );

            if ($uploadStats) {
                $stats['total_uploads'] = $uploadStats['total'];
            }

            // Get last activity
            $lastActivity = $this->db->fetchOne(
                "SELECT GREATEST(
                    COALESCE(u.last_login, '1970-01-01'),
                    COALESCE(u.updated_at, '1970-01-01'),
                    COALESCE(ct.updated_at, '1970-01-01'),
                    COALESCE(up.updated_at, '1970-01-01')
                ) as last_activity
                 FROM users u
                 LEFT JOIN contestants ct ON u.id = ct.user_id
                 LEFT JOIN uploads up ON u.id = up.user_id
                 WHERE u.id = ?",
                [$userId]
            );

            if ($lastActivity && $lastActivity['last_activity'] !== '1970-01-01') {
                $stats['last_activity'] = $lastActivity['last_activity'];
            }

            return $stats;

        } catch (Exception $e) {
            error_log("Get user stats failed: " . $e->getMessage());
            return [
                'total_contests' => 0,
                'active_contests' => 0,
                'total_votes_received' => 0,
                'total_uploads' => 0,
                'last_activity' => null
            ];
        }
    }

    public function getSystemStats() {
        try {
            $stats = [
                'total_users' => 0,
                'active_users' => 0,
                'new_users_today' => 0,
                'new_users_this_week' => 0,
                'new_users_this_month' => 0,
                'users_by_role' => [],
                'users_by_status' => []
            ];

            // Total users
            $totalUsers = $this->db->fetchOne("SELECT COUNT(*) as total FROM users WHERE status != 'deleted'");
            $stats['total_users'] = $totalUsers['total'];

            // Active users
            $activeUsers = $this->db->fetchOne("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
            $stats['active_users'] = $activeUsers['total'];

            // New users today
            $newUsersToday = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()"
            );
            $stats['new_users_today'] = $newUsersToday['total'];

            // New users this week
            $newUsersThisWeek = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM users WHERE YEARWEEK(created_at) = YEARWEEK(CURDATE())"
            );
            $stats['new_users_this_week'] = $newUsersThisWeek['total'];

            // New users this month
            $newUsersThisMonth = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM users WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"
            );
            $stats['new_users_this_month'] = $newUsersThisMonth['total'];

            // Users by role
            $usersByRole = $this->db->fetchAll(
                "SELECT role, COUNT(*) as count FROM users WHERE status != 'deleted' GROUP BY role"
            );
            $stats['users_by_role'] = $usersByRole;

            // Users by status
            $usersByStatus = $this->db->fetchAll(
                "SELECT status, COUNT(*) as count FROM users WHERE status != 'deleted' GROUP BY status"
            );
            $stats['users_by_status'] = $usersByStatus;

            return $stats;

        } catch (Exception $e) {
            error_log("Get system stats failed: " . $e->getMessage());
            return [
                'total_users' => 0,
                'active_users' => 0,
                'new_users_today' => 0,
                'new_users_this_week' => 0,
                'new_users_this_month' => 0,
                'users_by_role' => [],
                'users_by_status' => []
            ];
        }
    }

    // ==================== USER ACTIVITY LOGGING ====================

    public function logUserActivity($userId, $action, $details = null) {
        try {
            $this->db->execute(
                "INSERT INTO audit_logs (user_id, action, table_name, new_values, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)",
                [
                    $userId,
                    $action,
                    'users',
                    json_encode($details),
                    $this->getClientIP(),
                    $this->getUserAgent()
                ]
            );

            return true;

        } catch (Exception $e) {
            error_log("User activity logging failed: " . $e->getMessage());
            return false;
        }
    }

    public function getUserActivityLog($userId, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;

            $logs = $this->db->fetchAll(
                "SELECT action, table_name, new_values, ip_address, created_at
                 FROM audit_logs
                 WHERE user_id = ?
                 ORDER BY created_at DESC
                 LIMIT ? OFFSET ?",
                [$userId, $limit, $offset]
            );

            // Get total count
            $total = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM audit_logs WHERE user_id = ?",
                [$userId]
            );

            return [
                'logs' => $logs,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total['total'],
                    'pages' => ceil($total['total'] / $limit)
                ]
            ];

        } catch (Exception $e) {
            error_log("Get user activity log failed: " . $e->getMessage());
            return ['logs' => [], 'pagination' => ['page' => 1, 'limit' => 10, 'total' => 0, 'pages' => 0]];
        }
    }

    // ==================== VALIDATION ====================

    private function validateUserData($userData) {
        if (!isset($userData['username']) || strlen($userData['username']) < 3) {
            return ['valid' => false, 'message' => 'Username phải có ít nhất 3 ký tự'];
        }

        if (!isset($userData['email']) || !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Email không hợp lệ'];
        }

        if (!isset($userData['password']) || strlen($userData['password']) < 6) {
            return ['valid' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự'];
        }

        if (isset($userData['phone']) && !preg_match('/^[0-9+\-\s()]+$/', $userData['phone'])) {
            return ['valid' => false, 'message' => 'Số điện thoại không hợp lệ'];
        }

        return ['valid' => true];
    }

    // ==================== UTILITY FUNCTIONS ====================

    private function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
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
}

// Helper function để sử dụng user service
function user() {
    return new UserService();
}
?>
