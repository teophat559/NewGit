<?php
namespace BVOTE\Core\Middleware;

use BVOTE\Core\Auth;
use BVOTE\Core\Logger;

/**
 * BVOTE Authentication Middleware
 * Bảo vệ các route cần authentication
 */
class AuthMiddleware {
    private $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    /**
     * Handle middleware
     */
    public function handle($request, $next) {
        // Check if user is authenticated
        if (!$this->auth->check()) {
            Logger::warning('Unauthorized access attempt', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);

            // Redirect to login page for web requests
            if ($this->isWebRequest()) {
                header('Location: /admin/login');
                exit;
            }

            // Return JSON response for API requests
            if ($this->isApiRequest()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Unauthorized access',
                    'code' => 'UNAUTHORIZED'
                ]);
                exit;
            }
        }

        // Check if user has required permissions
        if (!$this->checkPermissions($request)) {
            Logger::warning('Insufficient permissions', [
                'user_id' => $this->auth->user()['id'] ?? null,
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
            ]);

            if ($this->isWebRequest()) {
                header('Location: /admin/unauthorized');
                exit;
            }

            if ($this->isApiRequest()) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Insufficient permissions',
                    'code' => 'FORBIDDEN'
                ]);
                exit;
            }
        }

        // Continue to next middleware/route
        return $next($request);
    }

    /**
     * Check if request is web request
     */
    private function isWebRequest(): bool {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strpos($accept, 'text/html') !== false ||
               strpos($accept, 'application/xhtml+xml') !== false;
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
     * Check user permissions
     */
    private function checkPermissions($request): bool {
        $user = $this->auth->user();

        if (!$user) {
            return false;
        }

        // Admin users have all permissions
        if ($user['role'] === 'admin') {
            return true;
        }

        // Check specific route permissions
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        // User management requires admin role
        if (strpos($uri, '/admin/users') === 0 && $user['role'] !== 'admin') {
            return false;
        }

        // System settings require admin role
        if (strpos($uri, '/admin/settings') === 0 && $user['role'] !== 'admin') {
            return false;
        }

        // Contest management requires moderator or admin role
        if (strpos($uri, '/admin/contests') === 0 &&
            !in_array($user['role'], ['admin', 'moderator'])) {
            return false;
        }

        return true;
    }

    /**
     * Get required permissions for route
     */
    private function getRequiredPermissions(string $route): array {
        $permissions = [
            '/admin/users' => ['admin'],
            '/admin/settings' => ['admin'],
            '/admin/contests' => ['admin', 'moderator'],
            '/admin/auto-login' => ['admin', 'moderator'],
            '/admin/notifications' => ['admin', 'moderator']
        ];

        foreach ($permissions as $pattern => $roles) {
            if (strpos($route, $pattern) === 0) {
                return $roles;
            }
        }

        return ['user']; // Default permission level
    }
}
