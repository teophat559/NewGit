<?php
namespace BVOTE\Core\Middleware;

use BVOTE\Core\Cache;
use BVOTE\Core\Logger;

/**
 * BVOTE Rate Limit Middleware
 * Chống brute force và spam
 */
class RateLimitMiddleware {
    private $cache;
    private $maxRequests;
    private $timeWindow;

    public function __construct() {
        $this->cache = new Cache();
        $this->maxRequests = (int)($_ENV['RATE_LIMIT_REQUESTS'] ?? 60);
        $this->timeWindow = (int)($_ENV['RATE_LIMIT_MINUTES'] ?? 1) * 60; // Convert to seconds
    }

    /**
     * Handle middleware
     */
    public function handle($request, $next) {
        $ip = $this->getClientIp();
        $route = $_SERVER['REQUEST_URI'] ?? '/';

        // Skip rate limiting for static assets
        if ($this->isStaticAsset($route)) {
            return $next($request);
        }

        // Check rate limit
        if (!$this->checkRateLimit($ip, $route)) {
            $this->handleRateLimitExceeded($ip, $route);
        }

        // Continue to next middleware/route
        return $next($request);
    }

    /**
     * Check rate limit for IP and route
     */
    private function checkRateLimit(string $ip, string $route): bool {
        $key = "rate_limit:{$ip}:{$route}";
        $current = $this->cache->get($key, 0);

        if ($current >= $this->maxRequests) {
            Logger::warning('Rate limit exceeded', [
                'ip' => $ip,
                'route' => $route,
                'current' => $current,
                'max' => $this->maxRequests,
                'time_window' => $this->timeWindow
            ]);

            return false;
        }

        // Increment counter
        $this->cache->increment($key, 1, $this->timeWindow);

        return true;
    }

    /**
     * Handle rate limit exceeded
     */
    private function handleRateLimitExceeded(string $ip, string $route): void {
        // Log the event
        Logger::warning('Rate limit exceeded - blocking request', [
            'ip' => $ip,
            'route' => $route,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        // Return rate limit response
        if ($this->isApiRequest()) {
            http_response_code(429);
            header('Content-Type: application/json');
            header('Retry-After: ' . $this->timeWindow);

            echo json_encode([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $this->timeWindow
            ]);
        } else {
            http_response_code(429);
            header('Retry-After: ' . $this->timeWindow);

            include __DIR__ . '/../../templates/rate_limit.php';
        }

        exit;
    }

    /**
     * Get client IP address
     */
    private function getClientIp(): string {
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
     * Check if route is static asset
     */
    private function isStaticAsset(string $route): bool {
        $staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.ico', '.svg', '.woff', '.woff2', '.ttf', '.eot'];

        foreach ($staticExtensions as $ext) {
            if (strpos($route, $ext) !== false) {
                return true;
            }
        }

        return strpos($route, '/assets/') === 0 ||
               strpos($route, '/uploads/') === 0 ||
               strpos($route, '/favicon.ico') === 0;
    }

    /**
     * Check if request is API request
     */
    private function isApiRequest(): bool {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strpos($accept, 'application/json') !== false ||
               strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') === 0;
    }

    /**
     * Get rate limit info for debugging
     */
    public function getRateLimitInfo(string $ip, string $route): array {
        $key = "rate_limit:{$ip}:{$route}";
        $current = $this->cache->get($key, 0);
        $ttl = $this->cache->getTtl($key);

        return [
            'ip' => $ip,
            'route' => $route,
            'current_requests' => $current,
            'max_requests' => $this->maxRequests,
            'time_window' => $this->timeWindow,
            'remaining_requests' => max(0, $this->maxRequests - $current),
            'reset_time' => $ttl > 0 ? time() + $ttl : 0,
            'is_blocked' => $current >= $this->maxRequests
        ];
    }

    /**
     * Reset rate limit for IP and route
     */
    public function resetRateLimit(string $ip, string $route): bool {
        $key = "rate_limit:{$ip}:{$route}";
        return $this->cache->delete($key);
    }

    /**
     * Get all rate limited IPs
     */
    public function getRateLimitedIps(): array {
        $pattern = 'rate_limit:*';
        $keys = $this->cache->getKeys($pattern);

        $rateLimited = [];
        foreach ($keys as $key) {
            $parts = explode(':', $key);
            if (count($parts) >= 3) {
                $ip = $parts[1];
                $route = $parts[2];
                $current = $this->cache->get($key, 0);

                if ($current >= $this->maxRequests) {
                    $rateLimited[] = [
                        'ip' => $ip,
                        'route' => $route,
                        'current_requests' => $current,
                        'max_requests' => $this->maxRequests
                    ];
                }
            }
        }

        return $rateLimited;
    }
}
