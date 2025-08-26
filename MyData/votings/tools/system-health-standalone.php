<?php
/**
 * BVOTE System Health Check (Standalone)
 * Comprehensive system health monitoring without dependencies
 */

class StandaloneHealthCheck {
    private $results = [];
    private $errors = [];
    private $warnings = [];
    private $projectRoot;

    public function __construct($projectRoot) {
        $this->projectRoot = realpath($projectRoot);
    }

    public function run(): array {
        echo "🔍 BVOTE Standalone System Health Check\n";
        echo "=======================================\n\n";

        $this->checkPhpEnvironment();
        $this->checkFileSystem();
        $this->checkPermissions();
        $this->checkConfiguration();
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

    private function checkPhpEnvironment(): void {
        echo "🐘 PHP Environment Check...\n";

        // PHP Version
        if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
            $this->addResult('PHP Version', PHP_VERSION, 'OK');
        } else {
            $this->addError('PHP Version', 'Required: 7.4+, Current: ' . PHP_VERSION);
        }

        // Memory limit
        $memoryLimit = ini_get('memory_limit');
        $memoryBytes = $this->convertToBytes($memoryLimit);
        if ($memoryBytes >= 128 * 1024 * 1024) {
            $this->addResult('Memory Limit', $memoryLimit, 'OK');
        } else {
            $this->addWarning('Memory Limit', "Recommended: 128M+, Current: $memoryLimit");
        }

        // Required extensions
        $requiredExtensions = [
            'pdo' => 'Database connectivity',
            'json' => 'JSON processing',
            'mbstring' => 'Multi-byte string handling',
            'curl' => 'HTTP client functionality',
            'openssl' => 'Encryption and security'
        ];

        foreach ($requiredExtensions as $ext => $description) {
            if (extension_loaded($ext)) {
                $this->addResult("Extension: $ext", 'Loaded', 'OK');
            } else {
                $this->addError("Extension: $ext", "Required: $description");
            }
        }

        // Optional extensions
        $optionalExtensions = [
            'redis' => 'Redis caching',
            'memcached' => 'Memcached support',
            'gd' => 'Image processing'
        ];

        foreach ($optionalExtensions as $ext => $description) {
            if (extension_loaded($ext)) {
                $this->addResult("Extension: $ext", 'Available', 'OK');
            } else {
                $this->addWarning("Extension: $ext", "Optional: $description");
            }
        }

        echo "\n";
    }

    private function checkFileSystem(): void {
        echo "📁 File System Check...\n";

        // Check critical directories
        $directories = [
            'config' => 'Configuration files',
            'data' => 'Data storage',
            'logs' => 'Log files',
            'uploads' => 'File uploads',
            'storage' => 'Application storage',
            'modules' => 'Application modules',
            'tools' => 'System tools'
        ];

        foreach ($directories as $dir => $description) {
            $path = $this->projectRoot . '/' . $dir;
            if (is_dir($path)) {
                $this->addResult("Directory: $dir", 'Exists', 'OK');
            } else {
                $this->addError("Directory: $dir", "Missing: $description");
            }
        }

        // Check critical files
        $files = [
            'index.php' => 'Main entry point',
            'bootstrap.php' => 'Bootstrap file',
            'composer.json' => 'Composer configuration',
            '.gitignore' => 'Git ignore rules',
            'README.md' => 'Documentation'
        ];

        foreach ($files as $file => $description) {
            $path = $this->projectRoot . '/' . $file;
            if (file_exists($path)) {
                $size = filesize($path);
                if ($size > 0) {
                    $this->addResult("File: $file", "Exists ({$size} bytes)", 'OK');
                } else {
                    $this->addWarning("File: $file", "Empty file");
                }
            } else {
                $this->addError("File: $file", "Missing: $description");
            }
        }

        echo "\n";
    }

