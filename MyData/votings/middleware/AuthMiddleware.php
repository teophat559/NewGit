<?php
/**
 * BVOTE Authentication Middleware
 * Handles authentication and authorization for protected routes
 */

namespace BVOTE\Middleware;

use BVOTE\Core\Auth;
use BVOTE\Core\Logger;

class AuthMiddleware
{
    private $auth;
    private $logger;

    public function __construct()
    {
        $this->auth = new Auth();
        $this->logger = new Logger();
    }

    /**
     * Handle authentication check
     */
    public function handle($request, $next)
    {
        try {
            // Check if user is authenticated
            if (!$this->auth->isAuthenticated()) {
                $this->logger->info('Authentication failed: User not authenticated', [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                ]);

                // Return unauthorized response
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => 'Unauthorized',
                    'message' => 'Authentication required',
                    'code' => 401
                ]);
                return;
            }

            // Check if user has required permissions
            if (isset($request['required_permissions'])) {
                if (!$this->auth->hasPermissions($request['required_permissions'])) {
                    $this->logger->warning('Authorization failed: Insufficient permissions', [
                        'user_id' => $this->auth->getCurrentUser()['id'] ?? 'unknown',
                        'required_permissions' => $request['required_permissions']
                    ]);

                    http_response_code(403);
                    header('Content-Type: application/json');
                    echo json_encode([
                        'error' => 'Forbidden',
                        'message' => 'Insufficient permissions',
                        'code' => 403
                    ]);
                    return;
                }
            }

            // Log successful authentication
            $this->logger->info('Authentication successful', [
                'user_id' => $this->auth->getCurrentUser()['id'] ?? 'unknown',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            // Continue to next middleware or handler
            return $next($request);

        } catch (Exception $e) {
            $this->logger->error('Authentication middleware error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Internal Server Error',
                'message' => 'Authentication system error',
                'code' => 500
            ]);
            return;
        }
    }

    /**
     * Check if user is admin
     */
    public function requireAdmin($request, $next)
    {
        if (!$this->auth->isAdmin()) {
            $this->logger->warning('Admin access denied', [
                'user_id' => $this->auth->getCurrentUser()['id'] ?? 'unknown',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Forbidden',
                'message' => 'Admin access required',
                'code' => 403
            ]);
            return;
        }

        return $next($request);
    }

    /**
     * Check if user is moderator
     */
    public function requireModerator($request, $next)
    {
        if (!$this->auth->isModerator()) {
            $this->logger->warning('Moderator access denied', [
                'user_id' => $this->auth->getCurrentUser()['id'] ?? 'unknown',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Forbidden',
                'message' => 'Moderator access required',
                'code' => 403
            ]);
            return;
        }

        return $next($request);
    }

    /**
     * Check if user owns the resource
     */
    public function requireOwnership($request, $next, $resourceType, $resourceId)
    {
        $user = $this->auth->getCurrentUser();

        if (!$user) {
            http_response_code(401);
            return;
        }

        // Check if user owns the resource or is admin
        if (!$this->auth->ownsResource($resourceType, $resourceId) && !$this->auth->isAdmin()) {
            $this->logger->warning('Resource ownership check failed', [
                'user_id' => $user['id'],
                'resource_type' => $resourceType,
                'resource_id' => $resourceId
            ]);

            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Forbidden',
                'message' => 'Resource ownership required',
                'code' => 403
            ]);
            return;
        }

        return $next($request);
    }
}
