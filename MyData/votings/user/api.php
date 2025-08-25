<?php
session_start();
header('Content-Type: application/json');

require_once 'database.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Actions that don't require authentication
$public_actions = ['get_contests', 'get_contestants', 'get_rankings', 'get_recent_activities', 'search', 'get_contest_details'];

// Check authentication for protected actions
if (!in_array($action, $public_actions) && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Vui lòng đăng nhập để thực hiện hành động này']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;

try {
    switch ($action) {
        case 'vote':
            $contestant_id = $_POST['contestant_id'] ?? 0;
            $campaign_id = $_POST['campaign_id'] ?? 0;

            if (!$contestant_id || !$campaign_id) {
                throw new Exception('Thông tin không hợp lệ');
            }

            // Kiểm tra campaign còn active không
            $stmt = $pdo->prepare("SELECT * FROM campaigns WHERE id = ? AND status = 'active' AND end_date > NOW()");
            $stmt->execute([$campaign_id]);
            $campaign = $stmt->fetch();

            if (!$campaign) {
                throw new Exception('Cuộc thi đã kết thúc hoặc không tồn tại');
            }

            // Kiểm tra user đã vote trong campaign này chưa
            $stmt = $pdo->prepare("SELECT id FROM votes WHERE user_id = ? AND campaign_id = ?");
            $stmt->execute([$user_id, $campaign_id]);

            if ($stmt->fetch()) {
                throw new Exception('Bạn đã bình chọn trong cuộc thi này rồi');
            }

            // Kiểm tra contestant có thuộc campaign không
            $stmt = $pdo->prepare("SELECT * FROM contestants WHERE id = ? AND campaign_id = ?");
            $stmt->execute([$contestant_id, $campaign_id]);
            $contestant = $stmt->fetch();

            if (!$contestant) {
                throw new Exception('Thí sinh không tồn tại trong cuộc thi này');
            }

            // Thực hiện vote
            $stmt = $pdo->prepare("INSERT INTO votes (user_id, campaign_id, contestant_id, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, $campaign_id, $contestant_id]);

            // Cập nhật vote count cho contestant
            $stmt = $pdo->prepare("UPDATE contestants SET vote_count = vote_count + 1 WHERE id = ?");
            $stmt->execute([$contestant_id]);

            // Lấy vote count mới
            $stmt = $pdo->prepare("SELECT vote_count FROM contestants WHERE id = ?");
            $stmt->execute([$contestant_id]);
            $new_count = $stmt->fetchColumn();

            echo json_encode([
                'success' => true,
                'message' => 'Bình chọn thành công!',
                'new_vote_count' => $new_count
            ]);
            break;

        case 'get_contests':
            $stmt = $pdo->prepare("
                SELECT c.*,
                       COUNT(DISTINCT v.id) as total_votes,
                       COUNT(DISTINCT co.id) as total_contestants
                FROM campaigns c
                LEFT JOIN votes v ON c.id = v.campaign_id
                LEFT JOIN contestants co ON c.id = co.campaign_id
                WHERE c.status = 'active'
                GROUP BY c.id
                ORDER BY c.created_at DESC
            ");
            $stmt->execute();
            $contests = $stmt->fetchAll();

            echo json_encode(['success' => true, 'contests' => $contests]);
            break;

        case 'get_contestants':
            $campaign_id = $_GET['campaign_id'] ?? 0;

            if (!$campaign_id) {
                throw new Exception('Campaign ID không hợp lệ');
            }

            $stmt = $pdo->prepare("
                SELECT c.*,
                       CASE WHEN v.id IS NOT NULL THEN 1 ELSE 0 END as user_voted
                FROM contestants c
                LEFT JOIN votes v ON c.id = v.contestant_id AND v.user_id = ?
                WHERE c.campaign_id = ?
                ORDER BY c.vote_count DESC, c.name ASC
            ");
            $stmt->execute([$user_id, $campaign_id]);
            $contestants = $stmt->fetchAll();

            echo json_encode(['success' => true, 'contestants' => $contestants]);
            break;

        case 'get_rankings':
            $limit = $_GET['limit'] ?? 10;
            $campaign_id = $_GET['campaign_id'] ?? null;

            $sql = "
                SELECT c.*, ca.title as campaign_title
                FROM contestants c
                JOIN campaigns ca ON c.campaign_id = ca.id
                WHERE ca.status = 'active'
            ";

            $params = [];
            if ($campaign_id) {
                $sql .= " AND c.campaign_id = ?";
                $params[] = $campaign_id;
            }

            $sql .= " ORDER BY c.vote_count DESC LIMIT ?";
            $params[] = (int)$limit;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rankings = $stmt->fetchAll();

            echo json_encode(['success' => true, 'rankings' => $rankings]);
            break;

        case 'get_recent_activities':
            $limit = $_GET['limit'] ?? 20;

            // Check if votes table exists and has data
            try {
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM votes");
                $check_stmt->execute();
                $vote_count = $check_stmt->fetchColumn();

                if ($vote_count == 0) {
                    // No votes yet, return sample activities
                    $activities = [
                        [
                            'created_at' => date('Y-m-d H:i:s'),
                            'user_name' => 'Anonymous User',
                            'contestant_name' => 'Lê Thị Cẩm',
                            'campaign_title' => 'Cuộc thi Hoa khôi Đại học 2025'
                        ],
                        [
                            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                            'user_name' => 'Anonymous User',
                            'contestant_name' => 'Nguyễn Thị Anh',
                            'campaign_title' => 'Cuộc thi Hoa khôi Đại học 2025'
                        ],
                        [
                            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                            'user_name' => 'Anonymous User',
                            'contestant_name' => 'Trần Văn Bình',
                            'campaign_title' => 'Cuộc thi Tài năng Âm nhạc 2025'
                        ]
                    ];
                } else {
                    // Try with users table first
                    try {
                        $stmt = $pdo->prepare("
                            SELECT v.created_at, u.name as user_name, c.name as contestant_name, ca.title as campaign_title
                            FROM votes v
                            JOIN users u ON v.user_id = u.id
                            JOIN contestants c ON v.contestant_id = c.id
                            JOIN campaigns ca ON v.campaign_id = ca.id
                            WHERE ca.status = 'active'
                            ORDER BY v.created_at DESC
                            LIMIT ?
                        ");
                        $stmt->execute([(int)$limit]);
                        $activities = $stmt->fetchAll();
                    } catch (Exception $e) {
                        // Fallback without users table
                        $stmt = $pdo->prepare("
                            SELECT v.created_at, 'Anonymous User' as user_name, c.name as contestant_name, ca.title as campaign_title
                            FROM votes v
                            JOIN contestants c ON v.contestant_id = c.id
                            JOIN campaigns ca ON v.campaign_id = ca.id
                            WHERE ca.status = 'active'
                            ORDER BY v.created_at DESC
                            LIMIT ?
                        ");
                        $stmt->execute([(int)$limit]);
                        $activities = $stmt->fetchAll();
                    }
                }
            } catch (Exception $e) {
                // Return empty activities if everything fails
                $activities = [];
            }

            echo json_encode(['success' => true, 'activities' => $activities]);
            break;

        case 'search':
            $query = $_GET['q'] ?? '';
            $type = $_GET['type'] ?? 'all'; // all, contests, contestants
            $limit = $_GET['limit'] ?? 20;

            if (strlen($query) < 2) {
                echo json_encode(['success' => true, 'results' => []]);
                break;
            }

            $results = [];
            $search_term = '%' . $query . '%';

            if ($type === 'all' || $type === 'contests') {
                $stmt = $pdo->prepare("
                    SELECT 'contest' as type, c.id, c.title as name, c.description, c.image,
                           '' as campaign_title, c.status, c.end_date,
                           COUNT(DISTINCT v.id) as total_votes,
                           COUNT(DISTINCT co.id) as total_contestants
                    FROM campaigns c
                    LEFT JOIN votes v ON c.id = v.campaign_id
                    LEFT JOIN contestants co ON c.id = co.campaign_id
                    WHERE c.status = 'active' AND (c.title LIKE ? OR c.description LIKE ?)
                    GROUP BY c.id
                    ORDER BY total_votes DESC
                    LIMIT ?
                ");
                $stmt->execute([$search_term, $search_term, (int)($limit/2)]);
                $results = array_merge($results, $stmt->fetchAll());
            }

            if ($type === 'all' || $type === 'contestants') {
                $stmt = $pdo->prepare("
                    SELECT 'contestant' as type, c.id, c.name, c.description, c.image,
                           ca.title as campaign_title, c.vote_count, ca.id as campaign_id
                    FROM contestants c
                    JOIN campaigns ca ON c.campaign_id = ca.id
                    WHERE ca.status = 'active' AND (c.name LIKE ? OR c.description LIKE ?)
                    ORDER BY c.vote_count DESC
                    LIMIT ?
                ");
                $stmt->execute([$search_term, $search_term, (int)($limit/2)]);
                $results = array_merge($results, $stmt->fetchAll());
            }

            // Add search suggestions if no results found
            if (empty($results)) {
                $stmt = $pdo->prepare("
                    SELECT DISTINCT 'suggestion' as type, 'Có thể bạn muốn tìm' as name,
                           GROUP_CONCAT(DISTINCT ca.title SEPARATOR ', ') as description
                    FROM campaigns ca
                    WHERE ca.status = 'active'
                    LIMIT 3
                ");
                $stmt->execute();
                $suggestions = $stmt->fetchAll();
                $results = $suggestions;
            }

            echo json_encode(['success' => true, 'results' => $results, 'query' => $query]);
            break;        case 'get_user_stats':
            // Thống kê của user hiện tại
            $stmt = $pdo->prepare("
                SELECT
                    COUNT(DISTINCT v.campaign_id) as contests_voted,
                    COUNT(v.id) as total_votes,
                    u.created_at as member_since
                FROM users u
                LEFT JOIN votes v ON u.id = v.user_id
                WHERE u.id = ?
                GROUP BY u.id
            ");
            $stmt->execute([$user_id]);
            $stats = $stmt->fetch();

            echo json_encode(['success' => true, 'stats' => $stats]);
            break;

        case 'get_user_votes':
            // Lấy lịch sử vote của user
            if (!$user_id) {
                throw new Exception('Vui lòng đăng nhập');
            }

            $stmt = $pdo->prepare("
                SELECT v.created_at, c.name as contestant_name, ca.title as campaign_title,
                       co.vote_count, co.image as contestant_image
                FROM votes v
                JOIN contestants c ON v.contestant_id = c.id
                JOIN campaigns ca ON v.campaign_id = ca.id
                LEFT JOIN contestants co ON v.contestant_id = co.id
                WHERE v.user_id = ?
                ORDER BY v.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $votes = $stmt->fetchAll();

            echo json_encode(['success' => true, 'votes' => $votes]);
            break;

        case 'check_vote_status':
            // Kiểm tra user đã vote cho campaign nào chưa
            $campaign_id = $_GET['campaign_id'] ?? 0;

            if (!$campaign_id) {
                throw new Exception('Campaign ID không hợp lệ');
            }

            $voted = false;
            $voted_contestant = null;

            if ($user_id) {
                $stmt = $pdo->prepare("
                    SELECT v.*, c.name as contestant_name
                    FROM votes v
                    JOIN contestants c ON v.contestant_id = c.id
                    WHERE v.user_id = ? AND v.campaign_id = ?
                ");
                $stmt->execute([$user_id, $campaign_id]);
                $vote_info = $stmt->fetch();

                if ($vote_info) {
                    $voted = true;
                    $voted_contestant = $vote_info;
                }
            }

            echo json_encode([
                'success' => true,
                'voted' => $voted,
                'voted_contestant' => $voted_contestant
            ]);
            break;

        case 'get_campaign_stats':
            // Thống kê chi tiết cho campaign
            $campaign_id = $_GET['campaign_id'] ?? 0;

            if (!$campaign_id) {
                throw new Exception('Campaign ID không hợp lệ');
            }

            // Tổng số vote
            $stmt = $pdo->prepare("SELECT COUNT(*) as total_votes FROM votes WHERE campaign_id = ?");
            $stmt->execute([$campaign_id]);
            $total_votes = $stmt->fetchColumn();

            // Số contestant
            $stmt = $pdo->prepare("SELECT COUNT(*) as total_contestants FROM contestants WHERE campaign_id = ?");
            $stmt->execute([$campaign_id]);
            $total_contestants = $stmt->fetchColumn();

            // Top 5 contestants
            $stmt = $pdo->prepare("
                SELECT name, vote_count, image
                FROM contestants
                WHERE campaign_id = ?
                ORDER BY vote_count DESC
                LIMIT 5
            ");
            $stmt->execute([$campaign_id]);
            $top_contestants = $stmt->fetchAll();

            // Vote trend (last 7 days)
            $stmt = $pdo->prepare("
                SELECT DATE(created_at) as vote_date, COUNT(*) as daily_votes
                FROM votes
                WHERE campaign_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY vote_date ASC
            ");
            $stmt->execute([$campaign_id]);
            $vote_trend = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_votes' => $total_votes,
                    'total_contestants' => $total_contestants,
                    'top_contestants' => $top_contestants,
                    'vote_trend' => $vote_trend
                ]
            ]);
            break;

        case 'get_contest_details':
            $contest_id = $_GET['contest_id'] ?? 0;

            if (!$contest_id) {
                throw new Exception('Contest ID không hợp lệ');
            }

            // Lấy thông tin contest
            $stmt = $pdo->prepare("
                SELECT c.*,
                       COUNT(DISTINCT v.id) as total_votes,
                       COUNT(DISTINCT co.id) as total_contestants,
                       CASE WHEN uv.id IS NOT NULL THEN 1 ELSE 0 END as user_participated
                FROM campaigns c
                LEFT JOIN votes v ON c.id = v.campaign_id
                LEFT JOIN contestants co ON c.id = co.campaign_id
                LEFT JOIN votes uv ON c.id = uv.campaign_id AND uv.user_id = ?
                WHERE c.id = ?
                GROUP BY c.id
            ");
            $stmt->execute([$user_id, $contest_id]);
            $contest = $stmt->fetch();

            if (!$contest) {
                throw new Exception('Cuộc thi không tồn tại');
            }

            // Lấy top 3 contestants
            $stmt = $pdo->prepare("
                SELECT * FROM contestants
                WHERE campaign_id = ?
                ORDER BY vote_count DESC
                LIMIT 3
            ");
            $stmt->execute([$contest_id]);
            $top_contestants = $stmt->fetchAll();

            $contest['top_contestants'] = $top_contestants;

            echo json_encode(['success' => true, 'contest' => $contest]);
            break;

        default:
            throw new Exception('Hành động không hợp lệ');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Có lỗi xảy ra với cơ sở dữ liệu'
    ]);
    error_log('Database error: ' . $e->getMessage());
}
?>
