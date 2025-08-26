<?php
/**
 * SQLite Database Setup Script
 * Sử dụng SQLite để test hệ thống khi MySQL không khả dụng
 */

echo "🗄️ BVOTE SQLite Database Setup Starting...\n";
echo "==========================================\n\n";

try {
    // Tạo database SQLite
    $dbPath = __DIR__ . '/../storage/database.sqlite';
    $dbDir = dirname($dbPath);

    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0755, true);
    }

    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ SQLite database created at: $dbPath\n\n";

    // Tạo các bảng
    createTables($pdo);

    // Tạo dữ liệu mẫu
    createSampleData($pdo);

    // Verify setup
    verifySetup($pdo);

    echo "\n🎉 SQLite database setup completed successfully!\n";
    echo "📁 Database file: $dbPath\n";

} catch (Exception $e) {
    echo "❌ SQLite database setup failed: " . $e->getMessage() . "\n";
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
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'user' CHECK(role IN ('user', 'moderator', 'admin')),
            status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive', 'banned')),
            remember_token TEXT,
            email_verified_at TEXT,
            last_login TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "  ✅ users table created\n";

    // Contests table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            photo TEXT,
            start_date TEXT NOT NULL,
            end_date TEXT NOT NULL,
            status TEXT DEFAULT 'draft' CHECK(status IN ('draft', 'upcoming', 'active', 'ended', 'cancelled')),
            total_votes INTEGER DEFAULT 0,
            max_votes_per_user INTEGER DEFAULT 1,
            created_by INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "  ✅ contests table created\n";

    // Contestants table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contestants (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            contest_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            description TEXT,
            photo TEXT,
            vote_count INTEGER DEFAULT 0,
            status TEXT DEFAULT 'active' CHECK(status IN ('active', 'inactive', 'disqualified')),
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "  ✅ contestants table created\n";

    // Votes table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS votes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            contest_id INTEGER NOT NULL,
            contestant_id INTEGER NOT NULL,
            ip_address TEXT,
            user_agent TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, contest_id)
        )
    ");
    echo "  ✅ votes table created\n";

    // Permissions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE NOT NULL,
            description TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "  ✅ permissions table created\n";

    // User permissions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            permission_id INTEGER NOT NULL,
            granted_at TEXT DEFAULT CURRENT_TIMESTAMP,
            granted_by INTEGER,
            UNIQUE(user_id, permission_id)
        )
    ");
    echo "  ✅ user_permissions table created\n";

    // System settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT UNIQUE NOT NULL,
            setting_value TEXT,
            description TEXT,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "  ✅ system_settings table created\n";

    // Audit logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            table_name TEXT,
            record_id INTEGER,
            old_values TEXT,
            new_values TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )
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
