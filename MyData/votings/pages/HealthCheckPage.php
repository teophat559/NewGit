<?php
/**
 * BVOTE Health Check Page
 * Kiểm tra trạng thái hệ thống
 */

require_once __DIR__ . '/../bootstrap.php';

use BVOTE\Core\App;
use BVOTE\Core\Logger;

// Set JSON response
header('Content-Type: application/json');

try {
    $app = App::getInstance();

    // Get system information
    $systemInfo = [
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => $app->getVersion(),
        'environment' => $app->getConfig('app.env'),
        'uptime' => microtime(true) - $app->getStartTime(),
        'memory_usage' => $app->getMemoryUsage(),
        'memory_limit' => ini_get('memory_limit'),
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ];

    // Check database
    try {
        $db = $app->getService('database');
        if ($db) {
            $dbInfo = $db->getInfo();
            $systemInfo['database'] = [
                'status' => 'connected',
                'host' => $dbInfo['host'],
                'database' => $dbInfo['database'],
                'port' => $dbInfo['port'],
                'charset' => $dbInfo['charset']
            ];

            // Test database query
            $testResult = $db->raw('SELECT 1 as test');
            if ($testResult) {
                $systemInfo['database']['query_test'] = 'passed';
            } else {
                $systemInfo['database']['query_test'] = 'failed';
                $systemInfo['status'] = 'degraded';
            }
        } else {
            $systemInfo['database'] = ['status' => 'not_available'];
            $systemInfo['status'] = 'degraded';
        }
    } catch (Exception $e) {
        $systemInfo['database'] = [
            'status' => 'error',
            'error' => $e->getMessage()
        ];
        $systemInfo['status'] = 'unhealthy';
    }

    // Check Redis
    try {
        $redis = $app->getService('redis');
        if ($redis) {
            $systemInfo['redis'] = [
                'status' => 'connected',
                'info' => $redis->info()
            ];

            // Test Redis operations
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';

            $redis->set($testKey, $testValue, 60);
            $retrieved = $redis->get($testKey);
            $redis->del($testKey);

            if ($retrieved === $testValue) {
                $systemInfo['redis']['operations_test'] = 'passed';
            } else {
                $systemInfo['redis']['operations_test'] = 'failed';
                $systemInfo['status'] = 'degraded';
            }
        } else {
            $systemInfo['redis'] = ['status' => 'not_available'];
        }
    } catch (Exception $e) {
        $systemInfo['redis'] = [
            'status' => 'error',
            'error' => $e->getMessage()
        ];
    }

    // Check cache
    try {
        $cache = $app->getService('cache');
        if ($cache) {
            $cacheStats = $cache->getStats();
            $systemInfo['cache'] = [
                'status' => 'available',
                'driver' => $cacheStats['driver'],
                'redis_available' => $cacheStats['redis_available']
            ];

            // Test cache operations
            $testKey = 'health_check_cache_' . time();
            $testValue = 'test_cache_value';

            $cache->set($testKey, $testValue, 60);
            $retrieved = $cache->get($testKey);
            $cache->delete($testKey);

            if ($retrieved === $testValue) {
                $systemInfo['cache']['operations_test'] = 'passed';
            } else {
                $systemInfo['cache']['operations_test'] = 'failed';
                $systemInfo['status'] = 'degraded';
            }
        } else {
            $systemInfo['cache'] = ['status' => 'not_available'];
        }
    } catch (Exception $e) {
        $systemInfo['cache'] = [
            'status' => 'error',
            'error' => $e->getMessage()
        ];
    }

    // Check session
    try {
        $session = $app->getService('session');
        if ($session) {
            $sessionStats = $session->getStats();
            $systemInfo['session'] = [
                'status' => 'available',
                'driver' => $sessionStats['driver'],
                'redis_available' => $sessionStats['redis_available']
            ];
        } else {
            $systemInfo['session'] = ['status' => 'not_available'];
        }
    } catch (Exception $e) {
        $systemInfo['session'] = [
            'status' => 'error',
            'error' => $e->getMessage()
        ];
    }

    // Check file permissions
    $directories = [
        'storage/logs' => '0755',
        'storage/cache' => '0755',
        'storage/sessions' => '0755',
        'uploads' => '0755'
    ];

    $systemInfo['file_permissions'] = [];
    foreach ($directories as $path => $requiredPerms) {
        $fullPath = __DIR__ . '/../' . $path;

        if (is_dir($fullPath)) {
            $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
            $writable = is_writable($fullPath);

            $systemInfo['file_permissions'][$path] = [
                'permissions' => $perms,
                'required' => $requiredPerms,
                'writable' => $writable,
                'status' => ($perms === $requiredPerms && $writable) ? 'ok' : 'warning'
            ];

            if ($perms !== $requiredPerms || !$writable) {
                $systemInfo['status'] = 'degraded';
            }
        } else {
            $systemInfo['file_permissions'][$path] = [
                'status' => 'missing',
                'error' => 'Directory does not exist'
            ];
            $systemInfo['status'] = 'degraded';
        }
    }

    // Check environment variables
    $requiredEnvVars = [
        'APP_ENV',
        'DB_HOST',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
        'JWT_SECRET',
        'ENCRYPTION_KEY'
    ];

    $systemInfo['environment'] = [];
    foreach ($requiredEnvVars as $var) {
        $value = $_ENV[$var] ?? null;
        $systemInfo['environment'][$var] = [
            'set' => $value !== null,
            'value' => $value ? (strlen($value) > 10 ? substr($value, 0, 10) . '...' : $value) : null
        ];

        if ($value === null) {
            $systemInfo['status'] = 'degraded';
        }
    }

    // Check PHP extensions
    $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'curl', 'openssl'];
    $systemInfo['php_extensions'] = [];

    foreach ($requiredExtensions as $ext) {
        $loaded = extension_loaded($ext);
        $systemInfo['php_extensions'][$ext] = [
            'loaded' => $loaded,
            'status' => $loaded ? 'ok' : 'missing'
        ];

        if (!$loaded) {
            $systemInfo['status'] = 'unhealthy';
        }
    }

    // Check disk space
    $diskFree = disk_free_space(__DIR__ . '/..');
    $diskTotal = disk_total_space(__DIR__ . '/..');
    $diskUsed = $diskTotal - $diskFree;
    $diskUsagePercent = ($diskUsed / $diskTotal) * 100;

    $systemInfo['disk'] = [
        'free' => formatBytes($diskFree),
        'total' => formatBytes($diskTotal),
        'used' => formatBytes($diskUsed),
        'usage_percent' => round($diskUsagePercent, 2),
        'status' => $diskUsagePercent < 90 ? 'ok' : 'warning'
    ];

    if ($diskUsagePercent >= 95) {
        $systemInfo['status'] = 'degraded';
    }

    // Set HTTP status code based on system status
    $httpStatus = match($systemInfo['status']) {
        'healthy' => 200,
        'degraded' => 200,
        'unhealthy' => 503,
        default => 200
    };

    http_response_code($httpStatus);

    // Log health check
    Logger::info('Health check completed', [
        'status' => $systemInfo['status'],
        'response_time' => microtime(true) - $app->getStartTime()
    ]);

    echo json_encode($systemInfo, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);

    $errorResponse = [
        'status' => 'error',
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];

    Logger::error('Health check failed: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);

    echo json_encode($errorResponse, JSON_PRETTY_PRINT);
}

/**
 * Format bytes to human readable format
 */
function formatBytes(int $bytes, int $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}
?>
