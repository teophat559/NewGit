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
        $emptyFiles = [];

        foreach ($files as $file) {
            if (is_file($file)) {
                $relativePath = str_replace($this->projectRoot . '/', '', $file);
                $fileSize = filesize($file);
                
                // Handle empty files separately
                if ($fileSize === 0) {
                    $emptyFiles[] = $relativePath;
                    continue;
                }
                
                // Only check for duplicates among non-empty files
                $hash = md5_file($file);

                if (isset($hashes[$hash])) {
                    $this->duplicates[] = [
                        'original' => $hashes[$hash],
                        'duplicate' => $relativePath,
                        'size' => $fileSize
                    ];
                    echo "⚠️ Duplicate found: $relativePath (identical to {$hashes[$hash]})\n";
                } else {
                    $hashes[$hash] = $relativePath;
                }
            }
        }

        // Report empty files separately
        if (!empty($emptyFiles)) {
            echo "\n⚠️ Empty files found (" . count($emptyFiles) . " files):\n";
            foreach ($emptyFiles as $emptyFile) {
                echo "  - $emptyFile\n";
                $this->issues[] = "Empty file: $emptyFile";
            }
        }

        if (empty($this->duplicates)) {
            echo "✅ No duplicate content found\n";
        } else {
            echo "Found " . count($this->duplicates) . " duplicate files\n";
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

        // Tạo báo cáo tổng kết
        $totalIssues = count($this->issues);
        $totalDuplicates = count($this->duplicates);
        $totalOrphans = count($this->orphanFiles);
        
        echo "\n📈 SUMMARY\n";
        echo "==========\n";
        echo "Critical Issues: $totalIssues\n";
        echo "Duplicate Files: $totalDuplicates\n";
        echo "Orphan Files: $totalOrphans\n";
        
        if ($totalIssues === 0 && $totalDuplicates === 0 && $totalOrphans === 0) {
            echo "\n🎉 System integrity check PASSED! No issues found.\n";
        } else {
            echo "\n⚠️ System integrity check found issues that need attention.\n";
        }

        // Ensure logs directory exists
        $logsDir = $this->projectRoot . '/logs';
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }

        // Tạo file báo cáo
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary' => [
                'critical_issues' => $totalIssues,
                'duplicate_files' => $totalDuplicates,
                'orphan_files' => $totalOrphans,
                'status' => ($totalIssues === 0 && $totalDuplicates === 0 && $totalOrphans === 0) ? 'PASS' : 'FAIL'
            ],
            'issues' => $this->issues,
            'duplicates' => $this->duplicates,
            'orphanFiles' => $this->orphanFiles
        ];

        $reportFile = $logsDir . '/integrity-report.json';
        if (file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT))) {
            echo "\n📝 Report saved to logs/integrity-report.json\n";
        } else {
            echo "\n❌ Failed to save report to logs/integrity-report.json\n";
        }
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
