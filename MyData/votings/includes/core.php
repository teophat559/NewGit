<?php
/**
 * Core System Classes for BVOTE 2025
 * Production-ready architecture
 */

namespace VotingSystem\Core;

// Database Manager
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->connection = new \PDO($dsn, DB_USER, DB_PASS, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (\PDOException $e) {
            if (DEBUG_MODE) {
                die('Database connection failed: ' . $e->getMessage());
            } else {
                die('Database connection error');
            }
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}

// Security Manager
class Security {
    public static function generateCSRFToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    public static function validateCSRFToken($token) {
        return isset($_SESSION[CSRF_TOKEN_NAME]) &&
               hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }

    public static function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    public static function hashPassword($password) {
        return password_hash($password . PASSWORD_SALT, PASSWORD_DEFAULT);
    }

    public static function verifyPassword($password, $hash) {
        return password_verify($password . PASSWORD_SALT, $hash);
    }

    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public static function requireLogin($redirect = '/user/login.php') {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . $redirect);
            exit;
        }
    }

    public static function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }

    public static function requireAdmin($redirect = '/admin/login.php') {
        if (!self::isAdmin()) {
            header('Location: ' . BASE_URL . $redirect);
            exit;
        }
    }
}

// Session Manager
class Session {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public static function remove($key) {
        unset($_SESSION[$key]);
    }

    public static function destroy() {
        session_destroy();
    }

    public static function flash($key, $message = null) {
        if ($message !== null) {
            $_SESSION['flash'][$key] = $message;
        } else {
            $message = $_SESSION['flash'][$key] ?? null;
            unset($_SESSION['flash'][$key]);
            return $message;
        }
    }
}

// Cache Manager
class Cache {
    private static $cacheDir;

    public static function init() {
        self::$cacheDir = ROOT_PATH . '/data/cache/';
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }

    public static function get($key) {
        if (!ENABLE_CACHE) return null;

        $file = self::$cacheDir . md5($key) . '.cache';
        if (!file_exists($file)) return null;

        $data = unserialize(file_get_contents($file));
        if ($data['expires'] < time()) {
            unlink($file);
            return null;
        }

        return $data['value'];
    }

    public static function set($key, $value, $ttl = null) {
        if (!ENABLE_CACHE) return false;

        $ttl = $ttl ?? CACHE_TIME;
        $file = self::$cacheDir . md5($key) . '.cache';
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];

        return file_put_contents($file, serialize($data)) !== false;
    }

    public static function delete($key) {
        $file = self::$cacheDir . md5($key) . '.cache';
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    public static function clear() {
        $files = glob(self::$cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}

// File Manager
class FileManager {
    public static function uploadImage($file, $targetDir = 'images/') {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['success' => false, 'error' => 'No file uploaded'];
        }

        // Validate file
        $fileSize = $file['size'];
        $fileName = $file['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileSize > MAX_UPLOAD_SIZE) {
            return ['success' => false, 'error' => 'File too large'];
        }

        if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
            return ['success' => false, 'error' => 'Invalid file type'];
        }

        // Generate unique filename
        $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $targetPath = UPLOADS_PATH . '/' . $targetDir;

        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
        }

        $fullPath = $targetPath . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            return [
                'success' => true,
                'filename' => $newFileName,
                'path' => $targetDir . $newFileName,
                'url' => BASE_URL . '/uploads/' . $targetDir . $newFileName
            ];
        }

        return ['success' => false, 'error' => 'Upload failed'];
    }

    public static function deleteFile($filePath) {
        $fullPath = UPLOADS_PATH . '/' . $filePath;
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return true;
    }
}

// Logger
class Logger {
    private static $logDir;

    public static function init() {
        self::$logDir = LOGS_PATH . '/';
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }

    public static function log($message, $level = 'INFO', $file = 'system.log') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

        $logFile = self::$logDir . $file;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    public static function error($message) {
        self::log($message, 'ERROR', 'error.log');
    }

    public static function info($message) {
        self::log($message, 'INFO', 'system.log');
    }

    public static function debug($message) {
        if (DEBUG_MODE) {
            self::log($message, 'DEBUG', 'debug.log');
        }
    }
}

// Response Helper
class Response {
    public static function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function redirect($url, $permanent = false) {
        $code = $permanent ? 301 : 302;
        http_response_code($code);
        header('Location: ' . $url);
        exit;
    }

    public static function error($message, $code = 500) {
        http_response_code($code);
        if (DEBUG_MODE) {
            echo $message;
        } else {
            echo 'An error occurred';
        }
        exit;
    }
}

// Utility Functions
class Utils {
    public static function formatDate($date, $format = 'Y-m-d H:i:s') {
        return date($format, strtotime($date));
    }

    public static function timeAgo($datetime) {
        $time = time() - strtotime($datetime);

        if ($time < 60) return 'vừa xong';
        if ($time < 3600) return floor($time/60) . ' phút trước';
        if ($time < 86400) return floor($time/3600) . ' giờ trước';
        if ($time < 2592000) return floor($time/86400) . ' ngày trước';
        if ($time < 31536000) return floor($time/2592000) . ' tháng trước';
        return floor($time/31536000) . ' năm trước';
    }

    public static function generateSlug($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-');
    }

    public static function formatNumber($number) {
        return number_format($number, 0, ',', '.');
    }

    public static function truncateText($text, $length = 100) {
        if (strlen($text) <= $length) return $text;
        return substr($text, 0, $length) . '...';
    }
}
