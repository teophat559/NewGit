<?php
/**
 * BVOTE Rate Limiting Middleware
 * Prevents abuse and spam by limiting request frequency
 */

namespace BVOTE\Middleware;

use BVOTE\Core\Cache;
use BVOTE\Core\Logger;

class RateLimitMiddleware
{
    private $cache;
    private $logger;
    private $defaultLimit = 100; // requests per hour
    private $defaultWindow = 3600; // 1 hour in seconds

    public function __construct()
    {
        $this->cache = new Cache();
        $this->logger = new Logger();
    }

    /**
     * Handle rate limiting
     */
    public function handle($request, $next, $limit = null, $window = null)
    {
        $limit = $limit ?? $this->defaultLimit;
        $window = $window ?? $this->defaultWindow;

        try {
            $identifier = $this->getIdentifier($request);
            $key = "rate_limit:{$identifier}";

            // Get current request count
            $currentCount = (int)$this->cache->get($key, 0);

            // Check if limit exceeded
            if ($currentCount >= $limit) {
                $this->logger->warning('Rate limit exceeded', [
                    'identifier' => $identifier,
                    'limit' => $limit,
                    'window' => $window,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);

                http_response_code(429);
                header('Content-Type: application/json');
                header('Retry-After: ' . $window);
                echo json_encode([
                    'error' => 'Too Many Requests',
                    'message' => 'Rate limit exceeded. Please try again later.',
                    'retry_after' => $window,
                    'code' => 429
                ]);
                return;
            }

            // Increment request count
            $this->cache->set($key, $currentCount + 1, $window);

            // Add rate limit headers
            header('X-RateLimit-Limit: ' . $limit);
            header('X-RateLimit-Remaining: ' . ($limit - $currentCount - 1));
            header('X-RateLimit-Reset: ' . (time() + $window));

            // Log rate limit info
            $this->logger->info('Rate limit check passed', [
                'identifier' => $identifier,
                'current_count' => $currentCount + 1,
                'limit' => $limit
            ]);

            // Continue to next middleware or handler
            return $next($request);

        } catch (Exception $e) {
            $this->logger->error('Rate limiting middleware error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // On error, allow request to continue
            return $next($request);
        }
    }

    /**
     * Get identifier for rate limiting (IP + User ID if authenticated)
     */
    private function getIdentifier($request)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userId = $_SESSION['user_id'] ?? 'guest';

        return md5($ip . ':' . $userId);
    }

    /**
     * Strict rate limiting for sensitive endpoints
     */
    public function strict($request, $next, $limit = 10, $window = 300)
    {
        return $this->handle($request, $next, $limit, $window);
    }

    /**
     * Loose rate limiting for public endpoints
     */
    public function loose($request, $next, $limit = 1000, $window = 3600)
    {
        return $this->handle($request, $next, $limit, $window);
    }

    /**
     * Custom rate limiting for specific actions
     */
    public function custom($request, $next, $action, $limit, $window)
    {
        $identifier = $this->getIdentifier($request) . ':' . $action;
        $key = "rate_limit:{$identifier}";

        try {
            $currentCount = (int)$this->cache->get($key, 0);

            if ($currentCount >= $limit) {
                $this->logger->warning('Custom rate limit exceeded', [
                    'action' => $action,
                    'identifier' => $identifier,
                    'limit' => $limit,
                    'window' => $window
                ]);

                http_response_code(429);
                header('Content-Type: application/json');
                header('Retry-After: ' . $window);
                echo json_encode([
                    'error' => 'Too Many Requests',
                    'message' => "Rate limit exceeded for action: {$action}",
                    'retry_after' => $window,
                    'code' => 429
                ]);
                return;
            }

            $this->cache->set($key, $currentCount + 1, $window);

            // Add custom rate limit headers
            header('X-RateLimit-Action: ' . $action);
            header('X-RateLimit-Limit: ' . $limit);
            header('X-RateLimit-Remaining: ' . ($limit - $currentCount - 1));
            header('X-RateLimit-Reset: ' . (time() + $window));

            return $next($request);

        } catch (Exception $e) {
            $this->logger->error('Custom rate limiting error', [
                'action' => $action,
                'error' => $e->getMessage()
            ]);

            return $next($request);
        }
    }

    /**
     * Get current rate limit status
     */
    public function getStatus($identifier)
    {
        $key = "rate_limit:{$identifier}";
        $currentCount = (int)$this->cache->get($key, 0);

        return [
            'current' => $currentCount,
            'limit' => $this->defaultLimit,
            'remaining' => max(0, $this->defaultLimit - $currentCount),
            'reset_time' => time() + $this->defaultWindow
        ];
    }

    /**
     * Reset rate limit for specific identifier
     */
    public function reset($identifier)
    {
        $key = "rate_limit:{$identifier}";
        $this->cache->delete($key);

        $this->logger->info('Rate limit reset', [
            'identifier' => $identifier
        ]);

        return true;
    }
}
