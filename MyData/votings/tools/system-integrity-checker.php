<?php
/**
 * BVOTE 2025 - System Integrity Checker
 * Kiểm tra toàn vẹn hệ thống và tối ưu cấu trúc
 */

class SystemIntegrityChecker {
    private $projectRoot;
    private $issues = [];
    private $duplicates = [];
    private $orphanFiles = [];

    public function __construct($projectRoot) {
        $this->projectRoot = realpath($projectRoot);
    }

    /**
     * Kiểm tra toàn bộ hệ thống
     */
    public function checkSystem() {
        echo "🔍 BVOTE 2025 - SYSTEM INTEGRITY CHECK\n";
        echo "=====================================\n\n";

        $this->checkFileStructure();
        $this->findDuplicateFiles();
        $this->checkLinkIntegrity();
        $this->validatePermissions();
        $this->checkDatabaseConnections();
        $this->analyzeModuleStructure();

        $this->generateReport();
    }

    /**
     * Kiểm tra cấu trúc file
     */
    private function checkFileStructure() {
        echo "📁 Checking file structure...\n";

        $requiredDirs = [
            'modules/admin',
            'modules/user',
            'modules/api',
            'config',
            'data',
            'includes',
            'logs',
            'uploads',
            'puppeteer'
        ];

        foreach ($requiredDirs as $dir) {
            $path = $this->projectRoot . '/' . $dir;
            if (!is_dir($path)) {
                $this->issues[] = "Missing directory: $dir";
            } else {
                echo "✅ $dir exists\n";
            }
        }

        // Kiểm tra file quan trọng
        $requiredFiles = [
            'index.php',
            'user-interface.html',
            'admin-bot-control.html',
            'config/database.php',
            'includes/core.php'
        ];

        foreach ($requiredFiles as $file) {
            $path = $this->projectRoot . '/' . $file;
            if (!file_exists($path)) {
                $this->issues[] = "Missing file: $file";
            } else {
                echo "✅ $file exists\n";
            }
        }
    }

    /**
     * Tìm file trùng lặp
     */
    private function findDuplicateFiles() {
        echo "\n🔍 Scanning for duplicate files...\n";

        $files = $this->scanDirectory($this->projectRoot);
        $hashes = [];

        foreach ($files as $file) {
            if (is_file($file)) {
                $hash = md5_file($file);
                $relativePath = str_replace($this->projectRoot . '/', '', $file);

                if (isset($hashes[$hash])) {
                    $this->duplicates[] = [
                        'original' => $hashes[$hash],
                        'duplicate' => $relativePath
                    ];
                    echo "⚠️ Duplicate found: $relativePath\n";
                } else {
                    $hashes[$hash] = $relativePath;
                }
            }
        }

        if (empty($this->duplicates)) {
            echo "✅ No duplicates found\n";
        }
    }

    /**
     * Kiểm tra liên kết
     */
    private function checkLinkIntegrity() {
        echo "\n🔗 Checking link integrity...\n";

        // Kiểm tra include/require trong PHP
        $phpFiles = $this->findFilesByExtension('php');

        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $relativePath = str_replace($this->projectRoot . '/', '', $file);

            // Tìm include/require
            preg_match_all('/(?:include|require)(?:_once)?\s*\(?[\'"]([^\'"]+)[\'"]\)?/', $content, $matches);

            foreach ($matches[1] as $includePath) {
                $fullPath = dirname($file) . '/' . $includePath;
                if (!file_exists($fullPath)) {
                    $altPath = $this->projectRoot . '/' . $includePath;
                    if (!file_exists($altPath)) {
                        $this->issues[] = "Broken include in $relativePath: $includePath";
                    }
                }
            }
        }

        echo "✅ Link integrity check completed\n";
    }

    /**
     * Kiểm tra phân quyền
     */
    private function validatePermissions() {
        echo "\n🔐 Validating permissions...\n";

        $writableDirs = ['data', 'logs', 'uploads'];

        foreach ($writableDirs as $dir) {
            $path = $this->projectRoot . '/' . $dir;
            if (is_dir($path)) {
                if (!is_writable($path)) {
                    $this->issues[] = "Directory not writable: $dir";
                } else {
                    echo "✅ $dir is writable\n";
                }
            }
        }
    }

    /**
     * Kiểm tra kết nối database
     */
    private function checkDatabaseConnections() {
        echo "\n🗄️ Checking database connections...\n";

        $configFile = $this->projectRoot . '/config/database.php';
        if (file_exists($configFile)) {
            include $configFile;
            echo "✅ Database config loaded\n";
        } else {
            $this->issues[] = "Database config missing";
        }
    }

    /**
     * Phân tích cấu trúc module
     */
    private function analyzeModuleStructure() {
        echo "\n🏗️ Analyzing module structure...\n";

        $modules = ['admin', 'user', 'api'];

        foreach ($modules as $module) {
            $modulePath = $this->projectRoot . '/modules/' . $module;
            if (is_dir($modulePath)) {
                echo "📦 Module: $module\n";

                // Kiểm tra cấu trúc chuẩn
                $expectedDirs = ['assets', 'views', 'controllers'];
                foreach ($expectedDirs as $subDir) {
                    $subPath = $modulePath . '/' . $subDir;
                    if (is_dir($subPath)) {
                        echo "  ✅ $subDir/\n";
                    } else {
                        echo "  ⚠️ Missing: $subDir/\n";
                    }
                }
            }
        }
    }

    /**
     * Tạo báo cáo
     */
    private function generateReport() {
        echo "\n📊 SYSTEM INTEGRITY REPORT\n";
        echo "==========================\n";

        if (empty($this->issues)) {
            echo "✅ No critical issues found!\n";
        } else {
            echo "❌ Issues found:\n";
            foreach ($this->issues as $issue) {
                echo "  - $issue\n";
            }
        }

        if (!empty($this->duplicates)) {
            echo "\n📋 Duplicate files:\n";
            foreach ($this->duplicates as $dup) {
                echo "  - {$dup['duplicate']} (duplicate of {$dup['original']})\n";
            }
        }

        // Tạo file báo cáo
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'issues' => $this->issues,
            'duplicates' => $this->duplicates,
            'orphanFiles' => $this->orphanFiles
        ];

        file_put_contents($this->projectRoot . '/logs/integrity-report.json', json_encode($report, JSON_PRETTY_PRINT));
        echo "\n📝 Report saved to logs/integrity-report.json\n";
    }

    /**
     * Quét thư mục
     */
    private function scanDirectory($dir) {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Tìm file theo extension
     */
    private function findFilesByExtension($extension) {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->projectRoot));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === $extension) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}

// Chạy kiểm tra
if (php_sapi_name() === 'cli') {
    $projectRoot = dirname(__DIR__);
    $checker = new SystemIntegrityChecker($projectRoot);
    $checker->checkSystem();
}
?>
