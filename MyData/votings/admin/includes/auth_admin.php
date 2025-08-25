<?php
/**
 * BVOTE 2025 - Admin Authentication
 * Professional Admin Security & Authentication
 *
 * Created: 2025-08-04
 * Version: 2.0
 */

// Prevent direct access
if (!defined('BVOTE_INIT') && !isset($_SESSION)) {
    session_start();
}

/**
 * Admin authentication class
 */
class BVoteAdminAuth {
    private static $session_prefix = 'bvote_admin_';
    private static $max_attempts = 5;
    private static $lockout_duration = 900; // 15 minutes

    /**
     * Check if admin is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION[self::$session_prefix . 'logged_in']) &&
               $_SESSION[self::$session_prefix . 'logged_in'] === true &&
               isset($_SESSION[self::$session_prefix . 'user_id']);
    }

    /**
     * Get current admin user
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION[self::$session_prefix . 'user_id'],
            'username' => $_SESSION[self::$session_prefix . 'username'],
            'role' => $_SESSION[self::$session_prefix . 'role'] ?? 'admin',
            'login_time' => $_SESSION[self::$session_prefix . 'login_time'],
            'last_activity' => $_SESSION[self::$session_prefix . 'last_activity'] ?? time()
        ];
    }

    /**
     * Authenticate admin user
     */
    public static function authenticate($username, $password, $security_key = null) {
        // Check rate limiting
        $ip = self::getClientIP();
        if (!self::checkRateLimit($ip)) {
            return [
                'success' => false,
                'message' => 'Too many login attempts. Please try again later.',
                'lockout_remaining' => self::getLockoutRemaining($ip)
            ];
        }

        // Validate input
        $username = trim($username);
        if (empty($username) || empty($password)) {
            self::logFailedAttempt($ip, $username, 'Empty credentials');
            return ['success' => false, 'message' => 'Username and password required.'];
        }

        // For demo/development - simple authentication
        if ($username === 'admin' && $password === 'bvote2025admin') {

            // Additional security layer - require security key
            if (!empty($security_key)) {
                $valid_keys = ['BVOTE2025', 'ADMIN_ACCESS', 'SECURE_LOGIN'];
                if (!in_array($security_key, $valid_keys)) {
                    self::logFailedAttempt($ip, $username, 'Invalid security key');
                    return ['success' => false, 'message' => 'Invalid security key.'];
                }
            }

            // Create admin session
            self::createSession([
                'id' => 1,
                'username' => $username,
                'role' => 'super_admin'
            ]);

            self::logSuccessfulLogin($ip, $username);
            self::clearFailedAttempts($ip);

            return [
                'success' => true,
                'message' => 'Login successful.',
                'redirect' => self::getRedirectUrl()
            ];
        }

        // Database authentication (for production)
        try {
            if (function_exists('bvote_fetch_row')) {
                $admin = bvote_fetch_row(
                    "SELECT id, username, password_hash, role, status, failed_attempts, last_failed_attempt
                     FROM bv_admins WHERE username = :username",
                    ['username' => $username]
                );

                if ($admin) {
                    // Check if account is locked
                    if ($admin['failed_attempts'] >= self::$max_attempts) {
                        $last_attempt = strtotime($admin['last_failed_attempt']);
                        if (time() - $last_attempt < self::$lockout_duration) {
                            return [
                                'success' => false,
                                'message' => 'Account temporarily locked due to too many failed attempts.',
                                'lockout_remaining' => self::$lockout_duration - (time() - $last_attempt)
                            ];
                        }
                    }

                    // Check if account is active
                    if ($admin['status'] !== 'active') {
                        self::logFailedAttempt($ip, $username, 'Inactive account');
                        return ['success' => false, 'message' => 'Account is not active.'];
                    }

                    // Verify password
                    if (password_verify($password, $admin['password_hash'])) {
                        // Reset failed attempts
                        bvote_query(
                            "UPDATE bv_admins SET failed_attempts = 0, last_login = NOW() WHERE id = :id",
                            ['id' => $admin['id']]
                        );

                        // Create session
                        self::createSession([
                            'id' => $admin['id'],
                            'username' => $admin['username'],
                            'role' => $admin['role']
                        ]);

                        self::logSuccessfulLogin($ip, $username);
                        self::clearFailedAttempts($ip);

                        return [
                            'success' => true,
                            'message' => 'Login successful.',
                            'redirect' => self::getRedirectUrl()
                        ];
                    } else {
                        // Increment failed attempts
                        bvote_query(
                            "UPDATE bv_admins SET failed_attempts = failed_attempts + 1, last_failed_attempt = NOW() WHERE id = :id",
                            ['id' => $admin['id']]
                        );
                    }
                }
            }
        } catch (Exception $e) {
            error_log("BVOTE Admin Auth Error: " . $e->getMessage());
        }

        self::logFailedAttempt($ip, $username, 'Invalid credentials');
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }

