<?php
/**
 * MoreLogin API Service
 * Điều khiển trình duyệt ảo thông qua MoreLogin API
 */

class MoreLoginService {
    private $apiUrl = 'http://127.0.0.1:40000';
    private $apiId = '1650404388761056';
    private $apiKey = '13544368342b4be69e9d63c9f7f5133e';
    private $timeout = 30;

    public function __construct() {
        // Kiểm tra kết nối API
        $this->checkConnection();
    }

    /**
     * Kiểm tra kết nối đến MoreLogin API
     */
    private function checkConnection() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl . '/api/status');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("MoreLogin API not available on {$this->apiUrl}");
        }
    }

    /**
     * Tạo profile trình duyệt mới
     */
    public function createProfile($profileData) {
        $data = [
            'api_id' => $this->apiId,
            'api_key' => $this->apiKey,
            'action' => 'create_profile',
            'profile_name' => $profileData['name'] ?? 'Auto_' . time(),
            'platform' => $profileData['platform'],
            'proxy' => $profileData['proxy'] ?? null,
            'user_agent' => $profileData['user_agent'] ?? 'auto',
            'screen_resolution' => '1920x1080',
            'language' => 'vi-VN',
            'timezone' => 'Asia/Ho_Chi_Minh'
        ];

        return $this->makeRequest('/api/profile/create', $data);
    }

    /**
     * Mở profile trình duyệt
     */
    public function openProfile($profileId) {
        $data = [
            'api_id' => $this->apiId,
            'api_key' => $this->apiKey,
            'action' => 'open_profile',
            'profile_id' => $profileId
        ];

        return $this->makeRequest('/api/profile/open', $data);
    }

    /**
     * Đóng profile trình duyệt
     */
    public function closeProfile($profileId) {
        $data = [
            'api_id' => $this->apiId,
            'api_key' => $this->apiKey,
            'action' => 'close_profile',
            'profile_id' => $profileId
        ];

        return $this->makeRequest('/api/profile/close', $data);
    }

    /**
     * Lấy danh sách profiles
     */
    public function getProfiles() {
        $data = [
            'api_id' => $this->apiId,
            'api_key' => $this->apiKey,
            'action' => 'list_profiles'
        ];

        return $this->makeRequest('/api/profile/list', $data);
    }

    /**
     * Xóa profile
     */
    public function deleteProfile($profileId) {
        $data = [
            'api_id' => $this->apiId,
            'api_key' => $this->apiKey,
            'action' => 'delete_profile',
            'profile_id' => $profileId
        ];

        return $this->makeRequest('/api/profile/delete', $data);
    }

    /**
     * Bắt đầu session auto-login
     */
    public function startAutoLogin($sessionData) {
        // Tạo hoặc chọn profile phù hợp
        $profileId = $this->getOrCreateProfile($sessionData);

        if (!$profileId) {
            return ['success' => false, 'error' => 'Cannot create/select profile'];
        }

        // Mở trình duyệt
        $browserResult = $this->openProfile($profileId);
        if (!$browserResult['success']) {
            return ['success' => false, 'error' => 'Cannot open browser profile'];
        }

        // Gửi lệnh auto-login đến Puppeteer
        return $this->executePuppeteerLogin($profileId, $sessionData, $browserResult);
    }

    /**
     * Lấy hoặc tạo profile phù hợp
     */
    private function getOrCreateProfile($sessionData) {
        $platform = $sessionData['platform'];
        $profileName = "AutoLogin_{$platform}_" . date('Y-m-d');

        // Kiểm tra profile tồn tại
        $profiles = $this->getProfiles();
        if ($profiles['success'] && isset($profiles['data'])) {
            foreach ($profiles['data'] as $profile) {
                if (strpos($profile['name'], $profileName) !== false) {
                    return $profile['id'];
                }
            }
        }

        // Tạo profile mới
        $newProfile = $this->createProfile([
            'name' => $profileName,
            'platform' => $platform,
            'user_agent' => $sessionData['user_agent'] ?? null
        ]);

        return $newProfile['success'] ? $newProfile['profile_id'] : null;
    }

    /**
     * Thực thi auto-login với Puppeteer
     */
    private function executePuppeteerLogin($profileId, $sessionData, $browserData) {
        $puppeteerData = [
            'profile_id' => $profileId,
            'browser_port' => $browserData['port'] ?? 9222,
            'session_data' => $sessionData,
            'platform' => $sessionData['platform'],
            'credentials' => [
                'username' => $sessionData['username'],
                'password' => $sessionData['password']
            ],
            'callback_url' => $sessionData['callback_url'] ?? null
        ];

        // Gọi Puppeteer script
        return $this->triggerPuppeteerScript($puppeteerData);
    }

    /**
     * Kích hoạt Puppeteer script
     */
    private function triggerPuppeteerScript($data) {
        $scriptPath = __DIR__ . '/../puppeteer/auto-login-stealth.js';
        $dataFile = __DIR__ . '/../data/temp/login_' . $data['session_data']['session_id'] . '.json';

        // Tạo thư mục temp nếu chưa có
        $tempDir = dirname($dataFile);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Ghi dữ liệu vào file temp
        file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));

        // Chạy Puppeteer script trong background
        $command = "cd " . dirname($scriptPath) . " && node auto-login-stealth.js " . escapeshellarg($dataFile);

        if (PHP_OS_FAMILY === 'Windows') {
            pclose(popen("start /B $command", "r"));
        } else {
            exec("$command > /dev/null 2>&1 &");
        }

        return [
            'success' => true,
            'message' => 'Auto-login process started',
            'session_id' => $data['session_data']['session_id'],
            'profile_id' => $data['profile_id']
        ];
    }

    /**
     * Kiểm tra trạng thái auto-login
     */
    public function checkLoginStatus($sessionId) {
        $statusFile = __DIR__ . "/../data/temp/status_{$sessionId}.json";

        if (!file_exists($statusFile)) {
            return [
                'success' => false,
                'status' => 'not_found',
                'message' => 'Session not found'
            ];
        }

        $status = json_decode(file_get_contents($statusFile), true);
        return [
            'success' => true,
            'status' => $status['status'] ?? 'unknown',
            'message' => $status['message'] ?? '',
            'progress' => $status['progress'] ?? 0,
            'data' => $status['data'] ?? []
        ];
    }

    /**
     * Gửi OTP cho session đang xử lý
     */
    public function submitOTP($sessionId, $otpCode) {
        $otpFile = __DIR__ . "/../data/temp/otp_{$sessionId}.json";

        $otpData = [
            'session_id' => $sessionId,
            'otp_code' => $otpCode,
            'timestamp' => time()
        ];

        file_put_contents($otpFile, json_encode($otpData));

        return [
            'success' => true,
            'message' => 'OTP submitted'
        ];
    }

    /**
     * Dọn dẹp session
     */
    public function cleanupSession($sessionId) {
        $files = [
            __DIR__ . "/../data/temp/login_{$sessionId}.json",
            __DIR__ . "/../data/temp/status_{$sessionId}.json",
            __DIR__ . "/../data/temp/otp_{$sessionId}.json"
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        return ['success' => true];
    }

    /**
     * Thực hiện HTTP request đến MoreLogin API
     */
    private function makeRequest($endpoint, $data) {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'http_code' => $httpCode
            ];
        }

        $decodedResponse = json_decode($response, true);

        if ($httpCode === 200) {
            return [
                'success' => true,
                'data' => $decodedResponse,
                'http_code' => $httpCode
            ];
        } else {
            return [
                'success' => false,
                'error' => $decodedResponse['message'] ?? 'API request failed',
                'http_code' => $httpCode,
                'response' => $decodedResponse
            ];
        }
    }

    /**
     * Lấy thông tin API
     */
    public function getApiInfo() {
        return [
            'api_url' => $this->apiUrl,
            'api_id' => $this->apiId,
            'status' => $this->checkApiStatus()
        ];
    }

    /**
     * Kiểm tra trạng thái API
     */
    private function checkApiStatus() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl . '/api/status');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200 ? 'online' : 'offline';
    }
}
?>
