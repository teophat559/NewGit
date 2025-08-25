<?php
// admin.php - Xử lý API admin endpoints
require_once __DIR__ . '/../services/db.php';
require_once __DIR__ . '/../services/auth.php';
require_once __DIR__ . '/../services/user.php';
require_once __DIR__ . '/../services/dashboard.php';
require_once __DIR__ . '/../services/notification.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Lấy endpoint từ URL
$endpoint = end($pathParts);

try {
    switch ($method) {
        case 'GET':
            switch ($endpoint) {
                case 'dashboard':
                    handleGetDashboard();
                    break;

                case 'users':
                    handleGetUsers();
                    break;

                case 'user':
                    handleGetUser();
                    break;

                case 'stats':
                    handleGetStats();
                    break;

                case 'charts':
                    handleGetCharts();
                    break;

                case 'realtime':
                    handleGetRealTime();
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint không tồn tại']);
                    break;
            }
            break;

        case 'POST':
            switch ($endpoint) {
                case 'user-create':
                    handleCreateUser();
                    break;

                case 'user-update':
                    handleUpdateUser();
                    break;

                case 'user-delete':
                    handleDeleteUser();
                    break;

                case 'bulk-action':
                    handleBulkAction();
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint không tồn tại']);
                    break;
            }
            break;

        case 'PUT':
            switch ($endpoint) {
                case 'user-status':
                    handleUpdateUserStatus();
                    break;

                case 'user-role':
                    handleUpdateUserRole();
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint không tồn tại']);
                    break;
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method không được hỗ trợ']);
            break;
    }

} catch (Exception $e) {
    error_log("Admin route error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi server: ' . $e->getMessage()]);
}

// ==================== DASHBOARD HANDLERS ====================

function handleGetDashboard() {
    // Kiểm tra quyền admin
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Không có quyền truy cập']);
        return;
    }

    $auth = auth();
    $admin = $auth->validateAdminToken($token);

    if (!$admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Không có quyền truy cập admin']);
        return;
    }

    $dashboard = dashboard();
    $result = $dashboard->getOverviewStats();

    echo json_encode($result);
}

function handleGetStats() {
    // Kiểm tra quyền admin
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Không có quyền truy cập']);
        return;
    }

    $auth = auth();
    $admin = $auth->validateAdminToken($token);

    if (!$admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Không có quyền truy cập admin']);
        return;
    }

    $userService = user();
    $userStats = $userService->getSystemStats();

    echo json_encode([
        'success' => true,
        'data' => $userStats
    ]);
}

function handleGetCharts() {
    // Kiểm tra quyền admin
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Không có quyền truy cập']);
        return;
    }

    $auth = auth();
    $admin = $auth->validateAdminToken($token);

    if (!$admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Không có quyền truy cập admin']);
        return;
    }

    $chartType = $_GET['type'] ?? 'user_growth';
    $period = $_GET['period'] ?? '30';

    $dashboard = dashboard();
    $result = $dashboard->getChartData($chartType, $period);

    echo json_encode($result);
}

function handleGetRealTime() {
    // Kiểm tra quyền admin
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Không có quyền truy cập']);
        return;
    }

    $auth = auth();
    $admin = $auth->validateAdminToken($token);

    if (!$admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Không có quyền truy cập admin']);
        return;
    }

    $dashboard = dashboard();
    $result = $dashboard->getRealTimeUpdates();

    echo json_encode($result);
}

// ==================== USER MANAGEMENT HANDLERS ====================

function handleGetUsers() {
    // Kiểm tra quyền admin
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Không có quyền truy cập']);
        return;
    }

    $auth = auth();
    $admin = $auth->validateAdminToken($token);

    if (!$admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Không có quyền truy cập admin']);
        return;
    }

    $userService = user();

    $filters = [
        'status' => $_GET['status'] ?? null,
        'role' => $_GET['role'] ?? null,
        'search' => $_GET['search'] ?? null
    ];

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

    $result = $userService->getUsers($filters, $page, $limit);

    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
}

