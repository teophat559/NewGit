<?php
namespace BVOTE\Core;

/**
 * BVOTE Session Class
 * Quản lý session với Redis và file fallback
 */
class Session {
    private $redis;
    private $fallbackDir;
    private $useRedis;
    private $sessionName;
    private $sessionId;

    public function __construct() {
        $this->redis = null;
        $this->fallbackDir = __DIR__ . '/../storage/sessions';
        $this->useRedis = false;
        $this->sessionName = 'BVOTE_SESSION';
        $this->sessionId = null;

        // Tạo thư mục sessions nếu chưa có
        if (!is_dir($this->fallbackDir)) {
            mkdir($this->fallbackDir, 0755, true);
        }

        // Set session handler
        $this->setSessionHandler();
    }

    /**
     * Set Redis connection
     */
    public function setRedis($redis): void {
        $this->redis = $redis;
        $this->useRedis = $redis !== null;
    }

    /**
     * Start session
     */
    public function start(): bool {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }

        // Set session name
        session_name($this->sessionName);

        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $this->isSecure(),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        // Start session
        $result = session_start();

        if ($result) {
            $this->sessionId = session_id();
            $this->regenerateId();
        }

        return $result;
    }

    /**
     * Set session handler
     */
    private function setSessionHandler(): void {
        if ($this->useRedis) {
            session_set_save_handler(
                [$this, 'open'],
                [$this, 'close'],
                [$this, 'read'],
                [$this, 'write'],
                [$this, 'destroy'],
                [$this, 'gc']
            );
        }
    }

    /**
     * Set session value
     */
    public function set(string $key, $value): void {
        $_SESSION[$key] = $value;
    }

    /**
     * Get session value
     */
    public function get(string $key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session key exists
     */
    public function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session key
     */
    public function remove(string $key): void {
        unset($_SESSION[$key]);
    }

    /**
     * Get all session data
     */
    public function all(): array {
        return $_SESSION;
    }

    /**
     * Clear all session data
     */
    public function clear(): void {
        $_SESSION = [];
    }

    /**
     * Destroy current session
     */
    public function destroy(): bool {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->clear();
            return session_destroy();
        }
        return true;
    }

    /**
     * Regenerate session ID
     */
    public function regenerateId(): bool {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $oldId = session_id();
            $result = session_regenerate_id(true);

            if ($result) {
                $this->sessionId = session_id();

                // Log session regeneration
                Logger::info('Session ID regenerated', [
                    'old_id' => $oldId,
                    'new_id' => $this->sessionId
                ]);
            }

            return $result;
        }
        return false;
    }

    /**
     * Get session ID
     */
    public function getId(): ?string {
        return $this->sessionId;
    }

    /**
     * Set session ID
     */
    public function setId(string $id): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_id($id);
            $this->sessionId = $id;
            return true;
        }
        return false;
    }

    /**
     * Flash data (data that persists for one request)
     */
    public function flash(string $key, $value): void {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Get flash data
     */
    public function getFlash(string $key, $default = null) {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /**
     * Check if flash data exists
     */
    public function hasFlash(string $key): bool {
        return isset($_SESSION['_flash'][$key]);
    }

    /**
     * Keep flash data for next request
     */
    public function keepFlash(array $keys): void {
        foreach ($keys as $key) {
            if (isset($_SESSION['_flash'][$key])) {
                $_SESSION['_flash'][$key] = $_SESSION['_flash'][$key];
            }
        }
    }

    /**
     * Session handlers for Redis
     */
    public function open($path, $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string {
        if ($this->useRedis && $this->redis) {
            try {
                $data = $this->redis->get("session:{$id}");
                return $data ?: '';
            } catch (\Exception $e) {
                Logger::warning('Redis session read failed: ' . $e->getMessage());
            }
        }

        return $this->readFileSession($id);
    }

    public function write($id, $data): bool {
        if ($this->useRedis && $this->redis) {
            try {
                $ttl = ini_get('session.gc_maxlifetime') ?: 1440;
                return $this->redis->setex("session:{$id}", $ttl, $data);
            } catch (\Exception $e) {
                Logger::warning('Redis session write failed: ' . $e->getMessage());
            }
        }

        return $this->writeFileSession($id, $data);
    }

    public function destroySession($id): bool {
        if ($this->useRedis && $this->redis) {
            try {
                return $this->redis->del("session:{$id}") > 0;
            } catch (\Exception $e) {
                Logger::warning('Redis session destroy failed: ' . $e->getMessage());
            }
        }

        return $this->destroyFileSession($id);
    }

    public function gc($maxlifetime): int {
        if ($this->useRedis && $this->redis) {
            // Redis handles expiration automatically
            return 0;
        }

        return $this->gcFileSessions($maxlifetime);
    }

    /**
     * File session methods
     */
    private function readFileSession(string $id): string {
        $filename = $this->getSessionFilename($id);

        if (!file_exists($filename)) {
            return '';
        }

        $data = file_get_contents($filename);
        $sessionData = unserialize($data);

        if (!is_array($sessionData) || !isset($sessionData['expires'])) {
            return '';
        }

        if (time() > $sessionData['expires']) {
            unlink($filename);
            return '';
        }

        return $sessionData['data'];
    }

    private function writeFileSession(string $id, string $data): bool {
        $filename = $this->getSessionFilename($id);
        $maxlifetime = ini_get('session.gc_maxlifetime') ?: 1440;

        $sessionData = [
            'data' => $data,
            'expires' => time() + $maxlifetime
        ];

        return file_put_contents($filename, serialize($sessionData)) !== false;
    }

    private function destroyFileSession(string $id): bool {
        $filename = $this->getSessionFilename($id);

        if (file_exists($filename)) {
            return unlink($filename);
        }

        return true;
    }

    private function gcFileSessions(int $maxlifetime): int {
        $files = glob($this->fallbackDir . '/sess_*');
        $deleted = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                $data = file_get_contents($file);
                $sessionData = unserialize($data);

                if (!is_array($sessionData) || !isset($sessionData['expires'])) {
                    unlink($file);
                    $deleted++;
                    continue;
                }

                if (time() > $sessionData['expires']) {
                    unlink($file);
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    private function getSessionFilename(string $id): string {
        return $this->fallbackDir . '/sess_' . $id;
    }

    /**
     * Check if connection is secure
     */
    private function isSecure(): bool {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
               (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
    }

    /**
     * Get session statistics
     */
    public function getStats(): array {
        $stats = [
            'status' => session_status(),
            'name' => session_name(),
            'id' => $this->sessionId,
            'driver' => $this->useRedis ? 'redis' : 'file',
            'redis_available' => $this->useRedis && $this->redis,
            'file_sessions_dir' => $this->fallbackDir
        ];

        if ($this->useRedis && $this->redis) {
            try {
                $keys = $this->redis->keys('session:*');
                $stats['redis_sessions_count'] = count($keys);
            } catch (\Exception $e) {
                $stats['redis_error'] = $e->getMessage();
            }
        }

        // File session stats
        $files = glob($this->fallbackDir . '/sess_*');
        $stats['file_sessions_count'] = count($files);
        $stats['file_sessions_size'] = $this->getDirectorySize($this->fallbackDir);

        return $stats;
    }

    private function getDirectorySize(string $path): int {
        $size = 0;
        $files = glob($path . '/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                $size += filesize($file);
            } elseif (is_dir($file)) {
                $size += $this->getDirectorySize($file);
            }
        }

        return $size;
    }
}
