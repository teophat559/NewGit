<?php
// config.php - Cấu hình hệ thống cho XAMPP local
// Copy file này thành .env và điều chỉnh các giá trị

// ==================== DATABASE CONFIGURATION ====================
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'newb_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// ==================== APPLICATION CONFIGURATION ====================
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Contest Management System');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', $_ENV['APP_DEBUG'] ?? true);
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/newbvote2025s');
define('APP_TIMEZONE', $_ENV['APP_TIMEZONE'] ?? 'Asia/Ho_Chi_Minh');

// ==================== SECURITY CONFIGURATION ====================
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'votingnew2025s-jwt-secret-2025');
define('SESSION_SECRET', $_ENV['SESSION_SECRET'] ?? 'votingnew2025s-session-secret-2025');
define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? 'votingnew2025s-encryption-key-32');
define('ADMIN_KEY', $_ENV['ADMIN_KEY'] ?? 'admin-key-2025');

// ==================== UPLOAD CONFIGURATION ====================
define('UPLOAD_DIR', $_ENV['UPLOAD_DIR'] ?? 'uploads/');
define('MAX_FILE_SIZE', $_ENV['MAX_FILE_SIZE'] ?? 50 * 1024 * 1024); // 50MB
define('ALLOWED_FILE_TYPES', $_ENV['ALLOWED_FILE_TYPES'] ?? 'image,video,document,archive');

// ==================== MAIL CONFIGURATION ====================
define('MAIL_MAILER', $_ENV['MAIL_MAILER'] ?? 'smtp');
define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com');
define('MAIL_PORT', $_ENV['MAIL_PORT'] ?? 587);
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? 'your-email@gmail.com');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? 'your-app-password');
define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? 'tls');
define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS'] ?? 'your-email@gmail.com');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? APP_NAME);

// ==================== TELEGRAM CONFIGURATION ====================
define('TELEGRAM_BOT_TOKEN', $_ENV['TELEGRAM_BOT_TOKEN'] ?? '7001751139:AAFCC83DPRn1larWNjd_ms9xvY9rl0KJlGE');
define('TELEGRAM_CHAT_ID', $_ENV['TELEGRAM_CHAT_ID'] ?? '6936181519');

// ==================== CHROME AUTOMATION ====================
define('CHROME_EXECUTABLE_PATH', $_ENV['CHROME_EXECUTABLE_PATH'] ?? 'C:\Program Files\Google\Chrome\Application\chrome.exe');
define('CHROME_USER_DATA_DIR', $_ENV['CHROME_USER_DATA_DIR'] ?? 'C:\temp\chrome-profiles');
define('CHROME_HEADLESS', $_ENV['CHROME_HEADLESS'] ?? false);

// ==================== REDIS CONFIGURATION (Optional) ====================
define('REDIS_HOST', $_ENV['REDIS_HOST'] ?? '127.0.0.1');
define('REDIS_PASSWORD', $_ENV['REDIS_PASSWORD'] ?? null);
define('REDIS_PORT', $_ENV['REDIS_PORT'] ?? 6379);

// ==================== CACHE CONFIGURATION ====================
define('CACHE_DRIVER', $_ENV['CACHE_DRIVER'] ?? 'file');
define('CACHE_TTL', $_ENV['CACHE_TTL'] ?? 3600);

// ==================== LOGGING CONFIGURATION ====================
define('LOG_CHANNEL', $_ENV['LOG_CHANNEL'] ?? 'stack');
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'debug');
define('LOG_DAYS', $_ENV['LOG_DAYS'] ?? 14);

// ==================== EXTERNAL SERVICES ====================
define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? 'your-google-client-id');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? 'your-google-client-secret');
define('FACEBOOK_CLIENT_ID', $_ENV['FACEBOOK_CLIENT_ID'] ?? 'your-facebook-client-id');
define('FACEBOOK_CLIENT_SECRET', $_ENV['FACEBOOK_CLIENT_SECRET'] ?? 'your-facebook-client-secret');

// ==================== UTILITY FUNCTIONS ====================

function isProduction() {
    return APP_ENV === 'production';
}

function isDevelopment() {
    return APP_ENV === 'development';
}

function isDebug() {
    return APP_DEBUG === true;
}

function getBaseUrl() {
    return APP_URL;
}

function getUploadUrl($path = '') {
    return APP_URL . '/' . UPLOAD_DIR . $path;
}

function getConfig($key, $default = null) {
    $constant = strtoupper($key);
    return defined($constant) ? constant($constant) : $default;
}

// ==================== ERROR REPORTING ====================
if (isDevelopment() && isDebug()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ==================== DATABASE CONNECTION ====================
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    if (isDevelopment()) {
        die("Database connection failed: " . $e->getMessage());
    } else {
        die("Database connection failed. Please check your configuration.");
    }
}

// ==================== TIMEZONE SETTING ====================
date_default_timezone_set(APP_TIMEZONE);

// ==================== SESSION CONFIGURATION ====================
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isProduction());
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// ==================== SECURITY HEADERS ====================
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    if (isProduction()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
?>
