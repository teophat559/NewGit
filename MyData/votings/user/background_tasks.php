<?php
/**
 * BVOTE 2025 - Background Tasks & Maintenance
 * Chức năng: Xử lý các tác vụ nền và bảo trì hệ thống
 *
 * 🔒 INTERFACE LOCKED - Chỉ phát triển backend
 */

require_once 'database.php';

class BVoteBackgroundTasks {
    private $pdo;
    private $log_file;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->log_file = 'logs/background_tasks.log';

        // Tạo thư mục logs nếu chưa có
        if (!is_dir('logs')) {
            mkdir('logs', 0755, true);
        }
    }

    /**
     * Log hoạt động của background tasks
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Cập nhật thống kê vote count cho tất cả contestants
     */
    public function updateVoteCounts() {
        try {
            $this->log("Starting vote count update...");

            $stmt = $this->pdo->prepare("
                UPDATE contestants c
                SET vote_count = (
                    SELECT COUNT(*)
                    FROM votes v
                    WHERE v.contestant_id = c.id
                )
            ");
            $stmt->execute();

            $affected = $stmt->rowCount();
            $this->log("Updated vote counts for $affected contestants");

            return true;
        } catch (Exception $e) {
            $this->log("Error updating vote counts: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cleanup các session cũ
     */
    public function cleanupOldSessions() {
        try {
            $this->log("Starting session cleanup...");

            // Xóa các vote duplicate (nếu có)
            $stmt = $this->pdo->prepare("
                DELETE v1 FROM votes v1
                INNER JOIN votes v2
                WHERE v1.id > v2.id
                AND v1.user_id = v2.user_id
                AND v1.campaign_id = v2.campaign_id
            ");
            $stmt->execute();

            $deleted = $stmt->rowCount();
            if ($deleted > 0) {
                $this->log("Removed $deleted duplicate votes");
            }

            return true;
        } catch (Exception $e) {
            $this->log("Error in session cleanup: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Tính toán và cache rankings
     */
    public function updateRankingsCache() {
        try {
            $this->log("Starting rankings cache update...");

            // Lấy top rankings cho mỗi campaign
            $stmt = $this->pdo->prepare("
                SELECT c.campaign_id, c.id, c.name, c.vote_count, ca.title as campaign_title,
                       ROW_NUMBER() OVER (PARTITION BY c.campaign_id ORDER BY c.vote_count DESC) as rank_position
                FROM contestants c
                JOIN campaigns ca ON c.campaign_id = ca.id
                WHERE ca.status = 'active'
                ORDER BY c.campaign_id, c.vote_count DESC
            ");
            $stmt->execute();
            $rankings = $stmt->fetchAll();

            // Cache vào file JSON
            $cache_data = [
                'updated_at' => date('Y-m-d H:i:s'),
                'rankings' => $rankings,
                'total_contestants' => count($rankings)
            ];

            file_put_contents('cache/rankings.json', json_encode($cache_data, JSON_PRETTY_PRINT));
            $this->log("Updated rankings cache with " . count($rankings) . " contestants");

            return true;
        } catch (Exception $e) {
            $this->log("Error updating rankings cache: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Tạo báo cáo thống kê hàng ngày
     */
    public function generateDailyStats() {
        try {
            $this->log("Generating daily statistics...");

            $today = date('Y-m-d');

            // Thống kê votes hôm nay
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM votes WHERE DATE(created_at) = ?");
            $stmt->execute([$today]);
            $today_votes = $stmt->fetchColumn();

            // Thống kê users mới
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?");
            $stmt->execute([$today]);
            $new_users = $stmt->fetchColumn();

            // Top campaign hôm nay
            $stmt = $this->pdo->prepare("
                SELECT ca.title, COUNT(v.id) as votes_today
                FROM votes v
                JOIN campaigns ca ON v.campaign_id = ca.id
                WHERE DATE(v.created_at) = ?
                GROUP BY ca.id
                ORDER BY votes_today DESC
                LIMIT 1
            ");
            $stmt->execute([$today]);
            $top_campaign = $stmt->fetch();

            $stats = [
                'date' => $today,
                'votes_today' => $today_votes,
                'new_users' => $new_users,
                'top_campaign' => $top_campaign,
                'generated_at' => date('Y-m-d H:i:s')
            ];

            // Lưu vào file
            if (!is_dir('reports')) {
                mkdir('reports', 0755, true);
            }

            file_put_contents("reports/daily_stats_$today.json", json_encode($stats, JSON_PRETTY_PRINT));
            $this->log("Generated daily stats: $today_votes votes, $new_users new users");

            return $stats;
        } catch (Exception $e) {
            $this->log("Error generating daily stats: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kiểm tra tình trạng database
     */
    public function healthCheck() {
        try {
            $this->log("Starting health check...");

            $health = [
                'timestamp' => date('Y-m-d H:i:s'),
                'database' => 'OK',
                'tables' => [],
                'issues' => []
            ];

            // Kiểm tra từng bảng
            $tables = ['users', 'campaigns', 'contestants', 'votes'];
            foreach ($tables as $table) {
                try {
                    $stmt = $this->pdo->query("SELECT COUNT(*) FROM $table");
                    $count = $stmt->fetchColumn();
                    $health['tables'][$table] = $count;
                } catch (Exception $e) {
                    $health['issues'][] = "Table $table: " . $e->getMessage();
                }
            }

            // Kiểm tra orphaned votes
            $stmt = $this->pdo->query("
                SELECT COUNT(*) FROM votes v
                LEFT JOIN contestants c ON v.contestant_id = c.id
                WHERE c.id IS NULL
            ");
            $orphaned_votes = $stmt->fetchColumn();

            if ($orphaned_votes > 0) {
                $health['issues'][] = "Found $orphaned_votes orphaned votes";
            }

            file_put_contents('cache/health_check.json', json_encode($health, JSON_PRETTY_PRINT));
            $this->log("Health check completed. Issues found: " . count($health['issues']));

            return $health;
        } catch (Exception $e) {
            $this->log("Error in health check: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Chạy tất cả background tasks
     */
    public function runAllTasks() {
        $this->log("=== Starting background tasks execution ===");

        $results = [
            'vote_counts' => $this->updateVoteCounts(),
            'cleanup' => $this->cleanupOldSessions(),
            'rankings_cache' => $this->updateRankingsCache(),
            'daily_stats' => $this->generateDailyStats(),
            'health_check' => $this->healthCheck()
        ];

        $this->log("=== Background tasks completed ===");

        return $results;
    }
}

// Tạo thư mục cache nếu chưa có
if (!is_dir('cache')) {
    mkdir('cache', 0755, true);
}

// Chạy background tasks nếu được gọi trực tiếp
if (basename($_SERVER['PHP_SELF']) === 'background_tasks.php') {
    header('Content-Type: application/json');

    $tasks = new BVoteBackgroundTasks();
    $results = $tasks->runAllTasks();

    echo json_encode([
        'success' => true,
        'message' => 'Background tasks completed',
        'results' => $results,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