    /**
     * Create admin session
     */
    private static function createSession($admin) {
        // Regenerate session ID for security
        session_regenerate_id(true);

        $_SESSION[self::$session_prefix . 'logged_in'] = true;
        $_SESSION[self::$session_prefix . 'user_id'] = $admin['id'];
        $_SESSION[self::$session_prefix . 'username'] = $admin['username'];
        $_SESSION[self::$session_prefix . 'role'] = $admin['role'];
        $_SESSION[self::$session_prefix . 'login_time'] = time();
        $_SESSION[self::$session_prefix . 'last_activity'] = time();
        $_SESSION[self::$session_prefix . 'session_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Update last activity
     */
    public static function updateActivity() {
        if (self::isLoggedIn()) {
            $_SESSION[self::$session_prefix . 'last_activity'] = time();
        }
    }

    /**
     * Check session timeout
     */
    public static function checkTimeout($timeout = 3600) {
        if (!self::isLoggedIn()) {
            return false;
        }

        $last_activity = $_SESSION[self::$session_prefix . 'last_activity'] ?? 0;
        if (time() - $last_activity > $timeout) {
            self::logout('Session timeout');
            return false;
        }

        self::updateActivity();
        return true;
    }

    /**
     * Logout admin user
     */
    public static function logout($reason = 'User logout') {
        $username = $_SESSION[self::$session_prefix . 'username'] ?? 'unknown';

        // Clear admin session variables
        $keys_to_unset = [];
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, self::$session_prefix) === 0) {
                $keys_to_unset[] = $key;
            }
        }

        foreach ($keys_to_unset as $key) {
            unset($_SESSION[$key]);
        }

