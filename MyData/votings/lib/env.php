<?php
// env.php - Tự động đọc biến môi trường từ file .env
function env($key, $default = null) {
    static $vars = null;
    if ($vars === null) {
        $vars = [];
        $envPath = __DIR__ . '/.env';
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
                list($k, $v) = explode('=', $line, 2);
                $vars[trim($k)] = trim($v);
            }
        }
    }
    return $vars[$key] ?? $default;
}
// Ví dụ sử dụng: $db_host = env('DB_HOST', 'localhost');
