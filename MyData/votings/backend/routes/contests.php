<?php
// contests.php - Xử lý API cuộc thi và thí sinh
require_once __DIR__ . '/../services/db.php';
require_once __DIR__ . '/../services/auth.php';

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
                case 'list':
                    handleGetContests();
                    break;

                case 'detail':
                    handleGetContestDetail();
                    break;

                case 'contestants':
                    handleGetContestants();
                    break;

                case 'contestant':
                    handleGetContestantDetail();
                    break;

                case 'rankings':
                    handleGetRankings();
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint không tồn tại']);
                    break;
            }
            break;

        case 'POST':
            switch ($endpoint) {
                case 'create':
                    handleCreateContest();
                    break;

                case 'register':
                    handleRegisterContestant();
                    break;

                case 'vote':
                    handleVote();
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint không tồn tại']);
                    break;
            }
            break;

        case 'PUT':
            switch ($endpoint) {
                case 'update':
                    handleUpdateContest();
                    break;

                case 'contestant-update':
                    handleUpdateContestant();
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Endpoint không tồn tại']);
                    break;
            }
            break;

        case 'DELETE':
            switch ($endpoint) {
                case 'delete':
                    handleDeleteContest();
                    break;

                case 'contestant-delete':
                    handleDeleteContestant();
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
    error_log("Contests route error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi server: ' . $e->getMessage()]);
}

// ==================== CONTEST HANDLERS ====================

function handleGetContests() {
    $db = db();

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $status = $_GET['status'] ?? null;
    $search = $_GET['search'] ?? null;

    $offset = ($page - 1) * $limit;

    // Build query
    $whereConditions = [];
    $params = [];

    if ($status) {
        $whereConditions[] = "c.status = ?";
        $params[] = $status;
    }

    if ($search) {
        $whereConditions[] = "(c.title LIKE ? OR c.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM contests c $whereClause";
    $countResult = $db->fetchOne($countSql, $params);
    $total = $countResult['total'];

    // Get contests
    $sql = "SELECT c.*, a.username as created_by_name,
            (SELECT COUNT(*) FROM contestants ct WHERE ct.contest_id = c.id AND ct.status = 'approved') as contestant_count
            FROM contests c
            LEFT JOIN admins a ON c.created_by = a.id
            $whereClause
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;

    $contests = $db->fetchAll($sql, $params);

    echo json_encode([
        'success' => true,
        'data' => [
            'contests' => $contests,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]
    ]);
}

function handleGetContestDetail() {
    $contestId = $_GET['id'] ?? null;

    if (!$contestId) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu ID cuộc thi']);
        return;
    }

    $db = db();

    $contest = $db->fetchOne(
        "SELECT c.*, a.username as created_by_name
         FROM contests c
         LEFT JOIN admins a ON c.created_by = a.id
         WHERE c.id = ?",
        [$contestId]
    );

    if (!$contest) {
        http_response_code(404);
        echo json_encode(['error' => 'Không tìm thấy cuộc thi']);
        return;
    }

    // Get contestants count
    $contestantCount = $db->fetchOne(
        "SELECT COUNT(*) as count FROM contestants WHERE contest_id = ? AND status = 'approved'",
        [$contestId]
    );

    $contest['contestant_count'] = $contestantCount['count'];

    echo json_encode([
        'success' => true,
        'data' => $contest
    ]);
}

function handleCreateContest() {
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
        echo json_encode(['error' => 'Không có quyền tạo cuộc thi']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['title']) || !isset($input['start_date']) || !isset($input['end_date'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu thông tin cuộc thi']);
        return;
    }

    // Validation
    if (strlen($input['title']) < 5) {
        http_response_code(400);
        echo json_encode(['error' => 'Tiêu đề cuộc thi phải có ít nhất 5 ký tự']);
        return;
    }

    if (strtotime($input['start_date']) >= strtotime($input['end_date'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Ngày kết thúc phải sau ngày bắt đầu']);
        return;
    }

    $db = db();

    try {
        $db->beginTransaction();

        $contestId = $db->execute(
            "INSERT INTO contests (title, description, banner_url, start_date, end_date, status, max_contestants, voting_rules, prizes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $input['title'],
                $input['description'] ?? '',
                $input['banner_url'] ?? '',
                $input['start_date'],
                $input['end_date'],
                $input['status'] ?? 'draft',
                $input['max_contestants'] ?? 100,
                $input['voting_rules'] ?? '',
                json_encode($input['prizes'] ?? []),
                $admin['user_id']
            ]
        );

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Tạo cuộc thi thành công',
            'contest_id' => $db->lastInsertId()
        ]);

    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

function handleUpdateContest() {
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
        echo json_encode(['error' => 'Không có quyền cập nhật cuộc thi']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu ID cuộc thi']);
        return;
    }

    $db = db();

    // Kiểm tra cuộc thi tồn tại
    $existingContest = $db->fetchOne("SELECT id FROM contests WHERE id = ?", [$input['id']]);
    if (!$existingContest) {
        http_response_code(404);
        echo json_encode(['error' => 'Không tìm thấy cuộc thi']);
        return;
    }

    // Build update fields
    $updateFields = [];
    $params = [];

    $fields = ['title', 'description', 'banner_url', 'start_date', 'end_date', 'status', 'max_contestants', 'voting_rules', 'prizes'];

    foreach ($fields as $field) {
        if (isset($input[$field])) {
            $updateFields[] = "$field = ?";
            $params[] = $field === 'prizes' ? json_encode($input[$field]) : $input[$field];
        }
    }

    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'Không có dữ liệu để cập nhật']);
        return;
    }

    $params[] = $input['id'];

    $db->execute(
        "UPDATE contests SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?",
        $params
    );

    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật cuộc thi thành công'
    ]);
}