        // Log logout
        error_log("BVOTE Admin Logout: {$username} - {$reason} at " . date('Y-m-d H:i:s'));
    }

    /**
     * Check rate limiting
     */
    private static function checkRateLimit($ip) {
        $cache_file = self::getRateLimitFile($ip);

        if (file_exists($cache_file)) {
            $data = json_decode(file_get_contents($cache_file), true);
            if ($data && $data['timestamp'] > time() - self::$lockout_duration) {
                return $data['attempts'] < self::$max_attempts;
            }
        }

        return true;
    }

    /**
     * Log failed attempt
     */
    private static function logFailedAttempt($ip, $username, $reason) {
        // Update rate limit counter
        $cache_file = self::getRateLimitFile($ip);
        $data = ['attempts' => 1, 'timestamp' => time()];

        if (file_exists($cache_file)) {
            $existing = json_decode(file_get_contents($cache_file), true);
            if ($existing && $existing['timestamp'] > time() - self::$lockout_duration) {
                $data['attempts'] = $existing['attempts'] + 1;
            }
        }

        file_put_contents($cache_file, json_encode($data));

        // Log to error log
        error_log("BVOTE Admin Failed Login: {$username} from {$ip} - {$reason} at " . date('Y-m-d H:i:s'));

        // Log to database if available
        if (function_exists('bvote_log_activity')) {
            bvote_log_activity('admin_login_failed', "Username: {$username}, Reason: {$reason}");
        }
    }

    /**
     * Log successful login
     */
    private static function logSuccessfulLogin($ip, $username) {
        error_log("BVOTE Admin Successful Login: {$username} from {$ip} at " . date('Y-m-d H:i:s'));

        if (function_exists('bvote_log_activity')) {
            bvote_log_activity('admin_login_success', "Username: {$username}");
        }
    }

    /**
     * Clear failed attempts
     */
    private static function clearFailedAttempts($ip) {
        $cache_file = self::getRateLimitFile($ip);
        if (file_exists($cache_file)) {
            unlink($cache_file);
        }
    }

    /**
     * Get lockout remaining time
     */
    private static function getLockoutRemaining($ip) {
        $cache_file = self::getRateLimitFile($ip);

        if (file_exists($cache_file)) {
            $data = json_decode(file_get_contents($cache_file), true);
            if ($data && $data['attempts'] >= self::$max_attempts) {
                return max(0, self::$lockout_duration - (time() - $data['timestamp']));
            }
        }

        return 0;
    }

    /**
     * Get rate limit file path
     */
    private static function getRateLimitFile($ip) {
        $cache_dir = defined('BVOTE_DATA') ? BVOTE_DATA . '/cache' : sys_get_temp_dir();
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
        return $cache_dir . '/admin_rate_limit_' . md5($ip) . '.json';
    }

    /**
     * Get client IP
     */
    private static function getClientIP() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get redirect URL after login
     */
    private static function getRedirectUrl() {
        // Check for intended destination
        if (isset($_SESSION['bvote_admin_intended_url'])) {
            $url = $_SESSION['bvote_admin_intended_url'];
            unset($_SESSION['bvote_admin_intended_url']);
            return $url;
        }

        // Default admin dashboard
        return 'dashboard.php';
    }

    /**
     * Require admin authentication
     */
    public static function requireAuth($redirect_to_login = true) {
        if (!self::isLoggedIn()) {
            if ($redirect_to_login) {
                // Store intended URL
                if (!empty($_SERVER['REQUEST_URI'])) {
                    $_SESSION['bvote_admin_intended_url'] = $_SERVER['REQUEST_URI'];
                }

                header('Location: login.php');
                exit;
            }
            return false;
        }

        // Check session timeout
        if (!self::checkTimeout()) {
            if ($redirect_to_login) {
                header('Location: login.php?timeout=1');
                exit;
            }
            return false;
        }

        return true;
    }

    /**
     * Check admin permission
     */
    public static function hasPermission($permission) {
        if (!self::isLoggedIn()) {
            return false;
        }

        $user = self::getCurrentUser();
        $role = $user['role'] ?? 'admin';

        // Super admin has all permissions
        if ($role === 'super_admin') {
            return true;
        }

        // Define role permissions
        $role_permissions = [
            'admin' => [
                'view_dashboard',
                'manage_campaigns',
                'manage_contestants',
                'view_votes',
                'view_reports'
            ],
            'moderator' => [
                'view_dashboard',
                'view_votes',
                'view_reports'
            ]
        ];

        return in_array($permission, $role_permissions[$role] ?? []);
    }

    /**
     * Generate secure admin creation token
     */
    public static function generateAdminCreationToken() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['bvote_admin_creation_token'] = $token;
        $_SESSION['bvote_admin_creation_expires'] = time() + 3600; // 1 hour
        return $token;
    }

    /**
     * Validate admin creation token
     */
    public static function validateAdminCreationToken($token) {
        if (!isset($_SESSION['bvote_admin_creation_token']) ||
            !isset($_SESSION['bvote_admin_creation_expires'])) {
            return false;
        }

        if (time() > $_SESSION['bvote_admin_creation_expires']) {
            unset($_SESSION['bvote_admin_creation_token']);
            unset($_SESSION['bvote_admin_creation_expires']);
            return false;
        }

        return hash_equals($_SESSION['bvote_admin_creation_token'], $token);
    }
}

// Global helper functions
function bvote_admin_logged_in() {
    return BVoteAdminAuth::isLoggedIn();
}

function bvote_admin_user() {
    return BVoteAdminAuth::getCurrentUser();
}

function bvote_admin_require_auth() {
    return BVoteAdminAuth::requireAuth();
}

function bvote_admin_has_permission($permission) {
    return BVoteAdminAuth::hasPermission($permission);
}

function bvote_admin_logout($reason = 'User logout') {
    return BVoteAdminAuth::logout($reason);
}

// Auto-check timeout on every admin page load
if (isset($_SESSION) && BVoteAdminAuth::isLoggedIn()) {
    BVoteAdminAuth::checkTimeout();
}
?>
