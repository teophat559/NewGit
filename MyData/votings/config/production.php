<?php
/**
 * BVOTE 2025 - Production Configuration
 * Production Environment Settings
 * Enhanced for real-world deployment with core system integration
 *
 * Created: 2025-08-04
 * Version: 2.0
 */

// Prevent direct access
if (!defined('BVOTE_SYSTEM_ACCESS')) {
    die('Direct access not allowed');
}

// Initialize core system constants
if (!defined('BVOTE_ENVIRONMENT')) {
    define('BVOTE_ENVIRONMENT', 'production');
}

// Check if environment loader exists, otherwise use fallback
$env_loader_path = __DIR__ . '/../includes/env-loader.php';
if (file_exists($env_loader_path)) {
    require_once $env_loader_path;
} else {
    // Fallback environment loader class
    class EnvLoader {
        public static function get($key, $default = null) {
            return $_ENV[$key] ?? getenv($key) ?? $default;
        }

        public static function getBool($key, $default = false) {
            $value = self::get($key, $default);
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        public static function getInt($key, $default = 0) {
            return (int)self::get($key, $default);
        }
    }
}

// Environment settings
define('BVOTE_DEBUG', EnvLoader::getBool('APP_DEBUG', false));
define('BVOTE_VERSION', '2.0');
define('ENVIRONMENT', EnvLoader::get('APP_ENV', 'production'));
define('PRODUCTION_MODE', true);
define('DEBUG_MODE', BVOTE_DEBUG);
define('MAINTENANCE_MODE', EnvLoader::getBool('MAINTENANCE_MODE', false));
define('BASE_URL', EnvLoader::get('APP_URL', 'http://localhost'));
define('SITE_NAME', EnvLoader::get('APP_NAME', 'BVOTE PLATFORM'));
define('VERSION', '2025.1.0');

// Core BVOTE constants for compatibility
define('BVOTE_BASE_URL', BASE_URL);
define('BVOTE_SITE_NAME', SITE_NAME);

// Database configuration
define('BVOTE_DB_HOST', EnvLoader::get('DB_HOST', 'localhost'));
define('BVOTE_DB_NAME', EnvLoader::get('DB_DATABASE', 'bvote_production_db'));
define('BVOTE_DB_USER', EnvLoader::get('DB_USERNAME', 'bvote_system_user'));
define('BVOTE_DB_PASS', EnvLoader::get('DB_PASSWORD', 'BV2025_SecurePass!'));
define('BVOTE_DB_CHARSET', 'utf8mb4');

// Legacy compatibility
define('DB_HOST', BVOTE_DB_HOST);
define('DB_NAME', BVOTE_DB_NAME);
define('DB_USER', BVOTE_DB_USER);
define('DB_PASS', BVOTE_DB_PASS);
define('DB_CHARSET', BVOTE_DB_CHARSET);

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('MODULES_PATH', ROOT_PATH . '/modules');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . EnvLoader::get('UPLOADS_PATH', '/uploads'));
define('LOGS_PATH', ROOT_PATH . EnvLoader::get('LOGS_PATH', '/logs'));
define('DATA_PATH', ROOT_PATH . EnvLoader::get('DATA_PATH', '/data'));

// Security settings
define('BVOTE_SALT', EnvLoader::get('PASSWORD_SALT', 'BVOTE2025_SECURE_SALT_' . hash('sha256', __DIR__)));
define('BVOTE_SESSION_LIFETIME', EnvLoader::getInt('SESSION_LIFETIME', 3600)); // 1 hour
define('BVOTE_MAX_LOGIN_ATTEMPTS', EnvLoader::getInt('MAX_LOGIN_ATTEMPTS', 5));
define('BVOTE_LOCKOUT_DURATION', EnvLoader::getInt('LOCKOUT_DURATION', 900)); // 15 minutes

// Legacy compatibility
define('SESSION_NAME', EnvLoader::get('SESSION_NAME', 'BVOTE_SESSION'));
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_SALT', BVOTE_SALT);

// MoreLogin API Configuration
define('MORELOGIN_API_ENDPOINT', EnvLoader::get('MORELOGIN_API_ENDPOINT', 'http://127.0.0.1:40000'));
define('MORELOGIN_API_ID', EnvLoader::get('MORELOGIN_API_ID', '1650404388761056'));
define('MORELOGIN_API_KEY', EnvLoader::get('MORELOGIN_API_KEY', '13544368342b4be69e9d63c9f7f5133e'));

// File upload settings for BVOTE core compatibility
define('BVOTE_MAX_FILE_SIZE', EnvLoader::getInt('MAX_UPLOAD_SIZE', 5 * 1024 * 1024)); // 5MB
define('BVOTE_UPLOAD_DIR', ROOT_PATH . EnvLoader::get('UPLOADS_PATH', '/uploads/'));
$allowedExtensions = EnvLoader::get('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,webp');
define('BVOTE_ALLOWED_EXTENSIONS', explode(',', $allowedExtensions));

