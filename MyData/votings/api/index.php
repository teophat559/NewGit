<?php
/**
 * BVOTE 2025 - API Controller
 * REST API endpoints for the voting system
 */

// Security check
define('VOTING_SYSTEM_ACCESS', true);

// Include configuration
require_once __DIR__ . '/../config/config.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get the request route
$route = $_GET['route'] ?? '';
$route = trim($route, '/');
$method = $_SERVER['REQUEST_METHOD'];

// Parse route segments
$segments = explode('/', $route);
$version = $segments[0] ?? 'v1';
$endpoint = $segments[1] ?? '';
$id = $segments[2] ?? null;

// API response helper
function apiResponse($data, $status = 200, $message = 'Success') {
    http_response_code($status);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ]);
    exit();
}

// API error helper
function apiError($message, $status = 400, $code = null) {
    http_response_code($status);
    echo json_encode([
        'status' => $status,
        'error' => $message,
        'error_code' => $code,
        'timestamp' => date('c')
    ]);
    exit();
}

// Rate limiting (basic implementation)
function checkRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'];
    // Implement rate limiting logic here
    return true;
}

// API key validation (if required)
function validateApiKey() {
    if (!API_KEY_REQUIRED) {
        return true;
    }

    $apiKey = $_SERVER['HTTP_AUTHORIZATION'] ?? $_GET['api_key'] ?? null;
    // Implement API key validation logic here
    return !empty($apiKey);
}

// Check rate limit and API key
if (!checkRateLimit()) {
    apiError('Rate limit exceeded', 429);
}

if (!validateApiKey()) {
    apiError('Invalid or missing API key', 401);
}

// Route to appropriate API version
switch ($version) {
    case 'v1':
        // API v1 routes
        switch ($endpoint) {
            case 'campaigns':
                require_once __DIR__ . '/v1/campaigns.php';
                break;

            case 'contestants':
                require_once __DIR__ . '/v1/contestants.php';
                break;

            case 'votes':
                require_once __DIR__ . '/v1/votes.php';
                break;

            case 'stats':
                require_once __DIR__ . '/v1/stats.php';
                break;

            case 'auth':
                require_once __DIR__ . '/v1/auth.php';
                break;

            case '':
                // API info
                apiResponse([
                    'name' => APP_NAME,
                    'version' => APP_VERSION,
                    'api_version' => 'v1',
                    'endpoints' => [
                        'campaigns' => '/api/v1/campaigns',
                        'contestants' => '/api/v1/contestants',
                        'votes' => '/api/v1/votes',
                        'stats' => '/api/v1/stats',
                        'auth' => '/api/v1/auth'
                    ]
                ]);
                break;

            default:
                apiError('Endpoint not found', 404);
        }
        break;

    default:
        apiError('API version not supported', 404);
}
?>
