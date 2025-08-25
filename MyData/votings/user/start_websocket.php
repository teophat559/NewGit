<?php
/**
 * BVOTE 2025 - WebSocket Server Launcher
 * Khởi chạy hệ thống điều khiển 2 chiều Admin ↔ User
 */

// Kiểm tra PHP version
if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    die("❌ Requires PHP 8.3 or higher. Current version: " . PHP_VERSION . "\n");
}

// Kiểm tra required extensions
$requiredExtensions = ['sockets', 'json', 'pdo_mysql'];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        die("❌ Missing required extension: $ext\n");
    }
}

echo "🚀 BVOTE 2025 - Real-time Control System\n";
echo "=====================================\n";
echo "📅 Date: " . date('Y-m-d H:i:s') . "\n";
echo "🐘 PHP Version: " . PHP_VERSION . "\n";
echo "🌐 WebSocket Port: 8080\n";
echo "📊 Starting real-time admin control server...\n\n";

try {
    // Include database connection
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/websocket_server.php';

    // Create server instance
    $server = new BVoteRealtimeControlServer();

    echo "✅ Server ready for Admin ↔ User communication\n";
    echo "🔗 Admin can connect and monitor users in real-time\n";
    echo "👥 Users can be controlled and monitored by admins\n";
    echo "📝 All activities logged to database\n\n";
    echo "⚡ Press Ctrl+C to stop server\n";
    echo "=====================================\n\n";

} catch (Exception $e) {
    echo "❌ Server startup failed: " . $e->getMessage() . "\n";
    echo "📝 Check error logs for details\n";
    exit(1);
}
?>
