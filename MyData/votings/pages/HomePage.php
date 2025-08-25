<?php
/**
 * Homepage - BVOTE
 * Trang chủ cho user không đăng nhập với 3 khu vực chính
 */
require_once __DIR__ . '/../includes/database.php';

try {
    $db = getConnection();

    // Lấy 2 cuộc thi nổi bật
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

    // Lấy 3 thí sinh nổi bật
    $stmt = $db->prepare("
        SELECT c.id, c.name, c.contestant_number, c.description, c.image_path,
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

    // Lấy top 25 ranking
    $stmt = $db->prepare("
        SELECT c.id, c.name, c.contestant_number, c.image_path,
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

} catch (Exception $e) {
    // Fallback data nếu có lỗi database
    $featuredContests = [];
    $topContestants = [];
    $top25Ranking = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BVOTE - Hệ thống bình chọn trực tuyến</title>
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

                <nav class="flex space-x-8">
                    <a href="/" class="text-blue-600 border-b-2 border-blue-600 px-3 py-2">Trang chủ</a>
                    <a href="/contests" class="text-gray-500 hover:text-gray-700 px-3 py-2">Cuộc thi</a>
                    <a href="/rankings" class="text-gray-500 hover:text-gray-700 px-3 py-2">Bảng xếp hạng</a>
                    <a href="/about" class="text-gray-500 hover:text-gray-700 px-3 py-2">Giới thiệu</a>
                </nav>

                <div class="flex items-center space-x-4">
                    <a href="/user/login" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-sign-in-alt mr-2"></i>Đăng nhập
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Hero Section -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">
                Chào mừng đến với BVOTE
            </h1>
            <p class="text-xl text-gray-600 mb-8">
                Hệ thống bình chọn trực tuyến với Auto Login an toàn và minh bạch
            </p>
            <div class="flex justify-center space-x-4">
                <a href="/user/login" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors text-lg font-medium">
                    <i class="fas fa-play mr-2"></i>Bắt đầu bình chọn
                </a>
                <a href="/contests" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors text-lg font-medium">
                    <i class="fas fa-trophy mr-2"></i>Xem cuộc thi
                </a>
            </div>
        </div>

        <!-- Khu vực 1: Cuộc thi nổi bật -->
        <section class="mb-12">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Cuộc thi nổi bật</h2>
                <a href="/contests" class="text-blue-600 hover:text-blue-700 font-medium">
                    Xem tất cả <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($featuredContests as $contest): ?>
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden hover:shadow-md transition-shadow">
                    <div class="h-48 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                        <i class="fas fa-trophy text-6xl text-white opacity-80"></i>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">
                            <?php echo htmlspecialchars($contest['title']); ?>
                        </h3>
                        <p class="text-gray-600 mb-4">
                            <?php echo htmlspecialchars(substr($contest['description'], 0, 120)); ?>...
                        </p>
                        <div class="flex justify-between items-center mb-4">
                            <div class="text-sm text-gray-500">
                                <div>Bắt đầu: <?php echo date('d/m/Y', strtotime($contest['start_date'])); ?></div>
                                <div>Kết thúc: <?php echo date('d/m/Y', strtotime($contest['end_date'])); ?></div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-blue-600"><?php echo $contest['contestant_count']; ?></div>
                                <div class="text-sm text-gray-500">thí sinh</div>
                            </div>
                        </div>
                        <a href="/contests/<?php echo $contest['id']; ?>"
                           class="block w-full bg-blue-600 text-white text-center py-2 rounded hover:bg-blue-700 transition-colors">
                            Xem chi tiết
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Khu vực 2: Thí sinh nổi bật -->
        <section class="mb-12">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Thí sinh nổi bật</h2>
                <a href="/rankings" class="text-blue-600 hover:text-blue-700 font-medium">
                    Xem bảng xếp hạng <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($topContestants as $contestant): ?>
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden hover:shadow-md transition-shadow">
                    <div class="h-48 bg-gray-200 flex items-center justify-center overflow-hidden">
                        <?php if ($contestant['image_path']): ?>
                            <img src="/<?php echo htmlspecialchars($contestant['image_path']); ?>"
                                 alt="<?php echo htmlspecialchars($contestant['name']); ?>"
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user text-4xl text-gray-400"></i>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">
                            <?php echo htmlspecialchars($contestant['name']); ?>
                        </h3>
                        <p class="text-sm text-gray-500 mb-2">SBD: <?php echo htmlspecialchars($contestant['contestant_number']); ?></p>
                        <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars($contestant['contest_title']); ?></p>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600 mb-2">
                                <?php echo number_format($contestant['vote_count']); ?>
                            </div>
                            <div class="text-sm text-gray-500">lượt bình chọn</div>
                        </div>
                        <button onclick="requireLogin()"
                                class="w-full mt-3 bg-green-600 text-white py-2 rounded hover:bg-green-700 transition-colors">
                            Bình chọn
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Khu vực 3: Top 25 Ranking -->
        <section class="mb-12">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Top 25 Bảng xếp hạng</h2>
                <a href="/rankings" class="text-blue-600 hover:text-blue-700 font-medium">
                    Xem đầy đủ <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hạng</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thí sinh</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cuộc thi</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lượt bình chọn</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($top25Ranking as $index => $contestant): ?>
                            <tr class="hover:bg-gray-50 <?php echo $index < 3 ? 'bg-yellow-50' : ''; ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php if ($index < 3): ?>
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center mr-3">
                                                <?php if ($index === 0): ?>
                                                    <i class="fas fa-crown text-yellow-500 text-xl"></i>
                                                <?php elseif ($index === 1): ?>
                                                    <i class="fas fa-medal text-gray-400 text-xl"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-medal text-orange-500 text-xl"></i>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <span class="text-lg font-semibold <?php echo $index < 3 ? 'text-yellow-600' : 'text-gray-900'; ?>">
                                            #<?php echo $index + 1; ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <?php if ($contestant['image_path']): ?>
                                                <img class="h-10 w-10 rounded-full object-cover"
                                                     src="/<?php echo htmlspecialchars($contestant['image_path']); ?>"
                                                     alt="<?php echo htmlspecialchars($contestant['name']); ?>">
                                            <?php else: ?>
                                                <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                    <i class="fas fa-user text-gray-600"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($contestant['name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                SBD: <?php echo htmlspecialchars($contestant['contestant_number']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($contestant['contest_title']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-lg font-bold text-blue-600">
                                        <?php echo number_format($contestant['vote_count']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="requireLogin()"
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

        <!-- Features Section -->
        <section class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-8">Tại sao chọn BVOTE?</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="mx-auto h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Bảo mật cao</h3>
                    <p class="text-gray-600">Auto Login với OTP, rate limiting và audit log đầy đủ</p>
                </div>
                <div class="text-center">
                    <div class="mx-auto h-16 w-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-mobile-alt text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Dễ sử dụng</h3>
                    <p class="text-gray-600">Giao diện quen thuộc với các nền tảng phổ biến</p>
                </div>
                <div class="text-center">
                    <div class="mx-auto h-16 w-16 bg-purple-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-chart-line text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Minh bạch</h3>
                    <p class="text-gray-600">Thống kê realtime và báo cáo chi tiết</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <div class="h-8 w-8 bg-blue-600 rounded-full flex items-center justify-center mr-2">
                            <span class="text-white text-sm font-bold">BV</span>
                        </div>
                        <span class="text-xl font-bold">BVOTE</span>
                    </div>
                    <p class="text-gray-300 text-sm">
                        Hệ thống bình chọn trực tuyến với Auto Login an toàn và minh bạch.
                    </p>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Liên kết nhanh</h4>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="/" class="hover:text-white">Trang chủ</a></li>
                        <li><a href="/contests" class="hover:text-white">Cuộc thi</a></li>
                        <li><a href="/rankings" class="hover:text-white">Bảng xếp hạng</a></li>
                        <li><a href="/about" class="hover:text-white">Giới thiệu</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Hỗ trợ</h4>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li><a href="/help" class="hover:text-white">Hướng dẫn sử dụng</a></li>
                        <li><a href="/faq" class="hover:text-white">Câu hỏi thường gặp</a></li>
                        <li><a href="/contact" class="hover:text-white">Liên hệ</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Kết nối</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-300 hover:text-white">
                            <i class="fab fa-facebook text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white">
                            <i class="fab fa-instagram text-xl"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                <p class="text-gray-300 text-sm">
                    © 2024 BVOTE. Tất cả quyền được bảo lưu.
                </p>
            </div>
        </div>
    </footer>

    <script>
        function requireLogin() {
            if (confirm('Bạn cần đăng nhập để thực hiện chức năng này. Bạn có muốn chuyển đến trang đăng nhập?')) {
                window.location.href = '/user/login';
            }
        }

        // Smooth scroll cho các link nội bộ
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
