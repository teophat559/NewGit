<?php
/**
 * Test Database Connection
 */

echo "🔍 Testing Database Connection...\n";

try {
    // Test basic database connection
    $pdo = new PDO(
        'mysql:host=localhost;dbname=bvote_system;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    echo "✅ Database connection successful\n";

    // Test if tables exist
    $tables = ['users', 'contests', 'contestants', 'votes'];

    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' does not exist\n";
        }
    }

} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";

    // Try to create database
    try {
        $pdo = new PDO(
            'mysql:host=localhost;charset=utf8mb4',
            'root',
            '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $pdo->exec("CREATE DATABASE IF NOT EXISTS bvote_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Database 'bvote_system' created successfully\n";

    } catch (PDOException $e2) {
        echo "❌ Failed to create database: " . $e2->getMessage() . "\n";
    }
}

echo "\n🔍 Testing PHP Extensions...\n";

$required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ Extension '$ext' loaded\n";
    } else {
        echo "❌ Extension '$ext' not loaded\n";
    }
}

echo "\n🔍 Testing File Permissions...\n";

$directories = ['storage', 'storage/logs', 'storage/cache', 'storage/sessions', 'uploads'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✅ Directory '$dir' is writable\n";
        } else {
            echo "❌ Directory '$dir' is not writable\n";
        }
    } else {
        echo "❌ Directory '$dir' does not exist\n";
    }
}

echo "\n🎯 Test completed!\n";
