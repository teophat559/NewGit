<?php
/**
 * BVOTE Voting System Bootstrap
 * Khởi tạo ứng dụng và autoload
 */

// Kiểm tra PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('BVOTE requires PHP 7.4 or higher. Current version: ' . PHP_VERSION);
}

// Định nghĩa constants
if (!defined('BVOTE_VERSION')) define('BVOTE_VERSION', '1.0.0');
if (!defined('BVOTE_START_TIME')) define('BVOTE_START_TIME', microtime(true));
if (!defined('BVOTE_START_MEMORY')) define('BVOTE_START_MEMORY', memory_get_usage());

// Đường dẫn gốc
if (!defined('BASE_PATH')) define('BASE_PATH', __DIR__);
if (!defined('APP_PATH')) define('APP_PATH', BASE_PATH . '/app');
if (!defined('CONFIG_PATH')) define('CONFIG_PATH', BASE_PATH . '/config');
if (!defined('STORAGE_PATH')) define('STORAGE_PATH', BASE_PATH . '/storage');
if (!defined('PUBLIC_PATH')) define('PUBLIC_PATH', BASE_PATH . '/public');

// Autoload Composer
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
} else {
    die('Composer autoload not found. Please run: composer install');
}

// Load environment variables
if (file_exists(BASE_PATH . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
} else {
    die('.env file not found. Please copy .env.example to .env and configure it');
}

// Set error reporting based on environment
if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Ho_Chi_Minh');

// Start session with secure settings (only if not in CLI and session not already started)
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');

    session_start();
}

// Load core classes
require_once BASE_PATH . '/lib/Router.php';
require_once BASE_PATH . '/lib/Component.php';
require_once BASE_PATH . '/lib/Middleware.php';
require_once BASE_PATH . '/lib/auth.php';
require_once BASE_PATH . '/lib/services.php';
require_once BASE_PATH . '/lib/utils.php';

// Initialize core services (only if not in CLI or if database is available)
if (php_sapi_name() !== 'cli') {
    try {
        BVOTE\Core\App::getInstance()->initialize();
    } catch (Exception $e) {
        // Log error but don't crash the application
        error_log('Failed to initialize core services: ' . $e->getMessage());
    }
}

// Set security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Error handler
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Exception handler
set_exception_handler(function($exception) {
    BVOTE\Core\Logger::error('Uncaught Exception: ' . $exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);

    if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true') {
        throw $exception;
    } else {
        http_response_code(500);
        include BASE_PATH . '/templates/error.php';
    }
});

// Shutdown function
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        BVOTE\Core\Logger::error('Fatal Error: ' . $error['message'], [
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

echo "✅ BVOTE Bootstrap completed successfully\n";
