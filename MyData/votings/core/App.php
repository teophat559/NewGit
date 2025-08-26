<?php
namespace BVOTE\Core;

/**
 * BVOTE Core Application Class
 * Quản lý ứng dụng chính
 */
class App {
    private static $instance = null;
    private $container = [];
    private $services = [];
    private $config = [];

    private function __construct() {}

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Khởi tạo ứng dụng
     */
    public function initialize(): void {
        $this->loadConfiguration();
        $this->initializeServices();
        $this->setupErrorHandling();
        $this->setupSecurity();

        Logger::info('Application initialized successfully');
    }

    /**
     * Load configuration
     */
    private function loadConfiguration(): void {
        $this->config = [
            'app' => [
                'name' => $_ENV['APP_NAME'] ?? 'BVOTE Voting System',
                'env' => $_ENV['APP_ENV'] ?? 'production',
                'debug' => $_ENV['APP_DEBUG'] === 'true',
                'url' => $_ENV['APP_URL'] ?? 'http://localhost',
                'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Ho_Chi_Minh'
            ],
            'database' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => $_ENV['DB_PORT'] ?? 3306,
                'database' => $_ENV['DB_DATABASE'] ?? 'bvote_system',
                'username' => $_ENV['DB_USERNAME'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4'
            ],
            'redis' => [
                'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                'port' => $_ENV['REDIS_PORT'] ?? 6379,
                'password' => $_ENV['REDIS_PASSWORD'] ?? null,
                'database' => $_ENV['REDIS_DB'] ?? 0
            ],
            'mail' => [
                'driver' => $_ENV['MAIL_MAILER'] ?? 'smtp',
                'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
                'port' => $_ENV['MAIL_PORT'] ?? 587,
                'username' => $_ENV['MAIL_USERNAME'] ?? '',
                'password' => $_ENV['MAIL_PASSWORD'] ?? '',
                'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls'
            ],
            'security' => [
                'jwt_secret' => $_ENV['JWT_SECRET'] ?? '',
                'encryption_key' => $_ENV['ENCRYPTION_KEY'] ?? '',
                'admin_key' => $_ENV['ADMIN_KEY'] ?? '',
                'rate_limit_requests' => (int)($_ENV['RATE_LIMIT_REQUESTS'] ?? 60),
                'rate_limit_minutes' => (int)($_ENV['RATE_LIMIT_MINUTES'] ?? 1)
            ]
        ];
    }

    /**
     * Khởi tạo các services
     */
    private function initializeServices(): void {
        // Database service
        $this->services['database'] = new Database(
            $this->config['database']['host'],
            $this->config['database']['database'],
            $this->config['database']['username'],
            $this->config['database']['password'],
            $this->config['database']['port'],
            $this->config['database']['charset']
        );

        // Redis service
        if (extension_loaded('redis')) {
            try {
                $this->services['redis'] = new \Redis();
                $this->services['redis']->connect(
                    $this->config['redis']['host'],
                    $this->config['redis']['port']
                );

                if ($this->config['redis']['password']) {
                    $this->services['redis']->auth($this->config['redis']['password']);
                }

                $this->services['redis']->select($this->config['redis']['database']);
                Logger::info('Redis connected successfully');
            } catch (\Exception $e) {
                Logger::warning('Redis connection failed: ' . $e->getMessage());
            }
        }

        // Cache service
        $this->services['cache'] = new Cache($this->services['redis'] ?? null);

        // Session service
        $this->services['session'] = new Session();

        // Auth service
        $this->services['auth'] = new Auth($this->services['database']);

        // Logger service
        $this->services['logger'] = new Logger();
    }

    /**
     * Setup error handling
     */
    private function setupErrorHandling(): void {
        if ($this->config['app']['debug']) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }
    }

    /**
     * Setup security
     */
    private function setupSecurity(): void {
        // Set security headers
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');

            if ($this->config['app']['env'] === 'production') {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }

        // Set session security
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', $this->config['app']['env'] === 'production');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
    }

    /**
     * Get service
     */
    public function getService(string $name) {
        return $this->services[$name] ?? null;
    }

    /**
     * Get configuration
     */
    public function getConfig(string $key = null) {
        if ($key === null) {
            return $this->config;
        }

        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Check if application is in debug mode
     */
    public function isDebug(): bool {
        return $this->config['app']['debug'];
    }

    /**
     * Check if application is in production mode
     */
    public function isProduction(): bool {
        return $this->config['app']['env'] === 'production';
    }

    /**
     * Get application version
     */
    public function getVersion(): string {
        return BVOTE_VERSION;
    }

    /**
     * Get application start time
     */
    public function getStartTime(): float {
        return BVOTE_START_TIME;
    }

    /**
     * Get memory usage
     */
    public function getMemoryUsage(): int {
        return memory_get_usage() - BVOTE_START_MEMORY;
    }

    /**
     * Shutdown application
     */
    public function shutdown(): void {
        Logger::info('Application shutting down', [
            'memory_usage' => $this->getMemoryUsage(),
            'uptime' => microtime(true) - $this->getStartTime()
        ]);

        // Close database connections
        if (isset($this->services['database'])) {
            $this->services['database']->close();
        }

        // Close Redis connection
        if (isset($this->services['redis'])) {
            $this->services['redis']->close();
        }
    }
}
