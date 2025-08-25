<?php
/**
 * System Settings Page - BVOTE Admin
 * Cài đặt hệ thống với cấu hình chung và bảo mật
 */
session_start();

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login');
    exit;
}

require_once __DIR__ . '/../../../includes/database.php';
$db = getConnection();

// Xử lý cập nhật cài đặt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_general':
            $systemName = $_POST['system_name'] ?? '';
            $systemDescription = $_POST['system_description'] ?? '';
            $contactEmail = $_POST['contact_email'] ?? '';
            $maintenanceMode = isset($_POST['maintenance_mode']) ? 1 : 0;

            // Cập nhật cài đặt chung
            $settings = [
                'system_name' => $systemName,
                'system_description' => $systemDescription,
                'contact_email' => $contactEmail,
                'maintenance_mode' => $maintenanceMode
            ];

            foreach ($settings as $key => $value) {
                $stmt = $db->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value), updated_at = NOW()
                ");
                $stmt->execute([$key, $value]);
            }

            header('Location: /admin/settings/web-config?success=general_updated');
            exit;

        case 'update_security':
            $loginTtl = (int)($_POST['login_ttl'] ?? 120);
            $otpLength = (int)($_POST['otp_length'] ?? 6);
            $otpRetries = (int)($_POST['otp_retries'] ?? 3);
            $rateLimitRequests = (int)($_POST['rate_limit_requests'] ?? 5);
            $rateLimitWindow = (int)($_POST['rate_limit_window'] ?? 60);
            $requireOtpForNewIp = isset($_POST['require_otp_for_new_ip']) ? 1 : 0;

            // Cập nhật cài đặt bảo mật
            $securitySettings = [
                'login_ttl' => $loginTtl,
                'otp_length' => $otpLength,
                'otp_retries' => $otpRetries,
                'rate_limit_requests' => $rateLimitRequests,
                'rate_limit_window' => $rateLimitWindow,
                'require_otp_for_new_ip' => $requireOtpForNewIp
            ];

            foreach ($securitySettings as $key => $value) {
                $stmt = $db->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value), updated_at = NOW()
                ");
                $stmt->execute([$key, $value]);
            }

            header('Location: /admin/settings/web-config?success=security_updated');
            exit;

        case 'update_contest':
            $maxVotesPerUser = (int)($_POST['max_votes_per_user'] ?? 1);
            $contestDuration = (int)($_POST['contest_duration'] ?? 30);
            $autoApproveContestants = isset($_POST['auto_approve_contestants']) ? 1 : 0;
            $requireContestantApproval = isset($_POST['require_contestant_approval']) ? 1 : 0;

            // Cập nhật cài đặt cuộc thi
            $contestSettings = [
                'max_votes_per_user' => $maxVotesPerUser,
                'contest_duration' => $contestDuration,
                'auto_approve_contestants' => $autoApproveContestants,
                'require_contestant_approval' => $requireContestantApproval
            ];

            foreach ($contestSettings as $key => $value) {
                $stmt = $db->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value), updated_at = NOW()
                ");
                $stmt->execute([$key, $value]);
            }

            header('Location: /admin/settings/web-config?success=contest_updated');
            exit;
    }
}

// Lấy cài đặt hiện tại
$stmt = $db->prepare("SELECT setting_key, setting_value FROM system_settings");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Giá trị mặc định
$defaults = [
    'system_name' => 'BVOTE - Hệ thống bình chọn trực tuyến',
    'system_description' => 'Hệ thống bình chọn trực tuyến với Auto Login',
    'contact_email' => 'admin@bvote.com',
    'maintenance_mode' => 0,
    'login_ttl' => 120,
    'otp_length' => 6,
    'otp_retries' => 3,
    'rate_limit_requests' => 5,
    'rate_limit_window' => 60,
    'require_otp_for_new_ip' => 1,
    'max_votes_per_user' => 1,
    'contest_duration' => 30,
    'auto_approve_contestants' => 0,
    'require_contestant_approval' => 1
];

