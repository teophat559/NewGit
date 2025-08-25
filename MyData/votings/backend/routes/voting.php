<?php
// voting.php - Xử lý API voting endpoints
require_once __DIR__ . '/../services/db.php';
require_once __DIR__ . '/../services/auth.php';
require_once __DIR__ . '/../services/voting.php';

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
                case 'rankings':
                    handleGetRankings();
                    break;

                case 'stats':
                    handleGetVoteStats();
                    break;

                case 'votes':
                    handleGetVotes();
                    break;

                case 'validate':
                    handleValidateVotingRules();
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint không tồn tại']);
                    break;
            }
            break;

        case 'POST':
            switch ($endpoint) {
                case 'vote':
                    handleCastVote();
                    break;

                case 'bulk-vote':
                    handleBulkVote();
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint không tồn tại']);
                    break;
            }
            break;

        case 'DELETE':
            switch ($endpoint) {
                case 'vote':
                    handleRemoveVote();
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
    error_log("Voting route error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi server: ' . $e->getMessage()]);
}

// ==================== VOTING HANDLERS ====================

function handleCastVote() {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['contest_id']) || !isset($input['contestant_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu thông tin bình chọn']);
        return;
    }

    // Lấy user ID từ token (nếu có)
    $userId = null;
    $token = getTokenFromRequest();

    if ($token) {
        $auth = auth();
        $user = $auth->validateToken($token);
        if ($user) {
            $userId = $user['user_id'];
        }
    }

    $votingService = voting();
    $result = $votingService->castVote(
        $input['contest_id'],
        $input['contestant_id'],
        $userId,
        $input['vote_data'] ?? []
    );

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

function handleBulkVote() {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['contest_id']) || !isset($input['votes'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu thông tin bình chọn hàng loạt']);
        return;
    }

    // Lấy user ID từ token
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Cần đăng nhập để bình chọn hàng loạt']);
        return;
    }

    $auth = auth();
    $user = $auth->validateToken($token);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Token không hợp lệ']);
        return;
    }

    $votingService = voting();
    $results = [];
    $successCount = 0;
    $errorCount = 0;

    foreach ($input['votes'] as $vote) {
        $result = $votingService->castVote(
            $input['contest_id'],
            $vote['contestant_id'],
            $user['user_id'],
            $vote['vote_data'] ?? []
        );

        $results[] = $result;

        if ($result['success']) {
            $successCount++;
        } else {
            $errorCount++;
        }
    }

    echo json_encode([
        'success' => $errorCount === 0,
        'total' => count($input['votes']),
        'success_count' => $successCount,
        'error_count' => $errorCount,
        'results' => $results
    ]);
}

function handleRemoveVote() {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['vote_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu ID vote']);
        return;
    }

    // Lấy user ID từ token
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Cần đăng nhập để hủy bình chọn']);
        return;
    }

    $auth = auth();
    $user = $auth->validateToken($token);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Token không hợp lệ']);
        return;
    }

    $votingService = voting();
    $result = $votingService->removeVote($input['vote_id'], $user['user_id']);

    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(400);
        echo json_encode($result);
    }
}

// ==================== RANKINGS & STATISTICS HANDLERS ====================

function handleGetRankings() {
    $contestId = $_GET['contest_id'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

    if (!$contestId) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu ID cuộc thi']);
        return;
    }

    $votingService = voting();
    $result = $votingService->getContestRankings($contestId, $limit);

    echo json_encode($result);
}

function handleGetVoteStats() {
    $type = $_GET['type'] ?? 'contest';
    $id = $_GET['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu ID để lấy thống kê']);
        return;
    }

    $votingService = voting();

    switch ($type) {
        case 'contest':
            $result = $votingService->getContestVoteStats($id);
            break;

        case 'contestant':
            $result = $votingService->getContestantVoteStats($id);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Loại thống kê không được hỗ trợ']);
            return;
    }

    echo json_encode($result);
}

function handleGetVotes() {
    $filters = [
        'contest_id' => $_GET['contest_id'] ?? null,
        'contestant_id' => $_GET['contestant_id'] ?? null,
        'user_id' => $_GET['user_id'] ?? null,
        'ip_address' => $_GET['ip_address'] ?? null
    ];

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

    $votingService = voting();
    $result = $votingService->getVotes($filters, $page, $limit);

    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
}

function handleValidateVotingRules() {
    $contestId = $_GET['contest_id'] ?? null;

    if (!$contestId) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu ID cuộc thi']);
        return;
    }

    // Lấy user ID từ token (nếu có)
    $userId = null;
    $token = getTokenFromRequest();

    if ($token) {
        $auth = auth();
        $user = $auth->validateToken($token);
        if ($user) {
            $userId = $user['user_id'];
        }
    }

    $votingService = voting();
    $result = $votingService->validateVotingRules($contestId, $userId);

    echo json_encode($result);
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
