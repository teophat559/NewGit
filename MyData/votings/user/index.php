<?php
/**
 * BVOTE 2025 - User Interface Controller
 * Main entry point for user interface
 */

// Security check
define('VOTING_SYSTEM_ACCESS', true);

// Include configuration
require_once __DIR__ . '/../config/config.php';

// Start session
session_start();

// Get the request route
$route = $_GET['route'] ?? '';
$route = trim($route, '/');

// Handle different user routes
switch ($route) {
    case '':
    case 'index':
        // Main user interface
        if (file_exists(__DIR__ . '/index.html')) {
            readfile(__DIR__ . '/index.html');
        } else {
            echo "User interface not found";
        }
        break;

    case 'login':
        // Handle login
        require_once __DIR__ . '/auth_login.php';
        break;

    case 'logout':
        // Handle logout
        require_once __DIR__ . '/logout.php';
        break;

    case 'vote':
        // Handle voting
        require_once __DIR__ . '/vote.php';
        break;

    case 'process_vote':
        // Process vote submission
        require_once __DIR__ . '/process_vote.php';
        break;

    case 'history':
        // User voting history
        require_once __DIR__ . '/history.php';
        break;

    default:
        // 404 for unknown routes
        header("HTTP/1.1 404 Not Found");
        echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>404 - Không tìm thấy</title>
</head>
<body>
    <h1>404 - Không tìm thấy trang</h1>
    <p><a href='/user/'>Về trang chính</a></p>
</body>
</html>";
        break;
}
?>