// Merge với giá trị từ database
$currentSettings = array_merge($defaults, $settings);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt hệ thống - BVOTE Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="h-10 w-10 bg-blue-600 rounded-full flex items-center justify-center">
                        <span class="text-white text-lg font-bold">BV</span>
                    </div>
                    <h1 class="ml-3 text-xl font-semibold text-gray-900">BVOTE Admin</h1>
                </div>

                <nav class="flex space-x-8">
                    <a href="/admin/dashboard" class="text-gray-500 hover:text-gray-700 px-3 py-2">Dashboard</a>
                    <a href="/admin/auto-login/management" class="text-gray-500 hover:text-gray-700 px-3 py-2">Auto Login</a>
                    <a href="/admin/contest-management/contests" class="text-gray-500 hover:text-gray-700 px-3 py-2">Cuộc thi</a>
                    <a href="/admin/user-management/appearance" class="text-gray-500 hover:text-gray-700 px-3 py-2">Người dùng</a>
                    <a href="/admin/settings/web-config" class="text-blue-600 border-b-2 border-blue-600 px-3 py-2">Cài đặt</a>
                </nav>

                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Admin</span>
                    <a href="/admin/logout" class="text-red-600 hover:text-red-800">Đăng xuất</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Cài đặt hệ thống</h2>
            <p class="text-gray-600">Cấu hình các thông số hệ thống và bảo mật</p>
        </div>

        <!-- Settings Tabs -->
        <div class="bg-white rounded-lg shadow-sm border mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6">
                    <button onclick="showTab('general')" id="general-tab"
                            class="border-b-2 border-blue-500 text-blue-600 py-4 px-1 text-sm font-medium">
                        Cài đặt chung
                    </button>
                    <button onclick="showTab('security')" id="security-tab"
                            class="border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1 text-sm font-medium">
                        Bảo mật
                    </button>
                    <button onclick="showTab('contest')" id="contest-tab"
                            class="border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1 text-sm font-medium">
                        Cuộc thi
                    </button>
                </nav>
            </div>

            <!-- General Settings Tab -->
            <div id="general-tab-content" class="p-6">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="update_general">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tên hệ thống</label>
                        <input type="text" name="system_name"
                               value="<?php echo htmlspecialchars($currentSettings['system_name']); ?>"
                               class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mô tả hệ thống</label>
                        <textarea name="system_description" rows="3"
                                  class="w-full border border-gray-300 rounded-md px-3 py-2"><?php echo htmlspecialchars($currentSettings['system_description']); ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email liên hệ</label>
                        <input type="email" name="contact_email"
                               value="<?php echo htmlspecialchars($currentSettings['contact_email']); ?>"
                               class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="maintenance_mode" id="maintenance_mode"
                               value="1" <?php echo $currentSettings['maintenance_mode'] ? 'checked' : ''; ?>
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="maintenance_mode" class="ml-2 block text-sm text-gray-900">
                            Chế độ bảo trì
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            Lưu cài đặt chung
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security Settings Tab -->
            <div id="security-tab-content" class="p-6 hidden">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="update_security">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">TTL yêu cầu đăng nhập (giây)</label>
                            <input type="number" name="login_ttl" min="60" max="300"
                                   value="<?php echo $currentSettings['login_ttl']; ?>"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Độ dài OTP</label>
                            <select name="otp_length" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="6" <?php echo $currentSettings['otp_length'] == 6 ? 'selected' : ''; ?>>6 số</option>
                                <option value="8" <?php echo $currentSettings['otp_length'] == 8 ? 'selected' : ''; ?>>8 số</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Số lần nhập OTP sai tối đa</label>
                            <select name="otp_retries" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="3" <?php echo $currentSettings['otp_retries'] == 3 ? 'selected' : ''; ?>>3 lần</option>
                                <option value="5" <?php echo $currentSettings['otp_retries'] == 5 ? 'selected' : ''; ?>>5 lần</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Giới hạn yêu cầu (số lần)</label>
                            <input type="number" name="rate_limit_requests" min="1" max="20"
                                   value="<?php echo $currentSettings['rate_limit_requests']; ?>"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cửa sổ giới hạn (giây)</label>
                            <input type="number" name="rate_limit_window" min="30" max="300"
                                   value="<?php echo $currentSettings['rate_limit_window']; ?>"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="require_otp_for_new_ip" id="require_otp_for_new_ip"
                               value="1" <?php echo $currentSettings['require_otp_for_new_ip'] ? 'checked' : ''; ?>
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="require_otp_for_new_ip" class="ml-2 block text-sm text-gray-900">
                            Yêu cầu OTP cho IP mới
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            Lưu cài đặt bảo mật
                        </button>
                    </div>
                </form>
            </div>

            <!-- Contest Settings Tab -->
            <div id="contest-tab-content" class="p-6 hidden">
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="update_contest">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Giới hạn bình chọn/user</label>
                            <input type="number" name="max_votes_per_user" min="1" max="10"
                                   value="<?php echo $currentSettings['max_votes_per_user']; ?>"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Thời gian cuộc thi mặc định (ngày)</label>
                            <input type="number" name="contest_duration" min="1" max="365"
                                   value="<?php echo $currentSettings['contest_duration']; ?>"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="auto_approve_contestants" id="auto_approve_contestants"
                                   value="1" <?php echo $currentSettings['auto_approve_contestants'] ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="auto_approve_contestants" class="ml-2 block text-sm text-gray-900">
                                Tự động phê duyệt thí sinh
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="require_contestant_approval" id="require_contestant_approval"
                                   value="1" <?php echo $currentSettings['require_contestant_approval'] ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="require_contestant_approval" class="ml-2 block text-sm text-gray-900">
                                Yêu cầu phê duyệt thí sinh
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            Lưu cài đặt cuộc thi
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- System Info -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Thông tin hệ thống</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Phiên bản PHP</h4>
                    <p class="text-lg font-semibold text-gray-900"><?php echo PHP_VERSION; ?></p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Phiên bản MySQL</h4>
                    <p class="text-lg font-semibold text-gray-900"><?php echo $db->getAttribute(PDO::ATTR_SERVER_VERSION); ?></p>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Thời gian hoạt động</h4>
                    <p class="text-lg font-semibold text-gray-900"><?php echo round((time() - strtotime('2024-01-01')) / 86400); ?> ngày</p>
                </div>
            </div>
        </div>

        <!-- Success Messages -->
        <?php if (isset($_GET['success'])): ?>
        <div class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            <?php
            $message = '';
            switch ($_GET['success']) {
                case 'general_updated': $message = 'Cài đặt chung đã được cập nhật!'; break;
                case 'security_updated': $message = 'Cài đặt bảo mật đã được cập nhật!'; break;
                case 'contest_updated': $message = 'Cài đặt cuộc thi đã được cập nhật!'; break;
            }
            ?>
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span><?php echo $message; ?></span>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <script>
        function showTab(tabName) {
            // Ẩn tất cả tab content
            document.querySelectorAll('[id$="-tab-content"]').forEach(content => {
                content.classList.add('hidden');
            });

            // Bỏ active tất cả tab buttons
            document.querySelectorAll('[id$="-tab"]').forEach(button => {
                button.classList.remove('border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });

            // Hiển thị tab content được chọn
            document.getElementById(tabName + '-tab-content').classList.remove('hidden');

            // Active tab button được chọn
            document.getElementById(tabName + '-tab').classList.remove('border-transparent', 'text-gray-500');
            document.getElementById(tabName + '-tab').classList.add('border-blue-500', 'text-blue-600');
        }

        // Auto-hide success messages
        setTimeout(() => {
            const messages = document.querySelectorAll('.fixed');
            messages.forEach(msg => msg.remove());
        }, 5000);
    </script>
</body>
</html>
