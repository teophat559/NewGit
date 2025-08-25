<?php
/**
 * User Management Page - BVOTE Admin
 * Quản lý người dùng với thông tin cơ bản và thống kê
 */
session_start();

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login');
    exit;
}

require_once __DIR__ . '/../../../includes/database.php';
$db = getConnection();

// Xử lý actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    switch ($action) {
        case 'update_status':
            $status = $_POST['status'] ?? 'active';

            $stmt = $db->prepare("
                UPDATE users
                SET status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$status, $userId]);

            // Ghi audit log
            $stmt = $db->prepare("
                INSERT INTO audit_logs (actor_type, actor_id, action, details_json)
                VALUES ('admin', ?, 'update_user_status', ?)
            ");
            $stmt->execute([$_SESSION['admin_id'], json_encode(['user_id' => $userId, 'status' => $status])]);

            header('Location: /admin/user-management/appearance?success=status_updated');
            exit;

        case 'delete_user':
            // Kiểm tra xem user có votes nào không
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM votes WHERE user_id = ?");
            $stmt->execute([$userId]);
            $voteCount = $stmt->fetch()['count'];

            if ($voteCount > 0) {
                header('Location: /admin/user-management/appearance?error=has_votes');
                exit;
            }

            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);

            header('Location: /admin/user-management/appearance?success=user_deleted');
            exit;
    }
}

// Lấy danh sách users với filter
$status = $_GET['status'] ?? '';
$platform = $_GET['platform'] ?? '';
$search = $_GET['search'] ?? '';

$where = "WHERE 1=1";
$params = [];

if ($status) {
    $where .= " AND u.status = ?";
    $params[] = $status;
}

if ($platform) {
    $where .= " AND u.platform = ?";
    $params[] = $platform;
}

