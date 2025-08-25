<?php
/**
 * Contestants Management Page - BVOTE Admin
 * Quản lý thí sinh với CRUD và upload ảnh
 */
session_start();

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login');
    exit;
}

require_once __DIR__ . '/../../../includes/database.php';
$db = getConnection();

$contestId = $_GET['contest_id'] ?? null;
if (!$contestId) {
    header('Location: /admin/contest-management/contests');
    exit;
}

// Lấy thông tin contest
$stmt = $db->prepare("SELECT * FROM contests WHERE id = ?");
$stmt->execute([$contestId]);
$contest = $stmt->fetch();

if (!$contest) {
    header('Location: /admin/contest-management/contests');
    exit;
}

// Xử lý actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            $name = $_POST['name'] ?? '';
            $contestantNumber = $_POST['contestant_number'] ?? '';
            $description = $_POST['description'] ?? '';

            // Xử lý upload ảnh
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../../uploads/images/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fileName = uniqid() . '.' . $fileExtension;
                $imagePath = 'uploads/images/' . $fileName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../../../' . $imagePath)) {
                    // Upload thành công
                } else {
                    $imagePath = null;
                }
            }

            $stmt = $db->prepare("
                INSERT INTO contestants (contest_id, name, contestant_number, description, image_path)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$contestId, $name, $contestantNumber, $description, $imagePath]);

            header('Location: /admin/contest-management/contestants?contest_id=' . $contestId . '&success=created');
            exit;

        case 'update':
            $id = (int)($_POST['contestant_id'] ?? 0);
            $name = $_POST['name'] ?? '';
            $contestantNumber = $_POST['contestant_number'] ?? '';
            $description = $_POST['description'] ?? '';

            // Xử lý upload ảnh mới
            $imagePath = $_POST['current_image'] ?? null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../../uploads/images/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fileName = uniqid() . '.' . $fileExtension;
                $imagePath = 'uploads/images/' . $fileName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../../../' . $imagePath)) {
                    // Upload thành công
                } else {
                    $imagePath = $_POST['current_image'] ?? null;
                }
            }

            $stmt = $db->prepare("
                UPDATE contestants
                SET name = ?, contestant_number = ?, description = ?, image_path = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$name, $contestantNumber, $description, $imagePath, $id]);

            header('Location: /admin/contest-management/contestants?contest_id=' . $contestId . '&success=updated');
            exit;

        case 'delete':
            $id = (int)($_POST['contestant_id'] ?? 0);

            // Lấy thông tin ảnh để xóa
            $stmt = $db->prepare("SELECT image_path FROM contestants WHERE id = ?");
            $stmt->execute([$id]);
            $contestant = $stmt->fetch();

            if ($contestant && $contestant['image_path']) {
                $imagePath = __DIR__ . '/../../../' . $contestant['image_path'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $stmt = $db->prepare("DELETE FROM contestants WHERE id = ?");
            $stmt->execute([$id]);

            header('Location: /admin/contest-management/contestants?contest_id=' . $contestId . '&success=deleted');
            exit;
    }
}

