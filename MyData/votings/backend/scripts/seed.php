<?php
// seed.php - Tạo dữ liệu mẫu cho hệ thống
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../services/db.php';
require_once __DIR__ . '/../services/auth.php';

class DatabaseSeeder {
    private $db;
    private $auth;

    public function __construct() {
        $this->db = db();
        $this->auth = auth();
    }

    public function run() {
        try {
            echo "🌱 Bắt đầu tạo dữ liệu mẫu...\n";

            $this->seedAdmins();
            $this->seedUsers();
            $this->seedContests();
            $this->seedContestants();
            $this->seedNotificationTemplates();
            $this->seedSystemSettings();

            echo "✅ Tạo dữ liệu mẫu hoàn thành thành công!\n";

        } catch (Exception $e) {
            echo "❌ Tạo dữ liệu mẫu thất bại: " . $e->getMessage() . "\n";
            $this->db->rollback();
        }
    }

    private function seedAdmins() {
        echo "👑 Tạo tài khoản admin...\n";

        // Tạo admin chính
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $this->db->execute(
            "INSERT INTO admins (username, email, password_hash, full_name, role, permissions, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                'admin',
                'admin@example.com',
                $adminPassword,
                'Administrator',
                'super_admin',
                json_encode(['*']),
                'active'
            ]
        );

