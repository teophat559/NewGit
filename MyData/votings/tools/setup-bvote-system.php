<?php
/**
 * BVOTE System Setup Script
 * Script cài đặt hoàn chỉnh hệ thống BVOTE
 */
echo "🚀 Bắt đầu cài đặt hệ thống BVOTE...\n\n";

// Kiểm tra PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die("❌ Yêu cầu PHP 7.4 trở lên. Phiên bản hiện tại: " . PHP_VERSION . "\n");
}

// Kiểm tra extensions cần thiết
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        die("❌ Extension $ext không được cài đặt\n");
    }
}

echo "✅ Kiểm tra yêu cầu hệ thống hoàn tất\n";

// Load database configuration
require_once __DIR__ . '/setup-database.php';

try {
    $db = getConnection();
    echo "✅ Kết nối database thành công\n";
} catch (Exception $e) {
    die("❌ Không thể kết nối database: " . $e->getMessage() . "\n");
}

// Tạo database schema
echo "\n📊 Tạo database schema...\n";

$schema = "
-- Bảng users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255),
    platform ENUM('facebook', 'google', 'instagram', 'zalo', 'yahoo', 'microsoft', 'email', 'apple') NOT NULL,
    avatar VARCHAR(255),
    status ENUM('active', 'suspended', 'pending') DEFAULT 'pending',
    role ENUM('user', 'admin') DEFAULT 'user',
    last_login DATETIME,
    last_logout DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng roles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng user_roles
CREATE TABLE IF NOT EXISTS user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_role (user_id, role_id)
);

-- Bảng login_requests
CREATE TABLE IF NOT EXISTS login_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_hint VARCHAR(255) NOT NULL,
    platform ENUM('facebook', 'google', 'instagram', 'zalo', 'yahoo', 'microsoft', 'email', 'apple') NOT NULL,
    status ENUM('PENDING_REVIEW', 'OTP_REQUIRED', 'APPROVED', 'REJECTED', 'EXPIRED') DEFAULT 'PENDING_REVIEW',
    otp_required BOOLEAN DEFAULT FALSE,
    otp_length INT DEFAULT 6,
    otp_retries INT DEFAULT 3,
    otp_code VARCHAR(10),
    ttl_expires_at TIMESTAMP NOT NULL,
    meta_json JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_platform (platform),
    INDEX idx_created_at (created_at)
);

-- Bảng auth_sessions
CREATE TABLE IF NOT EXISTS auth_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) UNIQUE NOT NULL,
    ip VARCHAR(45),
    ua TEXT,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    revoked_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);

-- Bảng contests
CREATE TABLE IF NOT EXISTS contests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('draft', 'upcoming', 'active', 'ended') DEFAULT 'draft',
    max_votes_per_user INT DEFAULT 1,
    banner_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
);

-- Bảng contestants
CREATE TABLE IF NOT EXISTS contestants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contest_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    contestant_number VARCHAR(50) NOT NULL,
    description TEXT,
    image_path VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE CASCADE,
    UNIQUE KEY unique_contest_number (contest_id, contestant_number),
    INDEX idx_contest_id (contest_id),
    INDEX idx_status (status)
);

-- Bảng votes
CREATE TABLE IF NOT EXISTS votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contestant_id INT NOT NULL,
    contest_id INT NOT NULL,
    ip VARCHAR(45),
    ua TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE,
    FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_contestant (user_id, contestant_id),
    INDEX idx_user_id (user_id),
    INDEX idx_contestant_id (contestant_id),
    INDEX idx_contest_id (contest_id),
    INDEX idx_created_at (created_at)
);

-- Bảng audit_logs
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actor_type ENUM('user', 'admin', 'system') NOT NULL,
    actor_id INT,
    action VARCHAR(100) NOT NULL,
    details_json JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_actor (actor_type, actor_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- Bảng admin_links
CREATE TABLE IF NOT EXISTS admin_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    expires_at TIMESTAMP NULL,
    access_count INT DEFAULT 0,
    ip_allowlist JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_expires_at (expires_at)
);

-- Bảng system_settings
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
);
";

try {
    $db->exec($schema);
    echo "✅ Database schema đã được tạo thành công\n";
} catch (Exception $e) {
    die("❌ Lỗi tạo schema: " . $e->getMessage() . "\n");
}

// Tạo dữ liệu mẫu
echo "\n🌱 Tạo dữ liệu mẫu...\n";

