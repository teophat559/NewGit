<?php
/**
 * BVOTE 2025 - Advanced Permission & Access Control System
 * Hệ thống phân quyền và kiểm soát truy cập chi tiết
 */

class PermissionManager {
    private $db;
    private $permissions = [];
    private $roles = [];

    // Định nghĩa các quyền cơ bản
    const PERMISSIONS = [
        // Admin permissions
        'admin.dashboard.view' => 'Xem dashboard admin',
        'admin.users.manage' => 'Quản lý người dùng',
        'admin.sessions.monitor' => 'Giám sát session',
        'admin.sessions.control' => 'Điều khiển session',
        'admin.bot.manage' => 'Quản lý bot',
        'admin.system.configure' => 'Cấu hình hệ thống',
        'admin.logs.view' => 'Xem logs',
        'admin.reports.generate' => 'Tạo báo cáo',

        // User permissions
        'user.vote.cast' => 'Bình chọn',
        'user.profile.view' => 'Xem profile',
        'user.contests.view' => 'Xem cuộc thi',
        'user.participants.view' => 'Xem thí sinh',
        'user.history.view' => 'Xem lịch sử bình chọn',

        // API permissions
        'api.auth.login' => 'API đăng nhập',
        'api.vote.submit' => 'API gửi vote',
        'api.data.read' => 'API đọc dữ liệu',
        'api.admin.access' => 'API admin',

        // Bot permissions
        'bot.execute' => 'Thực thi bot',
        'bot.monitor' => 'Giám sát bot',
        'bot.configure' => 'Cấu hình bot'
    ];

    // Định nghĩa các vai trò
    const ROLES = [
        'super_admin' => [
            'name' => 'Super Administrator',
            'permissions' => '*' // Tất cả quyền
        ],
        'admin' => [
            'name' => 'Administrator',
            'permissions' => [
                'admin.dashboard.view',
                'admin.users.manage',
                'admin.sessions.monitor',
                'admin.sessions.control',
                'admin.bot.manage',
                'admin.logs.view',
                'admin.reports.generate'
            ]
        ],
        'moderator' => [
            'name' => 'Moderator',
            'permissions' => [
                'admin.dashboard.view',
                'admin.sessions.monitor',
                'admin.logs.view'
            ]
        ],
        'user' => [
            'name' => 'User',
            'permissions' => [
                'user.vote.cast',
                'user.profile.view',
                'user.contests.view',
                'user.participants.view',
                'user.history.view'
            ]
        ],
        'bot' => [
            'name' => 'Bot',
            'permissions' => [
                'bot.execute',
                'api.auth.login',
                'api.vote.submit'
            ]
        ],
        'guest' => [
            'name' => 'Guest',
            'permissions' => [
                'user.contests.view',
                'user.participants.view'
            ]
        ]
    ];

    public function __construct() {
        $this->db = $this->connectDatabase();
        $this->initializePermissions();
    }

    /**
     * Kiểm tra quyền truy cập
     */
    public function hasPermission($userId, $permission, $context = []) {
        // Lấy vai trò của user
        $userRoles = $this->getUserRoles($userId);

        if (empty($userRoles)) {
            return false;
        }

        // Kiểm tra từng vai trò
        foreach ($userRoles as $role) {
            if ($this->roleHasPermission($role, $permission)) {
                // Kiểm tra điều kiện bổ sung
                if ($this->checkPermissionContext($userId, $permission, $context)) {
                    $this->logPermissionCheck($userId, $permission, true);
                    return true;
                }
            }
        }

        $this->logPermissionCheck($userId, $permission, false);
        return false;
    }

    /**
     * Kiểm tra vai trò có quyền
     */
    private function roleHasPermission($role, $permission) {
        if (!isset(self::ROLES[$role])) {
            return false;
        }

        $rolePerms = self::ROLES[$role]['permissions'];

        // Super admin có tất cả quyền
        if ($rolePerms === '*') {
            return true;
        }

        return in_array($permission, $rolePerms);
    }

    /**
     * Kiểm tra context bổ sung cho permission
     */
    private function checkPermissionContext($userId, $permission, $context) {
        switch ($permission) {
            case 'user.vote.cast':
                // Kiểm tra đã đăng nhập, không bị chặn, trong thời gian bình chọn
                return $this->canUserVote($userId, $context);

            case 'admin.sessions.control':
                // Chỉ admin có thể điều khiển session khác
                return $this->isValidAdminAction($userId, $context);

            case 'api.admin.access':
                // Kiểm tra IP whitelist cho API admin
                return $this->isAdminIPAllowed($context['ip'] ?? '');

            default:
                return true;
        }
    }

