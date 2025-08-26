<?php
/**
 * BVOTE Public Entry Point
 * Điểm vào chính cho web application
 */

require_once __DIR__ . '/../bootstrap.php';

// Route to appropriate controller based on URL
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove leading slash
$path = ltrim($path, '/');

// Simple routing
switch ($path) {
    case '':
    case 'index.php':
        // Home page
        echo '<!DOCTYPE html>';
        echo '<html lang="en">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>BVOTE Voting System</title>';
        echo '<style>';
        echo 'body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }';
        echo '.container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }';
        echo 'h1 { color: #2c3e50; text-align: center; margin-bottom: 30px; }';
        echo '.feature { background: #ecf0f1; padding: 20px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #3498db; }';
        echo '.feature h3 { color: #2c3e50; margin-top: 0; }';
        echo '.btn { display: inline-block; padding: 12px 24px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }';
        echo '.btn:hover { background: #2980b9; }';
        echo '.status { text-align: center; margin: 20px 0; padding: 15px; background: #d5f4e6; border-radius: 5px; color: #27ae60; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';
        echo '<div class="container">';
        echo '<h1>🏆 BVOTE Voting System</h1>';
        echo '<div class="status">✅ System is running successfully!</div>';

        echo '<div class="feature">';
        echo '<h3>🎯 System Features</h3>';
        echo '<p>Advanced voting system with contest management, user authentication, and real-time monitoring.</p>';
        echo '</div>';

        echo '<div class="feature">';
        echo '<h3>🔧 Available Endpoints</h3>';
        echo '<p>• <a href="/health" class="btn">Health Check</a> - System status and diagnostics</p>';
        echo '<p>• <a href="/vote" class="btn">Vote System</a> - Voting functionality</p>';
        echo '</div>';

        echo '<div class="feature">';
        echo '<h3>📊 System Information</h3>';
        echo '<p><strong>Version:</strong> ' . (defined('BVOTE_VERSION') ? BVOTE_VERSION : '1.0.0') . '</p>';
        echo '<p><strong>Environment:</strong> ' . ($_ENV['APP_ENV'] ?? 'local') . '</p>';
        echo '<p><strong>PHP Version:</strong> ' . PHP_VERSION . '</p>';
        echo '<p><strong>Server Time:</strong> ' . date('Y-m-d H:i:s') . '</p>';
        echo '</div>';

        echo '<div style="text-align: center; margin-top: 30px;">';
        echo '<p><em>Your advanced voting system is ready!</em></p>';
        echo '</div>';
        echo '</div>';
        echo '</body>';
        echo '</html>';
        break;

    case 'health':
        // Health check endpoint
        require_once __DIR__ . '/../pages/HealthCheckPage.php';
        break;

    case 'vote':
        // Vote system endpoint
        require_once __DIR__ . '/../vote.php';
        break;

    default:
        // 404 Not Found
        http_response_code(404);
        include __DIR__ . '/../templates/404.php';
        break;
}
