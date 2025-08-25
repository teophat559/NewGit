<?php
// index.php - Backend API router chính
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/services/db.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get request path
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/backend/';
$requestPath = str_replace($basePath, '', $requestUri);
$pathParts = explode('/', trim($requestPath, '/'));

// Route handling
try {
    if (empty($pathParts) || $pathParts[0] === '') {
        // Root endpoint
        echo json_encode([
            'message' => 'Contest Management System API',
            'version' => '1.0.0',
            'status' => 'running',
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoints' => [
                'auth' => '/backend/auth/*',
                'contests' => '/backend/contests/*',
                'admin' => '/backend/admin/*',
                'api' => '/backend/api/*',
                'automation' => '/backend/automation/*',
                'integrations' => '/backend/integrations/*',
                'notifications' => '/backend/notifications/*',
                'upload' => '/backend/upload/*'
            ]
        ]);
        exit();
    }

    $route = $pathParts[0];

    switch ($route) {
        case 'auth':
            require __DIR__ . '/routes/auth.php';
            break;

        case 'contests':
            require __DIR__ . '/routes/contests.php';
            break;

        case 'admin':
            require __DIR__ . '/routes/admin.php';
            break;

        case 'api':
            require __DIR__ . '/routes/api.php';
            break;

        case 'automation':
            require __DIR__ . '/routes/automation.php';
            break;

        case 'integrations':
            require __DIR__ . '/routes/integrations.php';
            break;

        case 'notifications':
            require __DIR__ . '/routes/notifications.php';
            break;

                case 'upload':
            require __DIR__ . '/routes/upload.php';
            break;

        case 'voting':
            require __DIR__ . '/routes/voting.php';
            break;

        case 'ui':
            require __DIR__ . '/routes/ui.php';
            break;

        case 'health':
            // Health check endpoint
            $db = db();
            try {
                $db->query("SELECT 1");
                echo json_encode([
                    'status' => 'healthy',
                    'database' => 'connected',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'uptime' => 'running'
                ]);
            } catch (Exception $e) {
                http_response_code(503);
                echo json_encode([
                    'status' => 'unhealthy',
                    'database' => 'disconnected',
                    'error' => $e->getMessage(),
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode([
                'error' => 'Route không tồn tại',
                                  'available_routes' => [
                      'auth', 'contests', 'admin', 'api', 'automation',
                      'integrations', 'notifications', 'upload', 'voting', 'ui', 'health'
                  ]
            ]);
            break;
    }

} catch (Exception $e) {
    error_log("Backend routing error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Lỗi server',
        'message' => isDevelopment() ? $e->getMessage() : 'Đã xảy ra lỗi nội bộ',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