    /**
     * Kiểm tra user có thể vote
     */
    private function canUserVote($userId, $context) {
        // Kiểm tra user không bị chặn
        if ($this->isUserBlocked($userId)) {
            return false;
        }

        // Kiểm tra rate limiting
        if ($this->isVoteRateLimited($userId)) {
            return false;
        }

        // Kiểm tra contest đang mở
        $contestId = $context['contest_id'] ?? null;
        if ($contestId && !$this->isContestActive($contestId)) {
            return false;
        }

        return true;
    }

    /**
     * Kiểm tra admin action hợp lệ
     */
    private function isValidAdminAction($adminId, $context) {
        $targetSessionId = $context['session_id'] ?? null;

        if (!$targetSessionId) {
            return false;
        }

        // Admin không thể can thiệp session của admin khác cùng level
        $targetUserId = $this->getSessionUserId($targetSessionId);
        if ($targetUserId && $this->getUserLevel($targetUserId) >= $this->getUserLevel($adminId)) {
            return false;
        }

        return true;
    }

    /**
     * Lấy vai trò của user
     */
    private function getUserRoles($userId) {
        $stmt = $this->db->prepare("
            SELECT role FROM user_roles
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt->execute([$userId]);

        $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Mặc định là guest nếu không có role
        return empty($roles) ? ['guest'] : $roles;
    }

    /**
     * Gán vai trò cho user
     */
    public function assignRole($userId, $role, $assignedBy = null) {
        if (!isset(self::ROLES[$role])) {
            throw new Exception("Invalid role: $role");
        }

        $stmt = $this->db->prepare("
            INSERT INTO user_roles (user_id, role, assigned_by, assigned_at, status)
            VALUES (?, ?, ?, NOW(), 'active')
            ON DUPLICATE KEY UPDATE
            assigned_by = VALUES(assigned_by),
            assigned_at = VALUES(assigned_at),
            status = 'active'
        ");

        $result = $stmt->execute([$userId, $role, $assignedBy]);

        if ($result) {
            $this->logRoleChange($userId, $role, 'assigned', $assignedBy);
        }

        return $result;
    }

    /**
     * Gỡ vai trò của user
     */
    public function revokeRole($userId, $role, $revokedBy = null) {
        $stmt = $this->db->prepare("
            UPDATE user_roles
            SET status = 'revoked', revoked_by = ?, revoked_at = NOW()
            WHERE user_id = ? AND role = ?
        ");

        $result = $stmt->execute([$revokedBy, $userId, $role]);

        if ($result) {
            $this->logRoleChange($userId, $role, 'revoked', $revokedBy);
        }

        return $result;
    }

    /**
     * Tạo middleware kiểm tra quyền
     */
    public function createPermissionMiddleware($requiredPermission) {
        return function() use ($requiredPermission) {
            $userId = $this->getCurrentUserId();

            if (!$this->hasPermission($userId, $requiredPermission)) {
                http_response_code(403);
                echo json_encode([
                    'error' => 'Insufficient permissions',
                    'required' => $requiredPermission
                ]);
                exit;
            }
        };
    }

    /**
     * Kiểm tra quyền cho API endpoint
     */
    public function checkAPIPermission($endpoint, $method = 'GET') {
        $userId = $this->getCurrentUserId();
        $permission = $this->getAPIPermission($endpoint, $method);

        if (!$permission) {
            return true; // Endpoint công khai
        }

        return $this->hasPermission($userId, $permission, [
            'endpoint' => $endpoint,
            'method' => $method,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
    }

    /**
     * Lấy permission cho API endpoint
     */
    private function getAPIPermission($endpoint, $method) {
        $apiPermissions = [
            '/api/auth/login' => 'api.auth.login',
            '/api/vote' => 'api.vote.submit',
            '/api/admin/*' => 'api.admin.access',
            '/api/data/*' => 'api.data.read'
        ];

        foreach ($apiPermissions as $pattern => $permission) {
            if (fnmatch($pattern, $endpoint)) {
                return $permission;
            }
        }

        return null;
    }

    /**
     * Helper methods
     */
    private function getCurrentUserId() {
        return $_SESSION['user_id'] ?? 'guest';
    }

    private function isUserBlocked($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM user_blocks
            WHERE user_id = ? AND status = 'active'
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() > 0;
    }

    private function isVoteRateLimited($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM user_actions
            WHERE user_id = ? AND action = 'vote'
            AND timestamp > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() > 3; // Max 3 votes per minute
    }

    private function isContestActive($contestId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM contests
            WHERE id = ? AND status = 'active'
            AND start_date <= NOW() AND end_date >= NOW()
        ");
        $stmt->execute([$contestId]);
        return $stmt->fetchColumn() > 0;
    }

    private function getUserLevel($userId) {
        $roles = $this->getUserRoles($userId);
        $levels = [
            'super_admin' => 100,
            'admin' => 80,
            'moderator' => 60,
            'user' => 40,
            'bot' => 20,
            'guest' => 0
        ];

        $maxLevel = 0;
        foreach ($roles as $role) {
            if (isset($levels[$role])) {
                $maxLevel = max($maxLevel, $levels[$role]);
            }
        }

        return $maxLevel;
    }

    private function getSessionUserId($sessionId) {
        $stmt = $this->db->prepare("
            SELECT user_id FROM user_actions
            WHERE session_id = ?
            ORDER BY timestamp DESC
            LIMIT 1
        ");
        $stmt->execute([$sessionId]);
        return $stmt->fetchColumn();
    }

    private function isAdminIPAllowed($ip) {
        $allowedIPs = [
            '127.0.0.1',
            '::1',
            'localhost'
        ];

        return in_array($ip, $allowedIPs);
    }

    private function logPermissionCheck($userId, $permission, $granted) {
        $stmt = $this->db->prepare("
            INSERT INTO permission_logs
            (user_id, permission, granted, ip_address, user_agent, timestamp)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $userId,
            $permission,
            $granted ? 1 : 0,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }

    private function logRoleChange($userId, $role, $action, $changedBy) {
        $stmt = $this->db->prepare("
            INSERT INTO role_change_logs
            (user_id, role, action, changed_by, timestamp)
            VALUES (?, ?, ?, ?, NOW())
        ");

        $stmt->execute([$userId, $role, $action, $changedBy]);
    }

    private function connectDatabase() {
        try {
            $config = include __DIR__ . '/../config/database.php';
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
            return new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (Exception $e) {
            return null;
        }
    }

    private function initializePermissions() {
        if (!$this->db) return;

        $tables = [
            "CREATE TABLE IF NOT EXISTS user_roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(255),
                role VARCHAR(50),
                assigned_by VARCHAR(255),
                assigned_at DATETIME,
                revoked_by VARCHAR(255),
                revoked_at DATETIME,
                status ENUM('active', 'revoked') DEFAULT 'active',
                UNIQUE KEY unique_user_role (user_id, role)
            )",

            "CREATE TABLE IF NOT EXISTS permission_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(255),
                permission VARCHAR(100),
                granted BOOLEAN,
                ip_address VARCHAR(45),
                user_agent TEXT,
                timestamp DATETIME,
                INDEX idx_user_time (user_id, timestamp)
            )",

            "CREATE TABLE IF NOT EXISTS role_change_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(255),
                role VARCHAR(50),
                action ENUM('assigned', 'revoked'),
                changed_by VARCHAR(255),
                timestamp DATETIME
            )",

            "CREATE TABLE IF NOT EXISTS user_blocks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(255),
                reason TEXT,
                blocked_by VARCHAR(255),
                blocked_at DATETIME,
                expires_at DATETIME,
                status ENUM('active', 'expired') DEFAULT 'active'
            )"
        ];

        foreach ($tables as $sql) {
            try {
                $this->db->exec($sql);
            } catch (Exception $e) {
                error_log("Error creating permission table: " . $e->getMessage());
            }
        }
    }
}

/**
 * Permission Middleware cho các module
 */
class ModuleAccessControl {
    private $permissionManager;

    public function __construct() {
        $this->permissionManager = new PermissionManager();
    }

    /**
     * Middleware cho admin module
     */
    public function adminModuleAccess() {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            $this->redirectToLogin();
            return;
        }

        if (!$this->permissionManager->hasPermission($userId, 'admin.dashboard.view')) {
            $this->accessDenied();
            return;
        }
    }

    /**
     * Middleware cho user module
     */
    public function userModuleAccess() {
        // User module cho phép guest
        return true;
    }

    /**
     * Middleware cho API module
     */
    public function apiModuleAccess() {
        $endpoint = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (!$this->permissionManager->checkAPIPermission($endpoint, $method)) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['error' => 'Access denied']);
            exit;
        }
    }

    private function redirectToLogin() {
        header('Location: /modules/admin/views/login.php');
        exit;
    }

    private function accessDenied() {
        http_response_code(403);
        echo '<h1>Access Denied</h1><p>You do not have permission to access this resource.</p>';
        exit;
    }
}
?>
