<?php
/**
 * BVOTE System Health Check
 * Kiểm tra toàn bộ hệ thống trước khi deploy production
 */

require_once __DIR__ . '/../bootstrap.php';

use BVOTE\Core\App;
use BVOTE\Core\Database;
use BVOTE\Core\Logger;

class SystemHealthCheck {
    private $app;
    private $results = [];
    private $errors = [];
    private $warnings = [];

    public function __construct() {
        $this->app = App::getInstance();
    }

    /**
     * Chạy kiểm tra toàn bộ hệ thống
     */
    public function run(): array {
        echo "🔍 BVOTE System Health Check Starting...\n";
        echo "==========================================\n\n";

        $this->checkPhpEnvironment();
        $this->checkExtensions();
        $this->checkFilePermissions();
        $this->checkDatabase();
        $this->checkRedis();
        $this->checkSecurity();
        $this->checkPerformance();
        $this->checkDependencies();

        $this->generateReport();

        return [
            'results' => $this->results,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'overall_status' => empty($this->errors) ? 'HEALTHY' : 'UNHEALTHY'
        ];
    }

    /**
     * Kiểm tra môi trường PHP
     */
    private function checkPhpEnvironment(): void {
        echo "📋 PHP Environment Check...\n";

        // PHP Version
        $phpVersion = PHP_VERSION;
        if (version_compare($phpVersion, '7.4.0', '>=')) {
            $this->addResult('PHP Version', $phpVersion, 'OK');
        } else {
            $this->addError('PHP Version', "Required: 7.4+, Current: {$phpVersion}");
        }

        // Memory Limit
        $memoryLimit = ini_get('memory_limit');
        $memoryBytes = $this->parseMemoryLimit($memoryLimit);
        if ($memoryBytes >= 128 * 1024 * 1024) { // 128MB
            $this->addResult('Memory Limit', $memoryLimit, 'OK');
        } else {
            $this->addWarning('Memory Limit', "Recommended: 128M+, Current: {$memoryLimit}");
        }

        // Max Execution Time
        $maxExecTime = ini_get('max_execution_time');
        if ($maxExecTime >= 30) {
            $this->addResult('Max Execution Time', $maxExecTime . 's', 'OK');
        } else {
            $this->addWarning('Max Execution Time', "Recommended: 30s+, Current: {$maxExecTime}s");
        }

        // Upload Max Filesize
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $uploadBytes = $this->parseMemoryLimit($uploadMaxFilesize);
        if ($uploadBytes >= 50 * 1024 * 1024) { // 50MB
            $this->addResult('Upload Max Filesize', $uploadMaxFilesize, 'OK');
        } else {
            $this->addWarning('Upload Max Filesize', "Recommended: 50M+, Current: {$uploadMaxFilesize}");
        }

        echo "\n";
    }

    /**
     * Kiểm tra PHP Extensions
     */
    private function checkExtensions(): void {
        echo "🔌 PHP Extensions Check...\n";

        $requiredExtensions = [
            'pdo' => 'Database connectivity',
            'pdo_mysql' => 'MySQL database support',
            'json' => 'JSON processing',
            'mbstring' => 'Multibyte string support',
            'curl' => 'HTTP requests',
            'openssl' => 'Encryption and SSL',
            'gd' => 'Image processing',
            'zip' => 'File compression',
            'redis' => 'Redis caching (optional)',
            'memcached' => 'Memcached caching (optional)'
        ];

        foreach ($requiredExtensions as $ext => $description) {
            if (extension_loaded($ext)) {
                $this->addResult("Extension: {$ext}", $description, 'OK');
            } else {
                if (in_array($ext, ['redis', 'memcached'])) {
                    $this->addWarning("Extension: {$ext}", "Optional: {$description}");
                } else {
                    $this->addError("Extension: {$ext}", "Required: {$description}");
                }
            }
        }

        echo "\n";
    }

