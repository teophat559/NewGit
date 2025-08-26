<?php
namespace BVOTE\Core;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

/**
 * BVOTE Logger Class
 * Quản lý logging toàn diện với Monolog
 */
class Logger {
    private static $instance = null;
    private $monolog;
    private $logDir;
    private $logLevel;

    private function __construct() {
        $this->logDir = __DIR__ . '/../storage/logs';
        $this->logLevel = $this->getLogLevel();

        // Tạo thư mục logs nếu chưa có
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }

        $this->initializeLogger();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize Monolog logger
     */
    private function initializeLogger(): void {
        $this->monolog = new MonologLogger('bvote');

        // Daily rotating file handler
        $dailyHandler = new RotatingFileHandler(
            $this->logDir . '/bvote.log',
            30, // Keep logs for 30 days
            $this->logLevel
        );

        // Set formatter
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s'
        );
        $dailyHandler->setFormatter($formatter);

        $this->monolog->pushHandler($dailyHandler);

        // Error log handler
        $errorHandler = new RotatingFileHandler(
            $this->logDir . '/error.log',
            30,
            MonologLogger::ERROR
        );
        $errorHandler->setFormatter($formatter);
        $this->monolog->pushHandler($errorHandler);

        // Security log handler
        $securityHandler = new RotatingFileHandler(
            $this->logDir . '/security.log',
            90, // Keep security logs for 90 days
            MonologLogger::WARNING
        );
        $securityHandler->setFormatter($formatter);
        $this->monolog->pushHandler($securityHandler);
    }

    /**
     * Get log level from environment
     */
    private function getLogLevel(): int {
        $level = strtolower($_ENV['LOG_LEVEL'] ?? 'info');

        switch ($level) {
            case 'debug':
                return MonologLogger::DEBUG;
            case 'info':
                return MonologLogger::INFO;
            case 'notice':
                return MonologLogger::NOTICE;
            case 'warning':
                return MonologLogger::WARNING;
            case 'error':
                return MonologLogger::ERROR;
            case 'critical':
                return MonologLogger::CRITICAL;
            case 'alert':
                return MonologLogger::ALERT;
            case 'emergency':
                return MonologLogger::EMERGENCY;
            default:
                return MonologLogger::INFO;
        }
    }

    /**
     * Log emergency message
     */
    public static function emergency(string $message, array $context = []): void {
        self::getInstance()->monolog->emergency($message, $context);
    }

    /**
     * Log alert message
     */
    public static function alert(string $message, array $context = []): void {
        self::getInstance()->monolog->alert($message, $context);
    }

    /**
     * Log critical message
     */
    public static function critical(string $message, array $context = []): void {
        self::getInstance()->monolog->critical($message, $context);
    }

    /**
     * Log error message
     */
    public static function error(string $message, array $context = []): void {
        self::getInstance()->monolog->error($message, $context);
    }

    /**
     * Log warning message
     */
    public static function warning(string $message, array $context = []): void {
        self::getInstance()->monolog->warning($message, $context);
    }

    /**
     * Log notice message
     */
    public static function notice(string $message, array $context = []): void {
        self::getInstance()->monolog->notice($message, $context);
    }

    /**
     * Log info message
     */
    public static function info(string $message, array $context = []): void {
        self::getInstance()->monolog->info($message, $context);
    }

    /**
     * Log debug message
     */
    public static function debug(string $message, array $context = []): void {
        self::getInstance()->monolog->debug($message, $context);
    }

    /**
     * Log security event
     */
    public static function security(string $message, array $context = []): void {
        $context['type'] = 'security';
        self::getInstance()->monolog->warning($message, $context);
    }

    /**
     * Log authentication event
     */
    public static function auth(string $message, array $context = []): void {
        $context['type'] = 'authentication';
        self::getInstance()->monolog->info($message, $context);
    }

    /**
     * Log database operation
     */
    public static function database(string $message, array $context = []): void {
        $context['type'] = 'database';
        self::getInstance()->monolog->info($message, $context);
    }

    /**
     * Log API request
     */
    public static function api(string $message, array $context = []): void {
        $context['type'] = 'api';
        self::getInstance()->monolog->info($message, $context);
    }

    /**
     * Log user action
     */
    public static function user(string $message, array $context = []): void {
        $context['type'] = 'user_action';
        self::getInstance()->monolog->info($message, $context);
    }

    /**
     * Log system event
     */
    public static function system(string $message, array $context = []): void {
        $context['type'] = 'system';
        self::getInstance()->monolog->info($message, $context);
    }

    /**
     * Log performance metric
     */
    public static function performance(string $message, array $context = []): void {
        $context['type'] = 'performance';
        self::getInstance()->monolog->info($message, $context);
    }

    /**
     * Log with custom context
     */
    public static function log(string $level, string $message, array $context = []): void {
        // Add common context
        $context = array_merge($context, [
            'ip' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $_SESSION['user_id'] ?? null,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'timestamp' => time()
        ]);

        switch (strtolower($level)) {
            case 'emergency':
                self::emergency($message, $context);
                break;
            case 'alert':
                self::alert($message, $context);
                break;
            case 'critical':
                self::critical($message, $context);
                break;
            case 'error':
                self::error($message, $context);
                break;
            case 'warning':
                self::warning($message, $context);
                break;
            case 'notice':
                self::notice($message, $context);
                break;
            case 'info':
                self::info($message, $context);
                break;
            case 'debug':
                self::debug($message, $context);
                break;
            default:
                self::info($message, $context);
        }
    }

    /**
     * Get client IP address
     */
    private static function getClientIp(): string {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);

                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Get log statistics
     */
    public static function getStats(): array {
        $instance = self::getInstance();
        $logDir = $instance->logDir;

        $stats = [
            'log_directory' => $logDir,
            'log_level' => $_ENV['LOG_LEVEL'] ?? 'info',
            'log_files' => [],
            'total_size' => 0
        ];

        // Get log file information
        $logFiles = glob($logDir . '/*.log');
        foreach ($logFiles as $file) {
            $filename = basename($file);
            $size = filesize($file);
            $modified = filemtime($file);

            $stats['log_files'][$filename] = [
                'size' => $size,
                'size_human' => self::formatBytes($size),
                'modified' => date('Y-m-d H:i:s', $modified),
                'modified_timestamp' => $modified
            ];

            $stats['total_size'] += $size;
        }

        $stats['total_size_human'] = self::formatBytes($stats['total_size']);

        return $stats;
    }

    /**
     * Format bytes to human readable format
     */
    private static function formatBytes(int $bytes, int $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Clean old log files
     */
    public static function cleanOldLogs(int $days = 30): int {
        $instance = self::getInstance();
        $logDir = $instance->logDir;
        $cutoff = time() - ($days * 24 * 60 * 60);
        $deleted = 0;

        $logFiles = glob($logDir . '/*.log');
        foreach ($logFiles as $file) {
            if (filemtime($file) < $cutoff) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }

        self::info("Cleaned {$deleted} old log files older than {$days} days");

        return $deleted;
    }

    /**
     * Export logs for analysis
     */
    public static function exportLogs(string $startDate, string $endDate, string $outputFile = null): string {
        $instance = self::getInstance();
        $logDir = $instance->logDir;

        if (!$outputFile) {
            $outputFile = $logDir . '/export_' . date('Y-m-d_H-i-s') . '.log';
        }

        $startTimestamp = strtotime($startDate);
        $endTimestamp = strtotime($endDate);

        if (!$startTimestamp || !$endTimestamp) {
            throw new \InvalidArgumentException('Invalid date format');
        }

        $exported = [];
        $logFiles = glob($logDir . '/*.log');

        foreach ($logFiles as $file) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                    $logTimestamp = strtotime($matches[1]);

                    if ($logTimestamp >= $startTimestamp && $logTimestamp <= $endTimestamp) {
                        $exported[] = $line;
                    }
                }
            }
        }

        // Sort by timestamp
        usort($exported, function($a, $b) {
            preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $a, $matchesA);
            preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $b, $matchesB);

            return strtotime($matchesA[1]) <=> strtotime($matchesB[1]);
        });

        file_put_contents($outputFile, implode("\n", $exported));

        self::info("Exported " . count($exported) . " log entries to {$outputFile}");

        return $outputFile;
    }
}
