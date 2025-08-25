<?php
/**
 * BVOTE 2025 - Auto Login API
 * Handles automated login requests from user interface
 * Integrates with MoreLogin + Puppeteer automation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load configuration
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

class AutoLoginAPI {
    private $db;
    private $config;
    private $logFile;

    public function __construct() {
        $this->config = Config::getInstance();
        $this->db = Database::getInstance()->getConnection();
        $this->logFile = __DIR__ . '/data/auto_login.log';

        // Ensure log file exists
        if (!file_exists($this->logFile)) {
            file_put_contents($this->logFile, "# BVOTE 2025 Auto Login Log\n");
        }
    }

    /**
     * Main API endpoint handler
     */
    public function handleRequest() {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $pathSegments = explode('/', trim($path, '/'));

            // API routing
            switch ($method) {
                case 'POST':
                    if (end($pathSegments) === 'login') {
                        return $this->processLogin();
                    } elseif (end($pathSegments) === 'status') {
                        return $this->checkStatus();
                    }
                    break;

                case 'GET':
                    if (end($pathSegments) === 'platforms') {
                        return $this->getSupportedPlatforms();
                    } elseif (end($pathSegments) === 'logs') {
                        return $this->getLoginLogs();
                    }
                    break;
            }

            throw new Exception('Endpoint not found', 404);

        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Process login request
     */
    private function processLogin() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            throw new Exception('Invalid JSON data', 400);
        }

        // Validate required fields
        $required = ['platform', 'username', 'password', 'action', 'target'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception("Missing required field: {$field}", 400);
            }
        }

        // Create login session
        $sessionId = $this->createLoginSession($input);

        // Log the request
        $this->logRequest($sessionId, $input);

        // Send to automation system
        $automationResult = $this->sendToAutomationSystem($sessionId, $input);

        return $this->sendSuccess([
            'session_id' => $sessionId,
            'status' => 'processing',
            'message' => 'Login request submitted successfully',
            'automation_result' => $automationResult,
            'timestamp' => date('c')
        ]);
    }

    /**
     * Create login session record
     */
    private function createLoginSession($data) {
        $sessionId = $this->generateSessionId();

        $stmt = $this->db->prepare("
            INSERT INTO auto_login_sessions
            (session_id, platform, username, action_type, target_id, status, created_at, user_agent, ip_address)
            VALUES (?, ?, ?, ?, ?, 'pending', NOW(), ?, ?)
        ");

        $stmt->execute([
            $sessionId,
            $data['platform'],
            $this->hashUsername($data['username']), // Store hashed for privacy
            $data['action'],
            $data['target'],
            $data['userAgent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '',
            $this->getRealIpAddress()
        ]);

        return $sessionId;
    }

    /**
     * Send to automation system (MoreLogin + Puppeteer)
     */
    private function sendToAutomationSystem($sessionId, $data) {
        // Prepare automation payload
        $automationPayload = [
            'session_id' => $sessionId,
            'platform' => $data['platform'],
            'credentials' => [
                'username' => $data['username'],
                'password' => $data['password'],
                'otp' => $data['otp'] ?? null
            ],
            'action' => [
                'type' => $data['action'],
                'target' => $data['target']
            ],
            'settings' => [
                'browser_profile' => $this->selectBrowserProfile($data['platform']),
                'proxy_config' => $this->getProxyConfig(),
                'user_agent' => $this->generateUserAgent($data['platform']),
                'viewport' => $this->getRandomViewport(),
                'delays' => $this->getRandomDelays()
            ],
            'timestamp' => time()
        ];

        // Try WebSocket first, fallback to file queue
        if ($this->sendViaWebSocket($automationPayload)) {
            return ['method' => 'websocket', 'status' => 'sent'];
        } else {
            return $this->sendViaFileQueue($automationPayload);
        }
    }

    /**
     * Send via WebSocket to automation worker
     */
    private function sendViaWebSocket($payload) {
        try {
            // WebSocket implementation would go here
            // For now, simulate with file-based queue
            return false;
        } catch (Exception $e) {
            $this->logError("WebSocket send failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via file queue (fallback method)
     */
    private function sendViaFileQueue($payload) {
        $queueDir = __DIR__ . '/data/automation_queue';
        if (!is_dir($queueDir)) {
            mkdir($queueDir, 0755, true);
        }

        $queueFile = $queueDir . '/' . $payload['session_id'] . '.json';
        $success = file_put_contents($queueFile, json_encode($payload, JSON_PRETTY_PRINT));

        if ($success) {
            // Trigger automation worker
            $this->triggerAutomationWorker();
            return ['method' => 'file_queue', 'status' => 'queued', 'file' => $queueFile];
        } else {
            throw new Exception('Failed to queue automation request');
        }
    }

    /**
     * Trigger automation worker process
     */
    private function triggerAutomationWorker() {
        // Check if worker is already running
        $statusFile = __DIR__ . '/puppeteer/status.json';
        if (file_exists($statusFile)) {
            $status = json_decode(file_get_contents($statusFile), true);
            if ($status['status'] === 'running') {
                return; // Worker already running
            }
        }

        // Start automation worker
        $cmd = 'cd ' . escapeshellarg(__DIR__ . '/puppeteer') . ' && node autoLogin.js > /dev/null 2>&1 &';

        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = 'cd /d ' . escapeshellarg(__DIR__ . '/puppeteer') . ' && start /B node autoLogin.js > nul 2>&1';
        }

        exec($cmd);

        // Update status
        $status = [
            'status' => 'starting',
            'timestamp' => time(),
            'pid' => getmypid(),
            'trigger_method' => 'api'
        ];
        file_put_contents($statusFile, json_encode($status));
    }

    /**
     * Get supported platforms
     */
    private function getSupportedPlatforms() {
        $platforms = [
            'facebook' => [
                'name' => 'Facebook',
                'icon' => 'fab fa-facebook',
                'login_url' => 'https://www.facebook.com/login',
                'supported_features' => ['basic_login', 'two_factor', 'checkpoint_bypass'],
                'success_rate' => 85
            ],
            'gmail' => [
                'name' => 'Gmail',
                'icon' => 'fab fa-google',
                'login_url' => 'https://accounts.google.com/signin',
                'supported_features' => ['basic_login', 'two_factor', 'app_passwords'],
                'success_rate' => 92
            ],
            'instagram' => [
                'name' => 'Instagram',
                'icon' => 'fab fa-instagram',
                'login_url' => 'https://www.instagram.com/accounts/login/',
                'supported_features' => ['basic_login', 'two_factor', 'phone_verification'],
                'success_rate' => 78
            ],
            'zalo' => [
                'name' => 'Zalo',
                'icon' => 'fas fa-comment',
                'login_url' => 'https://id.zalo.me/account/login',
                'supported_features' => ['basic_login', 'sms_otp'],
                'success_rate' => 90
            ],
            'yahoo' => [
                'name' => 'Yahoo',
                'icon' => 'fab fa-yahoo',
                'login_url' => 'https://login.yahoo.com/',
                'supported_features' => ['basic_login', 'two_factor'],
                'success_rate' => 88
            ],
            'microsoft' => [
                'name' => 'Microsoft',
                'icon' => 'fab fa-microsoft',
                'login_url' => 'https://login.microsoftonline.com/',
                'supported_features' => ['basic_login', 'two_factor', 'enterprise_sso'],
                'success_rate' => 94
            ]
        ];

        return $this->sendSuccess($platforms);
    }

    /**
     * Check login status
     */
    private function checkStatus() {
        $input = json_decode(file_get_contents('php://input'), true);
        $sessionId = $input['session_id'] ?? null;

        if (!$sessionId) {
            throw new Exception('Session ID required', 400);
        }

        $stmt = $this->db->prepare("
            SELECT session_id, platform, username, status, result_data,
                   created_at, updated_at, error_message
            FROM auto_login_sessions
            WHERE session_id = ?
        ");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            throw new Exception('Session not found', 404);
        }

        // Get real-time status from automation worker
        $liveStatus = $this->getLiveStatus($sessionId);

        return $this->sendSuccess([
            'session' => $session,
            'live_status' => $liveStatus,
            'timestamp' => date('c')
        ]);
    }

    /**
     * Get live status from automation worker
     */
    private function getLiveStatus($sessionId) {
        $statusFile = __DIR__ . "/data/automation_queue/{$sessionId}_status.json";

        if (file_exists($statusFile)) {
            $content = file_get_contents($statusFile);
            return json_decode($content, true);
        }

        return ['status' => 'unknown', 'message' => 'No live status available'];
    }

    /**
     * Get login logs
     */
    private function getLoginLogs() {
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);

        $stmt = $this->db->prepare("
            SELECT session_id, platform, status, created_at, updated_at,
                   error_message, ip_address
            FROM auto_login_sessions
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->sendSuccess([
            'logs' => $logs,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $this->getTotalLoginCount()
            ]
        ]);
    }

    /**
     * Select appropriate browser profile for platform
     */
    private function selectBrowserProfile($platform) {
        $profiles = [
            'facebook' => 'chrome_android_mobile',
            'gmail' => 'chrome_desktop',
            'instagram' => 'chrome_android_mobile',
            'zalo' => 'chrome_mobile',
            'yahoo' => 'firefox_desktop',
            'microsoft' => 'edge_desktop'
        ];

        return $profiles[$platform] ?? 'chrome_desktop';
    }

    /**
     * Get proxy configuration
     */
    private function getProxyConfig() {
        // Return proxy configuration based on environment
        return [
            'enabled' => false, // Set to true in production
            'type' => 'http',
            'host' => '127.0.0.1',
            'port' => 8080,
            'auth' => false
        ];
    }

    /**
     * Generate platform-specific user agent
     */
    private function generateUserAgent($platform) {
        $userAgents = [
            'facebook' => [
                'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Mobile Safari/537.36',
                'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Mobile/15E148 Safari/604.1'
            ],
            'gmail' => [
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]
        ];

        $platformAgents = $userAgents[$platform] ?? $userAgents['gmail'];
        return $platformAgents[array_rand($platformAgents)];
    }

    /**
     * Get random viewport size
     */
    private function getRandomViewport() {
        $viewports = [
            ['width' => 1920, 'height' => 1080],
            ['width' => 1366, 'height' => 768],
            ['width' => 1440, 'height' => 900],
            ['width' => 375, 'height' => 667], // iPhone
            ['width' => 414, 'height' => 896], // iPhone X
        ];

        return $viewports[array_rand($viewports)];
    }

    /**
     * Get random delays for human-like behavior
     */
    private function getRandomDelays() {
        return [
            'page_load' => rand(2000, 4000),
            'input_delay' => rand(100, 300),
            'click_delay' => rand(200, 500),
            'form_submit' => rand(1000, 2000)
        ];
    }

    /**
     * Utility functions
     */
    private function generateSessionId() {
        return 'login_' . bin2hex(random_bytes(16)) . '_' . time();
    }

    private function hashUsername($username) {
        return hash('sha256', $username . $this->config->get('security.salt', 'default_salt'));
    }

    private function getRealIpAddress() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    private function getTotalLoginCount() {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM auto_login_sessions");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    private function logRequest($sessionId, $data) {
        $logEntry = [
            'timestamp' => date('c'),
            'session_id' => $sessionId,
            'platform' => $data['platform'],
            'action' => $data['action'],
            'target' => $data['target'],
            'ip' => $this->getRealIpAddress(),
            'user_agent' => $data['userAgent'] ?? ''
        ];

        file_put_contents($this->logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }

    private function logError($message) {
        error_log("[BVOTE AutoLogin] " . $message);

        $errorEntry = [
            'timestamp' => date('c'),
            'level' => 'ERROR',
            'message' => $message,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
        ];

        file_put_contents($this->logFile, json_encode($errorEntry) . "\n", FILE_APPEND | LOCK_EX);
    }

    private function sendSuccess($data) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ]);
        exit();
    }

    private function sendError($message, $code = 500) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => date('c')
        ]);
        exit();
    }
}

// Initialize database table if not exists
function initializeDatabase() {
    try {
        $db = Database::getInstance()->getConnection();

        $sql = "
        CREATE TABLE IF NOT EXISTS auto_login_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(100) UNIQUE NOT NULL,
            platform VARCHAR(50) NOT NULL,
            username VARCHAR(255) NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            target_id VARCHAR(100),
            status ENUM('pending', 'processing', 'success', 'failed', 'timeout') DEFAULT 'pending',
            result_data JSON,
            error_message TEXT,
            user_agent TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_session_id (session_id),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $db->exec($sql);

    } catch (Exception $e) {
        error_log("Database initialization failed: " . $e->getMessage());
    }
}

// Initialize and run API
try {
    initializeDatabase();
    $api = new AutoLoginAPI();
    $api->handleRequest();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'timestamp' => date('c')
    ]);
}
