<?php
// upload.php - Route xử lý upload file
require_once __DIR__ . '/../services/upload.php';
require_once __DIR__ . '/../services/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

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
                case 'upload':
                    handleFileUpload();
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint không tồn tại']);
                    break;
            }
            break;

        case 'GET':
            switch ($endpoint) {
                case 'list':
                    handleGetFileList();
                    break;

                case 'detail':
                    $fileId = $_GET['id'] ?? null;
                    if ($fileId) {
                        handleGetFileDetail($fileId);
                    } else {
                        http_response_code(400);
                        echo json_encode(['error' => 'Thiếu ID file']);
                    }
                    break;

                case 'stats':
                    handleGetUploadStats();
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint không tồn tại']);
                    break;
            }
            break;

        case 'PUT':
            if ($endpoint === 'update') {
                $fileId = $_GET['id'] ?? null;
                if ($fileId) {
                    handleUpdateFileInfo($fileId);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Thiếu ID file']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint không tồn tại']);
            }
            break;

        case 'DELETE':
            if ($endpoint === 'delete') {
                $fileId = $_GET['id'] ?? null;
                if ($fileId) {
                    handleDeleteFile($fileId);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Thiếu ID file']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint không tồn tại']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method không được hỗ trợ']);
            break;
    }

} catch (Exception $e) {
    error_log("Upload route error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi server: ' . $e->getMessage()]);
}

// ==================== HANDLER FUNCTIONS ====================

function handleFileUpload() {
    // Kiểm tra quyền upload
    $auth = auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Yêu cầu đăng nhập']);
        return;
    }

    // Kiểm tra file upload
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Không có file được upload hoặc có lỗi upload']);
        return;
    }

    $file = $_FILES['file'];
    $category = $_POST['category'] ?? 'general';
    $description = $_POST['description'] ?? '';
    $tags = $_POST['tags'] ?? '';

    // Upload file
    $uploadService = upload();
    $result = $uploadService->uploadFile($file, $category, $description, $tags);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

function handleGetFileList() {
    // Kiểm tra quyền xem danh sách file
    $auth = auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Yêu cầu đăng nhập']);
        return;
    }

    $category = $_GET['category'] ?? null;
    $search = $_GET['search'] ?? null;
    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 20);

    // Giới hạn limit
    if ($limit > 100) $limit = 100;
    if ($page < 1) $page = 1;

    $uploadService = upload();
    $result = $uploadService->getFileList($category, $search, $page, $limit);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

function handleGetFileDetail($fileId) {
    // Kiểm tra quyền xem chi tiết file
    $auth = auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Yêu cầu đăng nhập']);
        return;
    }

    $uploadService = upload();
    $result = $uploadService->getFileById($fileId);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(404);
        echo json_encode($result);
    }
}

function handleUpdateFileInfo($fileId) {
    // Kiểm tra quyền cập nhật file
    $auth = auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Yêu cầu đăng nhập']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Dữ liệu không hợp lệ']);
        return;
    }

    $uploadService = upload();
    $result = $uploadService->updateFileInfo($fileId, $input);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

function handleDeleteFile($fileId) {
    // Kiểm tra quyền xóa file
    $auth = auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Yêu cầu đăng nhập']);
        return;
    }

    $uploadService = upload();
    $result = $uploadService->deleteFile($fileId);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

function handleGetUploadStats() {
    // Kiểm tra quyền xem thống kê
    $auth = auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Yêu cầu đăng nhập']);
        return;
    }

    // Chỉ admin mới được xem thống kê
    if (!$auth->hasPermission('upload.stats')) {
        http_response_code(403);
        echo json_encode(['error' => 'Không có quyền xem thống kê upload']);
        return;
    }

    $uploadService = upload();
    $result = $uploadService->getUploadStats();

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}
?>
