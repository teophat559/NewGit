<?php
/**
 * BVOTE 2025 - Auto Login Bot System
 * Chức năng: Điều khiển trình duyệt tự động đăng nhập qua MoreLogin + Puppeteer
 *
 * 🔒 INTERFACE LOCKED - Pure backend processing
 */

require_once 'database.php';

class AutoLoginBot {
    private $moreLoginAPI;
    private $puppeteerCommand;
    private $browserProfiles;
    private $logFile;

    public function __construct() {
        $this->moreLoginAPI = 'http://127.0.0.1:35000'; // MoreLogin API endpoint
        $this->puppeteerCommand = 'node';
        $this->browserProfiles = $this->loadBrowserProfiles();
        $this->logFile = 'logs/auto_login.log';

        // Tạo thư mục logs
        if (!is_dir('logs')) {
            mkdir('logs', 0755, true);
        }
    }

    /**
     * Xử lý yêu cầu đăng nhập từ WebSocket
     */
    public function processLoginRequest($loginData) {
        $this->log("🔄 Processing login request for: {$loginData['platform']} / {$loginData['username']}");

        try {
            // Lấy hoặc tạo browser profile
            $profileId = $this->getBrowserProfile($loginData['platform'], $loginData['username']);

            // Khởi động browser
            $browserInstance = $this->startBrowser($profileId);

            if (!$browserInstance) {
                return $this->createErrorResponse('browser_failed', 'Không thể khởi động trình duyệt');
            }

            // Thực hiện đăng nhập
            $result = $this->performLogin($browserInstance, $loginData);

            // Đóng browser
            $this->closeBrowser($browserInstance);

            return $result;

        } catch (Exception $e) {
            $this->log("❌ Login error: " . $e->getMessage());
            return $this->createErrorResponse('system_error', $e->getMessage());
        }
    }

    /**
     * Lấy hoặc tạo browser profile từ MoreLogin
     */
    private function getBrowserProfile($platform, $username) {
        $profileName = strtolower($platform) . '_' . md5($username);

        // Kiểm tra profile đã tồn tại
        if (isset($this->browserProfiles[$profileName])) {
            return $this->browserProfiles[$profileName]['id'];
        }

        // Tạo profile mới
        $profileData = [
            'name' => $profileName,
            'platform' => $platform,
            'user_agent' => $this->generateUserAgent(),
            'screen_resolution' => '1920x1080',
            'timezone' => 'Asia/Ho_Chi_Minh',
            'language' => 'vi-VN,vi;q=0.9,en;q=0.8'
        ];

        $profileId = $this->createMoreLoginProfile($profileData);

        if ($profileId) {
            $this->browserProfiles[$profileName] = [
                'id' => $profileId,
                'platform' => $platform,
                'created_at' => time()
            ];
            $this->saveBrowserProfiles();
        }

        return $profileId;
    }

