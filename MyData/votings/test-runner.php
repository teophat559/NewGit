<?php
/**
 * BVOTE Comprehensive Test Runner
 * Tests all available functionality in current environment
 */

class BVoteTestRunner {
    private $results = [];
    private $passedTests = 0;
    private $failedTests = 0;
    private $totalTests = 0;
    
    public function __construct() {
        echo "🔍 BVOTE COMPREHENSIVE TEST RUNNER\n";
        echo "==================================\n\n";
        echo "📋 Running all available tests to verify system functionality...\n\n";
    }
    
    public function runAllTests() {
        $this->testPhpEnvironment();
        $this->testFileSystem();
        $this->testCoreFiles();
        $this->testConfiguration();
        $this->testSecurityFeatures();
        $this->testWebInterface();
        $this->testBackendAPI();
        $this->testHealthEndpoints();
        $this->generateFinalReport();
    }
    
    private function testPhpEnvironment() {
        echo "📋 Test Suite 1: PHP Environment\n";
        echo "--------------------------------\n";
        
        // PHP Version Check
        $phpVersion = PHP_VERSION;
        $this->runTest("PHP Version ($phpVersion >= 7.4)", version_compare($phpVersion, '7.4.0', '>='));
        
        // Required Extensions
        $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl', 'curl', 'gd'];
        foreach ($requiredExtensions as $ext) {
            $this->runTest("Extension: $ext", extension_loaded($ext));
        }
        
        // Memory and Limits
        $memoryLimit = ini_get('memory_limit');
        $this->runTest("Memory Limit Set", $memoryLimit !== false);
        
        echo "\n";
    }
    
    private function testFileSystem() {
        echo "📋 Test Suite 2: File System\n";
        echo "----------------------------\n";
        
        // Required directories
        $directories = [
            'storage' => 'Storage directory',
            'storage/logs' => 'Logs directory', 
            'storage/cache' => 'Cache directory',
            'uploads' => 'Uploads directory',
            'core' => 'Core classes',
            'services' => 'Services directory'
        ];
        
        foreach ($directories as $dir => $description) {
            $exists = is_dir($dir);
            $writable = $exists && is_writable($dir);
            $this->runTest("$description exists", $exists);
            if ($exists) {
                $this->runTest("$description writable", $writable);
            }
        }
        
        echo "\n";
    }
    
    private function testCoreFiles() {
        echo "📋 Test Suite 3: Core Files\n";
        echo "---------------------------\n";
        
        $coreFiles = [
            'bootstrap.php' => 'Bootstrap',
            'config.php' => 'Configuration',
            'index.html' => 'Main interface',
            'vote.php' => 'Vote controller',
            'router.php' => 'Router',
            'core/App.php' => 'App class',
            'core/Database.php' => 'Database class',
            'backend/index.php' => 'Backend API'
        ];
        
        foreach ($coreFiles as $file => $description) {
            $exists = file_exists($file);
            $readable = $exists && is_readable($file);
            $this->runTest("$description exists", $exists);
            if ($exists) {
                $this->runTest("$description readable", $readable);
                
                // Test if PHP files have no syntax errors
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    $syntaxCheck = $this->checkPhpSyntax($file);
                    $this->runTest("$description syntax", $syntaxCheck);
                }
            }
        }
        
