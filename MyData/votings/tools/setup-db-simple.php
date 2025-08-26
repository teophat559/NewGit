<?php
/**
 * Simple Database Setup Script
 * Không phụ thuộc vào core classes
 */

echo "🗄️ BVOTE Database Setup Starting...\n";
echo "=====================================\n\n";

try {
    // Kết nối database (không có database name)
    $pdo = new PDO(
        'mysql:host=localhost;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    echo "✅ Database connected successfully\n";

    // Tạo database nếu chưa có
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `bvote_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database 'bvote_system' created/verified\n";

    // Chọn database
    $pdo->exec("USE `bvote_system`");
    echo "✅ Using database 'bvote_system'\n\n";

    // Tạo các bảng
    createTables($pdo);

    // Tạo dữ liệu mẫu
    createSampleData($pdo);

    echo "\n🎉 Database setup completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Database setup failed: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Create database tables
 */
function createTables(PDO $pdo): void {
    echo "📋 Creating tables...\n";

    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('user', 'moderator', 'admin') DEFAULT 'user',
            status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
            remember_token VARCHAR(255) NULL,
            email_verified_at TIMESTAMP NULL,
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ users table created\n";

    // Contests table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            photo VARCHAR(500) NULL,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            status ENUM('draft', 'upcoming', 'active', 'ended', 'cancelled') DEFAULT 'draft',
            total_votes INT DEFAULT 0,
            max_votes_per_user INT DEFAULT 1,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_dates (start_date, end_date),
            INDEX idx_created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ contests table created\n";

    // Contestants table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contestants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contest_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            photo VARCHAR(500) NULL,
            vote_count INT DEFAULT 0,
            status ENUM('active', 'inactive', 'disqualified') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_contest_id (contest_id),
            INDEX idx_vote_count (vote_count),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ contestants table created\n";

    // Votes table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS votes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            contest_id INT NOT NULL,
            contestant_id INT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_contest (user_id, contest_id),
            INDEX idx_contestant (contestant_id),
            INDEX idx_created_at (created_at),
            UNIQUE KEY unique_user_contest (user_id, contest_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ votes table created\n";

    // Permissions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) UNIQUE NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ permissions table created\n";

    // User permissions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            permission_id INT NOT NULL,
            granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            granted_by INT NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_permission_id (permission_id),
            UNIQUE KEY unique_user_permission (user_id, permission_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ user_permissions table created\n";

    // System settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(255) UNIQUE NOT NULL,
            setting_value TEXT,
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ system_settings table created\n";

    // Audit logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            action VARCHAR(255) NOT NULL,
            table_name VARCHAR(255) NULL,
            record_id INT NULL,
            old_values JSON NULL,
            new_values JSON NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_table_record (table_name, record_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✅ audit_logs table created\n";

    echo "\n";
}

/**
 * Create sample data
 */
function createSampleData(PDO $pdo): void {
    echo "📊 Creating sample data...\n";

    // Create admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status, email_verified_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['System Administrator', 'admin@bvote.com', $adminPassword, 'admin', 'active', date('Y-m-d H:i:s')]);
    $adminId = $pdo->lastInsertId();
    echo "  ✅ Admin user created (admin@bvote.com / admin123)\n";

    // Create sample user
    $userPassword = password_hash('user123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, status, email_verified_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Sample User', 'user@bvote.com', $userPassword, 'user', 'active', date('Y-m-d H:i:s')]);
    $userId = $pdo->lastInsertId();
    echo "  ✅ Sample user created (user@bvote.com / user123)\n";

    // Create sample contest
    $stmt = $pdo->prepare("INSERT INTO contests (name, description, start_date, end_date, status, max_votes_per_user, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        'Sample Beauty Contest 2024',
        'A sample beauty contest to demonstrate the voting system',
        date('Y-m-d H:i:s'),
        date('Y-m-d H:i:s', strtotime('+30 days')),
        'active',
        1,
        $adminId
    ]);
    $contestId = $pdo->lastInsertId();
    echo "  ✅ Sample contest created\n";

    // Create sample contestants
    $contestants = [
        ['Sarah Johnson', 'Beautiful and talented contestant'],
        ['Maria Garcia', 'Elegant and charming participant'],
        ['Emma Wilson', 'Graceful and confident competitor'],
        ['Lisa Brown', 'Stunning and charismatic candidate']
    ];

    $stmt = $pdo->prepare("INSERT INTO contestants (name, description, contest_id, status) VALUES (?, ?, ?, ?)");
    foreach ($contestants as $contestant) {
        $stmt->execute([$contestant[0], $contestant[1], $contestId, 'active']);
    }
    echo "  ✅ Sample contestants created\n";

    // Create basic permissions
    $permissions = [
        'manage_contests' => 'Manage contests and contestants',
        'manage_users' => 'Manage user accounts',
        'view_reports' => 'View system reports and analytics',
        'system_settings' => 'Modify system settings'
    ];

    $stmt = $pdo->prepare("INSERT INTO permissions (name, description) VALUES (?, ?)");
    foreach ($permissions as $name => $description) {
        $stmt->execute([$name, $description]);
    }
    echo "  ✅ Basic permissions created\n";

    // Grant all permissions to admin
    $stmt = $pdo->query("SELECT id FROM permissions");
    $permissionIds = $stmt->fetchAll();
    $stmt = $pdo->prepare("INSERT INTO user_permissions (user_id, permission_id, granted_by) VALUES (?, ?, ?)");
    foreach ($permissionIds as $permission) {
        $stmt->execute([$adminId, $permission['id'], $adminId]);
    }
    echo "  ✅ Admin permissions granted\n";

    // Create system settings
    $settings = [
        'site_name' => 'BVOTE Voting System',
        'site_description' => 'Advanced voting system with contest management',
        'max_contests_per_user' => '5',
        'max_contestants_per_contest' => '20',
        'voting_enabled' => 'true',
        'registration_enabled' => 'true',
        'maintenance_mode' => 'false'
    ];

    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
    foreach ($settings as $key => $value) {
        $stmt->execute([$key, $value, 'System setting for ' . $key]);
    }
    echo "  ✅ System settings created\n";

    echo "\n";
}

/**
 * Verify database setup
 */
function verifySetup(PDO $pdo): void {
    echo "🔍 Verifying database setup...\n";

    $tables = ['users', 'contests', 'contestants', 'votes', 'permissions', 'user_permissions', 'system_settings', 'audit_logs'];

    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch();
        echo "  ✅ {$table}: {$result['count']} records\n";
    }

    echo "\n";
}