// Email settings (configure for production)
define('BVOTE_SMTP_HOST', EnvLoader::get('SMTP_HOST', 'smtp.gmail.com'));
define('BVOTE_SMTP_PORT', EnvLoader::getInt('SMTP_PORT', 587));
define('BVOTE_SMTP_USER', EnvLoader::get('SMTP_USER', ''));
define('BVOTE_SMTP_PASS', EnvLoader::get('SMTP_PASS', ''));
define('BVOTE_FROM_EMAIL', EnvLoader::get('FROM_EMAIL', 'noreply@bvote2025.com'));
define('BVOTE_FROM_NAME', EnvLoader::get('FROM_NAME', 'BVOTE 2025'));

// Server settings
define('BVOTE_SERVER_IP', EnvLoader::get('SERVER_IP', '31.97.48.96'));
define('BVOTE_SERVER_DOMAIN', EnvLoader::get('SERVER_DOMAIN', 'alma-cyberpanel.localhost'));
define('BVOTE_SSL_ENABLED', EnvLoader::getBool('SSL_ENABLED', false));

// Logging
define('BVOTE_LOG_LEVEL', EnvLoader::get('LOG_LEVEL', 'ERROR')); // ERROR, WARNING, INFO, DEBUG
define('BVOTE_LOG_FILE', LOGS_PATH . '/bvote_' . date('Y-m-d') . '.log');

// Cache settings (enhanced)
define('BVOTE_CACHE_ENABLED', EnvLoader::getBool('CACHE_ENABLED', true));
define('BVOTE_CACHE_LIFETIME', EnvLoader::getInt('CACHE_TIME', 3600));
define('BVOTE_CACHE_DIR', DATA_PATH . '/cache/');

// Performance settings
define('BVOTE_ENABLE_GZIP', EnvLoader::getBool('ENABLE_GZIP', true));
define('BVOTE_MINIFY_HTML', EnvLoader::getBool('MINIFY_HTML', true));
define('BVOTE_COMBINE_CSS', EnvLoader::getBool('COMBINE_CSS', true));
define('BVOTE_COMBINE_JS', EnvLoader::getBool('COMBINE_JS', true));

// Rate limiting
define('BVOTE_RATE_LIMIT_VOTES', EnvLoader::getInt('RATE_LIMIT_VOTES', 10)); // votes per hour per IP
define('BVOTE_RATE_LIMIT_API', EnvLoader::getInt('RATE_LIMIT_API', 100)); // API calls per hour per IP
define('BVOTE_RATE_LIMIT_LOGIN', EnvLoader::getInt('RATE_LIMIT_LOGIN', 5)); // login attempts per 15 minutes

// Admin settings
define('BVOTE_ADMIN_SESSION_TIMEOUT', EnvLoader::getInt('ADMIN_SESSION_TIMEOUT', 1800)); // 30 minutes
define('BVOTE_ADMIN_REQUIRE_2FA', EnvLoader::getBool('ADMIN_REQUIRE_2FA', false));
$admin_whitelist = EnvLoader::get('ADMIN_IP_WHITELIST', '');
define('BVOTE_ADMIN_IP_WHITELIST', empty($admin_whitelist) ? [] : explode(',', $admin_whitelist));

// Backup settings
define('BVOTE_AUTO_BACKUP', EnvLoader::getBool('AUTO_BACKUP', true));
define('BVOTE_BACKUP_INTERVAL', EnvLoader::getInt('BACKUP_INTERVAL', 86400)); // 24 hours
define('BVOTE_BACKUP_RETENTION', EnvLoader::getInt('BACKUP_RETENTION', 30)); // days
define('BVOTE_BACKUP_DIR', ROOT_PATH . '/backups/');

// Upload settings
define('MAX_UPLOAD_SIZE', EnvLoader::getInt('MAX_UPLOAD_SIZE', 10 * 1024 * 1024)); // 10MB
$allowedExtensions = EnvLoader::get('ALLOWED_EXTENSIONS', 'jpg,jpeg,png,gif,webp');
define('ALLOWED_EXTENSIONS', explode(',', $allowedExtensions));

// Cache settings
define('ENABLE_CACHE', EnvLoader::getBool('CACHE_ENABLED', true));
define('CACHE_TIME', EnvLoader::getInt('CACHE_TIME', 3600)); // 1 hour

// Error reporting for production
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 0);
}
ini_set('log_errors', 1);
ini_set('error_log', LOGS_PATH . '/error.log');

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Session configuration
ini_set('session.name', SESSION_NAME);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_lifetime', 86400); // 24 hours

// Session Security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// PHP Security
ini_set('expose_php', 0);
ini_set('allow_url_fopen', 0);
ini_set('allow_url_include', 0);

