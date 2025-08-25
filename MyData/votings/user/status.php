<?php
// System status check for BVOTE 2025
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trạng thái hệ thống - BVOTE Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .glass-effect { backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900">
    <div class="max-w-4xl mx-auto p-6">
        <!-- Header -->
        <div class="bg-black/20 glass-effect border border-purple-500/30 rounded-xl p-6 mb-6">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-white">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white">BVOTE 2025 - System Status</h1>
            </div>
            <p class="text-gray-400">Hệ thống bình chọn trực tuyến PHP 8.3</p>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <a href="index.html" class="bg-purple-600 hover:bg-purple-700 text-white p-4 rounded-lg font-medium transition-colors text-center">
                🏠 Trang chủ User
            </a>
            <a href="../admin/dashboard.php" class="bg-blue-600 hover:bg-blue-700 text-white p-4 rounded-lg font-medium transition-colors text-center">
                ⚙️ Admin Dashboard
            </a>
            <a href="login.php" class="bg-green-600 hover:bg-green-700 text-white p-4 rounded-lg font-medium transition-colors text-center">
                🔑 Đăng nhập
            </a>
        </div>

        <!-- System Status -->
        <div class="bg-black/20 glass-effect border border-purple-500/30 rounded-xl p-6 mb-6">
            <h2 class="text-xl font-bold text-white mb-4">🔧 System Components</h2>
            <div class="space-y-3">
                <?php
                $checks = [
                    'PHP Version' => version_compare(PHP_VERSION, '8.0.0', '>=') ? '✅ ' . PHP_VERSION : '❌ ' . PHP_VERSION,
                    'Session Support' => function_exists('session_start') ? '✅ Enabled' : '❌ Disabled',
                    'PDO Extension' => extension_loaded('pdo') ? '✅ Loaded' : '❌ Not loaded',
                    'PDO MySQL' => extension_loaded('pdo_mysql') ? '✅ Available' : '❌ Not available',
                    'User Interface' => file_exists('index.html') ? '✅ Ready' : '❌ Missing',
                    'Login System' => file_exists('login.php') ? '✅ Ready' : '❌ Missing',
                    'Registration' => file_exists('register.php') ? '✅ Ready' : '❌ Missing',
                    'API Endpoint' => file_exists('api.php') ? '✅ Ready' : '❌ Missing',
                    'Database Config' => file_exists('database.php') ? '✅ Ready' : '❌ Missing'
                ];

                foreach ($checks as $component => $status) {
                    echo "<div class='flex items-center justify-between py-2 border-b border-purple-500/10 last:border-b-0'>";
                    echo "<span class='text-gray-300'>$component</span>";
                    echo "<span class='font-mono text-sm'>$status</span>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <!-- Database Status -->
        <div class="bg-black/20 glass-effect border border-purple-500/30 rounded-xl p-6 mb-6">
            <h2 class="text-xl font-bold text-white mb-4">🗄️ Database Status</h2>
            <?php
            try {
                require_once 'database.php';
                echo '<div class="text-green-400 mb-4">✅ Database connection successful</div>';

                // Check tables
                $tables = ['users', 'campaigns', 'contestants', 'votes'];
                foreach ($tables as $table) {
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                        $count = $stmt->fetchColumn();
                        echo "<div class='flex items-center justify-between py-2 border-b border-purple-500/10'>";
                        echo "<span class='text-gray-300'>Table: $table</span>";
                        echo "<span class='text-green-400'>✅ $count records</span>";
                        echo "</div>";
                    } catch (Exception $e) {
                        echo "<div class='flex items-center justify-between py-2 border-b border-purple-500/10'>";
                        echo "<span class='text-gray-300'>Table: $table</span>";
                        echo "<span class='text-red-400'>❌ Error</span>";
                        echo "</div>";
                    }
                }
            } catch (Exception $e) {
                echo '<div class="text-red-400">❌ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        </div>

        <!-- Demo Accounts -->
        <div class="bg-black/20 glass-effect border border-purple-500/30 rounded-xl p-6 mb-6">
            <h2 class="text-xl font-bold text-white mb-4">👤 Demo Accounts</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-purple-500/10 border border-purple-500/20 rounded-lg p-4">
                    <h3 class="font-bold text-white mb-2">Admin Account</h3>
                    <p class="text-sm text-gray-300 mb-2">Email: admin@bvote.com</p>
                    <p class="text-sm text-gray-300 mb-3">Password: admin123</p>
                    <a href="login.php" class="inline-block bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm transition-colors">
                        Login as Admin
                    </a>
                </div>

                <div class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-4">
                    <h3 class="font-bold text-white mb-2">User Account</h3>
                    <p class="text-sm text-gray-300 mb-2">Email: user@bvote.com</p>
                    <p class="text-sm text-gray-300 mb-3">Password: user123</p>
                    <a href="login.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm transition-colors">
                        Login as User
                    </a>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="bg-black/20 glass-effect border border-purple-500/30 rounded-xl p-6">
            <h2 class="text-xl font-bold text-white mb-4">🚀 Platform Features</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <h3 class="font-bold text-purple-400">User Interface</h3>
                    <ul class="text-sm text-gray-300 space-y-1">
                        <li>✅ Dark UI/Modern Design</li>
                        <li>✅ Responsive Layout</li>
                        <li>✅ Glass Morphism Effects</li>
                        <li>✅ Real-time Search</li>
                        <li>✅ Social Login Integration</li>
                        <li>✅ Contest Management</li>
                        <li>✅ Voting System</li>
                        <li>✅ Rankings & Activities</li>
                    </ul>
                </div>

                <div class="space-y-2">
                    <h3 class="font-bold text-purple-400">Admin Features</h3>
                    <ul class="text-sm text-gray-300 space-y-1">
                        <li>✅ Protected Admin Interface</li>
                        <li>✅ Comprehensive Dashboard</li>
                        <li>✅ Campaign Management</li>
                        <li>✅ Contestant Management</li>
                        <li>✅ Vote Tracking</li>
                        <li>✅ User Management</li>
                        <li>✅ Statistics & Reports</li>
                        <li>✅ System Settings</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-gray-400 text-sm">
                BVOTE 2025 - Professional Voting Platform |
                PHP <?php echo PHP_VERSION; ?> |
                <?php echo date('Y-m-d H:i:s'); ?>
            </p>
        </div>
    </div>
</body>
</html>
