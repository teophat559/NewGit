<?php
/**
 * BVOTE Master Integrity Tool
 * Comprehensive system integrity management
 */

class MasterIntegrityTool {
    private $projectRoot;
    private $tools = [];

    public function __construct($projectRoot) {
        $this->projectRoot = realpath($projectRoot);
        $this->initializeTools();
    }

    private function initializeTools() {
        $this->tools = [
            'integrity' => [
                'name' => 'System Integrity Checker',
                'file' => 'system-integrity-checker.php',
                'description' => 'Checks file structure, duplicates, and link integrity'
            ],
            'health' => [
                'name' => 'System Health Check',
                'file' => 'system-health-standalone.php', 
                'description' => 'Comprehensive system health monitoring'
            ],
            'repair' => [
                'name' => 'System Repair Tool',
                'file' => 'system-integrity-repair.php',
                'description' => 'Automatically fixes common system issues'
            ],
            'final' => [
                'name' => 'Final System Test',
                'file' => 'final-test.php',
                'description' => 'Complete system validation test'
            ]
        ];
    }

    public function run($action = 'menu') {
        echo "🏆 BVOTE MASTER INTEGRITY TOOL\n";
        echo "==============================\n\n";

        switch ($action) {
            case 'all':
                $this->runAllChecks();
                break;
            case 'integrity':
                $this->runTool('integrity');
                break;
            case 'health':
                $this->runTool('health');
                break;
            case 'repair':
                $this->runTool('repair');
                break;
            case 'final':
                $this->runTool('final');
                break;
            case 'dashboard':
                $this->openDashboard();
                break;
            case 'status':
                $this->showStatus();
                break;
            default:
                $this->showMenu();
        }
    }

    private function runAllChecks() {
        echo "🚀 Running Complete System Integrity Check Suite\n";
        echo "=================================================\n\n";

        $results = [];

        // 1. System Health Check
        echo "1️⃣ Running System Health Check...\n";
        echo "-----------------------------------\n";
        $results['health'] = $this->runTool('health', false);
        echo "\n";

        // 2. System Integrity Check
        echo "2️⃣ Running System Integrity Check...\n";
        echo "-------------------------------------\n";
        $results['integrity'] = $this->runTool('integrity', false);
        echo "\n";

        // 3. Repair if needed
        if ($this->hasIssues()) {
            echo "3️⃣ Issues detected, running repair tool...\n";
            echo "-------------------------------------------\n";
            $results['repair'] = $this->runTool('repair', false);
            echo "\n";

            // 4. Re-check after repair
            echo "4️⃣ Re-checking after repairs...\n";
            echo "--------------------------------\n";
            $results['integrity_post'] = $this->runTool('integrity', false);
            echo "\n";
        }

        // 5. Final validation
        echo "5️⃣ Running Final System Test...\n";
        echo "--------------------------------\n";
        $results['final'] = $this->runTool('final', false);

        $this->generateMasterReport($results);
    }