if ($search) {
    $where .= " AND (u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $db->prepare("
    SELECT u.*,
           (SELECT COUNT(*) FROM votes WHERE user_id = u.id) as vote_count,
           (SELECT MAX(created_at) FROM votes WHERE user_id = u.id) as last_vote,
           (SELECT COUNT(*) FROM login_requests WHERE user_hint = u.username AND status = 'APPROVED') as login_count
    FROM users u
    $where
    ORDER BY u.last_login DESC
    LIMIT 100
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Thống kê tổng quan
$stmt = $db->prepare("
    SELECT
        COUNT(*) as total_users,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_users,
        COUNT(CASE WHEN status = 'suspended' THEN 1 END) as suspended_users,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_users,
        AVG(CASE WHEN last_login IS NOT NULL THEN TIMESTAMPDIFF(DAY, last_login, NOW()) END) as avg_days_since_login
    FROM users
");
$stmt->execute();
$userStats = $stmt->fetch();

// Thống kê theo platform
$stmt = $db->prepare("
    SELECT platform, COUNT(*) as count
    FROM users
    GROUP BY platform
    ORDER BY count DESC
");
$stmt->execute();
$platformStats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng - BVOTE Admin</title>
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
                    <a href="/admin/user-management/appearance" class="text-blue-600 border-b-2 border-blue-600 px-3 py-2">Người dùng</a>
                    <a href="/admin/settings/web-config" class="text-gray-500 hover:text-gray-700 px-3 py-2">Cài đặt</a>
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
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Quản lý người dùng</h2>
                <p class="text-gray-600">Xem và quản lý tài khoản người dùng</p>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-500">Tổng: <?php echo $userStats['total_users']; ?> người dùng</span>
                <button onclick="exportUsers()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-download mr-2"></i>Xuất Excel
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Người dùng hoạt động</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $userStats['active_users']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <i class="fas fa-user-slash text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Tài khoản bị khóa</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $userStats['suspended_users']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Chờ xác minh</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $userStats['pending_users']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-calendar text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Ngày đăng nhập TB</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo round($userStats['avg_days_since_login'] ?? 0); ?> ngày</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Platform Distribution -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Phân bố theo nền tảng</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($platformStats as $stat): ?>
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600"><?php echo $stat['count']; ?></div>
                    <div class="text-sm text-gray-600 capitalize"><?php echo $stat['platform']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                    <select name="status" class="border border-gray-300 rounded-md px-3 py-2">
                        <option value="">Tất cả</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                        <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Bị khóa</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Chờ xác minh</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nền tảng</label>
                    <select name="platform" class="border border-gray-300 rounded-md px-3 py-2">
                        <option value="">Tất cả</option>
                        <option value="facebook" <?php echo $platform === 'facebook' ? 'selected' : ''; ?>>Facebook</option>
                        <option value="google" <?php echo $platform === 'google' ? 'selected' : ''; ?>>Google</option>
                        <option value="instagram" <?php echo $platform === 'instagram' ? 'selected' : ''; ?>>Instagram</option>
                        <option value="zalo" <?php echo $platform === 'zalo' ? 'selected' : ''; ?>>Zalo</option>
                        <option value="yahoo" <?php echo $platform === 'yahoo' ? 'selected' : ''; ?>>Yahoo</option>
                        <option value="microsoft" <?php echo $platform === 'microsoft' ? 'selected' : ''; ?>>Microsoft</option>
                        <option value="email" <?php echo $platform === 'email' ? 'selected' : ''; ?>>Email</option>
                        <option value="apple" <?php echo $platform === 'apple' ? 'selected' : ''; ?>>Apple</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Tên/Email..."
                           class="border border-gray-300 rounded-md px-3 py-2">
                </div>

                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Tìm kiếm
                </button>

                <a href="/admin/user-management/appearance" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors">
                    <i class="fas fa-times mr-2"></i>Xóa filter
                </a>
            </form>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Người dùng</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nền tảng</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hoạt động</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thống kê</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <?php if ($user['avatar']): ?>
                                            <img class="h-10 w-10 rounded-full" src="/<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar">
                                        <?php else: ?>
                                            <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                <i class="fas fa-user text-gray-600"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mr-2">
                                        <i class="fas fa-<?php echo getPlatformIcon($user['platform']); ?> text-gray-600"></i>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900 capitalize"><?php echo $user['platform']; ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo getUserStatusBadge($user['status']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <div>Đăng nhập cuối: <?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Chưa đăng nhập'; ?></div>
                                    <div class="text-xs text-gray-500">Lần đăng nhập: <?php echo $user['login_count']; ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <div class="font-semibold text-blue-600"><?php echo number_format($user['vote_count']); ?> lượt bình chọn</div>
                                    <?php if ($user['last_vote']): ?>
                                        <div class="text-xs text-gray-500">Lần cuối: <?php echo date('d/m/Y', strtotime($user['last_vote'])); ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button onclick="showStatusModal(<?php echo $user['id']; ?>, '<?php echo $user['status']; ?>')"
                                            class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['vote_count'] == 0): ?>
                                    <button onclick="deleteUser(<?php echo $user['id']; ?>)"
                                            class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
        <div class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            <?php
            $message = '';
            switch ($_GET['success']) {
                case 'status_updated': $message = 'Trạng thái người dùng đã được cập nhật!'; break;
                case 'user_deleted': $message = 'Người dùng đã được xóa!'; break;
            }
            ?>
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span><?php echo $message; ?></span>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
        <div class="fixed top-4 right-4 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            <?php
            $message = '';
            switch ($_GET['error']) {
                case 'has_votes': $message = 'Không thể xóa người dùng đã có lượt bình chọn!'; break;
            }
            ?>
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span><?php echo $message; ?></span>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Cập nhật trạng thái</h3>

                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="user_id" id="statusUserId">

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái mới</label>
                        <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="active">Hoạt động</option>
                            <option value="suspended">Bị khóa</option>
                            <option value="pending">Chờ xác minh</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="hideStatusModal()"
                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors">
                            Hủy
                        </button>
                        <button type="submit"
                                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            Cập nhật
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showStatusModal(userId, currentStatus) {
            document.getElementById('statusUserId').value = userId;
            document.querySelector('select[name="status"]').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
        }

        function hideStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        function deleteUser(userId) {
            if (confirm('Bạn có chắc chắn muốn xóa người dùng này?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function exportUsers() {
            // Tạo URL export với filter hiện tại
            const url = new URL(window.location.href);
            url.searchParams.set('export', 'excel');
            window.open(url.toString(), '_blank');
        }

        // Auto-hide success/error messages
        setTimeout(() => {
            const messages = document.querySelectorAll('.fixed');
            messages.forEach(msg => msg.remove());
        }, 5000);

        // Close modal when clicking outside
        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideStatusModal();
            }
        });
    </script>
</body>
</html>

<?php
function getPlatformIcon($platform) {
    $icons = [
        'facebook' => 'facebook',
        'google' => 'google',
        'instagram' => 'instagram',
        'zalo' => 'comment',
        'yahoo' => 'envelope',
        'microsoft' => 'windows',
        'email' => 'envelope',
        'apple' => 'apple'
    ];
    return $icons[$platform] ?? 'user';
}

function getUserStatusBadge($status) {
    $configs = [
        'active' => ['class' => 'bg-green-100 text-green-800', 'text' => 'Hoạt động'],
        'suspended' => ['class' => 'bg-red-100 text-red-800', 'text' => 'Bị khóa'],
        'pending' => ['class' => 'bg-yellow-100 text-yellow-800', 'text' => 'Chờ xác minh']
    ];

    $config = $configs[$status] ?? $configs['pending'];

    return sprintf(
        '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full %s">%s</span>',
        $config['class'],
        $config['text']
    );
}
?>
