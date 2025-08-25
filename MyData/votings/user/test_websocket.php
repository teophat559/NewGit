<?php
/**
 * BVOTE 2025 - WebSocket Connection Test
 * Test hệ thống kết nối 2 chiều Admin ↔ User
 */

echo "🧪 BVOTE WebSocket Connection Test\n";
echo "=================================\n";

// Test 1: Socket Extension
echo "1. Checking PHP Socket Extension... ";
if (extension_loaded('sockets')) {
    echo "✅ OK\n";
} else {
    echo "❌ MISSING\n";
    exit(1);
}

// Test 2: Port Availability
echo "2. Checking port 8080 availability... ";
$socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    echo "❌ Cannot create socket\n";
    exit(1);
}

$bind = @socket_bind($socket, '0.0.0.0', 8080);
if ($bind === false) {
    echo "❌ Port 8080 is in use\n";
    socket_close($socket);
    exit(1);
} else {
    echo "✅ Available\n";
    socket_close($socket);
}

// Test 3: Database Connection
echo "3. Testing database connection... ";
try {
    require_once __DIR__ . '/../config/database.php';
    echo "✅ Connected\n";
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Required Tables
echo "4. Checking required tables... ";
try {
    $tables = [
        'realtime_activities',
        'admin_commands_log',
        'login_attempts',
        'votes'
    ];

    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            echo "❌ Missing table: $table\n";
            exit(1);
        }
    }
    echo "✅ All tables exist\n";
} catch (Exception $e) {
    echo "❌ Table check failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: WebSocket Server Class
echo "5. Testing WebSocket server class... ";
try {
    require_once __DIR__ . '/websocket_server.php';
    if (class_exists('BVoteRealtimeControlServer')) {
        echo "✅ Class loaded\n";
    } else {
        echo "❌ Class not found\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ All tests passed! WebSocket server ready to start.\n";
echo "🚀 Run: php start_websocket.php\n";
echo "🌐 Admin can connect to: ws://localhost:8080\n";
echo "👥 Users will connect automatically via web interface\n\n";

// Sample connection code
echo "📋 Sample Admin Connection (JavaScript):\n";
echo "----------------------------------------\n";
echo "const ws = new WebSocket('ws://localhost:8080');\n";
echo "ws.onopen = () => {\n";
echo "  ws.send(JSON.stringify({\n";
echo "    type: 'admin_register',\n";
echo "    admin_key: 'admin_master_key_2025',\n";
echo "    admin_id: 'admin_001'\n";
echo "  }));\n";
echo "};\n\n";

echo "📋 Sample User Registration (JavaScript):\n";
echo "-----------------------------------------\n";
echo "ws.send(JSON.stringify({\n";
echo "  type: 'user_register',\n";
echo "  email: 'user@example.com',\n";
echo "  user_id: 'user_001'\n";
echo "}));\n\n";

echo "🎛️  Admin Control Commands Available:\n";
echo "- approve_otp: Approve user OTP verification\n";
echo "- reject_login: Reject user login attempt\n";
echo "- send_notification: Send message to user\n";
echo "- terminate_session: Force disconnect user\n";
echo "- request_reverification: Request user re-auth\n";
echo "- get_system_status: Get current system stats\n\n";
?>