    private function checkPermissions(): void {
        echo "🔐 Permissions Check...\n";

        $directories = [
            'logs' => '0755',
            'uploads' => '0755',
            'storage' => '0755',
            'data' => '0755'
        ];

        foreach ($directories as $dir => $requiredPerms) {
            $path = $this->projectRoot . '/' . $dir;
            
            if (is_dir($path)) {
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                if ($perms >= $requiredPerms) {
                    $this->addResult("Permissions: $dir", $perms, 'OK');
                } else {
                    $this->addWarning("Permissions: $dir", "Required: $requiredPerms, Current: $perms");
                }

                if (is_writable($path)) {
                    $this->addResult("Writable: $dir", 'Yes', 'OK');
                } else {
                    $this->addError("Writable: $dir", 'Directory must be writable');
                }
            } else {
                $this->addWarning("Directory: $dir", 'Directory does not exist');
            }
        }

        // Check .env permissions
        $envFile = $this->projectRoot . '/.env';
        if (file_exists($envFile)) {
            $envPerms = substr(sprintf('%o', fileperms($envFile)), -4);
            if ($envPerms === '0600') {
                $this->addResult('.env Permissions', $envPerms, 'OK');
            } else {
                $this->addWarning('.env Permissions', "Recommended: 0600, Current: $envPerms");
            }
        } else {
            $this->addWarning('.env File', 'File does not exist - using .env.example');
        }

        echo "\n";
    }

    private function checkConfiguration(): void {
        echo "⚙️ Configuration Check...\n";

        // Check database config
        $dbConfig = $this->projectRoot . '/config/database.php';
        if (file_exists($dbConfig)) {
            $this->addResult('Database Config', 'Available', 'OK');
            
            // Try to validate config structure
            try {
                $config = include $dbConfig;
                if (is_array($config) && isset($config['host'])) {
                    $this->addResult('Database Config Format', 'Valid', 'OK');
                } else {
                    $this->addWarning('Database Config Format', 'Invalid structure');
                }
            } catch (Exception $e) {
                $this->addError('Database Config', 'Error loading: ' . $e->getMessage());
            }
        } else {
            $this->addError('Database Config', 'config/database.php not found');
        }

        // Check environment file
        $envExample = $this->projectRoot . '/.env.example';
        if (file_exists($envExample)) {
            $this->addResult('.env.example', 'Available', 'OK');
        } else {
            $this->addWarning('.env.example', 'Template not found');
        }

        echo "\n";
    }

    private function checkSecurity(): void {
        echo "🔒 Security Check...\n";

        // Check for security headers capability
        if (!headers_sent()) {
            $this->addResult('Headers Control', 'Available', 'OK');
        } else {
            $this->addWarning('Headers Control', 'Headers already sent');
        }

        // Check session configuration
        if (session_status() === PHP_SESSION_NONE) {
            $this->addResult('Session Status', 'Ready', 'OK');
        } else {
            $this->addResult('Session Status', 'Active', 'OK');
        }

        // Check for sensitive files in web root
        $sensitiveFiles = ['.env', 'composer.lock', 'config.php'];
        foreach ($sensitiveFiles as $file) {
            $path = $this->projectRoot . '/' . $file;
            if (file_exists($path)) {
                $this->addWarning("Sensitive File: $file", 'Present in web root - ensure protected');
            }
        }

        // Check directory protection
        $protectedDirs = ['logs', 'config', 'storage'];
        foreach ($protectedDirs as $dir) {
            $indexFile = $this->projectRoot . '/' . $dir . '/index.php';
            if (file_exists($indexFile)) {
                $this->addResult("Protection: $dir", 'Index protection exists', 'OK');
            } else {
                $this->addWarning("Protection: $dir", 'No index protection found');
            }
        }

        echo "\n";
    }

    private function checkPerformance(): void {
        echo "⚡ Performance Check...\n";

        // Check OPcache
        if (extension_loaded('opcache') && opcache_get_status()) {
            $this->addResult('OPcache', 'Enabled', 'OK');
        } else {
            $this->addWarning('OPcache', 'Not enabled - consider enabling for production');
        }

        // Check memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryMB = round($memoryUsage / 1024 / 1024, 2);
        if ($memoryMB < 64) {
            $this->addResult('Memory Usage', "{$memoryMB}MB", 'OK');
        } else {
            $this->addWarning('Memory Usage', "{$memoryMB}MB - Monitor for optimization");
        }

        // Check execution time
        if (ini_get('max_execution_time') >= 30) {
            $this->addResult('Max Execution Time', ini_get('max_execution_time') . 's', 'OK');
        } else {
            $this->addWarning('Max Execution Time', 'Consider increasing for complex operations');
        }

        echo "\n";
    }

