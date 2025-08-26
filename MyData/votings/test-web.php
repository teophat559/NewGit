<?php
/**
 * Simple Web Interface Test
 * Test web functionality without composer dependencies
 */

// Simple routing without dependencies
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove query parameters
$path = strtok($path, '?');

// Simple routing
switch ($path) {
    case '/':
    case '/index.html':
        // Serve the HTML file directly
        header('Content-Type: text/html');
        readfile(__DIR__ . '/index.html');
        break;
        
    case '/health':
        // Simple health check
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'system' => 'BVOTE Test Environment',
            'php_version' => PHP_VERSION,
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . 'MB'
        ], JSON_PRETTY_PRINT);
        break;
        
    case '/test':
        // Test endpoint
        header('Content-Type: application/json');
        echo json_encode([
            'message' => 'BVOTE Test Endpoint Working!',
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $_SERVER['REQUEST_METHOD'],
            'path' => $path,
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ], JSON_PRETTY_PRINT);
        break;
        
    case '/info':
        // System info
        header('Content-Type: text/html');
        echo "<h1>BVOTE System Info</h1>";
        echo "<h2>PHP Configuration</h2>";
        echo "<p>PHP Version: " . PHP_VERSION . "</p>";
        echo "<p>Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
        echo "<p>Memory: " . ini_get('memory_limit') . "</p>";
        echo "<h2>Loaded Extensions</h2><ul>";
        $extensions = get_loaded_extensions();
        sort($extensions);
        foreach ($extensions as $ext) {
            echo "<li>$ext</li>";
        }
        echo "</ul>";
        break;
        
    default:
        // 404 for other routes
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Route not found',
            'path' => $path,
            'available_routes' => ['/', '/health', '/test', '/info']
        ], JSON_PRETTY_PRINT);
        break;
}
?>