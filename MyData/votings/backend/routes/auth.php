<?php
// auth.php - Xử lý authentication endpoints
require_once __DIR__ . '/../services/auth.php';
require_once __DIR__ . '/../services/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE');
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
        case 'POST':
            switch ($endpoint) {
                case 'login':
                    handleUserLogin();
                    break;

                case 'admin-login':
                    handleAdminLogin();
                    break;

                case 'register':
                    handleUserRegister();
                    break;

                case 'logout':
                    handleLogout();
                    break;

                case 'change-password':
                    handleChangePassword();
                    break;

                case 'reset-password':
                    handleResetPassword();
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint không tồn tại']);
                    break;
            }
            break;

        case 'GET':
            switch ($endpoint) {
                case 'profile':
                    handleGetProfile();
                    break;

                case 'validate':
                    handleValidateToken();
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
    error_log("Auth route error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi server: ' . $e->getMessage()]);
}

// ==================== HANDLER FUNCTIONS ====================

function handleUserLogin() {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['username']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu thông tin đăng nhập']);
        return;
    }

    $auth = auth();
    $result = $auth->loginUser($input['username'], $input['password']);

    if ($result['success']) {
        // Set cookie
        setcookie('auth_token', $result['token'], time() + 86400, '/', '', true, true);

        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'user' => $result['user'],
            'token' => $result['token']
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
}

function handleAdminLogin() {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['username']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu thông tin đăng nhập admin']);
        return;
    }

    $auth = auth();
    $result = $auth->loginAdmin($input['username'], $input['password']);

    if ($result['success']) {
        // Set cookie
        setcookie('auth_token', $result['token'], time() + 86400, '/', '', true, true);

        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'admin' => $result['admin'],
            'token' => $result['token']
        ]);
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
}

function handleUserRegister() {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['username']) || !isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu thông tin đăng ký']);
        return;
    }

    // Validation
    if (strlen($input['username']) < 3) {
        http_response_code(400);
        echo json_encode(['error' => 'Username phải có ít nhất 3 ký tự']);
        return;
    }

    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email không hợp lệ']);
        return;
    }

    if (strlen($input['password']) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Mật khẩu phải có ít nhất 6 ký tự']);
        return;
    }

    $auth = auth();
    $result = $auth->registerUser(
        $input['username'],
        $input['email'],
        $input['password'],
        $input['full_name'] ?? null
    );

    if ($result['success']) {
        // Set cookie
        setcookie('auth_token', $result['token'], time() + 86400, '/', '', true, true);

        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'user_id' => $result['user_id'],
            'token' => $result['token']
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
}

function handleLogout() {
    $token = getTokenFromRequest();

    if ($token) {
        $auth = auth();
        $auth->logout($token);

        // Remove cookie
        setcookie('auth_token', '', time() - 3600, '/');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Đăng xuất thành công'
    ]);
}

function handleChangePassword() {
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Không có quyền truy cập']);
        return;
    }

    $auth = auth();
    $user = $auth->validateToken($token);

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Token không hợp lệ']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['old_password']) || !isset($input['new_password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu thông tin đổi mật khẩu']);
        return;
    }

    if (strlen($input['new_password']) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Mật khẩu mới phải có ít nhất 6 ký tự']);
        return;
    }

    $result = $auth->changePassword($user['user_id'], $input['old_password'], $input['new_password']);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message']
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
}

function handleResetPassword() {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['email'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu email']);
        return;
    }

    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email không hợp lệ']);
        return;
    }

    $auth = auth();
    $result = $auth->resetPassword($input['email']);

    echo json_encode($result);
}

function handleGetProfile() {
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Không có quyền truy cập']);
        return;
    }

    $auth = auth();
    $user = $auth->validateToken($token);

    if (!$user) {
        // Thử validate admin token
        $admin = $auth->validateAdminToken($token);
        if ($admin) {
            echo json_encode([
                'success' => true,
                'user' => $admin,
                'type' => 'admin'
            ]);
            return;
        }

        http_response_code(401);
        echo json_encode(['error' => 'Token không hợp lệ']);
        return;
    }

    echo json_encode([
        'success' => true,
        'user' => $user,
        'type' => 'user'
    ]);
}

function handleValidateToken() {
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Không có token']);
        return;
    }

    $auth = auth();
    $user = $auth->validateToken($token);

    if ($user) {
        echo json_encode([
            'valid' => true,
            'user' => $user,
            'type' => 'user'
        ]);
        return;
    }

    $admin = $auth->validateAdminToken($token);
    if ($admin) {
        echo json_encode([
            'valid' => true,
            'user' => $admin,
            'type' => 'admin'
        ]);
        return;
    }

    echo json_encode([
        'valid' => false,
        'message' => 'Token không hợp lệ hoặc đã hết hạn'
    ]);
}

function getTokenFromRequest() {
    // Kiểm tra Authorization header
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $auth = $headers['Authorization'];
        if (strpos($auth, 'Bearer ') === 0) {
            return substr($auth, 7);
        }
    }

    // Kiểm tra cookie
    if (isset($_COOKIE['auth_token'])) {
        return $_COOKIE['auth_token'];
    }

    // Kiểm tra POST/GET parameter
    if (isset($_POST['token'])) {
        return $_POST['token'];
    }

    if (isset($_GET['token'])) {
        return $_GET['token'];
    }

    return null;
}
?>
