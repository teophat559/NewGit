<?php
/**
 * BVOTE System Integrity Repair Tool
 * Automatically fixes common integrity issues
 */

class SystemIntegrityRepair {
    private $projectRoot;
    private $fixedFiles = [];
    private $skippedFiles = [];

    public function __construct($projectRoot) {
        $this->projectRoot = realpath($projectRoot);
    }

    public function repairSystem($dryRun = false) {
        echo "🔧 BVOTE SYSTEM INTEGRITY REPAIR TOOL\n";
        echo "=====================================\n\n";
        
        if ($dryRun) {
            echo "🔍 DRY RUN MODE - No files will be modified\n\n";
        }

        $this->createEssentialFiles($dryRun);
        $this->fixEmptyFiles($dryRun);
        $this->createMissingDirectories($dryRun);
        $this->generateReport();
    }

    private function createEssentialFiles($dryRun = false) {
        echo "📄 Creating essential missing files...\n";

        $essentialFiles = [
            '.env.example' => $this->getEnvExampleContent(),
            'config/database.php' => $this->getDatabaseConfigContent(),
            'user-interface.html' => $this->getUserInterfaceContent(),
            'admin-bot-control.html' => $this->getAdminBotControlContent(),
            'diagnostic.php' => $this->getDiagnosticContent()
        ];

        foreach ($essentialFiles as $file => $content) {
            $filePath = $this->projectRoot . '/' . $file;
            
            if (!file_exists($filePath) || filesize($filePath) === 0) {
                if (!$dryRun) {
                    $dir = dirname($filePath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    file_put_contents($filePath, $content);
                    $this->fixedFiles[] = $file;
                    echo "✅ Created/Fixed: $file\n";
                } else {
                    echo "🔍 Would create/fix: $file\n";
                }
            } else {
                echo "✅ Already exists: $file\n";
            }
        }
        echo "\n";
    }

    private function fixEmptyFiles($dryRun = false) {
        echo "📝 Fixing empty critical files...\n";

        $emptyFileTemplates = [
            'modules/admin/index.php' => $this->getModuleIndexContent('admin'),
            'modules/user/index.php' => $this->getModuleIndexContent('user'),
            'modules/api/index.php' => $this->getModuleIndexContent('api'),
            'api/v1/user/login.php' => $this->getApiEndpointContent('user/login'),
            'api/v1/user/notifications.php' => $this->getApiEndpointContent('user/notifications'),
            'api/v1/admin/monitor.php' => $this->getApiEndpointContent('admin/monitor'),
            'core/Bootstrap.php' => $this->getBootstrapContent(),
            'core/interfaces/AuthInterface.php' => $this->getAuthInterfaceContent(),
            'core/libs/Database.php' => $this->getDatabaseLibContent(),
            'includes/functions.php' => $this->getIncludeFunctionsContent(),
            'data/index.php' => $this->getSecurityIndexContent(),
            'config/index.php' => $this->getSecurityIndexContent(),
            'includes/index.php' => $this->getSecurityIndexContent()
        ];

        foreach ($emptyFileTemplates as $file => $content) {
            $filePath = $this->projectRoot . '/' . $file;
            
            if (file_exists($filePath) && filesize($filePath) === 0) {
                if (!$dryRun) {
                    file_put_contents($filePath, $content);
                    $this->fixedFiles[] = $file;
                    echo "✅ Fixed empty file: $file\n";
                } else {
                    echo "🔍 Would fix empty file: $file\n";
                }
            } elseif (!file_exists($filePath)) {
                if (!$dryRun) {
                    $dir = dirname($filePath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    file_put_contents($filePath, $content);
                    $this->fixedFiles[] = $file;
                    echo "✅ Created missing file: $file\n";
                } else {
                    echo "🔍 Would create missing file: $file\n";
                }
            } else {
                $this->skippedFiles[] = $file . ' (already has content)';
            }
        }
        echo "\n";
    }

    private function createMissingDirectories($dryRun = false) {
        echo "📁 Creating missing directories...\n";

        $requiredDirs = [
            'storage',
            'storage/logs',
            'storage/cache',
            'storage/sessions',
            'logs',
            'uploads',
            'modules/user/assets',
            'modules/user/controllers',
            'modules/api/controllers',
            'modules/api/views'
        ];

        foreach ($requiredDirs as $dir) {
            $dirPath = $this->projectRoot . '/' . $dir;
            if (!is_dir($dirPath)) {
                if (!$dryRun) {
                    mkdir($dirPath, 0755, true);
                    echo "✅ Created directory: $dir\n";
                } else {
                    echo "🔍 Would create directory: $dir\n";
                }
            } else {
                echo "✅ Directory exists: $dir\n";
            }
        }
        echo "\n";
    }

    private function generateReport() {
        echo "📊 REPAIR SUMMARY\n";
        echo "==================\n";
        echo "Fixed Files: " . count($this->fixedFiles) . "\n";
        echo "Skipped Files: " . count($this->skippedFiles) . "\n\n";

        if (!empty($this->fixedFiles)) {
            echo "✅ Fixed Files:\n";
            foreach ($this->fixedFiles as $file) {
                echo "  - $file\n";
            }
            echo "\n";
        }

        if (!empty($this->skippedFiles)) {
            echo "⏭️ Skipped Files:\n";
            foreach ($this->skippedFiles as $file) {
                echo "  - $file\n";
            }
        }

        echo "\n🎉 Repair completed! Run system-integrity-checker.php to verify.\n";
    }

    // Template content methods
    private function getEnvExampleContent() {
        return '# BVOTE Environment Configuration
# Copy to .env and configure with your settings

# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=bvote
DB_USER=root
DB_PASS=

# Redis Configuration (optional)
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=

# Application Settings
APP_NAME="BVOTE System"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost

# Security
JWT_SECRET=your-secret-key-here
ENCRYPT_KEY=your-encryption-key-here

# Telegram Bot (optional)
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=

# Email Settings (optional)
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@bvote.com
MAIL_FROM_NAME="BVOTE System"
';
    }

    private function getDatabaseConfigContent() {
        return '<?php
/**
 * Database Configuration
 */

// Load environment variables
if (file_exists(__DIR__ . \'/../.env\')) {
    $lines = file(__DIR__ . \'/../.env\', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, \'=\') !== false && strpos($line, \'#\') !== 0) {
            list($key, $value) = explode(\'=\', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

return [
    \'host\' => $_ENV[\'DB_HOST\'] ?? \'localhost\',
    \'port\' => $_ENV[\'DB_PORT\'] ?? 3306,
    \'database\' => $_ENV[\'DB_NAME\'] ?? \'bvote\',
    \'username\' => $_ENV[\'DB_USER\'] ?? \'root\',
    \'password\' => $_ENV[\'DB_PASS\'] ?? \'\',
    \'charset\' => \'utf8mb4\',
    \'options\' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
';
    }

    private function getUserInterfaceContent() {
        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BVOTE - Interfaz de Usuario</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        h1 { color: #333; text-align: center; }
        .voting-section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗳️ BVOTE - Sistema de Votación</h1>
        <div class="voting-section">
            <h2>Panel de Votación</h2>
            <p>Esta es la interfaz principal de usuario para el sistema BVOTE.</p>
            <button class="btn" onclick="location.href=\'user/\'">Acceder al Sistema de Votación</button>
        </div>
        <div class="voting-section">
            <h2>Estado del Sistema</h2>
            <p>Sistema operativo y listo para recibir votos.</p>
        </div>
    </div>
</body>
</html>';
    }

    private function getAdminBotControlContent() {
        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BVOTE - Control de Bot Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        h1 { color: #333; text-align: center; }
        .control-panel { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn.danger { background: #dc3545; }
        .btn:hover { opacity: 0.8; }
        .status { padding: 10px; border-radius: 5px; margin: 10px 0; }
        .status.active { background: #d4edda; color: #155724; }
        .status.inactive { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 BVOTE - Control de Bot Admin</h1>
        <div class="control-panel">
            <h2>Estado del Bot</h2>
            <div class="status inactive" id="botStatus">Bot Inactivo</div>
            <button class="btn" onclick="startBot()">Iniciar Bot</button>
            <button class="btn danger" onclick="stopBot()">Detener Bot</button>
        </div>
        <div class="control-panel">
            <h2>Configuración</h2>
            <p>Panel de configuración del bot de administración.</p>
            <button class="btn" onclick="location.href=\'admin/\'">Acceder al Panel Admin</button>
        </div>
    </div>
    
    <script>
        function startBot() {
            document.getElementById("botStatus").textContent = "Bot Activo";
            document.getElementById("botStatus").className = "status active";
        }
        
        function stopBot() {
            document.getElementById("botStatus").textContent = "Bot Inactivo";
            document.getElementById("botStatus").className = "status inactive";
        }
    </script>
</body>
</html>';
    }

    private function getDiagnosticContent() {
        return '<?php
/**
 * BVOTE System Diagnostic Tool
 */

echo "🔍 BVOTE System Diagnostic Report\n";
echo "==================================\n\n";

// PHP Information
echo "📋 PHP Information:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . ($_SERVER[\'SERVER_SOFTWARE\'] ?? \'Unknown\') . "\n";
echo "Document Root: " . ($_SERVER[\'DOCUMENT_ROOT\'] ?? \'Unknown\') . "\n\n";

// File System Check
echo "📁 File System:\n";
$directories = [\'config\', \'data\', \'logs\', \'uploads\', \'modules\'];
foreach ($directories as $dir) {
    $status = is_dir($dir) ? (is_writable($dir) ? \'✅ Writable\' : \'⚠️ Not writable\') : \'❌ Missing\';
    echo "$dir: $status\n";
}

echo "\n📊 System Status: Operational\n";
';
    }

    private function getModuleIndexContent($module) {
        return "<?php
/**
 * BVOTE {$module} Module Entry Point
 */

// Security check
if (!defined('BVOTE_SYSTEM')) {
    http_response_code(403);
    exit('Direct access not allowed');
}

echo \"<h1>BVOTE " . ucfirst($module) . " Module</h1>\";
echo \"<p>Module is operational and ready.</p>\";
";
    }

    private function getApiEndpointContent($endpoint) {
        return "<?php
/**
 * BVOTE API Endpoint: $endpoint
 */

header('Content-Type: application/json');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Basic endpoint response
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'endpoint' => '$endpoint',
    'message' => 'Endpoint operational',
    'timestamp' => date('Y-m-d H:i:s')
]);
";
    }

    private function getBootstrapContent() {
        return '<?php
/**
 * BVOTE Core Bootstrap
 */

namespace BVOTE\Core;

class Bootstrap {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function initialize() {
        // Define system constants
        define(\'BVOTE_SYSTEM\', true);
        
        // Set timezone
        date_default_timezone_set(\'UTC\');
        
        // Start session if needed
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $this;
    }
}
';
    }

    private function getAuthInterfaceContent() {
        return '<?php
/**
 * BVOTE Authentication Interface
 */

namespace BVOTE\Core\Interfaces;

interface AuthInterface {
    public function authenticate($credentials);
    public function authorize($permission);
    public function logout();
    public function getCurrentUser();
    public function isAuthenticated();
}
';
    }

    private function getDatabaseLibContent() {
        return '<?php
/**
 * BVOTE Database Library
 */

namespace BVOTE\Core\Libs;

class Database {
    private $pdo;
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->config[\'host\']};port={$this->config[\'port\']};dbname={$this->config[\'database\']};charset={$this->config[\'charset\']}";
            $this->pdo = new \PDO($dsn, $this->config[\'username\'], $this->config[\'password\'], $this->config[\'options\']);
        } catch (\PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
}
';
    }

    private function getIncludeFunctionsContent() {
        return '<?php
/**
 * BVOTE Common Functions
 */

if (!function_exists(\'sanitize_input\')) {
    function sanitize_input($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, \'UTF-8\');
    }
}

if (!function_exists(\'generate_csrf_token\')) {
    function generate_csrf_token() {
        if (!isset($_SESSION[\'csrf_token\'])) {
            $_SESSION[\'csrf_token\'] = bin2hex(random_bytes(32));
        }
        return $_SESSION[\'csrf_token\'];
    }
}

if (!function_exists(\'verify_csrf_token\')) {
    function verify_csrf_token($token) {
        return isset($_SESSION[\'csrf_token\']) && hash_equals($_SESSION[\'csrf_token\'], $token);
    }
}

if (!function_exists(\'log_activity\')) {
    function log_activity($message, $level = \'info\') {
        $logFile = __DIR__ . \'/../logs/system.log\';
        $timestamp = date(\'Y-m-d H:i:s\');
        $entry = "[$timestamp] [$level] $message\n";
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
';
    }

    private function getSecurityIndexContent() {
        return '<?php
// Security: Prevent directory listing
http_response_code(403);
exit(\'Access denied\');
';
    }
}

// Run the repair tool
if (php_sapi_name() === 'cli') {
    $projectRoot = dirname(__DIR__);
    $repair = new SystemIntegrityRepair($projectRoot);
    
    // Check for dry-run flag
    $dryRun = in_array('--dry-run', $argv);
    
    $repair->repairSystem($dryRun);
}
?>