function handleDeleteContest() {
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
        echo json_encode(['error' => 'Không có quyền xóa cuộc thi']);
        return;
    }

    $contestId = $_GET['id'] ?? null;

    if (!$contestId) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu ID cuộc thi']);
        return;
    }

    $db = db();

    // Kiểm tra cuộc thi tồn tại
    $existingContest = $db->fetchOne("SELECT id FROM contests WHERE id = ?", [$contestId]);
    if (!$existingContest) {
        http_response_code(404);
        echo json_encode(['error' => 'Không tìm thấy cuộc thi']);
        return;
    }

    try {
        $db->beginTransaction();

        // Xóa contestants (sẽ cascade từ foreign key)
        $db->execute("DELETE FROM contestants WHERE contest_id = ?", [$contestId]);

        // Xóa votes (sẽ cascade từ foreign key)
        $db->execute("DELETE FROM votes WHERE contest_id = ?", [$contestId]);

        // Xóa contest
        $db->execute("DELETE FROM contests WHERE id = ?", [$contestId]);

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Xóa cuộc thi thành công'
        ]);

    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

// ==================== CONTESTANT HANDLERS ====================

function handleGetContestants() {
    $contestId = $_GET['contest_id'] ?? null;

    if (!$contestId) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu ID cuộc thi']);
        return;
    }

    $db = db();

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $status = $_GET['status'] ?? null;

    $offset = ($page - 1) * $limit;

    // Build query
    $whereConditions = ["c.contest_id = ?"];
    $params = [$contestId];

    if ($status) {
        $whereConditions[] = "c.status = ?";
        $params[] = $status;
    }

    $whereClause = "WHERE " . implode(" AND ", $whereConditions);

    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM contestants c $whereClause";
    $countResult = $db->fetchOne($countSql, $params);
    $total = $countResult['total'];

    // Get contestants
    $sql = "SELECT c.*, u.username, u.full_name as user_full_name, u.avatar_url as user_avatar
            FROM contestants c
            LEFT JOIN users u ON c.user_id = u.id
            $whereClause
            ORDER BY c.total_votes DESC, c.created_at ASC
            LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;

    $contestants = $db->fetchAll($sql, $params);

    echo json_encode([
        'success' => true,
        'data' => [
            'contestants' => $contestants,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]
    ]);
}

