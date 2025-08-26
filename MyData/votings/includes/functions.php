<?php
/**
 * BVOTE Common Functions
 */

if (!function_exists('sanitize_input')) {
    function sanitize_input($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('log_activity')) {
    function log_activity($message, $level = 'info') {
        $logFile = __DIR__ . '/../logs/system.log';
        $timestamp = date('Y-m-d H:i:s');
        $entry = "[$timestamp] [$level] $message\n";
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
