<?php
/**
 * BVOTE System Test Script
 * Kiểm tra toàn bộ hệ thống
 */

echo "🔍 BVOTE System Test Starting...\n";
echo "================================\n\n";

// Test 1: PHP Environment
echo "📋 Test 1: PHP Environment\n";
echo "---------------------------\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PHP SAPI: " . php_sapi_name() . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "s\n";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";
echo "Post Max Size: " . ini_get('post_max_size') . "\n\n";

// Test 2: Required Extensions
echo "📋 Test 2: Required Extensions\n";
echo "-------------------------------\n";
$required_extensions = [
    'pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl',
    'curl', 'gd', 'zip', 'fileinfo', 'session'
];

$all_extensions_loaded = true;
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ Extension '$ext' loaded\n";
    } else {
        echo "❌ Extension '$ext' not loaded\n";
        $all_extensions_loaded = false;
    }
}
echo "\n";

// Test 3: File System
echo "📋 Test 3: File System\n";
echo "----------------------\n";
$directories = [
    'storage' => 'Storage directory',
    'storage/logs' => 'Logs directory',
    'storage/cache' => 'Cache directory',
    'storage/sessions' => 'Sessions directory',
    'uploads' => 'Uploads directory',
    'templates' => 'Templates directory',
    'core' => 'Core classes directory',
    'services' => 'Services directory'
];

$all_directories_ok = true;
foreach ($directories as $dir => $description) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✅ $description: exists and writable\n";
        } else {
            echo "⚠️  $description: exists but not writable\n";
            $all_directories_ok = false;
        }
    } else {
        echo "❌ $description: does not exist\n";
        $all_directories_ok = false;
    }
}
echo "\n";

// Test 4: Core Files
echo "📋 Test 4: Core Files\n";
echo "---------------------\n";
$core_files = [
    'bootstrap.php' => 'Bootstrap file',
    'core/App.php' => 'App class',
    'core/Database.php' => 'Database class',
    'core/Auth.php' => 'Auth class',
    'core/Session.php' => 'Session class',
    'core/Cache.php' => 'Cache class',
    'core/Logger.php' => 'Logger class',
    'core/Validator.php' => 'Validator class',
    'router.php' => 'Router file',
    'vote.php' => 'Vote controller'
];

$all_core_files_ok = true;
foreach ($core_files as $file => $description) {
    if (file_exists($file)) {
        if (is_readable($file)) {
            echo "✅ $description: exists and readable\n";
        } else {
            echo "⚠️  $description: exists but not readable\n";
            $all_core_files_ok = false;
        }
    } else {
        echo "❌ $description: does not exist\n";
        $all_core_files_ok = false;
    }
}
echo "\n";

