<?php
/**
 * Test Database Connection with different credentials
 */

echo "🔍 Testing Database Connections...\n";
echo "==================================\n\n";

$connections = [
    ['host' => 'localhost', 'user' => 'root', 'pass' => ''],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'root'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'password'],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'root'],
    ['host' => '127.0.0.1', 'user' => 'root', 'pass' => 'password']
];

foreach ($connections as $conn) {
    echo "Testing: {$conn['host']} - {$conn['user']} - " . ($conn['pass'] ? '***' : 'empty') . "\n";

    try {
        $pdo = new PDO(
            "mysql:host={$conn['host']};charset=utf8mb4",
            $conn['user'],
            $conn['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        echo "  ✅ Connection successful!\n";

        // Test if we can create database
        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `bvote_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "  ✅ Database 'bvote_system' created/verified\n";

            // Test if we can use database
            $pdo->exec("USE `bvote_system`");
            echo "  ✅ Can use database 'bvote_system'\n";

            echo "  🎯 This connection will work for setup!\n\n";
            break;

        } catch (PDOException $e) {
            echo "  ❌ Database operation failed: " . $e->getMessage() . "\n\n";
        }

    } catch (PDOException $e) {
        echo "  ❌ Connection failed: " . $e->getMessage() . "\n\n";
    }
}

echo "🔍 Checking if MySQL service is running...\n";

// Check if MySQL port is open
$port = 3306;
$connection = @fsockopen('localhost', $port, $errno, $errstr, 5);
if (is_resource($connection)) {
    echo "✅ MySQL port $port is open\n";
    fclose($connection);
} else {
    echo "❌ MySQL port $port is not accessible\n";
    echo "   Error: $errstr ($errno)\n";
}

echo "\n🔍 Checking XAMPP/MySQL status...\n";

// Check common MySQL data directories
$mysqlDirs = [
    'C:\xampp\mysql\data',
    'C:\wamp\bin\mysql\mysql8.0.31\data',
    'C:\wamp64\bin\mysql\mysql8.0.31\data'
];

foreach ($mysqlDirs as $dir) {
    if (is_dir($dir)) {
        echo "✅ MySQL data directory found: $dir\n";
    }
}

echo "\n🎯 Connection test completed!\n";
