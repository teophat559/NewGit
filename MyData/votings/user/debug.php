<?php
// BVOTE 2025 - System Diagnostic & Debug Tool
// Chức năng: Kiểm tra và debug toàn bộ hệ thống

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Diagnostic - BVOTE 2025</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .test-result { font-family: 'Courier New', monospace; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen p-6">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-6 text-purple-400">🔧 BVOTE 2025 - System Diagnostic</h1>

        <!-- Interface Lock Status -->
        <div class="bg-gray-800 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4 text-yellow-400">🔒 Interface Lock Status</h2>
            <div class="test-result">
                <?php
                $lock_config = include(__DIR__ . '/../config/interface_lock_config.php');
                echo "<div class='success'>✅ Interface Protection: " . $lock_config['protection_status'] . "</div>";
                echo "<div class='success'>📅 Lock Date: " . $lock_config['lock_date'] . "</div>";
                echo "<div class='warning'>⚠️  Only backend development allowed</div>";
                ?>
            </div>
        </div>

        <!-- Database Tests -->
        <div class="bg-gray-800 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4 text-blue-400">🗄️ Database Tests</h2>
            <div class="test-result space-y-2">
                <?php
                try {
                    require_once 'database.php';
                    echo "<div class='success'>✅ Database connection established</div>";

                    // Test each table
                    $tables = ['users', 'campaigns', 'contestants', 'votes'];
                    foreach ($tables as $table) {
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                            $count = $stmt->fetchColumn();
                            echo "<div class='success'>✅ Table '$table': $count records</div>";
                        } catch (Exception $e) {
                            echo "<div class='error'>❌ Table '$table' error: " . $e->getMessage() . "</div>";
                        }
                    }

                    // Test sample data
                    $stmt = $pdo->query("SELECT COUNT(*) FROM campaigns WHERE status = 'active'");
                    $active_campaigns = $stmt->fetchColumn();
                    echo "<div class='success'>✅ Active campaigns: $active_campaigns</div>";

                } catch (Exception $e) {
                    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
                }
                ?>
            </div>
        </div>

        <!-- API Tests -->
        <div class="bg-gray-800 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4 text-green-400">🔌 API Endpoint Tests</h2>
            <div class="test-result space-y-2" id="api-tests">
                <div>Testing API endpoints...</div>
            </div>
        </div>

        <!-- Frontend Tests -->
        <div class="bg-gray-800 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4 text-purple-400">🎨 Frontend Tests</h2>
            <div class="test-result space-y-2">
                <?php
                $files_to_check = [
                    'index.html' => 'Main user interface',
                    'login.php' => 'Login page',
                    'register.php' => 'Registration page',
                    'api.php' => 'API endpoint',
                    'check_session.php' => 'Session checker',
                    'logout.php' => 'Logout handler'
                ];

                foreach ($files_to_check as $file => $description) {
                    if (file_exists($file)) {
                        $size = round(filesize($file) / 1024, 2);
                        echo "<div class='success'>✅ $description ($file): {$size}KB</div>";
                    } else {
                        echo "<div class='error'>❌ Missing: $description ($file)</div>";
                    }
                }
                ?>
            </div>
        </div>

        <!-- Session Tests -->
        <div class="bg-gray-800 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4 text-orange-400">👤 Session Tests</h2>
            <div class="test-result space-y-2">
                <?php
                echo "<div class='success'>✅ Session ID: " . session_id() . "</div>";

                if (isset($_SESSION['user_id'])) {
                    echo "<div class='success'>✅ User logged in: " . ($_SESSION['user_name'] ?? 'Unknown') . "</div>";
                    echo "<div class='success'>✅ User role: " . ($_SESSION['user_role'] ?? 'user') . "</div>";
                } else {
                    echo "<div class='warning'>⚠️  No user logged in</div>";
                }

                echo "<div class='success'>✅ Session save path: " . session_save_path() . "</div>";
                ?>
            </div>
        </div>

        <!-- Performance Tests -->
        <div class="bg-gray-800 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold mb-4 text-red-400">⚡ Performance Tests</h2>
            <div class="test-result space-y-2">
                <?php
                $start_time = microtime(true);

                // Test database query performance
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM contestants");
                    $db_time = microtime(true) - $start_time;
                    echo "<div class='success'>✅ Database query time: " . round($db_time * 1000, 2) . "ms</div>";
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Database query failed</div>";
                }

                // Test memory usage
                $memory = round(memory_get_usage() / 1024 / 1024, 2);
                $peak_memory = round(memory_get_peak_usage() / 1024 / 1024, 2);
                echo "<div class='success'>✅ Memory usage: {$memory}MB (Peak: {$peak_memory}MB)</div>";

                // Test PHP configuration
                echo "<div class='success'>✅ PHP Version: " . PHP_VERSION . "</div>";
                echo "<div class='success'>✅ Max execution time: " . ini_get('max_execution_time') . "s</div>";
                echo "<div class='success'>✅ Post max size: " . ini_get('post_max_size') . "</div>";
                ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-bold mb-4 text-cyan-400">🚀 Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="index.html" class="bg-purple-600 hover:bg-purple-700 p-3 rounded text-center transition-colors">
                    🏠 User Interface
                </a>
                <a href="login.php" class="bg-blue-600 hover:bg-blue-700 p-3 rounded text-center transition-colors">
                    🔑 Login Page
                </a>
                <a href="register.php" class="bg-green-600 hover:bg-green-700 p-3 rounded text-center transition-colors">
                    📝 Register Page
                </a>
                <a href="../admin/dashboard.php" class="bg-red-600 hover:bg-red-700 p-3 rounded text-center transition-colors">
                    ⚙️ Admin Panel
                </a>
            </div>
        </div>
    </div>

    <script>
        // Test API endpoints
        async function testAPIEndpoints() {
            const container = document.getElementById('api-tests');
            const endpoints = [
                { name: 'Get Contests', url: 'api.php?action=get_contests' },
                { name: 'Get Rankings', url: 'api.php?action=get_rankings&limit=5' },
                { name: 'Get Recent Activities', url: 'api.php?action=get_recent_activities&limit=10' },
                { name: 'Search', url: 'api.php?action=search&q=test' }
            ];

            container.innerHTML = '';

            for (const endpoint of endpoints) {
                try {
                    const start = performance.now();
                    const response = await fetch(endpoint.url);
                    const time = Math.round(performance.now() - start);

                    if (response.ok) {
                        const data = await response.json();
                        container.innerHTML += `<div class="success">✅ ${endpoint.name}: ${response.status} (${time}ms)</div>`;
                    } else {
                        container.innerHTML += `<div class="error">❌ ${endpoint.name}: ${response.status}</div>`;
                    }
                } catch (error) {
                    container.innerHTML += `<div class="error">❌ ${endpoint.name}: Error - ${error.message}</div>`;
                }
            }
        }

        // Run API tests when page loads
        document.addEventListener('DOMContentLoaded', testAPIEndpoints);

        // Auto-refresh every 30 seconds
        setInterval(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
