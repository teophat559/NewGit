<?php
/**
 * Middleware System để xử lý request trước khi render
 */
abstract class Middleware {
    protected $next;

    public function __construct($next = null) {
        $this->next = $next;
    }

    /**
     * Xử lý middleware
     */
    abstract public function handle();

    /**
     * Chuyển tiếp đến middleware tiếp theo
     */
    protected function next() {
        if ($this->next) {
            return $this->next->handle();
        }
        return true;
    }
}

/**
 * Authentication Middleware
 */
class AuthMiddleware extends Middleware {
    private $redirectTo;

    public function __construct($redirectTo = '/admin/login', $next = null) {
        parent::__construct($next);
        $this->redirectTo = $redirectTo;
    }

    public function handle() {
        if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
            header('Location: ' . $this->redirectTo);
            exit;
        }

        return $this->next();
    }
}

/**
 * Guest Middleware (chỉ cho phép khách)
 */
class GuestMiddleware extends Middleware {
    private $redirectTo;

    public function __construct($redirectTo = '/admin/dashboard', $next = null) {
        parent::__construct($next);
        $this->redirectTo = $redirectTo;
    }

    public function handle() {
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
            header('Location: ' . $this->redirectTo);
            exit;
        }

        return $this->next();
    }
}

/**
 * User Authentication Middleware
 */
class UserAuthMiddleware extends Middleware {
    private $redirectTo;

    public function __construct($redirectTo = '/login', $next = null) {
        parent::__construct($next);
        $this->redirectTo = $redirectTo;
    }

    public function handle() {
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            header('Location: ' . $this->redirectTo);
            exit;
        }

        return $this->next();
    }
}

/**
 * CSRF Protection Middleware
 */
class CSRFMiddleware extends Middleware {
    public function handle() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                http_response_code(403);
                echo 'CSRF token không hợp lệ';
                exit;
            }
        }

        return $this->next();
    }
}

/**
 * Rate Limiting Middleware
 */
class RateLimitMiddleware extends Middleware {
    private $maxRequests;
    private $timeWindow;

    public function __construct($maxRequests = 100, $timeWindow = 3600, $next = null) {
        parent::__construct($next);
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }

    public function handle() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "rate_limit_{$ip}";

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 0,
                'reset_time' => time() + $this->timeWindow
            ];
        }

        if (time() > $_SESSION[$key]['reset_time']) {
            $_SESSION[$key] = [
                'count' => 0,
                'reset_time' => time() + $this->timeWindow
            ];
        }

        if ($_SESSION[$key]['count'] >= $this->maxRequests) {
            http_response_code(429);
            echo 'Quá nhiều request. Vui lòng thử lại sau.';
            exit;
        }

        $_SESSION[$key]['count']++;

        return $this->next();
    }
}

/**
 * Logging Middleware
 */
class LoggingMiddleware extends Middleware {
    public function handle() {
        $startTime = microtime(true);

        $result = $this->next();

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        $this->log([
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'uri' => $_SERVER['REQUEST_URI'] ?? '/',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'duration_ms' => round($duration, 2),
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        return $result;
    }

    private function log($data) {
        $logFile = __DIR__ . '/../logs/access.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logEntry = json_encode($data) . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Middleware Stack
 */
class MiddlewareStack {
    private $middlewares = [];

    /**
     * Thêm middleware vào stack
     */
    public function add($middleware) {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Chạy tất cả middleware
     */
    public function run() {
        $next = null;

        // Tạo chain ngược
        for ($i = count($this->middlewares) - 1; $i >= 0; $i--) {
            $middleware = $this->middlewares[$i];
            if (is_string($middleware)) {
                $middleware = new $middleware($next);
            } elseif ($middleware instanceof Middleware) {
                $middleware->next = $next;
            }
            $next = $middleware;
        }

        if ($next) {
            return $next->handle();
        }

        return true;
    }

    /**
     * Tạo middleware stack từ array
     */
    public static function fromArray($middlewares) {
        $stack = new self();
        foreach ($middlewares as $middleware) {
            $stack->add($middleware);
        }
        return $stack;
    }
}