// Security headers (applied automatically)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    if (defined('BVOTE_SSL_ENABLED') && BVOTE_SSL_ENABLED) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    // CSP header for enhanced security
    $csp = "default-src 'self'; ";
    $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval'; ";
    $csp .= "style-src 'self' 'unsafe-inline'; ";
    $csp .= "img-src 'self' data:; ";
    $csp .= "font-src 'self'; ";
    $csp .= "connect-src 'self'; ";
    $csp .= "media-src 'self'; ";
    $csp .= "object-src 'none'; ";
    $csp .= "frame-src 'none';";

    header("Content-Security-Policy: $csp");
}

// Error handling for production
if (!BVOTE_DEBUG) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);

    // Custom error handler for production
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        $error_msg = "Error [$errno]: $errstr in $errfile on line $errline";
        error_log($error_msg);

        // Don't show errors to users in production
        if ($errno === E_ERROR || $errno === E_PARSE || $errno === E_CORE_ERROR) {
            http_response_code(500);
            if (file_exists(ROOT_PATH . '/templates/error.php')) {
                include ROOT_PATH . '/templates/error.php';
            } else {
                echo '<!DOCTYPE html><html><head><title>Error</title></head><body><h1>Service Temporarily Unavailable</h1><p>Please try again later.</p></body></html>';
            }
            exit;
        }

        return true;
    });

    // Exception handler for production
    set_exception_handler(function($exception) {
        error_log("Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());

        http_response_code(500);
        if (file_exists(ROOT_PATH . '/templates/error.php')) {
            include ROOT_PATH . '/templates/error.php';
        } else {
            echo '<!DOCTYPE html><html><head><title>Error</title></head><body><h1>Service Temporarily Unavailable</h1><p>Please try again later.</p></body></html>';
        }
        exit;
    });
}

// Performance optimizations
if (defined('BVOTE_ENABLE_GZIP') && BVOTE_ENABLE_GZIP && !ob_get_level()) {
    ob_start('ob_gzhandler');
}

// Set PHP configuration for production
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', (defined('BVOTE_SSL_ENABLED') && BVOTE_SSL_ENABLED) ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', defined('BVOTE_SESSION_LIFETIME') ? BVOTE_SESSION_LIFETIME : 3600);

// Register shutdown function for cleanup
register_shutdown_function(function() {
    // Clean up temporary files
    $temp_dir = sys_get_temp_dir() . '/bvote_temp';
    if (is_dir($temp_dir)) {
        $files = glob($temp_dir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && time() - filemtime($file) > 3600) {
                unlink($file);
            }
        }
    }

    // Log script execution time for monitoring
    if (defined('BVOTE_START_TIME')) {
        $execution_time = microtime(true) - BVOTE_START_TIME;
        if ($execution_time > 5) { // Log slow requests
            error_log("BVOTE Slow Request: {$_SERVER['REQUEST_URI']} took {$execution_time}s");
        }
    }
});

// Set start time for performance monitoring
if (!defined('BVOTE_START_TIME')) {
    define('BVOTE_START_TIME', microtime(true));
}

/**
 * Production configuration validation
 */
function bvote_validate_production_config() {
    $errors = [];

    // Check database connection
    try {
        $pdo = new PDO(
            "mysql:host=" . BVOTE_DB_HOST . ";dbname=" . BVOTE_DB_NAME,
            BVOTE_DB_USER,
            BVOTE_DB_PASS
        );
    } catch (PDOException $e) {
        $errors[] = "Database connection failed: " . $e->getMessage();
    }

    // Check required directories
    $required_dirs = [
        ROOT_PATH . '/uploads',
        ROOT_PATH . '/data',
        ROOT_PATH . '/data/cache',
        ROOT_PATH . '/data/logs',
        ROOT_PATH . '/backups'
    ];

    foreach ($required_dirs as $dir) {
        if (!is_dir($dir)) {
            $errors[] = "Required directory missing: $dir";
        } elseif (!is_writable($dir)) {
            $errors[] = "Directory not writable: $dir";
        }
    }

    // Check file permissions
    $required_files = [
        ROOT_PATH . '/includes/bootstrap.php',
        ROOT_PATH . '/includes/database.php',
        ROOT_PATH . '/includes/functions.php'
    ];

    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            $errors[] = "Required file missing: $file";
        } elseif (!is_readable($file)) {
            $errors[] = "File not readable: $file";
        }
    }

    // Check security settings
    if (BVOTE_SALT === 'CHANGE_THIS_SALT_IN_PRODUCTION') {
        $errors[] = "Security salt must be changed in production";
    }

    if (empty(BVOTE_SMTP_USER) || empty(BVOTE_SMTP_PASS)) {
        $errors[] = "Email configuration incomplete (optional in development)";
    }

    return $errors;
}

// Run validation in development mode
if (defined('BVOTE_DEBUG') && BVOTE_DEBUG) {
    $config_errors = bvote_validate_production_config();
    if (!empty($config_errors)) {
        error_log("BVOTE Production Config Validation: " . implode(', ', $config_errors));
    }
}
