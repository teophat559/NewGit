<?php
/**
 * Contests Management Page - BVOTE Admin
 * Quản lý cuộc thi với CRUD và filter
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
    
    switch ($action) {
        case 'create':
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $startDate = $_POST['start_date'] ?? '';
            $endDate = $_POST['end_date'] ?? '';
            $maxVotes = (int)($_POST['max_votes_per_user'] ?? 1);
            
            $stmt = $db->prepare("
                INSERT INTO contests (title, description, start_date, end_date, max_votes_per_user, status) 
                VALUES (?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([$title, $description, $startDate, $endDate, $maxVotes]);
            
            header('Location: /admin/contest-management/contests?success=created');
            exit;
            
        case 'update':
            $id = (int)($_POST['contest_id'] ?? 0);
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $startDate = $_POST['start_date'] ?? '';
            $endDate = $_POST['end_date'] ?? '';
            $maxVotes = (int)($_POST['max_votes_per_user'] ?? 1);
            $status = $_POST['status'] ?? 'active';
            
            $stmt = $db->prepare("
                UPDATE contests 
                SET title = ?, description = ?, start_date = ?, end_date = ?, 
                    max_votes_per_user = ?, status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $startDate, $endDate, $maxVotes, $status, $id]);
            
            header('Location: /admin/contest-management/contests?success=updated');
            exit;
            
        case 'delete':
            $id = (int)($_POST['contest_id'] ?? 0);
            
            // Kiểm tra xem có contestants nào không
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM contestants WHERE contest_id = ?");
            $stmt->execute([$id]);
            $contestantCount = $stmt->fetch()['count'];
            
            if ($contestantCount > 0) {
                header('Location: /admin/contest-management/contests?error=has_contestants');
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM contests WHERE id = ?");
            $stmt->execute([$id]);
            
            header('Location: /admin/contest-management/contests?success=deleted');
            exit;
    }
}

// Lấy danh sách contests với filter
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where = "WHERE 1=1";
$params = [];

if ($status) {
    $where .= " AND status = ?";
    $params[] = $status;
}

if ($search) {
    $where .= " AND (title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $db->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM contestants WHERE contest_id = c.id) as contestant_count,
           (SELECT COUNT(*) FROM votes v JOIN contestants ct ON v.contestant_id = ct.id WHERE ct.contest_id = c.id) as total_votes
    FROM contests c 
    $where
    ORDER BY created_at DESC
");
$stmt->execute($params);
$contests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý cuộc thi - BVOTE Admin</title>
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
                    <a href="/admin/contest-management/contests" class="text-blue-600 border-b-2 border-blue-600 px-3 py-2">Cuộc thi</a>
                    <a href="/admin/user-management/appearance" class="text-gray-500 hover:text-gray-700 px-3 py-2">Người dùng</a>
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
                <h2 class="text-2xl font-bold text-gray-900">Quản lý cuộc thi</h2>
                <p class="text-gray-600">Tạo và quản lý các cuộc thi bình chọn</p>
            </div>
            <button onclick="showCreateModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Tạo cuộc thi mới
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                    <select name="status" class="border border-gray-300 rounded-md px-3 py-2">
                        <option value="">Tất cả</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Đang diễn ra</option>
                        <option value="upcoming" <?php echo $status === 'upcoming' ? 'selected' : ''; ?>>Sắp diễn ra</option>
                        <option value="ended" <?php echo $status === 'ended' ? 'selected' : ''; ?>>Đã kết thúc</option>
                        <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Nháp</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Tên cuộc thi..." 
                           class="border border-gray-300 rounded-md px-3 py-2">
                </div>
                
                <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Tìm kiếm
                </button>
                
                <a href="/admin/contest-management/contests" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors">
                    <i class="fas fa-times mr-2"></i>Xóa filter
                </a>
            </form>
        </div>

        <!-- Contests Table -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cuộc thi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thí sinh</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lượt bình chọn</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($contests as $contest): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($contest['title']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($contest['description'], 0, 100)); ?>...</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <div>Bắt đầu: <?php echo date('d/m/Y', strtotime($contest['start_date'])); ?></div>
                                    <div>Kết thúc: <?php echo date('d/m/Y', strtotime($contest['end_date'])); ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo $contest['contestant_count']; ?> thí sinh</div>
                                <div class="text-sm text-gray-500">Giới hạn: <?php echo $contest['max_votes_per_user']; ?> lượt/user</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-blue-600"><?php echo number_format($contest['total_votes']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $statusClass = '';
                                $statusText = '';
                                switch ($contest['status']) {
                                    case 'active':
                                        $statusClass = 'bg-green-100 text-green-800';
                                        $statusText = 'Đang diễn ra';
                                        break;
                                    case 'upcoming':
                                        $statusClass = 'bg-blue-100 text-blue-800';
                                        $statusText = 'Sắp diễn ra';
                                        break;
                                    case 'ended':
                                        $statusClass = 'bg-gray-100 text-gray-800';
                                        $statusText = 'Đã kết thúc';
                                        break;
                                    case 'draft':
                                        $statusClass = 'bg-yellow-100 text-yellow-800';
                                        $statusText = 'Nháp';
                                        break;
                                }
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($contest)); ?>)" 
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="/admin/contest-management/contestants?contest_id=<?php echo $contest['id']; ?>" 
                                   class="text-green-600 hover:text-green-900 mr-3">
                                    <i class="fas fa-users"></i>
                                </a>
                                <button onclick="deleteContest(<?php echo $contest['id']; ?>)" 
                                        class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </button>
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
                case 'created': $message = 'Cuộc thi đã được tạo thành công!'; break;
                case 'updated': $message = 'Cuộc thi đã được cập nhật!'; break;
                case 'deleted': $message = 'Cuộc thi đã được xóa!'; break;
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
                case 'has_contestants': $message = 'Không thể xóa cuộc thi đã có thí sinh!'; break;
            }
            ?>
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span><?php echo $message; ?></span>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Create/Edit Modal -->
    <div id="contestModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900 mb-4">Tạo cuộc thi mới</h3>
                
                <form id="contestForm" method="POST">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="contest_id" id="contestId">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tên cuộc thi</label>
                            <input type="text" name="title" id="contestTitle" required 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                            <textarea name="description" id="contestDescription" rows="3" 
                                      class="w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ngày bắt đầu</label>
                                <input type="datetime-local" name="start_date" id="startDate" required 
                                       class="w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ngày kết thúc</label>
                                <input type="datetime-local" name="end_date" id="endDate" required 
                                       class="w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Giới hạn bình chọn/user</label>
                            <input type="number" name="max_votes_per_user" id="maxVotes" min="1" value="1" required 
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        
                        <div id="statusField" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                            <select name="status" id="contestStatus" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="active">Đang diễn ra</option>
                                <option value="upcoming">Sắp diễn ra</option>
                                <option value="ended">Đã kết thúc</option>
                                <option value="draft">Nháp</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="hideModal()" 
                                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors">
                            Hủy
                        </button>
                        <button type="submit" 
                                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            Lưu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showCreateModal() {
            document.getElementById('modalTitle').textContent = 'Tạo cuộc thi mới';
            document.getElementById('formAction').value = 'create';
            document.getElementById('contestId').value = '';
            document.getElementById('contestTitle').value = '';
            document.getElementById('contestDescription').value = '';
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            document.getElementById('maxVotes').value = '1';
            document.getElementById('statusField').classList.add('hidden');
            
            document.getElementById('contestModal').classList.remove('hidden');
        }

        function showEditModal(contest) {
            document.getElementById('modalTitle').textContent = 'Chỉnh sửa cuộc thi';
            document.getElementById('formAction').value = 'update';
            document.getElementById('contestId').value = contest.id;
            document.getElementById('contestTitle').value = contest.title;
            document.getElementById('contestDescription').value = contest.description;
            document.getElementById('startDate').value = contest.start_date.replace(' ', 'T');
            document.getElementById('endDate').value = contest.end_date.replace(' ', 'T');
            document.getElementById('maxVotes').value = contest.max_votes_per_user;
            document.getElementById('contestStatus').value = contest.status;
            document.getElementById('statusField').classList.remove('hidden');
            
            document.getElementById('contestModal').classList.remove('hidden');
        }

        function hideModal() {
            document.getElementById('contestModal').classList.add('hidden');
        }

        function deleteContest(contestId) {
            if (confirm('Bạn có chắc chắn muốn xóa cuộc thi này?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="contest_id" value="${contestId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Auto-hide success/error messages
        setTimeout(() => {
            const messages = document.querySelectorAll('.fixed');
            messages.forEach(msg => msg.remove());
        }, 5000);

        // Close modal when clicking outside
        document.getElementById('contestModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideModal();
            }
        });
    </script>
</body>
</html>