        // Tạo admin thường
        $moderatorPassword = password_hash('mod123', PASSWORD_DEFAULT);
        $this->db->execute(
            "INSERT INTO admins (username, email, password_hash, full_name, role, permissions, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                'moderator',
                'mod@example.com',
                $moderatorPassword,
                'Moderator',
                'admin',
                json_encode(['contests.read', 'contests.write', 'users.read', 'notifications.read']),
                'active'
            ]
        );

        echo "✅ Tạo 2 tài khoản admin\n";
    }

    private function seedUsers() {
        echo "👥 Tạo người dùng mẫu...\n";

        $users = [
            ['john_doe', 'john@example.com', 'John Doe', 'user123'],
            ['jane_smith', 'jane@example.com', 'Jane Smith', 'user123'],
            ['bob_wilson', 'bob@example.com', 'Bob Wilson', 'user123'],
            ['alice_brown', 'alice@example.com', 'Alice Brown', 'user123'],
            ['charlie_davis', 'charlie@example.com', 'Charlie Davis', 'user123']
        ];

        foreach ($users as $user) {
            $passwordHash = password_hash($user[3], PASSWORD_DEFAULT);
            $this->db->execute(
                "INSERT INTO users (username, email, password_hash, full_name, status, role)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$user[0], $user[1], $passwordHash, $user[2], 'active', 'user']
            );
        }

        echo "✅ Tạo 5 người dùng mẫu\n";
    }

    private function seedContests() {
        echo "🏆 Tạo cuộc thi mẫu...\n";

        $contests = [
            [
                'title' => 'Cuộc thi Ảnh đẹp 2024',
                'description' => 'Cuộc thi nhiếp ảnh dành cho tất cả mọi người yêu thích nghệ thuật nhiếp ảnh',
                'start_date' => '2024-01-01 00:00:00',
                'end_date' => '2024-12-31 23:59:59',
                'status' => 'active',
                'max_contestants' => 100,
                'voting_rules' => 'Mỗi IP chỉ được bình chọn 1 lần cho mỗi thí sinh',
                'prizes' => json_encode([
                    'first' => '10,000,000 VND',
                    'second' => '5,000,000 VND',
                    'third' => '3,000,000 VND'
                ])
            ],
            [
                'title' => 'Cuộc thi Video ngắn',
                'description' => 'Cuộc thi làm video ngắn sáng tạo, thời lượng tối đa 3 phút',
                'start_date' => '2024-03-01 00:00:00',
                'end_date' => '2024-06-30 23:59:59',
                'status' => 'voting',
                'max_contestants' => 50,
                'voting_rules' => 'Bình chọn công khai, mỗi người 3 phiếu',
                'prizes' => json_encode([
                    'first' => '15,000,000 VND',
                    'second' => '8,000,000 VND',
                    'third' => '4,000,000 VND'
                ])
            ],
            [
                'title' => 'Cuộc thi Thiết kế Logo',
                'description' => 'Thiết kế logo cho các thương hiệu mới',
                'start_date' => '2024-07-01 00:00:00',
                'end_date' => '2024-09-30 23:59:59',
                'status' => 'draft',
                'max_contestants' => 30,
                'voting_rules' => 'Bình chọn bởi ban giám khảo chuyên nghiệp',
                'prizes' => json_encode([
                    'first' => '20,000,000 VND',
                    'second' => '10,000,000 VND',
                    'third' => '5,000,000 VND'
                ])
            ]
        ];

        foreach ($contests as $contest) {
            $this->db->execute(
                "INSERT INTO contests (title, description, start_date, end_date, status, max_contestants, voting_rules, prizes, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $contest['title'],
                    $contest['description'],
                    $contest['start_date'],
                    $contest['end_date'],
                    $contest['status'],
                    $contest['max_contestants'],
                    $contest['voting_rules'],
                    $contest['prizes'],
                    1 // admin ID
                ]
            );
        }

        echo "✅ Tạo 3 cuộc thi mẫu\n";
    }

    private function seedContestants() {
        echo "🎭 Tạo thí sinh mẫu...\n";

        // Lấy contest IDs
        $contests = $this->db->fetchAll("SELECT id FROM contests WHERE status = 'active' OR status = 'voting'");
        $users = $this->db->fetchAll("SELECT id FROM users WHERE status = 'active'");

        if (empty($contests) || empty($users)) {
            echo "⚠️ Không có contests hoặc users để tạo contestants\n";
            return;
        }

        $contestantNames = [
            'Nguyễn Văn A', 'Trần Thị B', 'Lê Văn C', 'Phạm Thị D', 'Hoàng Văn E',
            'Vũ Thị F', 'Đặng Văn G', 'Bùi Thị H', 'Ngô Văn I', 'Dương Thị K'
        ];

        $contestantCount = 0;

        foreach ($contests as $contest) {
            $contestId = $contest['id'];
            $maxContestants = rand(5, 15);

            for ($i = 0; $i < $maxContestants && $i < count($users); $i++) {
                $userId = $users[$i]['id'];
                $name = $contestantNames[array_rand($contestantNames)];
                $status = rand(0, 10) > 2 ? 'approved' : 'pending'; // 80% approved
                $votes = $status === 'approved' ? rand(0, 100) : 0;

                $this->db->execute(
                    "INSERT INTO contestants (contest_id, user_id, name, description, status, total_votes)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $contestId,
                        $userId,
                        $name,
                        "Mô tả cho thí sinh $name",
                        $status,
                        $votes
                    ]
                );

                $contestantCount++;
            }
        }

        echo "✅ Tạo $contestantCount thí sinh mẫu\n";
    }

    private function seedNotificationTemplates() {
        echo "🔔 Tạo mẫu thông báo...\n";

        $templates = [
            [
                'name' => 'welcome_user',
                'title_template' => 'Chào mừng {username} đến với hệ thống!',
                'message_template' => 'Xin chào {full_name}, chúc mừng bạn đã đăng ký thành công tài khoản tại hệ thống quản lý cuộc thi.',
                'type' => 'success',
                'variables' => json_encode(['username', 'full_name'])
            ],
            [
                'name' => 'contest_approved',
                'title_template' => 'Đăng ký cuộc thi được phê duyệt',
                'message_template' => 'Chúc mừng! Đăng ký của bạn cho cuộc thi "{contest_title}" đã được phê duyệt. Bạn có thể bắt đầu tham gia.',
                'type' => 'success',
                'variables' => json_encode(['contest_title'])
            ],
            [
                'name' => 'contest_rejected',
                'title_template' => 'Đăng ký cuộc thi bị từ chối',
                'message_template' => 'Rất tiếc, đăng ký của bạn cho cuộc thi "{contest_title}" đã bị từ chối. Lý do: {reason}',
                'type' => 'warning',
                'variables' => json_encode(['contest_title', 'reason'])
            ],
            [
                'name' => 'voting_started',
                'title_template' => 'Cuộc thi đã bắt đầu bình chọn',
                'message_template' => 'Cuộc thi "{contest_title}" đã bắt đầu giai đoạn bình chọn. Hãy tham gia bình chọn cho các thí sinh yêu thích!',
                'type' => 'info',
                'variables' => json_encode(['contest_title'])
            ]
        ];

        foreach ($templates as $template) {
            $this->db->execute(
                "INSERT INTO notification_templates (name, title_template, message_template, type, variables, is_active, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [
                    $template['name'],
                    $template['title_template'],
                    $template['message_template'],
                    $template['type'],
                    $template['variables'],
                    true,
                    1 // admin ID
                ]
            );
        }

        echo "✅ Tạo 4 mẫu thông báo\n";
    }

    private function seedSystemSettings() {
        echo "⚙️ Tạo cài đặt hệ thống...\n";

        $settings = [
            [
                'setting_key' => 'site_name',
                'setting_value' => 'Contest Management System',
                'setting_type' => 'string',
                'description' => 'Tên website',
                'is_public' => true
            ],
            [
                'setting_key' => 'site_description',
                'setting_value' => 'Hệ thống quản lý cuộc thi trực tuyến',
                'setting_type' => 'string',
                'description' => 'Mô tả website',
                'is_public' => true
            ],
            [
                'setting_key' => 'max_file_size',
                'setting_value' => '10485760',
                'setting_type' => 'number',
                'description' => 'Kích thước file tối đa (bytes)',
                'is_public' => true
            ],
            [
                'setting_key' => 'maintenance_mode',
                'setting_value' => 'false',
                'setting_type' => 'boolean',
                'description' => 'Chế độ bảo trì',
                'is_public' => true
            ],
            [
                'setting_key' => 'default_language',
                'setting_value' => 'vi',
                'setting_type' => 'string',
                'description' => 'Ngôn ngữ mặc định',
                'is_public' => true
            ]
        ];

        foreach ($settings as $setting) {
            $this->db->execute(
                "INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public)
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $setting['setting_key'],
                    $setting['setting_value'],
                    $setting['setting_type'],
                    $setting['description'],
                    $setting['is_public']
                ]
            );
        }

        echo "✅ Tạo 5 cài đặt hệ thống\n";
    }
}

// Chạy seeder nếu được gọi trực tiếp
if (php_sapi_name() === 'cli') {
    $seeder = new DatabaseSeeder();
    $seeder->run();
}
?>