    private function checkDependencies(): void {
        echo "📦 Dependencies Check...\n";

        // Composer
        if (file_exists($this->projectRoot . '/vendor/autoload.php')) {
            $this->addResult('Composer Autoload', 'Available', 'OK');
        } else {
            $this->addError('Composer Autoload', 'Missing - Run: composer install');
        }

        // Node modules (if applicable)
        if (file_exists($this->projectRoot . '/package.json')) {
            if (file_exists($this->projectRoot . '/node_modules')) {
                $this->addResult('Node Modules', 'Available', 'OK');
            } else {
                $this->addWarning('Node Modules', 'Missing - Run: npm install');
            }
        }

        echo "\n";
    }

    private function generateReport(): void {
        echo "📊 Health Check Report\n";
        echo "======================\n\n";

        $totalChecks = count($this->results) + count($this->errors) + count($this->warnings);
        $successRate = $totalChecks > 0 ? round((count($this->results) / $totalChecks) * 100, 2) : 0;

        echo "Total Checks: $totalChecks\n";
        echo "Passed: " . count($this->results) . "\n";
        echo "Warnings: " . count($this->warnings) . "\n";
        echo "Errors: " . count($this->errors) . "\n";
        echo "Success Rate: {$successRate}%\n\n";

        if (!empty($this->errors)) {
            echo "❌ ERRORS:\n";
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

        $status = empty($this->errors) ? 'HEALTHY' : 'NEEDS ATTENTION';
        echo "🏥 Overall Status: $status\n";

        // Save report
        $this->saveReport($status, $successRate);
    }

    private function saveReport($status, $successRate): void {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => $status,
            'success_rate' => $successRate,
            'summary' => [
                'total_checks' => count($this->results) + count($this->errors) + count($this->warnings),
                'passed' => count($this->results),
                'warnings' => count($this->warnings),
                'errors' => count($this->errors)
            ],
            'results' => $this->results,
            'warnings' => $this->warnings,
            'errors' => $this->errors
        ];

        $logsDir = $this->projectRoot . '/logs';
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }

        $reportFile = $logsDir . '/health-report.json';
        if (file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT))) {
            echo "\n📝 Health report saved to logs/health-report.json\n";
        }
    }

    private function addResult($check, $value, $status): void {
        $this->results[] = [
            'check' => $check,
            'value' => $value,
            'status' => $status
        ];
        echo "✅ $check: $value\n";
    }

    private function addWarning($check, $message): void {
        $this->warnings[] = [
            'check' => $check,
            'message' => $message
        ];
        echo "⚠️ $check: $message\n";
    }

    private function addError($check, $message): void {
        $this->errors[] = [
            'check' => $check,
            'message' => $message
        ];
        echo "❌ $check: $message\n";
    }

    private function convertToBytes($value): int {
        $value = trim($value);
        $last = strtolower($value[strlen($value)-1]);
        $value = (int) $value;

        switch($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }

        return $value;
    }
}

// Run the health check
if (php_sapi_name() === 'cli') {
    $projectRoot = dirname(__DIR__);
    $healthCheck = new StandaloneHealthCheck($projectRoot);
    $result = $healthCheck->run();
    
    exit($result['overall_status'] === 'HEALTHY' ? 0 : 1);
} else {
    // Web interface
    $projectRoot = dirname(__DIR__);
    $healthCheck = new StandaloneHealthCheck($projectRoot);
    
    ob_start();
    $result = $healthCheck->run();
    $output = ob_get_clean();
    
    header('Content-Type: text/plain');
    echo $output;
}
?>