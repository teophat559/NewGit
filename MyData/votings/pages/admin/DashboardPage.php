<?php
/**
 * Admin Dashboard - BVOTE
 * Trang tổng quan với thống kê và biểu đồ
 */
session_start();

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login');
    exit;
}

require_once __DIR__ . '/../../includes/database.php';
$db = getConnection();

// Lấy thống kê tổng quan
$stats = [];

// Tổng contests
$stmt = $db->prepare("SELECT COUNT(*) as total FROM contests");
$stmt->execute();
$stats['total_contests'] = $stmt->fetch()['total'];

// Tổng contestants
$stmt = $db->prepare("SELECT COUNT(*) as total FROM contestants");
$stmt->execute();
$stats['total_contestants'] = $stmt->fetch()['total'];

// Tổng users
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
$stmt->execute();
$stats['total_users'] = $stmt->fetch()['total'];

// Tổng votes
$stmt = $db->prepare("SELECT COUNT(*) as total FROM votes");
$stmt->execute();
$stats['total_votes'] = $stmt->fetch()['total'];

// Thống kê Auto Login (24h gần nhất)
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_requests,
        COUNT(CASE WHEN status = 'APPROVED' THEN 1 END) as approved,
        COUNT(CASE WHEN status = 'REJECTED' THEN 1 END) as rejected,
        COUNT(CASE WHEN status = 'OTP_REQUIRED' THEN 1 END) as otp_required,
        AVG(CASE WHEN status IN ('APPROVED', 'REJECTED') THEN TIMESTAMPDIFF(SECOND, created_at, updated_at) END) as avg_response_time
    FROM login_requests 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$stmt->execute();
$autoLoginStats = $stmt->fetch();

// Thống kê theo platform
$stmt = $db->prepare("
    SELECT platform, COUNT(*) as count 
    FROM login_requests 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY platform
");
$stmt->execute();
$platformStats = $stmt->fetchAll();

// Thống kê votes theo ngày (7 ngày gần nhất)
$stmt = $db->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as vote_count
    FROM votes 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
");
$stmt->execute();
$dailyVotes = $stmt->fetchAll();

// Thống kê login success rate
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'APPROVED' THEN 1 END) as success
    FROM login_requests 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$stmt->execute();
$loginRate = $stmt->fetch();
$successRate = $loginRate['total'] > 0 ? round(($loginRate['success'] / $loginRate['total']) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BVOTE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a href="/admin/dashboard" class="text-blue-600 border-b-2 border-blue-600 px-3 py-2">Dashboard</a>
                    <a href="/admin/auto-login/management" class="text-gray-500 hover:text-gray-700 px-3 py-2">Auto Login</a>
                    <a href="/admin/contest-management/contests" class="text-gray-500 hover:text-gray-700 px-3 py-2">Cuộc thi</a>
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
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Contests -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-trophy text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Tổng cuộc thi</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_contests']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Contestants -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Tổng thí sinh</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_contestants']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Users -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-user-friends text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Tổng người dùng</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_users']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Votes -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-600">
                        <i class="fas fa-vote-yea text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Tổng phiếu bình chọn</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($stats['total_votes']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Auto Login Stats -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Auto Login Overview -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Thống kê Auto Login (24h)</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Tổng yêu cầu:</span>
                        <span class="font-semibold"><?php echo $autoLoginStats['total_requests']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Phê duyệt:</span>
                        <span class="font-semibold text-green-600"><?php echo $autoLoginStats['approved']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Từ chối:</span>
                        <span class="font-semibold text-red-600"><?php echo $autoLoginStats['rejected']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Yêu cầu OTP:</span>
                        <span class="font-semibold text-yellow-600"><?php echo $autoLoginStats['otp_required']; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Tỉ lệ thành công:</span>
                        <span class="font-semibold text-blue-600"><?php echo $successRate; ?>%</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Thời gian xử lý TB:</span>
                        <span class="font-semibold"><?php echo round($autoLoginStats['avg_response_time'] ?? 0, 1); ?>s</span>
                    </div>
                </div>
            </div>

            <!-- Platform Distribution -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Phân bố theo nền tảng</h3>
                <div class="space-y-3">
                    <?php foreach ($platformStats as $platform): ?>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 capitalize"><?php echo $platform['platform']; ?></span>
                        <span class="font-semibold"><?php echo $platform['count']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Daily Votes Chart -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Lượt bình chọn theo ngày</h3>
                <canvas id="dailyVotesChart" width="400" height="200"></canvas>
            </div>

            <!-- Login Success Rate Chart -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Tỉ lệ đăng nhập thành công</h3>
                <canvas id="loginRateChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Thao tác nhanh</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="/admin/auto-login/management" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-sign-in-alt text-blue-600"></i>
                    </div>
                    <div class="ml-3">
                        <p class="font-medium text-gray-900">Quản lý Auto Login</p>
                        <p class="text-sm text-gray-500">Xem và xử lý yêu cầu đăng nhập</p>
                    </div>
                </a>

                <a href="/admin/contest-management/contests" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="fas fa-trophy text-green-600"></i>
                    </div>
                    <div class="ml-3">
                        <p class="font-medium text-gray-900">Quản lý cuộc thi</p>
                        <p class="text-sm text-gray-500">Tạo và quản lý cuộc thi</p>
                    </div>
                </a>

                <a href="/admin/user-management/appearance" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <i class="fas fa-users text-purple-600"></i>
                    </div>
                    <div class="ml-3">
                        <p class="font-medium text-gray-900">Quản lý người dùng</p>
                        <p class="text-sm text-gray-500">Xem và quản lý tài khoản</p>
                    </div>
                </a>
            </div>
        </div>
    </main>

    <script>
        // Daily Votes Chart
        const dailyVotesCtx = document.getElementById('dailyVotesChart').getContext('2d');
        new Chart(dailyVotesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($dailyVotes, 'date')); ?>,
                datasets: [{
                    label: 'Lượt bình chọn',
                    data: <?php echo json_encode(array_column($dailyVotes, 'vote_count')); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Login Success Rate Chart
        const loginRateCtx = document.getElementById('loginRateChart').getContext('2d');
        new Chart(loginRateCtx, {
            type: 'doughnut',
            data: {
                labels: ['Thành công', 'Thất bại'],
                datasets: [{
                    data: [<?php echo $loginRate['success']; ?>, <?php echo $loginRate['total'] - $loginRate['success']; ?>],
                    backgroundColor: [
                        'rgb(34, 197, 94)',
                        'rgb(239, 68, 68)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Auto-refresh stats every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