try {
    // Tạo admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("
        INSERT INTO users (username, email, password_hash, platform, status, role)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        password_hash = VALUES(password_hash), status = VALUES(status), role = VALUES(role)
    ");
    $stmt->execute(['admin', 'admin@bvote.com', $adminPassword, 'email', 'active', 'admin']);
    echo "✅ Admin user đã được tạo (username: admin, password: admin123)\n";

    // Tạo roles
    $roles = [
        ['user', 'Người dùng thông thường'],
        ['admin', 'Quản trị viên hệ thống']
    ];

    foreach ($roles as $role) {
        $stmt = $db->prepare("
            INSERT INTO roles (name, description)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE description = VALUES(description)
        ");
        $stmt->execute($role);
    }
    echo "✅ Roles đã được tạo\n";

    // Gán role admin cho user admin
    $adminUserId = $db->query("SELECT id FROM users WHERE username = 'admin'")->fetch()['id'];
    $adminRoleId = $db->query("SELECT id FROM roles WHERE name = 'admin'")->fetch()['id'];

    $stmt = $db->prepare("
        INSERT INTO user_roles (user_id, role_id)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE user_id = user_id
    ");
    $stmt->execute([$adminUserId, $adminRoleId]);
    echo "✅ Admin role đã được gán cho user admin\n";

    // Tạo cuộc thi mẫu
    $stmt = $db->prepare("
        INSERT INTO contests (title, description, start_date, end_date, status)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        title = VALUES(title), description = VALUES(description),
        start_date = VALUES(start_date), end_date = VALUES(end_date), status = VALUES(status)
    ");
    $stmt->execute([
        'Cuộc thi Tài năng 2024',
        'Cuộc thi tìm kiếm tài năng trẻ với nhiều hạng mục đa dạng',
        date('Y-m-d'),
        date('Y-m-d', strtotime('+30 days')),
        'active'
    ]);
    echo "✅ Cuộc thi mẫu đã được tạo\n";

    // Tạo thí sinh mẫu
    $contestId = $db->query("SELECT id FROM contests WHERE title = 'Cuộc thi Tài năng 2024'")->fetch()['id'];
    $contestants = [
        ['Nguyễn Văn A', '001', 'Thí sinh tài năng với giọng hát hay'],
        ['Trần Thị B', '002', 'Thí sinh có khả năng nhảy múa xuất sắc'],
        ['Lê Văn C', '003', 'Thí sinh có tài năng âm nhạc đặc biệt']
    ];

    foreach ($contestants as $contestant) {
        $stmt = $db->prepare("
            INSERT INTO contestants (contest_id, name, contestant_number, description, status)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            name = VALUES(name), description = VALUES(description), status = VALUES(status)
        ");
        $stmt->execute([$contestId, $contestant[0], $contestant[1], $contestant[2], 'active']);
    }
    echo "✅ 3 thí sinh mẫu đã được tạo\n";

    // Tạo cài đặt hệ thống mặc định
    $defaultSettings = [
        'system_name' => 'BVOTE - Hệ thống bình chọn trực tuyến',
        'system_description' => 'Hệ thống bình chọn trực tuyến với Auto Login',
        'contact_email' => 'admin@bvote.com',
        'maintenance_mode' => '0',
        'login_ttl' => '120',
        'otp_length' => '6',
        'otp_retries' => '3',
        'rate_limit_requests' => '5',
        'rate_limit_window' => '60',
        'require_otp_for_new_ip' => '1',
        'max_votes_per_user' => '1',
        'contest_duration' => '30',
        'auto_approve_contestants' => '0',
        'require_contestant_approval' => '1'
    ];

    foreach ($defaultSettings as $key => $value) {
        $stmt = $db->prepare("
            INSERT INTO system_settings (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        $stmt->execute([$key, $value]);
    }
    echo "✅ Cài đặt hệ thống mặc định đã được tạo\n";

} catch (Exception $e) {
    die("❌ Lỗi tạo dữ liệu mẫu: " . $e->getMessage() . "\n");
}

// Kiểm tra các file component
echo "\n🔍 Kiểm tra các file component...\n";

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
foreach ($components as $component) {
    if (!file_exists(__DIR__ . '/../' . $component)) {
        $missingComponents[] = $component;
    }
}

if (empty($missingComponents)) {
    echo "✅ Tất cả component đã được tạo\n";
} else {
    echo "⚠️  Các component bị thiếu:\n";
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
        mkdir(__DIR__ . '/' . $dir, 0755, true);
        echo "✅ Đã tạo thư mục: $dir\n";
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

echo "\n🎉 Cài đặt hệ thống BVOTE hoàn tất!\n\n";

echo "📋 Thông tin đăng nhập:\n";
echo "   - Admin: http://localhost/admin/login\n";
echo "   - User: http://localhost/user/login\n";
echo "   - Homepage: http://localhost/\n\n";

echo "🔑 Thông tin đăng nhập Admin:\n";
echo "   - Username: admin\n";
echo "   - Password: admin123\n\n";

echo "📚 Hướng dẫn sử dụng:\n";
echo "   1. Đăng nhập admin để quản lý hệ thống\n";
echo "   2. Tạo cuộc thi và thêm thí sinh\n";
echo "   3. User đăng nhập qua các nền tảng clone\n";
echo "   4. Admin phê duyệt yêu cầu đăng nhập\n";
echo "   5. User bình chọn sau khi được phê duyệt\n\n";

echo "🚀 Hệ thống đã sẵn sàng sử dụng!\n";
?>
