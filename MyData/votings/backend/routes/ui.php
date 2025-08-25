<?php
// ui.php - Xử lý API UI components endpoints
require_once __DIR__ . '/../services/db.php';
require_once __DIR__ . '/../services/auth.php';
require_once __DIR__ . '/../services/ui_components.php';

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
                case 'form':
                    handleGetForm();
                    break;

                case 'table':
                    handleGetTable();
                    break;

                case 'modal':
                    handleGetModal();
                    break;

                case 'pagination':
                    handleGetPagination();
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint không tồn tại']);
                    break;
            }
            break;

        case 'POST':
            switch ($endpoint) {
                case 'validate':
                    handleValidateForm();
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
    error_log("UI route error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi server: ' . $e->getMessage()]);
}

// ==================== FORM HANDLERS ====================

function handleGetForm() {
    $formType = $_GET['type'] ?? null;

    if (!$formType) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu loại form']);
        return;
    }

    // Lấy dữ liệu từ query parameters
    $data = [];
    $options = [];

    // Parse data từ query string
    if (isset($_GET['data'])) {
        $data = json_decode($_GET['data'], true) ?? [];
    }

    // Parse options từ query string
    if (isset($_GET['options'])) {
        $options = json_decode($_GET['options'], true) ?? [];
    }

    $uiService = ui();
    $result = $uiService->generateForm($formType, $data, $options);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

// ==================== TABLE HANDLERS ====================

function handleGetTable() {
    $tableType = $_GET['type'] ?? null;

    if (!$tableType) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu loại bảng']);
        return;
    }

    // Lấy dữ liệu từ query parameters
    $data = [];
    $options = [];

    // Parse data từ query string
    if (isset($_GET['data'])) {
        $data = json_decode($_GET['data'], true) ?? [];
    }

    // Parse options từ query string
    if (isset($_GET['options'])) {
        $options = json_decode($_GET['options'], true) ?? [];
    }

    $uiService = ui();
    $result = $uiService->generateTable($tableType, $data, $options);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

// ==================== MODAL HANDLERS ====================

function handleGetModal() {
    $modalType = $_GET['type'] ?? null;

    if (!$modalType) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu loại modal']);
        return;
    }

    // Lấy dữ liệu từ query parameters
    $data = [];
    $options = [];

    // Parse data từ query string
    if (isset($_GET['data'])) {
        $data = json_decode($_GET['data'], true) ?? [];
    }

    // Parse options từ query string
    if (isset($_GET['options'])) {
        $options = json_decode($_GET['options'], true) ?? [];
    }

    $uiService = ui();
    $result = $uiService->generateModal($modalType, $data, $options);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

// ==================== PAGINATION HANDLERS ====================

function handleGetPagination() {
    $total = isset($_GET['total']) ? (int)$_GET['total'] : 0;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
    $urlPattern = $_GET['url_pattern'] ?? '?page={page}';

    if ($total <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Tổng số items phải lớn hơn 0']);
        return;
    }

    $uiService = ui();
    $result = $uiService->generatePagination($total, $currentPage, $perPage, $urlPattern);

    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
}

// ==================== VALIDATION HANDLERS ====================

function handleValidateForm() {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['form_type']) || !isset($input['data'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu thông tin validation']);
        return;
    }

    $uiService = ui();
    $result = $uiService->validateFormData($input['form_type'], $input['data']);

    if ($result['valid']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}
?>
