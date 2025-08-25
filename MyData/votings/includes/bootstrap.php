<?php
/**
 * BVOTE 2025 - Bootstrap File
 * Core System Initialization
 *
 * Created: 2025-08-04
 * Version: 2.0
 */

// Prevent direct access
if (!defined('BVOTE_INIT')) {
    define('BVOTE_INIT', true);
}

// Error reporting for development
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

// Set timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Define core constants
define('BVOTE_VERSION', '2.0');
define('BVOTE_ROOT', dirname(__DIR__));
define('BVOTE_INCLUDES', BVOTE_ROOT . '/includes');
define('BVOTE_CONFIG', BVOTE_ROOT . '/config');
define('BVOTE_DATA', BVOTE_ROOT . '/data');
define('BVOTE_UPLOADS', BVOTE_ROOT . '/uploads');

// Security settings
if (!isset($_SESSION)) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true,
        'name' => 'BVOTE_SESSION'
    ]);
}

// Include security configuration
if (file_exists(BVOTE_CONFIG . '/security.php')) {
    require_once BVOTE_CONFIG . '/security.php';
    bvote_set_security_headers();
}

// Include database configuration
if (file_exists(BVOTE_INCLUDES . '/database.php')) {
    require_once BVOTE_INCLUDES . '/database.php';
}

// Include core functions
if (file_exists(BVOTE_INCLUDES . '/functions.php')) {
    require_once BVOTE_INCLUDES . '/functions.php';
}

// Auto-detect environment
if (file_exists(BVOTE_CONFIG . '/production.php') &&
    (isset($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], 'localhost') === false)) {
    require_once BVOTE_CONFIG . '/production.php';
} else {
    // Development environment defaults
    define('BVOTE_ENVIRONMENT', 'development');
    define('BVOTE_DEBUG', true);
}

// Initialize error logging
$log_dir = BVOTE_DATA . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

$log_file = $log_dir . '/bvote_' . date('Y-m-d') . '.log';
ini_set('error_log', $log_file);

/**
 * Custom error handler
 */
function bvote_error_handler($errno, $errstr, $errfile, $errline) {
    $error_types = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE_ERROR',
        E_CORE_WARNING => 'CORE_WARNING',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE'
    ];

    $type = $error_types[$errno] ?? 'UNKNOWN';
    $message = "[$type] $errstr in $errfile on line $errline";

    error_log($message);

    if (defined('BVOTE_DEBUG') && BVOTE_DEBUG) {
        echo "<div style='background:#f8d7da;color:#721c24;padding:10px;margin:5px;border:1px solid #f5c6cb;border-radius:5px;'>$message</div>";
    }

    return true;
}

set_error_handler('bvote_error_handler');

/**
 * Autoloader for classes
 */
spl_autoload_register(function ($class_name) {
    $class_dirs = [
        BVOTE_INCLUDES . '/classes/',
        BVOTE_ROOT . '/admin/classes/',
        BVOTE_ROOT . '/user/classes/',
        BVOTE_ROOT . '/api/classes/'
    ];

    foreach ($class_dirs as $dir) {
        $file = $dir . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// System initialization complete
if (defined('BVOTE_DEBUG') && BVOTE_DEBUG) {
    error_log("BVOTE 2025 Bootstrap: System initialized successfully");
}
?>