    /**
     * Tạo profile mới trong MoreLogin
     */
    private function createMoreLoginProfile($profileData) {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $this->moreLoginAPI . '/api/v1/profile/create',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'name' => $profileData['name'],
                    'user_agent' => $profileData['user_agent'],
                    'screen_resolution' => $profileData['screen_resolution'],
                    'timezone' => $profileData['timezone'],
                    'language' => $profileData['language'],
                    'platform' => $profileData['platform']
                ]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->getMoreLoginToken()
                ],
                CURLOPT_TIMEOUT => 30
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return $data['profile_id'] ?? null;
            }

            $this->log("❌ Failed to create MoreLogin profile: HTTP $httpCode");
            return null;

        } catch (Exception $e) {
            $this->log("❌ MoreLogin API error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Khởi động browser với profile ID
     */
    private function startBrowser($profileId) {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $this->moreLoginAPI . "/api/v1/profile/start/$profileId",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->getMoreLoginToken()
                ],
                CURLOPT_TIMEOUT => 60
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($httpCode === 200) {
                $data = json_decode($response, true);

                if ($data['success'] && isset($data['automation_port'])) {
                    return [
                        'profile_id' => $profileId,
                        'port' => $data['automation_port'],
                        'pid' => $data['pid'] ?? null
                    ];
                }
            }

            $this->log("❌ Failed to start browser: HTTP $httpCode");
            return null;

        } catch (Exception $e) {
            $this->log("❌ Browser start error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Thực hiện đăng nhập qua Puppeteer
     */
    private function performLogin($browserInstance, $loginData) {
        $scriptPath = __DIR__ . '/puppeteer_scripts/login_handler.js';

        // Tạo script parameters
        $params = [
            'port' => $browserInstance['port'],
            'platform' => $loginData['platform'],
            'username' => $loginData['username'],
            'password' => $loginData['password'],
            'otp' => $loginData['otp'] ?? null
        ];

        $paramsJson = escapeshellarg(json_encode($params));
        $command = "node $scriptPath $paramsJson 2>&1";

        $this->log("🚀 Executing Puppeteer script: {$loginData['platform']}");

        $output = shell_exec($command);
        $result = json_decode($output, true);

        if (!$result) {
            $this->log("❌ Invalid Puppeteer response: $output");
            return $this->createErrorResponse('script_error', 'Script execution failed');
        }

        $this->log("📊 Puppeteer result: " . ($result['success'] ? 'SUCCESS' : 'FAILED'));

        return $result;
    }

    /**
     * Tạo Puppeteer script cho từng platform
     */
    public function generatePuppeteerScript() {
        $scriptDir = __DIR__ . '/puppeteer_scripts';
        if (!is_dir($scriptDir)) {
            mkdir($scriptDir, 0755, true);
        }

        $script = <<<'JAVASCRIPT'
const puppeteer = require('puppeteer-core');

async function performLogin(params) {
    const { port, platform, username, password, otp } = params;

    try {
        // Kết nối với browser đã khởi động
        const browser = await puppeteer.connect({
            browserURL: `http://127.0.0.1:${port}`,
            defaultViewport: null
        });

        const page = await browser.newPage();

        // Set user agent và headers
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

        let result = { success: false, error_type: 'unknown' };

        switch (platform.toLowerCase()) {
            case 'facebook':
                result = await loginFacebook(page, username, password, otp);
                break;
            case 'gmail':
            case 'google':
                result = await loginGoogle(page, username, password, otp);
                break;
            case 'instagram':
                result = await loginInstagram(page, username, password, otp);
                break;
            case 'zalo':
                result = await loginZalo(page, username, password, otp);
                break;
            case 'yahoo':
                result = await loginYahoo(page, username, password, otp);
                break;
            case 'microsoft':
                result = await loginMicrosoft(page, username, password, otp);
                break;
            default:
                result = { success: false, error_type: 'unsupported_platform' };
        }

        await page.close();
        return result;

    } catch (error) {
        console.error('Login error:', error.message);
        return {
            success: false,
            error_type: 'system_error',
            message: error.message
        };
    }
}

async function loginFacebook(page, username, password, otp) {
    try {
        await page.goto('https://www.facebook.com/login', { waitUntil: 'networkidle2' });

        // Điền thông tin đăng nhập
        await page.waitForSelector('#email', { timeout: 10000 });
        await page.type('#email', username);
        await page.type('#pass', password);

        // Click đăng nhập
        await page.click('[name="login"]');

        // Chờ redirect hoặc error
        await page.waitForNavigation({ timeout: 15000 });

        const currentUrl = page.url();

        // Kiểm tra kết quả đăng nhập
        if (currentUrl.includes('facebook.com') && !currentUrl.includes('login')) {
            // Kiểm tra checkpoint
            if (currentUrl.includes('checkpoint')) {
                return { success: false, error_type: 'device_verification' };
            }

            // Kiểm tra 2FA
            const otpInput = await page.$('input[name="approvals_code"]');
            if (otpInput) {
                if (otp) {
                    await page.type('input[name="approvals_code"]', otp);
                    await page.click('[type="submit"]');
                    await page.waitForNavigation({ timeout: 10000 });

                    if (page.url().includes('facebook.com') && !page.url().includes('login')) {
                        return { success: true, platform: 'facebook' };
                    } else {
                        return { success: false, error_type: 'invalid_otp' };
                    }
                } else {
                    return { success: false, error_type: 'requires_otp' };
                }
            }

            return { success: true, platform: 'facebook' };
        } else {
            // Kiểm tra error message
            const errorElement = await page.$('.error');
            if (errorElement) {
                return { success: false, error_type: 'invalid_credentials' };
            }

            return { success: false, error_type: 'unknown' };
        }

    } catch (error) {
        console.error('Facebook login error:', error.message);
        return { success: false, error_type: 'system_error' };
    }
}

async function loginGoogle(page, username, password, otp) {
    try {
        await page.goto('https://accounts.google.com/signin', { waitUntil: 'networkidle2' });

        // Điền email
        await page.waitForSelector('#identifierId', { timeout: 10000 });
        await page.type('#identifierId', username);
        await page.click('#identifierNext');

        // Chờ trang password
        await page.waitForSelector('input[name="password"]', { timeout: 10000 });
        await page.type('input[name="password"]', password);
        await page.click('#passwordNext');

        // Chờ kết quả
        await page.waitForNavigation({ timeout: 15000 });

        const currentUrl = page.url();

        if (currentUrl.includes('myaccount.google.com') || currentUrl.includes('accounts.google.com/ManageAccount')) {
            return { success: true, platform: 'google' };
        } else if (currentUrl.includes('challenge')) {
            return { success: false, error_type: 'requires_otp' };
        } else {
            return { success: false, error_type: 'invalid_credentials' };
        }

    } catch (error) {
        console.error('Google login error:', error.message);
        return { success: false, error_type: 'system_error' };
    }
}

async function loginInstagram(page, username, password, otp) {
    try {
        await page.goto('https://www.instagram.com/accounts/login/', { waitUntil: 'networkidle2' });

        // Điền thông tin
        await page.waitForSelector('input[name="username"]', { timeout: 10000 });
        await page.type('input[name="username"]', username);
        await page.type('input[name="password"]', password);

        // Click đăng nhập
        await page.click('button[type="submit"]');

        await page.waitForNavigation({ timeout: 15000 });

        if (page.url() === 'https://www.instagram.com/') {
            return { success: true, platform: 'instagram' };
        } else {
            return { success: false, error_type: 'invalid_credentials' };
        }

    } catch (error) {
        return { success: false, error_type: 'system_error' };
    }
}

async function loginZalo(page, username, password, otp) {
    // Zalo web login implementation
    try {
        await page.goto('https://id.zalo.me/account/login', { waitUntil: 'networkidle2' });

        await page.waitForSelector('input[name="username"]', { timeout: 10000 });
        await page.type('input[name="username"]', username);
        await page.type('input[name="password"]', password);

        await page.click('button[type="submit"]');
        await page.waitForNavigation({ timeout: 15000 });

        if (page.url().includes('zalo.me') && !page.url().includes('login')) {
            return { success: true, platform: 'zalo' };
        } else {
            return { success: false, error_type: 'invalid_credentials' };
        }

    } catch (error) {
        return { success: false, error_type: 'system_error' };
    }
}

async function loginYahoo(page, username, password, otp) {
    // Yahoo login implementation
    try {
        await page.goto('https://login.yahoo.com/', { waitUntil: 'networkidle2' });

        await page.waitForSelector('#login-username', { timeout: 10000 });
        await page.type('#login-username', username);
        await page.click('#login-signin');

        await page.waitForSelector('#login-passwd', { timeout: 10000 });
        await page.type('#login-passwd', password);
        await page.click('#login-signin');

        await page.waitForNavigation({ timeout: 15000 });

        if (page.url().includes('yahoo.com') && !page.url().includes('login')) {
            return { success: true, platform: 'yahoo' };
        } else {
            return { success: false, error_type: 'invalid_credentials' };
        }

    } catch (error) {
        return { success: false, error_type: 'system_error' };
    }
}

async function loginMicrosoft(page, username, password, otp) {
    // Microsoft login implementation
    try {
        await page.goto('https://login.microsoftonline.com/', { waitUntil: 'networkidle2' });

        await page.waitForSelector('input[name="loginfmt"]', { timeout: 10000 });
        await page.type('input[name="loginfmt"]', username);
        await page.click('#idSIButton9');

        await page.waitForSelector('input[name="passwd"]', { timeout: 10000 });
        await page.type('input[name="passwd"]', password);
        await page.click('#idSIButton9');

        await page.waitForNavigation({ timeout: 15000 });

        if (page.url().includes('office.com') || page.url().includes('portal.azure.com')) {
            return { success: true, platform: 'microsoft' };
        } else {
            return { success: false, error_type: 'invalid_credentials' };
        }

    } catch (error) {
        return { success: false, error_type: 'system_error' };
    }
}

// Main execution
const params = JSON.parse(process.argv[2]);
performLogin(params).then(result => {
    console.log(JSON.stringify(result));
    process.exit(0);
}).catch(error => {
    console.log(JSON.stringify({
        success: false,
        error_type: 'system_error',
        message: error.message
    }));
    process.exit(1);
});
JAVASCRIPT;

        file_put_contents($scriptDir . '/login_handler.js', $script);
        $this->log("✅ Generated Puppeteer login script");
    }

    /**
     * Đóng browser instance
     */
    private function closeBrowser($browserInstance) {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $this->moreLoginAPI . "/api/v1/profile/stop/{$browserInstance['profile_id']}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->getMoreLoginToken()
                ],
                CURLOPT_TIMEOUT => 30
            ]);

            curl_exec($curl);
            curl_close($curl);

        } catch (Exception $e) {
            $this->log("❌ Browser close error: " . $e->getMessage());
        }
    }

    /**
     * Utility functions
     */
    private function createErrorResponse($errorType, $message) {
        return [
            'success' => false,
            'error_type' => $errorType,
            'message' => $message
        ];
    }

    private function generateUserAgent() {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36'
        ];

        return $userAgents[array_rand($userAgents)];
    }

    private function getMoreLoginToken() {
        // Return your MoreLogin API token
        return getenv('MORELOGIN_TOKEN') ?: 'your_morelogin_api_token_here';
    }

    private function loadBrowserProfiles() {
        $file = 'cache/browser_profiles.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true) ?: [];
        }
        return [];
    }

    private function saveBrowserProfiles() {
        if (!is_dir('cache')) {
            mkdir('cache', 0755, true);
        }
        file_put_contents('cache/browser_profiles.json', json_encode($this->browserProfiles, JSON_PRETTY_PRINT));
    }

    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        echo $logEntry;
    }
}

// CLI usage
if (basename($_SERVER['PHP_SELF']) === 'auto_login_bot.php') {
    $bot = new AutoLoginBot();
    $bot->generatePuppeteerScript();
    echo "🤖 Auto Login Bot system ready!\n";
}
?>