    private function runTool($toolKey, $showOutput = true) {
        if (!isset($this->tools[$toolKey])) {
            echo "❌ Tool '$toolKey' not found!\n";
            return false;
        }

        $tool = $this->tools[$toolKey];
        $toolPath = $this->projectRoot . '/tools/' . $tool['file'];

        if (!file_exists($toolPath)) {
            echo "❌ Tool file not found: {$tool['file']}\n";
            return false;
        }

        if ($showOutput) {
            echo "🔧 Running {$tool['name']}...\n";
            echo "Description: {$tool['description']}\n\n";
        }

        // Capture output
        ob_start();
        $result = include $toolPath;
        $output = ob_get_clean();

        if ($showOutput) {
            echo $output;
        }

        return [
            'tool' => $toolKey,
            'output' => $output,
            'result' => $result,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    private function hasIssues() {
        $reportFile = $this->projectRoot . '/logs/integrity-report.json';
        if (!file_exists($reportFile)) {
            return false;
        }

        $report = json_decode(file_get_contents($reportFile), true);
        return !empty($report['issues']) || !empty($report['duplicates']);
    }

    private function showStatus() {
        echo "📊 SYSTEM INTEGRITY STATUS\n";
        echo "===========================\n\n";

        // Check latest reports
        $reports = [
            'integrity' => $this->projectRoot . '/logs/integrity-report.json',
            'health' => $this->projectRoot . '/logs/health-report.json'
        ];

        foreach ($reports as $type => $reportFile) {
            if (file_exists($reportFile)) {
                $report = json_decode(file_get_contents($reportFile), true);
                echo "📋 " . ucfirst($type) . " Report:\n";
                echo "   Timestamp: " . $report['timestamp'] . "\n";
                
                if ($type === 'integrity' && isset($report['summary'])) {
                    echo "   Status: " . $report['summary']['status'] . "\n";
                    echo "   Issues: " . $report['summary']['critical_issues'] . "\n";
                    echo "   Duplicates: " . $report['summary']['duplicate_files'] . "\n";
                } elseif ($type === 'health') {
                    echo "   Status: " . $report['status'] . "\n";
                    echo "   Success Rate: " . $report['success_rate'] . "%\n";
                    echo "   Errors: " . $report['summary']['errors'] . "\n";
                    echo "   Warnings: " . $report['summary']['warnings'] . "\n";
                }
                echo "\n";
            } else {
                echo "📋 " . ucfirst($type) . " Report: Not available\n\n";
            }
        }

        // System summary
        echo "🎯 QUICK RECOMMENDATIONS:\n";
        if (!file_exists($this->projectRoot . '/vendor/autoload.php')) {
            echo "   • Run 'composer install' to install dependencies\n";
        }
        if (!file_exists($this->projectRoot . '/.env')) {
            echo "   • Create .env file from .env.example\n";
        }
        if ($this->hasIssues()) {
            echo "   • Run repair tool to fix identified issues\n";
        } else {
            echo "   • System appears to be in good condition\n";
        }
    }

    private function openDashboard() {
        $dashboardUrl = 'http://localhost/tools/integrity-dashboard.php';
        echo "🌐 Opening Integrity Dashboard...\n";
        echo "Dashboard URL: $dashboardUrl\n\n";
        echo "You can access the dashboard at: tools/integrity-dashboard.php\n";
    }

    private function showMenu() {
        echo "Available Actions:\n";
        echo "==================\n\n";
        
        echo "🔍 integrity  - Run system integrity check\n";
        echo "🏥 health     - Run system health check\n"; 
        echo "🔧 repair     - Run system repair tool\n";
        echo "✅ final      - Run final system test\n";
        echo "🚀 all        - Run complete check suite\n";
        echo "📊 status     - Show current system status\n";
        echo "🌐 dashboard  - Open integrity dashboard\n\n";

        echo "Usage Examples:\n";
        echo "---------------\n";
        echo "php tools/master-integrity.php all\n";
        echo "php tools/master-integrity.php integrity\n";
        echo "php tools/master-integrity.php status\n\n";

        echo "For web interface, access: tools/integrity-dashboard.php\n";
    }

    private function generateMasterReport($results) {
        echo "\n📈 MASTER INTEGRITY REPORT\n";
        echo "===========================\n\n";

        $summary = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tools_run' => count($results),
            'overall_status' => 'UNKNOWN'
        ];

        // Analyze results
        $hasErrors = false;
        $hasWarnings = false;

        foreach ($results as $toolKey => $result) {
            if ($result) {
                echo "✅ {$this->tools[$toolKey]['name']}: Completed\n";
            } else {
                echo "❌ {$this->tools[$toolKey]['name']}: Failed\n";
                $hasErrors = true;
            }
        }

        // Determine overall status
        if ($hasErrors) {
            $summary['overall_status'] = 'CRITICAL';
        } elseif ($hasWarnings || $this->hasIssues()) {
            $summary['overall_status'] = 'NEEDS_ATTENTION';
        } else {
            $summary['overall_status'] = 'HEALTHY';
        }

        echo "\n🏆 Overall System Status: " . $summary['overall_status'] . "\n";

        // Save master report
        $masterReport = [
            'summary' => $summary,
            'results' => $results,
            'recommendations' => $this->getRecommendations()
        ];

        $logsDir = $this->projectRoot . '/logs';
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }

        $reportFile = $logsDir . '/master-integrity-report.json';
        if (file_put_contents($reportFile, json_encode($masterReport, JSON_PRETTY_PRINT))) {
            echo "\n📝 Master report saved to logs/master-integrity-report.json\n";
        }

        echo "\n🎯 Next Steps:\n";
        foreach ($this->getRecommendations() as $recommendation) {
            echo "   • $recommendation\n";
        }
    }

    private function getRecommendations() {
        $recommendations = [];

        if (!file_exists($this->projectRoot . '/vendor/autoload.php')) {
            $recommendations[] = "Install Composer dependencies: composer install";
        }

        if (!file_exists($this->projectRoot . '/.env')) {
            $recommendations[] = "Create environment configuration: cp .env.example .env";
        }

        if ($this->hasIssues()) {
            $recommendations[] = "Address integrity issues using the repair tool";
        }

        $recommendations[] = "Review logs in the /logs directory for detailed information";
        $recommendations[] = "Access the integrity dashboard for ongoing monitoring";

        return $recommendations;
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $projectRoot = dirname(__DIR__);
    $masterTool = new MasterIntegrityTool($projectRoot);
    
    $action = $argv[1] ?? 'menu';
    $masterTool->run($action);
} else {
    // Web interface - redirect to dashboard
    header('Location: integrity-dashboard.php');
    exit;
}
?>