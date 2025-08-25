<?php
/**
 * User Home Page - BVOTE
 * Trang chủ sau khi đăng nhập thành công
 */
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: /user/login');
    exit;
}

// Lấy thông tin user
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Kết nối database
require_once __DIR__ . '/../includes/database.php';
$db = getConnection();

// Lấy contests nổi bật
$stmt = $db->prepare("
    SELECT id, title, description, start_date, end_date, status,
           (SELECT COUNT(*) FROM contestants WHERE contest_id = c.id) as contestant_count
    FROM contests c
    WHERE status = 'active'
    ORDER BY start_date DESC
    LIMIT 2
");
$stmt->execute();
$featuredContests = $stmt->fetchAll();

// Lấy contestants nổi bật
$stmt = $db->prepare("
    SELECT c.id, c.name, c.contestant_number, c.description,
           co.title as contest_title,
           (SELECT COUNT(*) FROM votes v WHERE v.contestant_id = c.id) as vote_count
    FROM contestants c
    JOIN contests co ON c.contest_id = co.id
    WHERE co.status = 'active'
    ORDER BY vote_count DESC
    LIMIT 3
");
$stmt->execute();
$topContestants = $stmt->fetchAll();

// Lấy bảng xếp hạng top 25
$stmt = $db->prepare("
    SELECT c.id, c.name, c.contestant_number, c.description,
           co.title as contest_title,
           (SELECT COUNT(*) FROM votes v WHERE v.contestant_id = c.id) as vote_count
    FROM contestants c
    JOIN contests co ON c.contest_id = co.id
    WHERE co.status = 'active'
    ORDER BY vote_count DESC
    LIMIT 25
");
$stmt->execute();
$top25Ranking = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - BVOTE</title>
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
                    <h1 class="ml-3 text-xl font-semibold text-gray-900">BVOTE</h1>
                </div>

                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Xin chào, <strong><?php echo htmlspecialchars($username); ?></strong></span>
                    <a href="/user/logout" class="text-red-600 hover:text-red-800">Đăng xuất</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Khu vực 1: 2 cuộc thi nổi bật -->
        <section class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Cuộc thi nổi bật</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($featuredContests as $contest): ?>
                <div class="bg-white rounded-lg shadow-sm border p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-start justify-between mb-4">
                        <h3 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($contest['title']); ?></h3>
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full">
                            <?php echo $contest['contestant_count']; ?> thí sinh
                        </span>
                    </div>
                    <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($contest['description']); ?></p>
                    <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                        <span>Bắt đầu: <?php echo date('d/m/Y', strtotime($contest['start_date'])); ?></span>
                        <span>Kết thúc: <?php echo date('d/m/Y', strtotime($contest['end_date'])); ?></span>
                    </div>
                    <a href="/contests/<?php echo $contest['id']; ?>"
                       class="inline-block bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors">
                        Xem chi tiết
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Khu vực 2: 3 thí sinh nổi bật -->
        <section class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Thí sinh nổi bật</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($topContestants as $contestant): ?>
                <div class="bg-white rounded-lg shadow-sm border p-6 text-center hover:shadow-md transition-shadow">
                    <div class="w-20 h-20 bg-gray-200 rounded-full mx-auto mb-4 flex items-center justify-center">
                        <i class="fas fa-user text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($contestant['name']); ?></h3>
                    <p class="text-gray-600 mb-2">SBD: <?php echo htmlspecialchars($contestant['contestant_number']); ?></p>
                    <p class="text-sm text-gray-500 mb-3"><?php echo htmlspecialchars($contestant['contest_title']); ?></p>
                    <div class="text-2xl font-bold text-blue-600 mb-4"><?php echo number_format($contestant['vote_count']); ?> lượt</div>
                    <button onclick="voteForContestant(<?php echo $contestant['id']; ?>)"
                            class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition-colors">
                        Bình chọn
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Khu vực 3: Bảng xếp hạng top 25 -->
        <section class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Bảng xếp hạng Top 25</h2>
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thứ hạng</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thí sinh</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SBD</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cuộc thi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lượt bình chọn</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($top25Ranking as $index => $contestant): ?>
                            <tr class="<?php echo $index < 3 ? 'bg-yellow-50' : ''; ?> hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php if ($index < 3): ?>
                                            <span class="text-2xl mr-2">
                                                <?php echo $index === 0 ? '🥇' : ($index === 1 ? '🥈' : '🥉'); ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="text-sm font-medium text-gray-900"><?php echo $index + 1; ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($contestant['name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($contestant['contestant_number']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($contestant['contest_title']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-blue-600"><?php echo number_format($contestant['vote_count']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button onclick="voteForContestant(<?php echo $contestant['id']; ?>)"
                                            class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition-colors">
                                        Bình chọn
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <!-- Toast Notification -->
    <div id="toast" class="fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span id="toast-message">Bình chọn thành công!</span>
        </div>
    </div>

    <script>
        function voteForContestant(contestantId) {
            // Gọi API bình chọn
            fetch('/api/votes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    contestant_id: contestantId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Bình chọn thành công!');
                    // Cập nhật UI realtime
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showToast('Lỗi: ' + (data.error || 'Không thể bình chọn'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Lỗi kết nối', 'error');
            });
        }

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');

            // Cập nhật nội dung và màu sắc
            toastMessage.textContent = message;
            if (type === 'error') {
                toast.className = 'fixed top-4 right-4 bg-red-600 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50';
            } else {
                toast.className = 'fixed top-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300 z-50';
            }

            // Hiển thị toast
            toast.classList.remove('translate-x-full');

            // Tự động ẩn sau 3 giây
            setTimeout(() => {
                toast.classList.add('translate-x-full');
            }, 3000);
        }

        // Auto-refresh để cập nhật realtime
        setInterval(() => {
            // Có thể thêm logic cập nhật realtime ở đây
        }, 30000); // 30 giây
    </script>
</body>
</html>
