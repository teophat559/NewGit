<?php
/**
 * BVOTE 2025 - Real-time Admin-User Communication System
 * Hệ thống điều khiển 2 chiều giữa Admin và Web User
 */

class AdminUserBridge {
    private $db;
    private $sessionManager;
    private $logFile;

    public function __construct() {
        $this->db = $this->connectDatabase();
        $this->sessionManager = new SessionManager();
        $this->logFile = __DIR__ . '/../logs/admin-user-bridge.log';
        $this->initializeTables();
    }

    /**
     * Ghi nhận hành động của user
     */
    public function logUserAction($userId, $action, $data = [], $ipAddress = null) {
        $sessionId = session_id();
        $timestamp = date('Y-m-d H:i:s');
        $ipAddress = $ipAddress ?: $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Lưu vào database
        $stmt = $this->db->prepare("
            INSERT INTO user_actions
            (user_id, session_id, action, data, ip_address, timestamp, status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");

        $actionId = null;
        try {
            $stmt->execute([
                $userId,
                $sessionId,
                $action,
                json_encode($data),
                $ipAddress,
                $timestamp
            ]);
            $actionId = $this->db->lastInsertId();
        } catch (Exception $e) {
            $this->writeLog("Error logging action: " . $e->getMessage());
        }

        // Ghi log file
        $logEntry = [
            'action_id' => $actionId,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'action' => $action,
            'data' => $data,
            'ip_address' => $ipAddress,
            'timestamp' => $timestamp,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];

        $this->writeLog(json_encode($logEntry));

        // Gửi realtime tới admin
        $this->sendToAdmin($logEntry);

        return $actionId;
    }

    /**
     * Admin phản hồi hành động user
     */
    public function adminResponse($actionId, $adminId, $response, $responseData = []) {
        $timestamp = date('Y-m-d H:i:s');

        // Cập nhật database
        $stmt = $this->db->prepare("
            UPDATE user_actions
            SET admin_response = ?, admin_id = ?, response_data = ?, response_timestamp = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $response,
            $adminId,
            json_encode($responseData),
            $timestamp,
            $actionId
        ]);

        // Lấy thông tin action gốc
        $stmt = $this->db->prepare("SELECT * FROM user_actions WHERE id = ?");
        $stmt->execute([$actionId]);
        $action = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($action) {
            // Gửi phản hồi tới user
            $this->sendToUser($action['session_id'], [
                'type' => 'admin_response',
                'action_id' => $actionId,
                'response' => $response,
                'data' => $responseData,
                'timestamp' => $timestamp
            ]);

            // Log admin response
            $this->writeLog("Admin response: Action #{$actionId} - {$response}");
        }

        return true;
    }

    /**
     * Lấy tất cả session đang hoạt động
     */
    public function getActiveSessions() {
        $stmt = $this->db->prepare("
            SELECT
                session_id,
                user_id,
                ip_address,
                MIN(timestamp) as first_action,
                MAX(timestamp) as last_action,
                COUNT(*) as total_actions,
                GROUP_CONCAT(DISTINCT action) as actions
            FROM user_actions
            WHERE timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY session_id, user_id, ip_address
            ORDER BY last_action DESC
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy chi tiết session
     */
    public function getSessionDetails($sessionId) {
        $stmt = $this->db->prepare("
            SELECT * FROM user_actions
            WHERE session_id = ?
            ORDER BY timestamp DESC
        ");

        $stmt->execute([$sessionId]);
        $actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse JSON data
        foreach ($actions as &$action) {
            $action['data'] = json_decode($action['data'], true);
            $action['response_data'] = json_decode($action['response_data'], true);
        }

        return $actions;
    }

    /**
     * Admin can thiệp session
     */
    public function adminIntervention($sessionId, $adminId, $intervention, $reason = '') {
        $timestamp = date('Y-m-d H:i:s');

        // Ghi nhận can thiệp
        $stmt = $this->db->prepare("
            INSERT INTO admin_interventions
            (session_id, admin_id, intervention, reason, timestamp)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([$sessionId, $adminId, $intervention, $reason, $timestamp]);

        // Gửi lệnh tới user
        $this->sendToUser($sessionId, [
            'type' => 'admin_intervention',
            'intervention' => $intervention,
            'reason' => $reason,
            'timestamp' => $timestamp
        ]);

        $this->writeLog("Admin intervention: Session {$sessionId} - {$intervention}");

        return true;
    }

    /**
     * Chặn session nghi ngờ spam
     */
    public function blockSuspiciousSession($sessionId, $reason) {
        $stmt = $this->db->prepare("
            UPDATE user_actions
            SET status = 'blocked', block_reason = ?
            WHERE session_id = ?
        ");

        $stmt->execute([$reason, $sessionId]);

        $this->sendToUser($sessionId, [
            'type' => 'session_blocked',
            'reason' => $reason,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        return true;
    }

    /**
     * Phát hiện hành vi bất thường
     */
    public function detectAnomalousActivity() {
        $anomalies = [];

        // Kiểm tra vote spam (quá nhiều vote trong thời gian ngắn)
        $stmt = $this->db->prepare("
            SELECT session_id, user_id, ip_address, COUNT(*) as vote_count
            FROM user_actions
            WHERE action = 'vote'
            AND timestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            GROUP BY session_id
            HAVING vote_count > 10
        ");

        $stmt->execute();
        $spamVotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($spamVotes as $spam) {
            $anomalies[] = [
                'type' => 'vote_spam',
                'session_id' => $spam['session_id'],
                'details' => "Excessive voting: {$spam['vote_count']} votes in 5 minutes"
            ];
        }

        // Kiểm tra multiple sessions từ cùng IP
        $stmt = $this->db->prepare("
            SELECT ip_address, COUNT(DISTINCT session_id) as session_count
            FROM user_actions
            WHERE timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY ip_address
            HAVING session_count > 5
        ");

        $stmt->execute();
        $multiSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($multiSessions as $multi) {
            $anomalies[] = [
                'type' => 'multiple_sessions',
                'ip_address' => $multi['ip_address'],
                'details' => "Multiple sessions: {$multi['session_count']} from same IP"
            ];
        }

        return $anomalies;
    }

    /**
     * Gửi tin nhắn tới admin (WebSocket)
     */
    private function sendToAdmin($data) {
        // WebSocket hoặc SSE implementation
        $adminPayload = [
            'channel' => 'admin_monitoring',
            'data' => $data,
            'timestamp' => time()
        ];

        // Lưu vào queue để admin dashboard nhận
        file_put_contents(
            __DIR__ . '/../data/admin_queue.json',
            json_encode($adminPayload) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * Gửi tin nhắn tới user (WebSocket)
     */
    private function sendToUser($sessionId, $data) {
        $userPayload = [
            'session_id' => $sessionId,
            'data' => $data,
            'timestamp' => time()
        ];

        // Lưu vào queue để user nhận
        file_put_contents(
            __DIR__ . "/../data/user_queue_{$sessionId}.json",
            json_encode($userPayload) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * Ghi log
     */
    private function writeLog($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Kết nối database
     */
    private function connectDatabase() {
        try {
            $config = include __DIR__ . '/../config/database.php';
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
            return new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (Exception $e) {
            $this->writeLog("Database connection failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Khởi tạo bảng database
     */
    private function initializeTables() {
        if (!$this->db) return;

        $tables = [
            "CREATE TABLE IF NOT EXISTS user_actions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(255),
                session_id VARCHAR(255),
                action VARCHAR(100),
                data TEXT,
                ip_address VARCHAR(45),
                timestamp DATETIME,
                status ENUM('active', 'blocked', 'completed') DEFAULT 'active',
                admin_response VARCHAR(100),
                admin_id VARCHAR(255),
                response_data TEXT,
                response_timestamp DATETIME,
                block_reason TEXT,
                INDEX idx_session (session_id),
                INDEX idx_timestamp (timestamp),
                INDEX idx_status (status)
            )",

            "CREATE TABLE IF NOT EXISTS admin_interventions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                session_id VARCHAR(255),
                admin_id VARCHAR(255),
                intervention VARCHAR(100),
                reason TEXT,
                timestamp DATETIME,
                INDEX idx_session (session_id),
                INDEX idx_timestamp (timestamp)
            )"
        ];

        foreach ($tables as $sql) {
            try {
                $this->db->exec($sql);
            } catch (Exception $e) {
                $this->writeLog("Error creating table: " . $e->getMessage());
            }
        }
    }
}

/**
 * Session Manager
 */
class SessionManager {
    public function createSession($userId, $platform) {
        session_start();
        $_SESSION['user_id'] = $userId;
        $_SESSION['platform'] = $platform;
        $_SESSION['start_time'] = time();

        return session_id();
    }

    public function validateSession($sessionId) {
        if (session_id() !== $sessionId) {
            return false;
        }

        return isset($_SESSION['user_id']);
    }

    public function destroySession($sessionId) {
        if (session_id() === $sessionId) {
            session_destroy();
        }

        // Xóa user queue file
        $queueFile = __DIR__ . "/../data/user_queue_{$sessionId}.json";
        if (file_exists($queueFile)) {
            unlink($queueFile);
        }
    }
}

// API Endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $bridge = new AdminUserBridge();
    $input = json_decode(file_get_contents('php://input'), true);

    switch ($input['action']) {
        case 'log_user_action':
            $result = $bridge->logUserAction(
                $input['user_id'],
                $input['user_action'],
                $input['data'] ?? [],
                $_SERVER['REMOTE_ADDR']
            );
            echo json_encode(['success' => true, 'action_id' => $result]);
            break;

        case 'admin_response':
            $result = $bridge->adminResponse(
                $input['action_id'],
                $input['admin_id'],
                $input['response'],
                $input['response_data'] ?? []
            );
            echo json_encode(['success' => $result]);
            break;

        case 'get_active_sessions':
            $sessions = $bridge->getActiveSessions();
            echo json_encode(['sessions' => $sessions]);
            break;

        case 'get_session_details':
            $details = $bridge->getSessionDetails($input['session_id']);
            echo json_encode(['details' => $details]);
            break;

        case 'admin_intervention':
            $result = $bridge->adminIntervention(
                $input['session_id'],
                $input['admin_id'],
                $input['intervention'],
                $input['reason'] ?? ''
            );
            echo json_encode(['success' => $result]);
            break;

        case 'detect_anomalies':
            $anomalies = $bridge->detectAnomalousActivity();
            echo json_encode(['anomalies' => $anomalies]);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}
?>