function handleGetUser() {
    // Kiểm tra quyền admin
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Không có quyền truy cập']);
        return;
    }

    $auth = auth();
    $admin = $auth->validateAdminToken($token);

    if (!$admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Không có quyền truy cập admin']);
        return;
    }

    $userId = $_GET['id'] ?? null;

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu ID người dùng']);
        return;
    }

    $userService = user();
    $user = $userService->getUserById($userId);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Không tìm thấy người dùng']);
        return;
    }

    echo json_encode([
        'success' => true,
        'data' => $user
    ]);
}

function handleCreateUser() {
    // Kiểm tra quyền admin
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Không có quyền truy cập']);
        return;
    }

    $auth = auth();
    $admin = $auth->validateAdminToken($token);

    if (!$admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Không có quyền truy cập admin']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Dữ liệu không hợp lệ']);
        return;
    }

    $userService = user();
    $result = $userService->createUser($input);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

function handleUpdateUser() {
    // Kiểm tra quyền admin
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Không có quyền truy cập']);
        return;
    }

    $auth = auth();
    $admin = $auth->validateAdminToken($token);

    if (!$admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Không có quyền truy cập admin']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu ID người dùng']);
        return;
    }

    $userService = user();
    $result = $userService->updateUser($input['id'], $input);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

function handleDeleteUser() {
    // Kiểm tra quyền admin
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Không có quyền truy cập']);
        return;
    }

    $auth = auth();
    $admin = $auth->validateAdminToken($token);

    if (!$admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Không có quyền truy cập admin']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu ID người dùng']);
        return;
    }

    $userService = user();
    $result = $userService->deleteUser($input['id']);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

function handleUpdateUserStatus() {
    // Kiểm tra quyền admin
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Không có quyền truy cập']);
        return;
    }

    $auth = auth();
    $admin = $auth->validateAdminToken($token);

    if (!$admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Không có quyền truy cập admin']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['id']) || !isset($input['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu thông tin cập nhật']);
        return;
    }

    $userService = user();
    $result = $userService->updateUser($input['id'], ['status' => $input['status']]);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

function handleUpdateUserRole() {
    // Kiểm tra quyền admin
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Không có quyền truy cập']);
        return;
    }

    $auth = auth();
    $admin = $auth->validateAdminToken($token);

    if (!$admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Không có quyền truy cập admin']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['id']) || !isset($input['role'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu thông tin cập nhật']);
        return;
    }

    $userService = user();
    $result = $userService->updateUser($input['id'], ['role' => $input['role']]);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

function handleBulkAction() {
    // Kiểm tra quyền admin
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Không có quyền truy cập']);
        return;
    }

    $auth = auth();
    $admin = $auth->validateAdminToken($token);

    if (!$admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Không có quyền truy cập admin']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['action']) || !isset($input['user_ids'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu thông tin hành động hàng loạt']);
        return;
    }

    $userService = user();
    $results = [];
    $successCount = 0;
    $errorCount = 0;

    foreach ($input['user_ids'] as $userId) {
        switch ($input['action']) {
            case 'activate':
                $result = $userService->updateUser($userId, ['status' => 'active']);
                break;

            case 'deactivate':
                $result = $userService->updateUser($userId, ['status' => 'inactive']);
                break;

            case 'delete':
                $result = $userService->deleteUser($userId);
                break;

            default:
                $result = ['success' => false, 'message' => 'Hành động không được hỗ trợ'];
                break;
        }

        $results[] = $result;

        if ($result['success']) {
            $successCount++;
        } else {
            $errorCount++;
        }
    }

    echo json_encode([
        'success' => $errorCount === 0,
        'total' => count($input['user_ids']),
        'success_count' => $successCount,
        'error_count' => $errorCount,
        'results' => $results
    ]);
}

// ==================== UTILITY FUNCTIONS ====================

function getTokenFromRequest() {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (strpos($auth, 'Bearer ') === 0) {
            return substr($auth, 7);
        }
    }

    if (isset($_COOKIE['auth_token'])) {
        return $_COOKIE['auth_token'];
    }

    if (isset($_POST['token'])) {
        return $_POST['token'];
    }

    if (isset($_GET['token'])) {
        return $_GET['token'];
    }

    return null;
}
?>
