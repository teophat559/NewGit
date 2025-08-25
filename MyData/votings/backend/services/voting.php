<?php
// Voting service - Quản lý hệ thống bình chọn và xếp hạng
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

class VotingService {
    private $db;
    private $auth;

    public function __construct() {
        $this->db = db();
        $this->auth = auth();
    }

    // ==================== VOTE MANAGEMENT ====================

    public function castVote($contestId, $contestantId, $userId = null, $voteData = []) {
        try {
            // Kiểm tra contest có đang trong giai đoạn voting
            $contest = $this->db->fetchOne(
                "SELECT * FROM contests WHERE id = ? AND status = 'voting'",
                [$contestId]
            );

            if (!$contest) {
                return ['success' => false, 'message' => 'Cuộc thi không trong giai đoạn bình chọn'];
            }

            // Kiểm tra contestant có tồn tại và được phê duyệt
            $contestant = $this->db->fetchOne(
                "SELECT * FROM contestants WHERE id = ? AND contest_id = ? AND status = 'approved'",
                [$contestantId, $contestId]
            );

            if (!$contestant) {
                return ['success' => false, 'message' => 'Thí sinh không tồn tại hoặc chưa được phê duyệt'];
            }

            // Kiểm tra quy tắc bình chọn
            $votingRules = $this->parseVotingRules($contest['voting_rules']);

            // Kiểm tra giới hạn votes per user
            if ($userId && isset($votingRules['max_votes_per_user'])) {
                $userVoteCount = $this->getUserVoteCountInContest($userId, $contestId);
                if ($userVoteCount >= $votingRules['max_votes_per_user']) {
                    return ['success' => false, 'message' => 'Bạn đã đạt giới hạn số lượt bình chọn cho cuộc thi này'];
                }
            }

            // Kiểm tra IP address limit
            $clientIP = $this->getClientIP();
            if (isset($votingRules['max_votes_per_ip'])) {
                $ipVoteCount = $this->getIPVoteCountInContest($clientIP, $contestId);
                if ($ipVoteCount >= $votingRules['max_votes_per_ip']) {
                    return ['success' => false, 'message' => 'IP này đã đạt giới hạn số lượt bình chọn'];
                }
            }

            // Kiểm tra user đã vote cho contestant này chưa
            if ($userId) {
                $existingVote = $this->db->fetchOne(
                    "SELECT id FROM votes WHERE user_id = ? AND contestant_id = ? AND contest_id = ?",
                    [$userId, $contestantId, $contestId]
                );

                if ($existingVote) {
                    return ['success' => false, 'message' => 'Bạn đã bình chọn cho thí sinh này rồi'];
                }
            }

            // Kiểm tra IP đã vote cho contestant này chưa
            $existingIPVote = $this->db->fetchOne(
                "SELECT id FROM votes WHERE ip_address = ? AND contestant_id = ? AND contest_id = ?",
                [$clientIP, $contestantId, $contestId]
            );

            if ($existingIPVote) {
                return ['success' => false, 'message' => 'IP này đã bình chọn cho thí sinh này rồi'];
            }

            // Tạo vote
            $this->db->execute(
                "INSERT INTO votes (contest_id, contestant_id, user_id, ip_address, user_agent, vote_weight, metadata, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)",
                [
                    $contestId,
                    $contestantId,
                    $userId,
                    $clientIP,
                    $this->getUserAgent(),
                    $voteData['weight'] ?? 1,
                    json_encode($voteData['metadata'] ?? [])
                ]
            );

            $voteId = $this->db->lastInsertId();

            // Cập nhật tổng votes của contestant
            $this->updateContestantVoteCount($contestantId);

            // Log activity
            $this->logVotingActivity($voteId, 'vote_cast', [
                'contest_id' => $contestId,
                'contestant_id' => $contestantId,
                'user_id' => $userId,
                'ip_address' => $clientIP
            ]);

            return [
                'success' => true,
                'message' => 'Bình chọn thành công',
                'vote_id' => $voteId
            ];

        } catch (Exception $e) {
            error_log("Vote casting failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi bình chọn: ' . $e->getMessage()];
        }
    }

    public function removeVote($voteId, $userId = null) {
        try {
            // Lấy thông tin vote
            $vote = $this->db->fetchOne(
                "SELECT * FROM votes WHERE id = ?",
                [$voteId]
            );

            if (!$vote) {
                return ['success' => false, 'message' => 'Vote không tồn tại'];
            }

            // Kiểm tra quyền xóa vote
            if ($userId && $vote['user_id'] != $userId) {
                return ['success' => false, 'message' => 'Không có quyền xóa vote này'];
            }

            // Xóa vote
            $this->db->execute("DELETE FROM votes WHERE id = ?", [$voteId]);

            // Cập nhật tổng votes của contestant
            $this->updateContestantVoteCount($vote['contestant_id']);

            // Log activity
            $this->logVotingActivity($voteId, 'vote_removed', [
                'contest_id' => $vote['contest_id'],
                'contestant_id' => $vote['contestant_id'],
                'user_id' => $vote['user_id']
            ]);

            return [
                'success' => true,
                'message' => 'Đã hủy bình chọn'
            ];

        } catch (Exception $e) {
            error_log("Vote removal failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hủy bình chọn: ' . $e->getMessage()];
        }
    }

    public function getVotes($filters = [], $page = 1, $limit = 10) {
        try {
            $whereConditions = [];
            $params = [];

            // Apply filters
            if (isset($filters['contest_id']) && $filters['contest_id']) {
                $whereConditions[] = "v.contest_id = ?";
                $params[] = $filters['contest_id'];
            }

            if (isset($filters['contestant_id']) && $filters['contestant_id']) {
                $whereConditions[] = "v.contestant_id = ?";
                $params[] = $filters['contestant_id'];
            }

            if (isset($filters['user_id']) && $filters['user_id']) {
                $whereConditions[] = "v.user_id = ?";
                $params[] = $filters['user_id'];
            }

            if (isset($filters['ip_address']) && $filters['ip_address']) {
                $whereConditions[] = "v.ip_address = ?";
                $params[] = $filters['ip_address'];
            }

            $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM votes v $whereClause";
            $countResult = $this->db->fetchOne($countSql, $params);
            $total = $countResult['total'];

            // Get votes
            $offset = ($page - 1) * $limit;
            $params[] = $limit;
            $params[] = $offset;

            $votes = $this->db->fetchAll(
                "SELECT v.*, c.title as contest_title, ct.name as contestant_name, u.username as voter_username
                 FROM votes v
                 JOIN contests c ON v.contest_id = c.id
                 JOIN contestants ct ON v.contestant_id = ct.id
                 LEFT JOIN users u ON v.user_id = u.id
                 $whereClause
                 ORDER BY v.created_at DESC
                 LIMIT ? OFFSET ?",
                $params
            );

            return [
                'votes' => $votes,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ];

        } catch (Exception $e) {
            error_log("Get votes failed: " . $e->getMessage());
            return ['votes' => [], 'pagination' => ['page' => 1, 'limit' => 10, 'total' => 0, 'pages' => 0]];
        }
    }

    // ==================== RANKINGS & STATISTICS ====================

    public function getContestRankings($contestId, $limit = 10) {
        try {
            $rankings = $this->db->fetchAll(
                "SELECT ct.*, u.username, u.full_name, u.avatar_url,
                        COUNT(v.id) as total_votes,
                        SUM(v.vote_weight) as weighted_votes,
                        RANK() OVER (ORDER BY COUNT(v.id) DESC) as rank
                 FROM contestants ct
                 LEFT JOIN users u ON ct.user_id = u.id
                 LEFT JOIN votes v ON ct.id = v.contestant_id
                 WHERE ct.contest_id = ? AND ct.status = 'approved'
                 GROUP BY ct.id
                 ORDER BY total_votes DESC, weighted_votes DESC
                 LIMIT ?",
                [$contestId, $limit]
            );

            return [
                'success' => true,
                'data' => $rankings
            ];

        } catch (Exception $e) {
            error_log("Get contest rankings failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi lấy xếp hạng: ' . $e->getMessage()];
        }
    }

    public function getContestantVoteStats($contestantId) {
        try {
            $stats = [
                'total_votes' => 0,
                'unique_voters' => 0,
                'weighted_votes' => 0,
                'votes_by_date' => [],
                'top_voters' => [],
                'recent_votes' => []
            ];

            // Total votes
            $totalVotes = $this->db->fetchOne(
                "SELECT COUNT(*) as total, SUM(vote_weight) as weighted FROM votes WHERE contestant_id = ?",
                [$contestantId]
            );

            $stats['total_votes'] = $totalVotes['total'];
            $stats['weighted_votes'] = $totalVotes['weighted'] ?? 0;

            // Unique voters
            $uniqueVoters = $this->db->fetchOne(
                "SELECT COUNT(DISTINCT user_id) as total FROM votes WHERE contestant_id = ? AND user_id IS NOT NULL",
                [$contestantId]
            );

            $stats['unique_voters'] = $uniqueVoters['total'];

            // Votes by date (last 30 days)
            $votesByDate = $this->db->fetchAll(
                "SELECT DATE(created_at) as date, COUNT(*) as count
                 FROM votes
                 WHERE contestant_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY date",
                [$contestantId]
            );

            $stats['votes_by_date'] = $votesByDate;

            // Top voters
            $topVoters = $this->db->fetchAll(
                "SELECT u.username, u.full_name, COUNT(v.id) as vote_count
                 FROM votes v
                 JOIN users u ON v.user_id = u.id
                 WHERE v.contestant_id = ?
                 GROUP BY v.user_id
                 ORDER BY vote_count DESC
                 LIMIT 10",
                [$contestantId]
            );

            $stats['top_voters'] = $topVoters;

            // Recent votes
            $recentVotes = $this->db->fetchAll(
                "SELECT v.*, u.username, u.full_name
                 FROM votes v
                 LEFT JOIN users u ON v.user_id = u.id
                 WHERE v.contestant_id = ?
                 ORDER BY v.created_at DESC
                 LIMIT 10",
                [$contestantId]
            );

            $stats['recent_votes'] = $recentVotes;

            return [
                'success' => true,
                'data' => $stats
            ];

        } catch (Exception $e) {
            error_log("Get contestant vote stats failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi lấy thống kê votes: ' . $e->getMessage()];
        }
    }

    public function getContestVoteStats($contestId) {
        try {
            $stats = [
                'total_votes' => 0,
                'total_voters' => 0,
                'average_votes_per_contestant' => 0,
                'voting_trends' => [],
                'top_contestants' => [],
                'voting_activity' => []
            ];

            // Total votes
            $totalVotes = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM votes WHERE contest_id = ?",
                [$contestId]
            );

            $stats['total_votes'] = $totalVotes['total'];

            // Total unique voters
            $totalVoters = $this->db->fetchOne(
                "SELECT COUNT(DISTINCT user_id) as total FROM votes WHERE contest_id = ? AND user_id IS NOT NULL",
                [$contestId]
            );

            $stats['total_voters'] = $totalVoters['total'];

            // Average votes per contestant
            $contestantCount = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM contestants WHERE contest_id = ? AND status = 'approved'",
                [$contestId]
            );

            if ($contestantCount['total'] > 0) {
                $stats['average_votes_per_contestant'] = round($stats['total_votes'] / $contestantCount['total'], 2);
            }

            // Voting trends (last 30 days)
            $votingTrends = $this->db->fetchAll(
                "SELECT DATE(created_at) as date, COUNT(*) as count
                 FROM votes
                 WHERE contest_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY date",
                [$contestId]
            );

            $stats['voting_trends'] = $votingTrends;

            // Top contestants
            $topContestants = $this->db->fetchAll(
                "SELECT ct.*, u.username, u.full_name, COUNT(v.id) as vote_count
                 FROM contestants ct
                 LEFT JOIN users u ON ct.user_id = u.id
                 LEFT JOIN votes v ON ct.id = v.contestant_id
                 WHERE ct.contest_id = ? AND ct.status = 'approved'
                 GROUP BY ct.id
                 ORDER BY vote_count DESC
                 LIMIT 10",
                [$contestId]
            );

            $stats['top_contestants'] = $topContestants;

            // Voting activity by hour
            $votingActivity = $this->db->fetchAll(
                "SELECT HOUR(created_at) as hour, COUNT(*) as count
                 FROM votes
                 WHERE contest_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                 GROUP BY HOUR(created_at)
                 ORDER BY hour",
                [$contestId]
            );

            $stats['voting_activity'] = $votingActivity;

            return [
                'success' => true,
                'data' => $stats
            ];

        } catch (Exception $e) {
            error_log("Get contest vote stats failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi lấy thống kê cuộc thi: ' . $e->getMessage()];
        }
    }

    // ==================== VOTING RULES & VALIDATION ====================

    private function parseVotingRules($rulesText) {
        $rules = [
            'max_votes_per_user' => 1,
            'max_votes_per_ip' => 1,
            'allow_anonymous' => true,
            'require_verification' => false,
            'vote_weight_multiplier' => 1
        ];

        // Parse rules from text
        if (strpos($rulesText, 'mỗi IP chỉ được bình chọn 1 lần') !== false) {
            $rules['max_votes_per_ip'] = 1;
        }

        if (strpos($rulesText, 'mỗi người 3 phiếu') !== false) {
            $rules['max_votes_per_user'] = 3;
        }

        if (strpos($rulesText, 'bình chọn bởi ban giám khảo') !== false) {
            $rules['require_verification'] = true;
        }

        return $rules;
    }

    public function validateVotingRules($contestId, $userId = null) {
        try {
            $contest = $this->db->fetchOne(
                "SELECT * FROM contests WHERE id = ?",
                [$contestId]
            );

            if (!$contest) {
                return ['valid' => false, 'message' => 'Cuộc thi không tồn tại'];
            }

            if ($contest['status'] !== 'voting') {
                return ['valid' => false, 'message' => 'Cuộc thi không trong giai đoạn bình chọn'];
            }

            $rules = $this->parseVotingRules($contest['voting_rules']);
            $clientIP = $this->getClientIP();

            $validation = [
                'valid' => true,
                'rules' => $rules,
                'user_votes_remaining' => 0,
                'ip_votes_remaining' => 0
            ];

            // Check user votes remaining
            if ($userId) {
                $userVoteCount = $this->getUserVoteCountInContest($userId, $contestId);
                $validation['user_votes_remaining'] = max(0, $rules['max_votes_per_user'] - $userVoteCount);

                if ($validation['user_votes_remaining'] <= 0) {
                    $validation['valid'] = false;
                    $validation['message'] = 'Bạn đã hết lượt bình chọn cho cuộc thi này';
                }
            }

            // Check IP votes remaining
            $ipVoteCount = $this->getIPVoteCountInContest($clientIP, $contestId);
            $validation['ip_votes_remaining'] = max(0, $rules['max_votes_per_ip'] - $ipVoteCount);

            if ($validation['ip_votes_remaining'] <= 0) {
                $validation['valid'] = false;
                $validation['message'] = 'IP này đã hết lượt bình chọn cho cuộc thi này';
            }

            return $validation;

        } catch (Exception $e) {
            error_log("Voting rules validation failed: " . $e->getMessage());
            return ['valid' => false, 'message' => 'Lỗi kiểm tra quy tắc bình chọn'];
        }
    }

    // ==================== UTILITY FUNCTIONS ====================

    private function updateContestantVoteCount($contestantId) {
        try {
            $voteCount = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM votes WHERE contestant_id = ?",
                [$contestantId]
            );

            $this->db->execute(
                "UPDATE contestants SET total_votes = ? WHERE id = ?",
                [$voteCount['total'], $contestantId]
            );

            return true;

        } catch (Exception $e) {
            error_log("Update contestant vote count failed: " . $e->getMessage());
            return false;
        }
    }

    private function getUserVoteCountInContest($userId, $contestId) {
        try {
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM votes WHERE user_id = ? AND contest_id = ?",
                [$userId, $contestId]
            );

            return $result ? $result['total'] : 0;

        } catch (Exception $e) {
            error_log("Get user vote count failed: " . $e->getMessage());
            return 0;
        }
    }

    private function getIPVoteCountInContest($ipAddress, $contestId) {
        try {
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM votes WHERE ip_address = ? AND contest_id = ?",
                [$ipAddress, $contestId]
            );

            return $result ? $result['total'] : 0;

        } catch (Exception $e) {
            error_log("Get IP vote count failed: " . $e->getMessage());
            return 0;
        }
    }

    private function logVotingActivity($voteId, $action, $details = null) {
        try {
            $this->db->execute(
                "INSERT INTO audit_logs (action, table_name, record_id, new_values, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)",
                [
                    $action,
                    'votes',
                    $voteId,
                    json_encode($details),
                    $this->getClientIP(),
                    $this->getUserAgent()
                ]
            );

            return true;

        } catch (Exception $e) {
            error_log("Voting activity logging failed: " . $e->getMessage());
            return false;
        }
    }

    private function getClientIP() {
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

    private function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
}

// Helper function để sử dụng voting service
function voting() {
    return new VotingService();
}
?>
