<?php
/**
 * Social Login Routes - BVOTE
 * Xử lý đăng nhập qua các nền tảng mạng xã hội
 */

require_once __DIR__ . '/../services/auto-login.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PATCH');
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
    $autoLogin = new AutoLoginService();

    switch ($method) {
        case 'POST':
            switch ($endpoint) {
                case 'social-login':
                    handleSocialLogin($autoLogin);
                    break;

                case 'otp':
                    // Xử lý OTP - cần request_id từ path
                    $requestId = $pathParts[count($pathParts) - 2] ?? null;
                    if ($requestId) {
                        handleOTPVerification($autoLogin, $requestId);
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => 'Thiếu request_id']);
                    }
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint không tồn tại']);
                    break;
            }
            break;

        case 'GET':
            switch ($endpoint) {
                case 'status':
                    // Xử lý kiểm tra trạng thái - cần request_id từ path
                    $requestId = $pathParts[count($pathParts) - 2] ?? null;
                    if ($requestId) {
                        handleGetStatus($autoLogin, $requestId);
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => 'Thiếu request_id']);
                    }
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
    error_log("Social route error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi server: ' . $e->getMessage()]);
}

// ==================== HANDLER FUNCTIONS ====================

/**
 * Xử lý đăng nhập qua mạng xã hội
 */
function handleSocialLogin($autoLogin) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['platform']) || !isset($input['user_hint'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu thông tin đăng nhập']);
        return;
    }

    $platform = $input['platform'];
    $userHint = $input['user_hint'];

    // Kiểm tra platform hợp lệ
    $validPlatforms = ['facebook', 'google', 'instagram', 'zalo', 'yahoo', 'microsoft', 'email'];
    if (!in_array($platform, $validPlatforms)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nền tảng không được hỗ trợ']);
        return;
    }

    // Lấy thông tin client
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    // Tạo yêu cầu đăng nhập
    $result = $autoLogin->createLoginRequest($userHint, $platform, $ipAddress, $userAgent);

    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Xử lý xác thực OTP
 */
function handleOTPVerification($autoLogin, $requestId) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['otp'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu mã OTP']);
        return;
    }

    $otp = $input['otp'];

    // Xác thực OTP
    $result = $autoLogin->verifyOTP($requestId, $otp);

    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Kiểm tra trạng thái yêu cầu
 */
function handleGetStatus($autoLogin, $requestId) {
    $result = $autoLogin->getRequestStatus($requestId);

    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(404);
        echo json_encode($result);
    }
}
