<?php
/**
 * Automation Routes - Chrome Automation API
 * Xử lý tất cả các endpoints liên quan đến tự động hóa Chrome
 */

require_once __DIR__ . '/../services/chrome_automation.php';
require_once __DIR__ . '/../services/auth.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialize services
$chromeService = new ChromeAutomationService();
$authService = new AuthService();

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Extract endpoint from path
$endpoint = end($pathParts);

try {
    switch ($method) {
        case 'GET':
            switch ($endpoint) {
                case 'profiles':
                    handleGetProfiles();
                    break;
                    
                case 'sessions':
                    handleGetSessions();
                    break;
                    
                case 'stats':
                    handleGetStats();
                    break;
                    
                case 'profile':
                    handleGetProfile();
                    break;
                    
                case 'session':
                    handleGetSession();
                    break;
                    
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint không tồn tại']);
                    break;
            }
            break;
            
        case 'POST':
            switch ($endpoint) {
                case 'profiles':
                    handleCreateProfile();
                    break;
                    
                case 'sessions':
                    handleCreateSession();
                    break;
                    
                case 'start':
                    handleStartAutomation();
                    break;
                    
                case 'stop':
                    handleStopAutomation();
                    break;
                    
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint không tồn tại']);
                    break;
            }
            break;
            
        case 'PUT':
            switch ($endpoint) {
                case 'profiles':
                    handleUpdateProfile();
                    break;
                    
                case 'sessions':
                    handleUpdateSession();
                    break;
                    
                case 'status':
                    handleUpdateStatus();
                    break;
                    
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint không tồn tại']);
                    break;
            }
            break;
            
        case 'DELETE':
            switch ($endpoint) {
                case 'profiles':
                    handleDeleteProfile();
                    break;
                    
                case 'sessions':
                    handleDeleteSession();
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
    http_response_code(500);
    echo json_encode([
        'error' => 'Lỗi server',
        'message' => $e->getMessage()
    ]);
}

/**
 * GET /automation/profiles - Lấy danh sách Chrome profiles
 */
function handleGetProfiles() {
    global $chromeService, $authService;
    
    // Kiểm tra quyền truy cập
    if (!$authService->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Chưa đăng nhập']);
        return;
    }
    
    $userId = $_GET['user_id'] ?? null;
    $status = $_GET['status'] ?? null;
    
    $result = $chromeService->getChromeProfiles($userId, $status);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * POST /automation/profiles - Tạo Chrome profile mới
 */
function handleCreateProfile() {
    global $chromeService, $authService;
    
    // Kiểm tra quyền truy cập
    if (!$authService->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Chưa đăng nhập']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    // Lấy user_id từ session hiện tại
    $currentUser = $authService->getCurrentUser();
    $input['user_id'] = $currentUser['id'];
    
    $result = $chromeService->createChromeProfile($input);
    
    if ($result['success']) {
        http_response_code(201);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * PUT /automation/profiles - Cập nhật Chrome profile
 */
function handleUpdateProfile() {
    global $chromeService, $authService;
    
    // Kiểm tra quyền truy cập
    if (!$authService->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Chưa đăng nhập']);
        return;
    }
    
    $profileId = $_GET['id'] ?? null;
    
    if (!$profileId) {
        http_response_code(400);
        echo json_encode(['error' => 'ID profile là bắt buộc']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    $result = $chromeService->updateChromeProfile($profileId, $input);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * DELETE /automation/profiles - Xóa Chrome profile
 */
function handleDeleteProfile() {
    global $chromeService, $authService;
    
    // Kiểm tra quyền truy cập
    if (!$authService->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Chưa đăng nhập']);
        return;
    }
    
    $profileId = $_GET['id'] ?? null;
    
    if (!$profileId) {
        http_response_code(400);
        echo json_encode(['error' => 'ID profile là bắt buộc']);
        return;
    }
    
    $result = $chromeService->deleteChromeProfile($profileId);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * GET /automation/sessions - Lấy danh sách phiên đăng nhập
 */
function handleGetSessions() {
    global $chromeService, $authService;
    
    // Kiểm tra quyền truy cập
    if (!$authService->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Chưa đăng nhập']);
        return;
    }
    
    $filters = [
        'user_id' => $_GET['user_id'] ?? null,
        'status' => $_GET['status'] ?? null,
        'platform' => $_GET['platform'] ?? null,
        'chrome_profile_id' => $_GET['chrome_profile_id'] ?? null
    ];
    
    // Loại bỏ các filter null
    $filters = array_filter($filters, function($value) {
        return $value !== null;
    });
    
    $result = $chromeService->getLoginSessions($filters);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * POST /automation/sessions - Tạo phiên đăng nhập mới
 */
function handleCreateSession() {
    global $chromeService, $authService;
    
    // Kiểm tra quyền truy cập
    if (!$authService->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Chưa đăng nhập']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    // Lấy user_id từ session hiện tại
    $currentUser = $authService->getCurrentUser();
    $input['user_id'] = $currentUser['id'];
    
    $result = $chromeService->createLoginSession($input);
    
    if ($result['success']) {
        http_response_code(201);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * PUT /automation/sessions - Cập nhật phiên đăng nhập
 */
function handleUpdateSession() {
    global $chromeService, $authService;
    
    // Kiểm tra quyền truy cập
    if (!$authService->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Chưa đăng nhập']);
        return;
    }
    
    $sessionId = $_GET['id'] ?? null;
    
    if (!$sessionId) {
        http_response_code(400);
        echo json_encode(['error' => 'ID phiên là bắt buộc']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    $result = $chromeService->updateLoginSessionStatus($sessionId, $input['status'], $input);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * DELETE /automation/sessions - Xóa phiên đăng nhập
 */
function handleDeleteSession() {
    global $chromeService, $authService;
    
    // Kiểm tra quyền truy cập
    if (!$authService->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Chưa đăng nhập']);
        return;
    }
    
    $sessionId = $_GET['id'] ?? null;
    
    if (!$sessionId) {
        http_response_code(400);
        echo json_encode(['error' => 'ID phiên là bắt buộc']);
        return;
    }
    
    $result = $chromeService->deleteLoginSession($sessionId);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * POST /automation/start - Khởi chạy automation
 */
function handleStartAutomation() {
    global $chromeService, $authService;
    
    // Kiểm tra quyền truy cập
    if (!$authService->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Chưa đăng nhập']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['session_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID phiên là bắt buộc']);
        return;
    }
    
    $result = $chromeService->startAutomation($input['session_id']);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * POST /automation/stop - Dừng automation
 */
function handleStopAutomation() {
    global $chromeService, $authService;
    
    // Kiểm tra quyền truy cập
    if (!$authService->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Chưa đăng nhập']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['session_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID phiên là bắt buộc']);
        return;
    }
    
    $result = $chromeService->stopAutomation($input['session_id']);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * GET /automation/stats - Lấy thống kê automation
 */
function handleGetStats() {
    global $chromeService, $authService;
    
    // Kiểm tra quyền truy cập
    if (!$authService->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Chưa đăng nhập']);
        return;
    }
    
    $userId = $_GET['user_id'] ?? null;
    
    $result = $chromeService->getAutomationStats($userId);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * GET /automation/profile - Lấy thông tin profile cụ thể
 */
function handleGetProfile() {
    global $chromeService, $authService;
    
    // Kiểm tra quyền truy cập
    if (!$authService->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Chưa đăng nhập']);
        return;
    }
    
    $profileId = $_GET['id'] ?? null;
    
    if (!$profileId) {
        http_response_code(400);
        echo json_encode(['error' => 'ID profile là bắt buộc']);
        return;
    }
    
    $result = $chromeService->getChromeProfiles();
    
    if ($result['success']) {
        $profile = array_filter($result['profiles'], function($p) use ($profileId) {
            return $p['id'] == $profileId;
        });
        
        if (!empty($profile)) {
            echo json_encode([
                'success' => true,
                'profile' => array_values($profile)[0]
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Profile không tồn tại']);
        }
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * GET /automation/session - Lấy thông tin phiên cụ thể
 */
function handleGetSession() {
    global $chromeService, $authService;
    
    // Kiểm tra quyền truy cập
    if (!$authService->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Chưa đăng nhập']);
        return;
    }
    
    $sessionId = $_GET['id'] ?? null;
    
    if (!$sessionId) {
        http_response_code(400);
        echo json_encode(['error' => 'ID phiên là bắt buộc']);
        return;
    }
    
    $result = $chromeService->getLoginSessions();
    
    if ($result['success']) {
        $session = array_filter($result['sessions'], function($s) use ($sessionId) {
            return $s['id'] == $sessionId;
        });
        
        if (!empty($session)) {
            echo json_encode([
                'success' => true,
                'session' => array_values($session)[0]
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Phiên đăng nhập không tồn tại']);
        }
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}
