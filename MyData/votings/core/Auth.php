<?php
namespace BVOTE\Core;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * BVOTE Authentication Class
 * Quản lý authentication và authorization
 */
class Auth {
    private $db;
    private $session;
    private $jwtSecret;
    private $jwtAlgorithm;

    public function __construct(Database $db) {
        $this->db = $db;
        $this->session = new Session();
        $this->jwtSecret = $_ENV['JWT_SECRET'] ?? 'default-secret-key';
        $this->jwtAlgorithm = 'HS256';
    }

    /**
     * Attempt login
     */
    public function attempt(string $email, string $password, bool $remember = false): bool {
        try {
            // Find user by email
            $user = $this->db->selectOne('users', 'email = :email', ['email' => $email]);

            if (!$user) {
                Logger::warning('Login attempt failed: User not found', ['email' => $email]);
                return false;
            }

            // Verify password
            if (!password_verify($password, $user['password'])) {
                Logger::warning('Login attempt failed: Invalid password', ['email' => $email]);
                return false;
            }

            // Check if user is active
            if ($user['status'] !== 'active') {
                Logger::warning('Login attempt failed: User inactive', ['email' => $email, 'status' => $user['status']]);
                return false;
            }

            // Login successful
            $this->login($user, $remember);

            Logger::info('User logged in successfully', [
                'user_id' => $user['id'],
                'email' => $user['email']
            ]);

            return true;

        } catch (\Exception $e) {
            Logger::error('Login attempt error: ' . $e->getMessage(), ['email' => $email]);
            return false;
        }
    }

    /**
     * Login user
     */
    public function login(array $user, bool $remember = false): void {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            $this->session->start();
        }

        // Store user data in session
        $this->session->set('user_id', $user['id']);
        $this->session->set('user_email', $user['email']);
        $this->session->set('user_role', $user['role']);
        $this->session->set('user_name', $user['name']);
        $this->session->set('authenticated', true);
        $this->session->set('login_time', time());

        // Set remember me token if requested
        if ($remember) {
            $token = $this->generateRememberToken($user['id']);
            $this->session->set('remember_token', $token);

            // Store token in database
            $this->db->update('users',
                ['remember_token' => $token],
                'id = :id',
                ['id' => $user['id']]
            );
        }

        // Update last login time
        $this->db->update('users',
            ['last_login' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $user['id']]
        );
    }

    /**
     * Logout user
     */
    public function logout(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $userId = $this->session->get('user_id');

            // Clear remember token from database
            if ($userId) {
                $this->db->update('users',
                    ['remember_token' => null],
                    'id = :id',
                    ['id' => $userId]
                );
            }

            // Destroy session
            $this->session->destroy();

            Logger::info('User logged out', ['user_id' => $userId]);
        }
    }

    /**
     * Check if user is authenticated
     */
    public function check(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            $this->session->start();
        }

        // Check session authentication
        if ($this->session->get('authenticated', false)) {
            return true;
        }

        // Check remember me token
        $rememberToken = $this->session->get('remember_token');
        if ($rememberToken) {
            return $this->validateRememberToken($rememberToken);
        }

        return false;
    }

    /**
     * Get current user
     */
    public function user(): ?array {
        if (!$this->check()) {
            return null;
        }

        $userId = $this->session->get('user_id');
        if (!$userId) {
            return null;
        }

        try {
            $user = $this->db->selectOne('users', 'id = :id', ['id' => $userId]);
            if ($user) {
                // Remove sensitive data
                unset($user['password'], $user['remember_token']);
                return $user;
            }
        } catch (\Exception $e) {
            Logger::error('Error getting user data: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool {
        $user = $this->user();
        return $user && $user['role'] === $role;
    }

    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole(array $roles): bool {
        $user = $this->user();
        return $user && in_array($user['role'], $roles);
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(string $permission): bool {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        // Admin has all permissions
        if ($user['role'] === 'admin') {
            return true;
        }

        // Check user permissions
        try {
            $userPermission = $this->db->selectOne(
                'user_permissions up
                 JOIN permissions p ON up.permission_id = p.id',
                'up.user_id = :user_id AND p.name = :permission',
                ['user_id' => $user['id'], 'permission' => $permission]
            );

            return $userPermission !== null;
        } catch (\Exception $e) {
            Logger::error('Error checking permission: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate JWT token
     */
    public function generateJWT(array $user, int $ttl = 3600): string {
        $payload = [
            'iss' => $_ENV['APP_URL'] ?? 'http://localhost',
            'aud' => $_ENV['APP_URL'] ?? 'http://localhost',
            'iat' => time(),
            'exp' => time() + $ttl,
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        return JWT::encode($payload, $this->jwtSecret, $this->jwtAlgorithm);
    }

    /**
     * Validate JWT token
     */
    public function validateJWT(string $token): ?array {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, $this->jwtAlgorithm));
            return (array) $decoded;
        } catch (\Exception $e) {
            Logger::warning('JWT validation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate remember me token
     */
    private function generateRememberToken(int $userId): string {
        $token = bin2hex(random_bytes(32));
        $hash = password_hash($token, PASSWORD_DEFAULT);

        return $hash;
    }

    /**
     * Validate remember me token
     */
    private function validateRememberToken(string $token): bool {
        try {
            $user = $this->db->selectOne('users', 'remember_token = :token', ['token' => $token]);

            if ($user && $user['status'] === 'active') {
                // Re-login user
                $this->login($user, true);
                return true;
            }
        } catch (\Exception $e) {
            Logger::error('Error validating remember token: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Create user
     */
    public function createUser(array $userData): ?int {
        try {
            // Hash password
            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);

            // Set default values
            $userData['status'] = $userData['status'] ?? 'active';
            $userData['role'] = $userData['role'] ?? 'user';
            $userData['created_at'] = date('Y-m-d H:i:s');
            $userData['updated_at'] = date('Y-m-d H:i:s');

            $userId = $this->db->insert('users', $userData);

            Logger::info('User created successfully', [
                'user_id' => $userId,
                'email' => $userData['email']
            ]);

            return $userId;

        } catch (\Exception $e) {
            Logger::error('Error creating user: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update user
     */
    public function updateUser(int $userId, array $userData): bool {
        try {
            // Hash password if provided
            if (isset($userData['password'])) {
                $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            }

            $userData['updated_at'] = date('Y-m-d H:i:s');

            $result = $this->db->update('users', $userData, 'id = :id', ['id' => $userId]);

            if ($result > 0) {
                Logger::info('User updated successfully', ['user_id' => $userId]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Logger::error('Error updating user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete user
     */
    public function deleteUser(int $userId): bool {
        try {
            $result = $this->db->delete('users', 'id = :id', ['id' => $userId]);

            if ($result > 0) {
                Logger::info('User deleted successfully', ['user_id' => $userId]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Logger::error('Error deleting user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get authentication statistics
     */
    public function getStats(): array {
        $stats = [
            'authenticated' => $this->check(),
            'session_status' => session_status(),
            'user_count' => 0,
            'active_users' => 0
        ];

        try {
            $stats['user_count'] = $this->db->count('users');
            $stats['active_users'] = $this->db->count('users', 'status = :status', ['status' => 'active']);
        } catch (\Exception $e) {
            Logger::error('Error getting auth stats: ' . $e->getMessage());
        }

        return $stats;
    }
}
