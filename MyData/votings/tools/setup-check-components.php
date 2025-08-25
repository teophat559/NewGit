<?php
/**
 * BVOTE Component Check Script
 * Kiểm tra các component mà không cần database
 */
echo "🔍 Kiểm tra các component hệ thống BVOTE...\n\n";

// Kiểm tra PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die("❌ Yêu cầu PHP 7.4 trở lên. Phiên bản hiện tại: " . PHP_VERSION . "\n");
}
echo "✅ PHP version: " . PHP_VERSION . "\n";

// Kiểm tra extensions cần thiết
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ Extension $ext: OK\n";
    } else {
        echo "❌ Extension $ext: Không có\n";
    }
}

echo "\n📁 Kiểm tra các file component...\n";

$components = [
    'pages/admin/DashboardPage.php',
    'pages/admin/auto-login/AutoLoginManagementPage.php',
    'pages/admin/contest/ContestsPage.php',
    'pages/admin/contest/ContestantsPage.php',
    'pages/admin/user/UserManagementPage.php',
    'pages/admin/settings/SystemSettingsPage.php',
    'pages/admin/AdminLoginPage.php',
    'pages/admin/AdminLogoutPage.php',
    'pages/UserLoginPage.php',
    'pages/UserHomePage.php',
    'pages/UserLogoutPage.php',
    'pages/HomePage.php',
    'components/login-clones/FacebookLoginClone.php',
    'components/login-clones/GoogleLoginClone.php',
    'components/login-clones/InstagramLoginClone.php',
    'components/login-clones/ZaloLoginClone.php',
    'components/login-clones/YahooLoginClone.php',
    'components/login-clones/OutlookLoginClone.php',
    'components/login-clones/EmailLoginClone.php',
    'components/login-clones/AppleLoginClone.php'
];

$missingComponents = [];
$existingComponents = [];

foreach ($components as $component) {
    if (file_exists(__DIR__ . '/../' . $component)) {
        $existingComponents[] = $component;
        echo "✅ $component\n";
    } else {
        $missingComponents[] = $component;
        echo "❌ $component\n";
    }
}

echo "\n📊 Thống kê:\n";
echo "   - Tổng component: " . count($components) . "\n";
echo "   - Đã có: " . count($existingComponents) . "\n";
echo "   - Bị thiếu: " . count($missingComponents) . "\n";

if (!empty($missingComponents)) {
    echo "\n⚠️  Các component bị thiếu:\n";
    foreach ($missingComponents as $component) {
        echo "   - $component\n";
    }
}

// Tạo thư mục uploads nếu chưa có
echo "\n📁 Tạo thư mục uploads...\n";

$uploadDirs = [
    '../uploads/contestants',
    '../uploads/temp'
];

foreach ($uploadDirs as $dir) {
    if (!is_dir(__DIR__ . '/' . $dir)) {
        if (mkdir(__DIR__ . '/' . $dir, 0755, true)) {
            echo "✅ Đã tạo thư mục: $dir\n";
        } else {
            echo "❌ Không thể tạo thư mục: $dir\n";
        }
    } else {
        echo "✅ Thư mục đã tồn tại: $dir\n";
    }
}

// Kiểm tra quyền ghi
foreach ($uploadDirs as $dir) {
    if (is_writable(__DIR__ . '/' . $dir)) {
        echo "✅ Thư mục có quyền ghi: $dir\n";
    } else {
        echo "⚠️  Thư mục không có quyền ghi: $dir\n";
    }
}

// Kiểm tra file app.php
echo "\n🔍 Kiểm tra file app.php...\n";
if (file_exists(__DIR__ . '/../app.php')) {
    echo "✅ app.php: OK\n";

    // Kiểm tra nội dung app.php
    $appContent = file_get_contents(__DIR__ . '/../app.php');
    if (strpos($appContent, 'AdminLoginPage.php') !== false) {
        echo "✅ AdminLoginPage route: OK\n";
    } else {
        echo "❌ AdminLoginPage route: Không tìm thấy\n";
    }

    if (strpos($appContent, 'HomePage.php') !== false) {
        echo "✅ HomePage route: OK\n";
    } else {
        echo "❌ HomePage route: Không tìm thấy\n";
    }
} else {
    echo "❌ app.php: Không tìm thấy\n";
}

echo "\n🎯 Hướng dẫn tiếp theo:\n";
echo "   1. Cài đặt và khởi động MySQL/XAMPP\n";
echo "   2. Tạo database 'bvote_system'\n";
    echo "   3. Chạy: php tools/setup-bvote-system.php\n";
echo "   4. Truy cập: http://localhost/admin/login\n";
echo "   5. Đăng nhập với: admin/admin123\n\n";

if (empty($missingComponents)) {
    echo "🎉 Tất cả component đã sẵn sàng!\n";
    echo "   Chỉ cần cài đặt database để hoàn tất.\n";
} else {
    echo "⚠️  Cần tạo các component bị thiếu trước khi cài đặt database.\n";
}
?>