function handleRegisterContestant() {
    // Kiểm tra quyền user
    $token = getTokenFromRequest();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Không có quyền truy cập']);
        return;
    }

    $auth = auth();
    $user = $auth->validateToken($token);

    if (!$user) {
        http_response_code(403);
        echo json_encode(['error' => 'Không có quyền đăng ký thí sinh']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['contest_id']) || !isset($input['name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu thông tin đăng ký']);
        return;
    }

    $db = db();

    // Kiểm tra cuộc thi tồn tại và đang mở đăng ký
    $contest = $db->fetchOne(
        "SELECT id, status, max_contestants FROM contests WHERE id = ?",
        [$input['contest_id']]
    );

    if (!$contest) {
        http_response_code(404);
        echo json_encode(['error' => 'Không tìm thấy cuộc thi']);
        return;
    }

    if ($contest['status'] !== 'active') {
        http_response_code(400);
        echo json_encode(['error' => 'Cuộc thi không mở đăng ký']);
        return;
    }

    // Kiểm tra đã đăng ký chưa
    $existingRegistration = $db->fetchOne(
        "SELECT id FROM contestants WHERE contest_id = ? AND user_id = ?",
        [$input['contest_id'], $user['user_id']]
    );

    if ($existingRegistration) {
        http_response_code(400);
        echo json_encode(['error' => 'Bạn đã đăng ký cuộc thi này rồi']);
        return;
    }

    // Kiểm tra số lượng thí sinh tối đa
    $contestantCount = $db->fetchOne(
        "SELECT COUNT(*) as count FROM contestants WHERE contest_id = ?",
        [$input['contest_id']]
    );

    if ($contestantCount['count'] >= $contest['max_contestants']) {
        http_response_code(400);
        echo json_encode(['error' => 'Cuộc thi đã đủ số lượng thí sinh']);
        return;
    }

    // Đăng ký thí sinh
    $contestantId = $db->execute(
        "INSERT INTO contestants (contest_id, user_id, name, description, image_url, video_url, social_links, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [
            $input['contest_id'],
            $user['user_id'],
            $input['name'],
            $input['description'] ?? '',
            $input['image_url'] ?? '',
            $input['video_url'] ?? '',
            json_encode($input['social_links'] ?? []),
            'pending'
        ]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Đăng ký thí sinh thành công, chờ phê duyệt',
        'contestant_id' => $db->lastInsertId()
    ]);
}

function handleVote() {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['contest_id']) || !isset($input['contestant_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu thông tin bình chọn']);
        return;
    }

    $db = db();

    // Kiểm tra cuộc thi đang trong giai đoạn voting
    $contest = $db->fetchOne(
        "SELECT id, status FROM contests WHERE id = ?",
        [$input['contest_id']]
    );

    if (!$contest) {
        http_response_code(404);
        echo json_encode(['error' => 'Không tìm thấy cuộc thi']);
        return;
    }

    if ($contest['status'] !== 'voting') {
        http_response_code(400);
        echo json_encode(['error' => 'Cuộc thi không trong giai đoạn bình chọn']);
        return;
    }

    // Kiểm tra thí sinh tồn tại và đã được phê duyệt
    $contestant = $db->fetchOne(
        "SELECT id, status FROM contestants WHERE id = ? AND contest_id = ?",
        [$input['contestant_id'], $input['contest_id']]
    );

    if (!$contestant) {
        http_response_code(404);
        echo json_encode(['error' => 'Không tìm thấy thí sinh']);
        return;
    }

    if ($contestant['status'] !== 'approved') {
        http_response_code(400);
        echo json_encode(['error' => 'Thí sinh chưa được phê duyệt']);
        return;
    }

    $voterIP = getClientIP();

    // Kiểm tra đã bình chọn chưa
    $existingVote = $db->fetchOne(
        "SELECT id FROM votes WHERE contest_id = ? AND contestant_id = ? AND voter_ip = ?",
        [$input['contest_id'], $input['contestant_id'], $voterIP]
    );

    if ($existingVote) {
        http_response_code(400);
        echo json_encode(['error' => 'Bạn đã bình chọn cho thí sinh này rồi']);
        return;
    }

    try {
        $db->beginTransaction();

        // Thêm vote
        $db->execute(
            "INSERT INTO votes (contest_id, contestant_id, voter_ip, voter_user_agent) VALUES (?, ?, ?, ?)",
            [$input['contest_id'], $input['contestant_id'], $voterIP, getUserAgent()]
        );

        // Cập nhật số vote của thí sinh
        $db->execute(
            "UPDATE contestants SET total_votes = total_votes + 1 WHERE id = ?",
            [$input['contestant_id']]
        );

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Bình chọn thành công'
        ]);

    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

function handleGetRankings() {
    $contestId = $_GET['contest_id'] ?? null;

    if (!$contestId) {
        http_response_code(400);
        echo json_encode(['error' => 'Thiếu ID cuộc thi']);
        return;
    }

    $db = db();

    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

    $rankings = $db->fetchAll(
        "SELECT c.*, u.username, u.full_name as user_full_name, u.avatar_url as user_avatar,
                ROW_NUMBER() OVER (ORDER BY c.total_votes DESC) as rank
         FROM contestants c
         LEFT JOIN users u ON c.user_id = u.id
         WHERE c.contest_id = ? AND c.status = 'approved'
         ORDER BY c.total_votes DESC, c.created_at ASC
         LIMIT ?",
        [$contestId, $limit]
    );

    echo json_encode([
        'success' => true,
        'data' => $rankings
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

function getClientIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}
?>