// Test 5: Composer Dependencies
echo "📋 Test 5: Composer Dependencies\n";
echo "--------------------------------\n";
if (file_exists('vendor/autoload.php')) {
    echo "✅ Composer autoload exists\n";

    // Test if we can load some key classes
    try {
        require_once 'vendor/autoload.php';
        echo "✅ Composer autoload loaded successfully\n";

        // Test some key classes
        $test_classes = [
            'Firebase\JWT\JWT',
            'Monolog\Logger',
            'PHPMailer\PHPMailer\PHPMailer',
            'Predis\Client'
        ];

        foreach ($test_classes as $class) {
            if (class_exists($class)) {
                echo "✅ Class '$class' available\n";
            } else {
                echo "❌ Class '$class' not available\n";
            }
        }

    } catch (Exception $e) {
        echo "❌ Composer autoload failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Composer autoload not found\n";
}
echo "\n";

// Test 6: Environment Configuration
echo "📋 Test 6: Environment Configuration\n";
echo "-----------------------------------\n";
if (file_exists('.env')) {
    echo "✅ .env file exists\n";

    // Load environment variables
    if (class_exists('Dotenv\Dotenv')) {
        try {
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
            $dotenv->load();
            echo "✅ Environment variables loaded\n";

            // Check key variables
            $key_vars = ['APP_NAME', 'APP_ENV', 'DB_HOST', 'DB_DATABASE'];
            foreach ($key_vars as $var) {
                if (isset($_ENV[$var])) {
                    echo "✅ Environment variable '$var' set\n";
                } else {
                    echo "⚠️  Environment variable '$var' not set\n";
                }
            }

        } catch (Exception $e) {
            echo "❌ Failed to load environment: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Dotenv class not available\n";
    }
} else {
    echo "❌ .env file not found\n";
}
echo "\n";

// Test 7: Database Connection (if possible)
echo "📋 Test 7: Database Connection\n";
echo "-------------------------------\n";
if (extension_loaded('pdo_mysql')) {
    try {
        // Try to connect to MySQL
        $pdo = new PDO(
            'mysql:host=localhost;charset=utf8mb4',
            'root',
            '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "✅ MySQL connection successful\n";

        // Try to create database
        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `bvote_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "✅ Database 'bvote_system' created/verified\n";
        } catch (PDOException $e) {
            echo "⚠️  Database creation failed: " . $e->getMessage() . "\n";
        }

    } catch (PDOException $e) {
        echo "❌ MySQL connection failed: " . $e->getMessage() . "\n";
        echo "   This is normal if MySQL service is not running\n";
    }
} else {
    echo "❌ PDO MySQL extension not available\n";
}
echo "\n";

// Test 8: Security Features
echo "📋 Test 8: Security Features\n";
echo "-----------------------------\n";

// Check if we can set security headers
if (!headers_sent()) {
    echo "✅ Can set security headers\n";

    // Test security headers
    $security_headers = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin'
    ];

    foreach ($security_headers as $header => $value) {
        header("$header: $value");
        echo "✅ Security header '$header' set\n";
    }
} else {
    echo "⚠️  Headers already sent, cannot set security headers\n";
}

// Test password hashing
if (function_exists('password_hash')) {
    $test_hash = password_hash('test123', PASSWORD_DEFAULT);
    if (password_verify('test123', $test_hash)) {
        echo "✅ Password hashing working correctly\n";
    } else {
        echo "❌ Password hashing not working\n";
    }
} else {
    echo "❌ Password hashing functions not available\n";
}
echo "\n";

// Test 9: Performance Features
echo "📋 Test 9: Performance Features\n";
echo "--------------------------------\n";

// Check OPcache
if (function_exists('opcache_get_status')) {
    $opcache_status = opcache_get_status();
    if ($opcache_status) {
        echo "✅ OPcache enabled and working\n";
        echo "   Memory usage: " . round($opcache_status['memory_usage']['used_memory'] / 1024 / 1024, 2) . "MB\n";
        echo "   Hit rate: " . round($opcache_status['opcache_statistics']['opcache_hit_rate'], 2) . "%\n";
    } else {
        echo "⚠️  OPcache enabled but not working\n";
    }
} else {
    echo "❌ OPcache not available\n";
}

// Check memory usage
$memory_usage = memory_get_usage(true);
$memory_limit = ini_get('memory_limit');
echo "✅ Current memory usage: " . round($memory_usage / 1024 / 1024, 2) . "MB\n";
echo "✅ Memory limit: $memory_limit\n";
echo "\n";

// Test 10: Overall System Status
echo "📋 Test 10: Overall System Status\n";
echo "----------------------------------\n";

$overall_score = 0;
$total_tests = 0;

// Calculate score based on critical components
if ($all_extensions_loaded) {
    $overall_score += 20;
    $total_tests++;
}
if ($all_directories_ok) {
    $overall_score += 15;
    $total_tests++;
}
if ($all_core_files_ok) {
    $overall_score += 25;
    $total_tests++;
}
if (file_exists('vendor/autoload.php')) {
    $overall_score += 20;
    $total_tests++;
}
if (file_exists('.env')) {
    $overall_score += 10;
    $total_tests++;
}
if (extension_loaded('pdo_mysql')) {
    $overall_score += 10;
    $total_tests++;
}

$percentage = $total_tests > 0 ? round(($overall_score / 100) * 100, 1) : 0;

echo "🎯 Overall System Score: $overall_score/100 ($percentage%)\n";

if ($percentage >= 90) {
    echo "🏆 Excellent! System is ready for production\n";
} elseif ($percentage >= 75) {
    echo "✅ Good! System is mostly ready with minor issues\n";
} elseif ($percentage >= 60) {
    echo "⚠️  Fair! System needs some fixes before production\n";
} else {
    echo "❌ Poor! System needs significant work before production\n";
}

echo "\n🎯 System test completed!\n";

// Recommendations
echo "\n📋 Recommendations:\n";
echo "-------------------\n";

if (!$all_extensions_loaded) {
    echo "• Enable missing PHP extensions\n";
}
if (!$all_directories_ok) {
    echo "• Fix directory permissions or create missing directories\n";
}
if (!$all_core_files_ok) {
    echo "• Create missing core files\n";
}
if (!file_exists('vendor/autoload.php')) {
    echo "• Run 'composer install' to install dependencies\n";
}
if (!file_exists('.env')) {
    echo "• Copy .env.example to .env and configure it\n";
}
if (!extension_loaded('pdo_mysql')) {
    echo "• Enable PDO MySQL extension\n";
}

echo "\n🚀 Next steps:\n";
echo "1. Fix any critical issues identified above\n";
echo "2. Configure database connection\n";
echo "3. Run database setup script\n";
echo "4. Test core functionality\n";
echo "5. Deploy to production environment\n";
