<?php
// Dashboard service - Thống kê và báo cáo cho admin
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/user.php';
require_once __DIR__ . '/notification.php';

class DashboardService {
    private $db;
    private $auth;
    private $userService;
    private $notificationService;

    public function __construct() {
        $this->db = db();
        $this->auth = auth();
        $this->userService = user();
        $this->notificationService = notification();
    }

    // ==================== OVERVIEW STATISTICS ====================

    public function getOverviewStats() {
        try {
            $stats = [
                'users' => $this->getUserStats(),
                'contests' => $this->getContestStats(),
                'notifications' => $this->getNotificationStats(),
                'system' => $this->getSystemStats(),
                'recent_activity' => $this->getRecentActivity()
            ];

            return [
                'success' => true,
                'data' => $stats
            ];

        } catch (Exception $e) {
            error_log("Get overview stats failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi lấy thống kê tổng quan: ' . $e->getMessage()
            ];
        }
    }

    private function getUserStats() {
        try {
            $stats = [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
                'new_today' => 0,
                'new_this_week' => 0,
                'new_this_month' => 0,
                'by_role' => [],
                'by_status' => [],
                'growth_chart' => []
            ];

            // Total users
            $totalUsers = $this->db->fetchOne("SELECT COUNT(*) as total FROM users WHERE status != 'deleted'");
            $stats['total'] = $totalUsers['total'];

            // Active users
            $activeUsers = $this->db->fetchOne("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
            $stats['active'] = $activeUsers['total'];

            // Inactive users
            $inactiveUsers = $this->db->fetchOne("SELECT COUNT(*) as total FROM users WHERE status = 'inactive'");
            $stats['inactive'] = $inactiveUsers['total'];

            // New users today
            $newToday = $this->db->fetchOne("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()");
            $stats['new_today'] = $newToday['total'];

            // New users this week
            $newThisWeek = $this->db->fetchOne("SELECT COUNT(*) as total FROM users WHERE YEARWEEK(created_at) = YEARWEEK(CURDATE())");
            $stats['new_this_week'] = $newThisWeek['total'];

            // New users this month
            $newThisMonth = $this->db->fetchOne("SELECT COUNT(*) as total FROM users WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
            $stats['new_this_month'] = $newThisMonth['total'];

            // Users by role
            $usersByRole = $this->db->fetchAll("SELECT role, COUNT(*) as count FROM users WHERE status != 'deleted' GROUP BY role");
            $stats['by_role'] = $usersByRole;

            // Users by status
            $usersByStatus = $this->db->fetchAll("SELECT status, COUNT(*) as count FROM users WHERE status != 'deleted' GROUP BY status");
            $stats['by_status'] = $usersByStatus;

            // Growth chart (last 30 days)
            $growthChart = $this->db->fetchAll(
                "SELECT DATE(created_at) as date, COUNT(*) as count
                 FROM users
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY date"
            );
            $stats['growth_chart'] = $growthChart;

            return $stats;

        } catch (Exception $e) {
            error_log("Get user stats failed: " . $e->getMessage());
            return [
                'total' => 0,
                'active' => 0,
                'inactive' => 0,
                'new_today' => 0,
                'new_this_week' => 0,
                'new_this_month' => 0,
                'by_role' => [],
                'by_status' => [],
                'growth_chart' => []
            ];
        }
    }

    private function getContestStats() {
        try {
            $stats = [
                'total' => 0,
                'active' => 0,
                'voting' => 0,
                'ended' => 0,
                'draft' => 0,
                'total_contestants' => 0,
                'total_votes' => 0,
                'by_status' => [],
                'recent_contests' => [],
                'top_contests' => []
            ];

            // Total contests
            $totalContests = $this->db->fetchOne("SELECT COUNT(*) as total FROM contests");
            $stats['total'] = $totalContests['total'];

            // Contests by status
            $contestsByStatus = $this->db->fetchAll("SELECT status, COUNT(*) as count FROM contests GROUP BY status");
            $stats['by_status'] = $contestsByStatus;

            // Set individual counts
            foreach ($contestsByStatus as $status) {
                switch ($status['status']) {
                    case 'active':
                        $stats['active'] = $status['count'];
                        break;
                    case 'voting':
                        $stats['voting'] = $status['count'];
                        break;
                    case 'ended':
                        $stats['ended'] = $status['count'];
                        break;
                    case 'draft':
                        $stats['draft'] = $status['count'];
                        break;
                }
            }

            // Total contestants
            $totalContestants = $this->db->fetchOne("SELECT COUNT(*) as total FROM contestants");
            $stats['total_contestants'] = $totalContestants['total'];

            // Total votes
            $totalVotes = $this->db->fetchOne("SELECT SUM(total_votes) as total FROM contestants");
            $stats['total_votes'] = $totalVotes['total'] ?? 0;

            // Recent contests
            $recentContests = $this->db->fetchAll(
                "SELECT c.*, a.username as created_by_name,
                        (SELECT COUNT(*) FROM contestants ct WHERE ct.contest_id = c.id) as contestant_count
                 FROM contests c
                 LEFT JOIN admins a ON c.created_by = a.id
                 ORDER BY c.created_at DESC
                 LIMIT 5"
            );
            $stats['recent_contests'] = $recentContests;

            // Top contests by contestants
            $topContests = $this->db->fetchAll(
                "SELECT c.*, COUNT(ct.id) as contestant_count
                 FROM contests c
                 LEFT JOIN contestants ct ON c.id = ct.contest_id
                 GROUP BY c.id
                 ORDER BY contestant_count DESC
                 LIMIT 5"
            );
            $stats['top_contests'] = $topContests;

            return $stats;

        } catch (Exception $e) {
            error_log("Get contest stats failed: " . $e->getMessage());
            return [
                'total' => 0,
                'active' => 0,
                'voting' => 0,
                'ended' => 0,
                'draft' => 0,
                'total_contestants' => 0,
                'total_votes' => 0,
                'by_status' => [],
                'recent_contests' => [],
                'top_contests' => []
            ];
        }
    }

    private function getNotificationStats() {
        try {
            $stats = [
                'total' => 0,
                'unread' => 0,
                'read' => 0,
                'by_type' => [],
                'recent' => [],
                'templates' => 0
            ];

            // Total notifications
            $totalNotifications = $this->db->fetchOne("SELECT COUNT(*) as total FROM notifications");
            $stats['total'] = $totalNotifications['total'];

            // Unread notifications
            $unreadNotifications = $this->db->fetchOne("SELECT COUNT(*) as total FROM notifications WHERE status = 'unread'");
            $stats['unread'] = $unreadNotifications['total'];

            // Read notifications
            $stats['read'] = $stats['total'] - $stats['unread'];

            // By type
            $notificationsByType = $this->db->fetchAll("SELECT type, COUNT(*) as count FROM notifications GROUP BY type");
            $stats['by_type'] = $notificationsByType;

            // Recent notifications
            $recentNotifications = $this->db->fetchAll(
                "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10"
            );
            $stats['recent'] = $recentNotifications;

            // Total templates
            $totalTemplates = $this->db->fetchOne("SELECT COUNT(*) as total FROM notification_templates");
            $stats['templates'] = $totalTemplates['total'];

            return $stats;

        } catch (Exception $e) {
            error_log("Get notification stats failed: " . $e->getMessage());
            return [
                'total' => 0,
                'unread' => 0,
                'read' => 0,
                'by_type' => [],
                'recent' => [],
                'templates' => 0
            ];
        }
    }

    private function getSystemStats() {
        try {
            $stats = [
                'uploads' => 0,
                'total_file_size' => 0,
                'chrome_profiles' => 0,
                'login_logs' => 0,
                'audit_logs' => 0,
                'system_settings' => 0
            ];

            // Total uploads
            $totalUploads = $this->db->fetchOne("SELECT COUNT(*) as total FROM uploads");
            $stats['uploads'] = $totalUploads['total'];

            // Total file size
            $totalFileSize = $this->db->fetchOne("SELECT SUM(size) as total FROM uploads");
            $stats['total_file_size'] = $totalFileSize['total'] ?? 0;

            // Chrome profiles
            $chromeProfiles = $this->db->fetchOne("SELECT COUNT(*) as total FROM chrome_profiles");
            $stats['chrome_profiles'] = $chromeProfiles['total'];

            // Login logs
            $loginLogs = $this->db->fetchOne("SELECT COUNT(*) as total FROM login_logs");
            $stats['login_logs'] = $loginLogs['total'];

            // Audit logs
            $auditLogs = $this->db->fetchOne("SELECT COUNT(*) as total FROM audit_logs");
            $stats['audit_logs'] = $auditLogs['total'];

            // System settings
            $systemSettings = $this->db->fetchOne("SELECT COUNT(*) as total FROM system_settings");
            $stats['system_settings'] = $systemSettings['total'];

            return $stats;

        } catch (Exception $e) {
            error_log("Get system stats failed: " . $e->getMessage());
            return [
                'uploads' => 0,
                'total_file_size' => 0,
                'chrome_profiles' => 0,
                'login_logs' => 0,
                'audit_logs' => 0,
                'system_settings' => 0
            ];
        }
    }

    private function getRecentActivity() {
        try {
            $activities = [];

            // Recent user registrations
            $recentUsers = $this->db->fetchAll(
                "SELECT 'user_registration' as type, username as title, created_at, 'user' as category
                 FROM users
                 WHERE status != 'deleted'
                 ORDER BY created_at DESC
                 LIMIT 5"
            );
            $activities = array_merge($activities, $recentUsers);

            // Recent contest creations
            $recentContests = $this->db->fetchAll(
                "SELECT 'contest_creation' as type, title, created_at, 'contest' as category
                 FROM contests
                 ORDER BY created_at DESC
                 LIMIT 5"
            );
            $activities = array_merge($activities, $recentContests);

            // Recent logins
            $recentLogins = $this->db->fetchAll(
                "SELECT 'user_login' as type, account as title, created_at, 'security' as category
                 FROM login_logs
                 WHERE status = 'success'
                 ORDER BY created_at DESC
                 LIMIT 5"
            );
            $activities = array_merge($activities, $recentLogins);

            // Sort by created_at
            usort($activities, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            // Return top 10
            return array_slice($activities, 0, 10);

        } catch (Exception $e) {
            error_log("Get recent activity failed: " . $e->getMessage());
            return [];
        }
    }

    // ==================== CHART DATA ====================

    public function getChartData($chartType, $period = '30') {
        try {
            switch ($chartType) {
                case 'user_growth':
                    return $this->getUserGrowthChart($period);

                case 'contest_activity':
                    return $this->getContestActivityChart($period);

                case 'voting_trends':
                    return $this->getVotingTrendsChart($period);

                case 'system_usage':
                    return $this->getSystemUsageChart($period);

                default:
                    return ['success' => false, 'message' => 'Loại biểu đồ không được hỗ trợ'];
            }

        } catch (Exception $e) {
            error_log("Get chart data failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi lấy dữ liệu biểu đồ: ' . $e->getMessage()];
        }
    }

    private function getUserGrowthChart($period) {
        try {
            $data = $this->db->fetchAll(
                "SELECT DATE(created_at) as date, COUNT(*) as count
                 FROM users
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY date",
                [$period]
            );

            return [
                'success' => true,
                'data' => $data,
                'chart_type' => 'line',
                'title' => 'Tăng trưởng người dùng',
                'x_axis' => 'Ngày',
                'y_axis' => 'Số người dùng mới'
            ];

        } catch (Exception $e) {
            error_log("Get user growth chart failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi lấy biểu đồ tăng trưởng người dùng'];
        }
    }

    private function getContestActivityChart($period) {
        try {
            $data = $this->db->fetchAll(
                "SELECT DATE(created_at) as date, COUNT(*) as count
                 FROM contests
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY date",
                [$period]
            );

            return [
                'success' => true,
                'data' => $data,
                'chart_type' => 'bar',
                'title' => 'Hoạt động cuộc thi',
                'x_axis' => 'Ngày',
                'y_axis' => 'Số cuộc thi mới'
            ];

        } catch (Exception $e) {
            error_log("Get contest activity chart failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi lấy biểu đồ hoạt động cuộc thi'];
        }
    }

    private function getVotingTrendsChart($period) {
        try {
            $data = $this->db->fetchAll(
                "SELECT DATE(v.created_at) as date, COUNT(*) as count
                 FROM votes v
                 JOIN contests c ON v.contest_id = c.id
                 WHERE v.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                 GROUP BY DATE(v.created_at)
                 ORDER BY date",
                [$period]
            );

            return [
                'success' => true,
                'data' => $data,
                'chart_type' => 'line',
                'title' => 'Xu hướng bình chọn',
                'x_axis' => 'Ngày',
                'y_axis' => 'Số lượt bình chọn'
            ];

        } catch (Exception $e) {
            error_log("Get voting trends chart failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi lấy biểu đồ xu hướng bình chọn'];
        }
    }

    private function getSystemUsageChart($period) {
        try {
            $data = $this->db->fetchAll(
                "SELECT DATE(created_at) as date, COUNT(*) as count
                 FROM uploads
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY date",
                [$period]
            );

            return [
                'success' => true,
                'data' => $data,
                'chart_type' => 'area',
                'title' => 'Sử dụng hệ thống',
                'x_axis' => 'Ngày',
                'y_axis' => 'Số file upload'
            ];

        } catch (Exception $e) {
            error_log("Get system usage chart failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi lấy biểu đồ sử dụng hệ thống'];
        }
    }

    // ==================== REAL-TIME UPDATES ====================

    public function getRealTimeUpdates() {
        try {
            $updates = [
                'new_users' => 0,
                'new_contests' => 0,
                'new_votes' => 0,
                'new_notifications' => 0,
                'system_alerts' => []
            ];

            // New users in last hour
            $newUsers = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            $updates['new_users'] = $newUsers['count'];

            // New contests in last hour
            $newContests = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM contests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            $updates['new_contests'] = $newContests['count'];

            // New votes in last hour
            $newVotes = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM votes WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            $updates['new_votes'] = $newVotes['count'];

            // New notifications in last hour
            $newNotifications = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM notifications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
            );
            $updates['new_notifications'] = $newNotifications['count'];

            // System alerts (e.g., failed logins, errors)
            $systemAlerts = $this->db->fetchAll(
                "SELECT 'failed_login' as type, account as message, created_at
                 FROM login_logs
                 WHERE status = 'failed' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                 ORDER BY created_at DESC
                 LIMIT 5"
            );
            $updates['system_alerts'] = $systemAlerts;

            return [
                'success' => true,
                'data' => $updates,
                'timestamp' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            error_log("Get real-time updates failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi lấy cập nhật thời gian thực: ' . $e->getMessage()
            ];
        }
    }

    // ==================== EXPORT FUNCTIONS ====================

    public function exportDashboardData($format = 'json') {
        try {
            $data = [
                'overview' => $this->getOverviewStats(),
                'exported_at' => date('Y-m-d H:i:s'),
                'exported_by' => 'system'
            ];

            switch ($format) {
                case 'json':
                    return [
                        'success' => true,
                        'data' => $data,
                        'format' => 'json'
                    ];

                case 'csv':
                    // TODO: Implement CSV export
                    return [
                        'success' => false,
                        'message' => 'Export CSV chưa được hỗ trợ'
                    ];

                default:
                    return [
                        'success' => false,
                        'message' => 'Định dạng export không được hỗ trợ'
                    ];
            }

        } catch (Exception $e) {
            error_log("Export dashboard data failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Lỗi export dữ liệu dashboard: ' . $e->getMessage()
            ];
        }
    }
}

// Helper function để sử dụng dashboard service
function dashboard() {
    return new DashboardService();
}
?>