    /**
     * Kiểm tra quyền file
     */
    private function checkFilePermissions(): void {
        echo "📁 File Permissions Check...\n";

        $directories = [
            'uploads' => '0755',
            'storage/logs' => '0755',
            'storage/cache' => '0755',
            'storage/sessions' => '0755',
            '.env' => '0600'
        ];

        foreach ($directories as $path => $requiredPerms) {
            $fullPath = __DIR__ . '/../' . $path;

            if (file_exists($fullPath)) {
                $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
                if ($perms === $requiredPerms) {
                    $this->addResult("Permissions: {$path}", $perms, 'OK');
                } else {
                    $this->addWarning("Permissions: {$path}", "Required: {$requiredPerms}, Current: {$perms}");
                }

                // Check if writable
                if (is_writable($fullPath)) {
                    $this->addResult("Writable: {$path}", 'Yes', 'OK');
                } else {
                    $this->addError("Writable: {$path}", 'No - Directory must be writable');
                }
            } else {
                $this->addWarning("Path: {$path}", 'Directory does not exist');
            }
        }

        echo "\n";
    }

    /**
     * Kiểm tra database
     */
    private function checkDatabase(): void {
        echo "🗄️ Database Check...\n";

        try {
            $db = $this->app->getService('database');

            if ($db) {
                $this->addResult('Database Connection', 'Connected', 'OK');

                // Check if can query
                $result = $db->raw('SELECT 1 as test');
                if ($result) {
                    $this->addResult('Database Query', 'Working', 'OK');
                } else {
                    $this->addError('Database Query', 'Failed to execute test query');
                }

                // Check database info
                $info = $db->getInfo();
                $this->addResult('Database Host', $info['host'], 'OK');
                $this->addResult('Database Name', $info['database'], 'OK');
                $this->addResult('Database Port', $info['port'], 'OK');
                $this->addResult('Database Charset', $info['charset'], 'OK');

            } else {
                $this->addError('Database Connection', 'Database service not available');
            }

        } catch (Exception $e) {
            $this->addError('Database Connection', 'Failed: ' . $e->getMessage());
        }

        echo "\n";
    }

    /**
     * Kiểm tra Redis
     */
    private function checkRedis(): void {
        echo "🔴 Redis Check...\n";

        try {
            $redis = $this->app->getService('redis');

            if ($redis) {
                $this->addResult('Redis Connection', 'Connected', 'OK');

                // Test Redis operations
                $testKey = 'health_check_' . time();
                $testValue = 'test_value';

                $redis->set($testKey, $testValue, 60);
                $retrieved = $redis->get($testKey);
                $redis->del($testKey);

                if ($retrieved === $testValue) {
                    $this->addResult('Redis Operations', 'Working', 'OK');
                } else {
                    $this->addError('Redis Operations', 'Failed to read/write test data');
                }

            } else {
                $this->addWarning('Redis Connection', 'Redis not available (optional)');
            }

        } catch (Exception $e) {
            $this->addWarning('Redis Connection', 'Failed: ' . $e->getMessage());
        }

        echo "\n";
    }

    /**
     * Kiểm tra bảo mật
     */
    private function checkSecurity(): void {
        echo "🔒 Security Check...\n";

        // Check .env file
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $envPerms = substr(sprintf('%o', fileperms($envFile)), -4);
            if ($envPerms === '0600') {
                $this->addResult('.env Permissions', $envPerms, 'OK');
            } else {
                $this->addWarning('.env Permissions', "Recommended: 0600, Current: {$envPerms}");
            }
        } else {
            $this->addError('.env File', 'File does not exist');
        }