// Lấy danh sách contestants
$stmt = $db->prepare("
    SELECT c.*,
           (SELECT COUNT(*) FROM votes WHERE contestant_id = c.id) as vote_count
    FROM contestants c
    WHERE c.contest_id = ?
    ORDER BY c.contestant_number
");
$stmt->execute([$contestId]);
$contestants = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý thí sinh - <?php echo htmlspecialchars($contest['title']); ?> - BVOTE Admin</title>
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
                <h2 class="text-2xl font-bold text-gray-900">Quản lý thí sinh</h2>
                <p class="text-gray-600">Cuộc thi: <strong><?php echo htmlspecialchars($contest['title']); ?></strong></p>
                <p class="text-sm text-gray-500">Tổng: <?php echo count($contestants); ?> thí sinh</p>
            </div>
            <div class="flex space-x-3">
                <a href="/admin/contest-management/contests" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Quay lại
                </a>
                <button onclick="showCreateModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Thêm thí sinh
                </button>
                        </div>
                    </div>

        <!-- Contestants Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($contestants as $contestant): ?>
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden hover:shadow-md transition-shadow">
                <!-- Image -->
                <div class="h-48 bg-gray-200 flex items-center justify-center overflow-hidden">
                    <?php if ($contestant['image_path']): ?>
                        <img src="/<?php echo htmlspecialchars($contestant['image_path']); ?>"
                             alt="<?php echo htmlspecialchars($contestant['name']); ?>"
                             class="w-full h-full object-cover">
                    <?php else: ?>
                        <i class="fas fa-user text-4xl text-gray-400"></i>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($contestant['name']); ?></h3>
                    <p class="text-sm text-gray-600 mb-2">SBD: <?php echo htmlspecialchars($contestant['contestant_number']); ?></p>
                    <p class="text-sm text-gray-500 mb-3"><?php echo htmlspecialchars(substr($contestant['description'], 0, 100)); ?>...</p>

                    <div class="flex items-center justify-between mb-4">
                        <span class="text-sm text-gray-500">Lượt bình chọn:</span>
                        <span class="text-lg font-bold text-blue-600"><?php echo number_format($contestant['vote_count']); ?></span>
                    </div>

                    <!-- Actions -->
                    <div class="flex space-x-2">
                        <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($contestant)); ?>)"
                                class="flex-1 bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700 transition-colors">
                            <i class="fas fa-edit mr-1"></i>Sửa
                        </button>
                        <button onclick="deleteContestant(<?php echo $contestant['id']; ?>)"
                                class="flex-1 bg-red-600 text-white px-3 py-2 rounded text-sm hover:bg-red-700 transition-colors">
                            <i class="fas fa-trash mr-1"></i>Xóa
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
                </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
        <div class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            <?php
            $message = '';
            switch ($_GET['success']) {
                case 'created': $message = 'Thí sinh đã được thêm thành công!'; break;
                case 'updated': $message = 'Thí sinh đã được cập nhật!'; break;
                case 'deleted': $message = 'Thí sinh đã được xóa!'; break;
            }
            ?>
                                    <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span><?php echo $message; ?></span>
                                        </div>
                                        </div>
                                        <?php endif; ?>
    </main>

    <!-- Create/Edit Modal -->
    <div id="contestantModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 id="modalTitle" class="text-lg font-semibold text-gray-900 mb-4">Thêm thí sinh mới</h3>

                <form id="contestantForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="contestant_id" id="contestantId">
                    <input type="hidden" name="current_image" id="currentImage">

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tên thí sinh</label>
                            <input type="text" name="name" id="contestantName" required
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                                    </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Số báo danh</label>
                            <input type="text" name="contestant_number" id="contestantNumber" required
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                            <textarea name="description" id="contestantDescription" rows="3"
                                      class="w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                        </div>

                            <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ảnh thí sinh</label>
                            <input type="file" name="image" id="contestantImage" accept="image/*"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <div id="currentImagePreview" class="hidden mt-2">
                                <img id="imagePreview" src="" alt="Preview" class="w-20 h-20 object-cover rounded">
                            </div>
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
            document.getElementById('modalTitle').textContent = 'Thêm thí sinh mới';
            document.getElementById('formAction').value = 'create';
            document.getElementById('contestantId').value = '';
            document.getElementById('contestantName').value = '';
            document.getElementById('contestantNumber').value = '';
            document.getElementById('contestantDescription').value = '';
            document.getElementById('contestantImage').value = '';
            document.getElementById('currentImage').value = '';
            document.getElementById('currentImagePreview').classList.add('hidden');

            document.getElementById('contestantModal').classList.remove('hidden');
        }

        function showEditModal(contestant) {
            document.getElementById('modalTitle').textContent = 'Chỉnh sửa thí sinh';
            document.getElementById('formAction').value = 'update';
            document.getElementById('contestantId').value = contestant.id;
            document.getElementById('contestantName').value = contestant.name;
            document.getElementById('contestantNumber').value = contestant.contestant_number;
            document.getElementById('contestantDescription').value = contestant.description;
            document.getElementById('currentImage').value = contestant.image_path || '';

            // Hiển thị ảnh hiện tại
            if (contestant.image_path) {
                document.getElementById('imagePreview').src = '/' + contestant.image_path;
                document.getElementById('currentImagePreview').classList.remove('hidden');
            } else {
                document.getElementById('currentImagePreview').classList.add('hidden');
            }

            document.getElementById('contestantModal').classList.remove('hidden');
        }

        function hideModal() {
            document.getElementById('contestantModal').classList.add('hidden');
        }

        function deleteContestant(contestantId) {
            if (confirm('Bạn có chắc chắn muốn xóa thí sinh này?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="contestant_id" value="${contestantId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Preview ảnh khi chọn file
        document.getElementById('contestantImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('currentImagePreview').classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });

        // Auto-hide success messages
        setTimeout(() => {
            const messages = document.querySelectorAll('.fixed');
            messages.forEach(msg => msg.remove());
        }, 5000);

        // Close modal when clicking outside
        document.getElementById('contestantModal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideModal();
            }
        });
        </script>
</body>
</html>