        echo "\n";
    }
    
    private function testConfiguration() {
        echo "📋 Test Suite 4: Configuration\n";
        echo "------------------------------\n";
        
        // .env file
        $envExists = file_exists('.env');
        $this->runTest(".env file exists", $envExists);
        
        if ($envExists) {
            $envContent = file_get_contents('.env');
            $this->runTest(".env has content", !empty($envContent));
            
            // Check for key configuration values
            $keySettings = [
                'APP_NAME' => 'Application name set',
                'APP_ENV' => 'Environment set',
                'DB_HOST' => 'Database host set',
                'DB_DATABASE' => 'Database name set'
            ];
            
            foreach ($keySettings as $setting => $description) {
                $hasSetting = strpos($envContent, $setting) !== false;
                $this->runTest($description, $hasSetting);
            }
        }
        
        // Config.php file
        $configExists = file_exists('config.php');
        $this->runTest("config.php exists", $configExists);
        
        if ($configExists) {
            $syntaxCheck = $this->checkPhpSyntax('config.php');
            $this->runTest("config.php syntax", $syntaxCheck);
        }
        
        echo "\n";
    }
    
    private function testSecurityFeatures() {
        echo "📋 Test Suite 5: Security Features\n";
        echo "----------------------------------\n";
        
        // Password hashing
        if (function_exists('password_hash')) {
            $testHash = password_hash('test123', PASSWORD_DEFAULT);
            $hashWorks = password_verify('test123', $testHash);
            $this->runTest("Password hashing", $hashWorks);
        }
        
        // OpenSSL
        $this->runTest("OpenSSL extension", extension_loaded('openssl'));
        
        // Session security functions
        $this->runTest("Session functions available", function_exists('session_start'));
        
        // File upload security
        $maxFileSize = ini_get('upload_max_filesize');
        $this->runTest("Upload limits configured", !empty($maxFileSize));
        
        echo "\n";
    }
    
    private function testWebInterface() {
        echo "📋 Test Suite 6: Web Interface\n";
        echo "------------------------------\n";
        
        // Main HTML file
        $indexExists = file_exists('index.html');
        $this->runTest("Main interface file exists", $indexExists);
        
        if ($indexExists) {
            $content = file_get_contents('index.html');
            $this->runTest("HTML content not empty", !empty($content));
            $this->runTest("Contains BVOTE branding", strpos($content, 'BVOTE') !== false);
            $this->runTest("Contains meta viewport", strpos($content, 'viewport') !== false);
            $this->runTest("Contains CSS framework", strpos($content, 'tailwindcss') !== false);
        }
        
        // Assets and public files
        $this->runTest("Assets directory exists", is_dir('assets'));
        $this->runTest("Public directory exists", is_dir('public'));
        
        echo "\n";
    }
    
    private function testBackendAPI() {
        echo "📋 Test Suite 7: Backend API\n";
        echo "----------------------------\n";
        
        // Backend files
        $backendFiles = [
            'backend/index.php' => 'Main API router',
            'backend/services/db.php' => 'Database service',
        ];
        
        foreach ($backendFiles as $file => $description) {
            if (file_exists($file)) {
                $this->runTest("$description exists", true);
                $syntaxCheck = $this->checkPhpSyntax($file);
                $this->runTest("$description syntax", $syntaxCheck);
            } else {
                $this->runTest("$description exists", false);
            }
        }
        
        // API routes directories
        $routesDir = 'backend/routes';
        $this->runTest("Routes directory exists", is_dir($routesDir));
        
        echo "\n";
    }
    
    private function testHealthEndpoints() {
        echo "📋 Test Suite 8: Health Check Endpoints\n";
        echo "---------------------------------------\n";
        
        // Health check files
        $healthFiles = [
            'pages/HealthCheckPage.php' => 'Health check page',
            'tools/system-health-check.php' => 'System health check',
            'backend/services/wait-health.php' => 'Backend health service'
        ];
        
        foreach ($healthFiles as $file => $description) {
            if (file_exists($file)) {
                $this->runTest("$description exists", true);
                $syntaxCheck = $this->checkPhpSyntax($file);
                $this->runTest("$description syntax", $syntaxCheck);
            } else {
                $this->runTest("$description exists", false);
            }
        }
        
        echo "\n";
    }
    
    private function checkPhpSyntax($file) {
        $output = [];
        $returnCode = 0;
        exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnCode);
        return $returnCode === 0;
    }
    
    private function runTest($testName, $passed) {
        $this->totalTests++;
        
        if ($passed) {
            echo "  ✅ $testName\n";
            $this->passedTests++;
            $this->results[] = ['test' => $testName, 'status' => 'PASS'];
        } else {
            echo "  ❌ $testName\n";
            $this->failedTests++;
            $this->results[] = ['test' => $testName, 'status' => 'FAIL'];
        }
    }
    
    private function generateFinalReport() {
        echo "📋 FINAL TEST REPORT\n";
        echo "===================\n\n";
        
        $passRate = $this->totalTests > 0 ? round(($this->passedTests / $this->totalTests) * 100, 1) : 0;
        
        echo "🎯 Test Results Summary:\n";
        echo "  Total Tests: {$this->totalTests}\n";
        echo "  Passed: {$this->passedTests}\n";
        echo "  Failed: {$this->failedTests}\n";
        echo "  Pass Rate: {$passRate}%\n\n";
        
        // System status based on pass rate
        if ($passRate >= 90) {
            echo "🏆 EXCELLENT: System is ready for production!\n";
            $status = "READY";
        } elseif ($passRate >= 80) {
            echo "✅ GOOD: System is mostly ready with minor issues\n";
            $status = "MOSTLY_READY";
        } elseif ($passRate >= 70) {
            echo "⚠️  FAIR: System needs some fixes before production\n";
            $status = "NEEDS_WORK";
        } else {
            echo "❌ POOR: System needs significant work\n";
            $status = "NOT_READY";
        }
        
        echo "\n📋 Next Steps Based on Results:\n";
        echo "-------------------------------\n";
        
        if ($this->failedTests > 0) {
            echo "🔧 Issues to Address:\n";
            foreach ($this->results as $result) {
                if ($result['status'] === 'FAIL') {
                    echo "  • Fix: {$result['test']}\n";
                }
            }
            echo "\n";
        }
        
        echo "🚀 Recommended Actions:\n";
        if ($passRate < 80) {
            echo "  1. Run 'composer install' to install dependencies\n";
            echo "  2. Set up database connection\n";
            echo "  3. Fix any missing files or directories\n";
        }
        echo "  4. Test web interface with built-in server\n";
        echo "  5. Test backend API endpoints\n";
        echo "  6. Run full integration tests\n";
        
        echo "\n🎯 System Status: $status\n";
        echo "🕐 Test completed at: " . date('Y-m-d H:i:s') . "\n";
    }
}

// Run the comprehensive tests
$testRunner = new BVoteTestRunner();
$testRunner->runAllTests();