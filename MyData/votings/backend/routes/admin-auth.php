<?php
/**
 * Admin Auth API Routes - BVOTE
 * Xử lý các API cho admin quản lý Auto Đăng Nhập
 */

require_once __DIR__ . '/../services/auto-login.php';
require_once __DIR__ . '/../../includes/auth_admin.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Kiểm tra quyền admin
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Không có quyền truy cập']);
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
        case 'GET':
            switch ($endpoint) {
                case 'requests':
                    handleGetRequests($autoLogin);
                    break;

                case 'stats':
                    handleGetStats($autoLogin);
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint không tồn tại']);
                    break;
            }
            break;

        case 'PATCH':
            // Xử lý các action - cần request_id từ path
            $requestId = $pathParts[count($pathParts) - 2] ?? null;
            if (!$requestId) {
                http_response_code(400);
                echo json_encode(['error' => 'Thiếu request_id']);
                break;
            }

            switch ($endpoint) {
                case 'approve':
                    handleApproveRequest($autoLogin, $requestId);
                    break;

                case 'reject':
                    handleRejectRequest($autoLogin, $requestId);
                    break;

                case 'require-otp':
                    handleRequireOTP($autoLogin, $requestId);
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
    error_log("Admin auth route error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi server: ' . $e->getMessage()]);
}

// ==================== HANDLER FUNCTIONS ====================

/**
 * Lấy danh sách yêu cầu
 */
function handleGetRequests($autoLogin) {
    $filters = [
        'platform' => $_GET['platform'] ?? '',
        'status' => $_GET['status'] ?? '',
        'limit' => (int)($_GET['limit'] ?? 100),
        'offset' => (int)($_GET['offset'] ?? 0)
    ];

    $result = $autoLogin->getPendingRequests($filters);

    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Lấy thống kê
 */
function handleGetStats($autoLogin) {
    try {
        // Lấy thống kê từ database
        $db = getConnection();

        $stats = [];

        // Đếm theo trạng thái
        $stmt = $db->prepare("
            SELECT status, COUNT(*) as count
            FROM login_requests
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY status
        ");
        $stmt->execute();

        while ($row = $stmt->fetch()) {
            switch ($row['status']) {
                case 'PENDING_REVIEW':
                    $stats['pending'] = (int)$row['count'];
                    break;
                case 'OTP_REQUIRED':
                    $stats['otp_required'] = (int)$row['count'];
                    break;
                case 'APPROVED':
                    $stats['approved'] = (int)$row['count'];
                    break;
                case 'REJECTED':
                    $stats['rejected'] = (int)$row['count'];
                    break;
                case 'EXPIRED':
                    $stats['expired'] = (int)$row['count'];
                    break;
            }
        }

        // Đếm theo platform
        $stmt = $db->prepare("
            SELECT platform, COUNT(*) as count
            FROM login_requests
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY platform
        ");
        $stmt->execute();

        $stats['by_platform'] = [];
        while ($row = $stmt->fetch()) {
            $stats['by_platform'][$row['platform']] = (int)$row['count'];
        }

        // Thống kê tổng quan
        $stmt = $db->prepare("
            SELECT
                COUNT(*) as total_requests,
                COUNT(CASE WHEN status = 'APPROVED' THEN 1 END) as total_approved,
                COUNT(CASE WHEN status = 'REJECTED' THEN 1 END) as total_rejected,
                AVG(CASE WHEN status IN ('APPROVED', 'REJECTED') THEN TIMESTAMPDIFF(SECOND, created_at, updated_at) END) as avg_response_time
            FROM login_requests
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute();
        $overview = $stmt->fetch();

        $stats['overview'] = [
            'total_requests' => (int)$overview['total_requests'],
            'total_approved' => (int)$overview['total_approved'],
            'total_rejected' => (int)$overview['total_rejected'],
            'approval_rate' => $overview['total_requests'] > 0 ? round(($overview['total_approved'] / $overview['total_requests']) * 100, 2) : 0,
            'avg_response_time' => round($overview['avg_response_time'] ?? 0, 2)
        ];

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);

    } catch (Exception $e) {
        error_log("Error getting stats: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Lỗi lấy thống kê'
        ]);
    }
}

/**
 * Phê duyệt yêu cầu
 */
function handleApproveRequest($autoLogin, $requestId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $adminId = $_SESSION['admin_id'] ?? 1;

    $result = $autoLogin->approveRequest($requestId, $adminId);

    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Từ chối yêu cầu
 */
function handleRejectRequest($autoLogin, $requestId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $adminId = $_SESSION['admin_id'] ?? 1;
    $reason = $input['reason'] ?? 'Admin rejected';

    $result = $autoLogin->rejectRequest($requestId, $adminId, $reason);

    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

/**
 * Yêu cầu OTP
 */
function handleRequireOTP($autoLogin, $requestId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $adminId = $_SESSION['admin_id'] ?? 1;
    $otpLength = $input['otp_length'] ?? 6;

    $result = $autoLogin->requireOTP($requestId, $adminId, $otpLength);

    if ($result['success']) {
        http_response_code(200);
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}