        // Check HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $this->addResult('HTTPS', 'Enabled', 'OK');
        } else {
            $this->addWarning('HTTPS', 'Not enabled - Required for production');
        }

        // Check security headers
        $headers = headers_list();
        $securityHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block'
        ];

        foreach ($securityHeaders as $header => $expectedValue) {
            $found = false;
            foreach ($headers as $h) {
                if (stripos($h, $header) !== false) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $this->addResult("Header: {$header}", 'Present', 'OK');
            } else {
                $this->addWarning("Header: {$header}", 'Missing');
            }
        }

        echo "\n";
    }

    /**
     * Kiểm tra performance
     */
    private function checkPerformance(): void {
        echo "⚡ Performance Check...\n";

        // Check OPcache
        if (function_exists('opcache_get_status')) {
            $opcacheStatus = opcache_get_status();
            if ($opcacheStatus && $opcacheStatus['opcache_enabled']) {
                $this->addResult('OPcache', 'Enabled', 'OK');
            } else {
                $this->addWarning('OPcache', 'Disabled - Enable for better performance');
            }
        } else {
            $this->addWarning('OPcache', 'Not available');
        }

        // Check memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->parseMemoryLimit($memoryLimit);

        $usagePercent = ($memoryUsage / $memoryLimitBytes) * 100;
        if ($usagePercent < 80) {
            $this->addResult('Memory Usage', round($usagePercent, 2) . '%', 'OK');
        } else {
            $this->addWarning('Memory Usage', round($usagePercent, 2) . '% - High usage');
        }

        echo "\n";
    }

    /**
     * Kiểm tra dependencies
     */
    private function checkDependencies(): void {
        echo "📦 Dependencies Check...\n";

        // Check Composer
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            $this->addResult('Composer Autoload', 'Available', 'OK');
        } else {
            $this->addError('Composer Autoload', 'Missing - Run: composer install');
        }

        // Check Node modules
        if (file_exists(__DIR__ . '/../node_modules')) {
            $this->addResult('Node Modules', 'Available', 'OK');
        } else {
            $this->addWarning('Node Modules', 'Missing - Run: npm install');
        }

        echo "\n";
    }

    /**
     * Tạo báo cáo
     */
    private function generateReport(): void {
        echo "📊 Health Check Report\n";
        echo "======================\n\n";

        $totalChecks = count($this->results) + count($this->errors) + count($this->warnings);
        $successRate = $totalChecks > 0 ? round((count($this->results) / $totalChecks) * 100, 2) : 0;

        echo "Total Checks: {$totalChecks}\n";
        echo "✅ Passed: " . count($this->results) . "\n";
        echo "⚠️ Warnings: " . count($this->warnings) . "\n";
        echo "❌ Errors: " . count($this->errors) . "\n";
        echo "Success Rate: {$successRate}%\n\n";

        if (!empty($this->errors)) {
            echo "❌ CRITICAL ERRORS:\n";
            foreach ($this->errors as $error) {
                echo "  - {$error['check']}: {$error['message']}\n";
            }
            echo "\n";
        }

        if (!empty($this->warnings)) {
            echo "⚠️ WARNINGS:\n";
            foreach ($this->warnings as $warning) {
                echo "  - {$warning['check']}: {$warning['message']}\n";
            }
            echo "\n";
        }

        if (empty($this->errors)) {
            echo "🎉 System is HEALTHY and ready for production!\n";
        } else {
            echo "🚨 System has CRITICAL issues that must be resolved before production!\n";
        }
    }

    /**
     * Helper methods
     */
    private function addResult(string $check, string $message, string $status): void {
        $this->results[] = ['check' => $check, 'message' => $message, 'status' => $status];
    }

    private function addWarning(string $check, string $message): void {
        $this->warnings[] = ['check' => $check, 'message' => $message];
    }

    private function addError(string $check, string $message): void {
        $this->errors[] = ['check' => $check, 'message' => $message];
    }

    private function parseMemoryLimit(string $limit): int {
        $unit = strtolower(substr($limit, -1));
        $value = (int)substr($limit, 0, -1);

        switch ($unit) {
            case 'k': return $value * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'g': return $value * 1024 * 1024 * 1024;
            default: return $value;
        }
    }
}

// Run health check
$healthCheck = new SystemHealthCheck();
$results = $healthCheck->run();

// Exit with appropriate code
exit(empty($results['errors']) ? 0 : 1);
