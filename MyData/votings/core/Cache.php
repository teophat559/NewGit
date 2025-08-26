<?php
namespace BVOTE\Core;

/**
 * BVOTE Cache Class
 * Quản lý caching với Redis và file fallback
 */
class Cache {
    private $redis;
    private $fallbackDir;
    private $useRedis;

    public function __construct($redis = null) {
        $this->redis = $redis;
        $this->fallbackDir = __DIR__ . '/../storage/cache';
        $this->useRedis = $redis !== null;

        // Tạo thư mục cache nếu chưa có
        if (!is_dir($this->fallbackDir)) {
            mkdir($this->fallbackDir, 0755, true);
        }
    }

    /**
     * Set cache value
     */
    public function set(string $key, $value, int $ttl = 3600): bool {
        if ($this->useRedis && $this->redis) {
            try {
                return $this->redis->setex($key, $ttl, serialize($value));
            } catch (\Exception $e) {
                Logger::warning('Redis cache failed, falling back to file cache: ' . $e->getMessage());
            }
        }

        return $this->setFileCache($key, $value, $ttl);
    }

    /**
     * Get cache value
     */
    public function get(string $key, $default = null) {
        if ($this->useRedis && $this->redis) {
            try {
                $value = $this->redis->get($key);
                if ($value !== false) {
                    return unserialize($value);
                }
            } catch (\Exception $e) {
                Logger::warning('Redis cache failed, falling back to file cache: ' . $e->getMessage());
            }
        }

        return $this->getFileCache($key, $default);
    }

    /**
     * Delete cache key
     */
    public function delete(string $key): bool {
        if ($this->useRedis && $this->redis) {
            try {
                return $this->redis->del($key) > 0;
            } catch (\Exception $e) {
                Logger::warning('Redis delete failed: ' . $e->getMessage());
            }
        }

        return $this->deleteFileCache($key);
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool {
        if ($this->useRedis && $this->redis) {
            try {
                return $this->redis->exists($key);
            } catch (\Exception $e) {
                Logger::warning('Redis exists check failed: ' . $e->getMessage());
            }
        }

        return $this->hasFileCache($key);
    }

    /**
     * Increment cache value
     */
    public function increment(string $key, int $value = 1, int $ttl = 3600): int {
        if ($this->useRedis && $this->redis) {
            try {
                $result = $this->redis->incrBy($key, $value);
                $this->redis->expire($key, $ttl);
                return $result;
            } catch (\Exception $e) {
                Logger::warning('Redis increment failed: ' . $e->getMessage());
            }
        }

        return $this->incrementFileCache($key, $value, $ttl);
    }

    /**
     * Get TTL of key
     */
    public function getTtl(string $key): int {
        if ($this->useRedis && $this->redis) {
            try {
                return $this->redis->ttl($key);
            } catch (\Exception $e) {
                Logger::warning('Redis TTL check failed: ' . $e->getMessage());
            }
        }

        return $this->getFileCacheTtl($key);
    }

    /**
     * Get all keys matching pattern
     */
    public function getKeys(string $pattern): array {
        if ($this->useRedis && $this->redis) {
            try {
                return $this->redis->keys($pattern);
            } catch (\Exception $e) {
                Logger::warning('Redis keys pattern failed: ' . $e->getMessage());
            }
        }

        return $this->getFileCacheKeys($pattern);
    }

    /**
     * Clear all cache
     */
    public function clear(): bool {
        if ($this->useRedis && $this->redis) {
            try {
                return $this->redis->flushDB();
            } catch (\Exception $e) {
                Logger::warning('Redis flush failed: ' . $e->getMessage());
            }
        }

        return $this->clearFileCache();
    }

    /**
     * File cache methods
     */
    private function setFileCache(string $key, $value, int $ttl): bool {
        $filename = $this->getCacheFilename($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];

        return file_put_contents($filename, serialize($data)) !== false;
    }

    private function getFileCache(string $key, $default = null) {
        $filename = $this->getCacheFilename($key);

        if (!file_exists($filename)) {
            return $default;
        }

        $data = unserialize(file_get_contents($filename));

        if (!is_array($data) || !isset($data['expires'])) {
            return $default;
        }

        if (time() > $data['expires']) {
            unlink($filename);
            return $default;
        }

        return $data['value'];
    }

    private function deleteFileCache(string $key): bool {
        $filename = $this->getCacheFilename($key);

        if (file_exists($filename)) {
            return unlink($filename);
        }

        return true;
    }

    private function hasFileCache(string $key): bool {
        $filename = $this->getCacheFilename($key);

        if (!file_exists($filename)) {
            return false;
        }

        $data = unserialize(file_get_contents($filename));

        if (!is_array($data) || !isset($data['expires'])) {
            return false;
        }

        if (time() > $data['expires']) {
            unlink($filename);
            return false;
        }

        return true;
    }

    private function incrementFileCache(string $key, int $value, int $ttl): int {
        $current = $this->getFileCache($key, 0);
        $newValue = $current + $value;

        $this->setFileCache($key, $newValue, $ttl);

        return $newValue;
    }

    private function getFileCacheTtl(string $key): int {
        $filename = $this->getCacheFilename($key);

        if (!file_exists($filename)) {
            return -1;
        }

        $data = unserialize(file_get_contents($filename));

        if (!is_array($data) || !isset($data['expires'])) {
            return -1;
        }

        $ttl = $data['expires'] - time();
        return max(0, $ttl);
    }

    private function getFileCacheKeys(string $pattern): array {
        $keys = [];
        $files = glob($this->fallbackDir . '/*');

        foreach ($files as $file) {
            if (is_file($file)) {
                $key = basename($file);
                if (fnmatch($pattern, $key)) {
                    $keys[] = $key;
                }
            }
        }

        return $keys;
    }

    private function clearFileCache(): bool {
        $files = glob($this->fallbackDir . '/*');
        $success = true;

        foreach ($files as $file) {
            if (is_file($file)) {
                if (!unlink($file)) {
                    $success = false;
                }
            }
        }

        return $success;
    }

    private function getCacheFilename(string $key): string {
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return $this->fallbackDir . '/' . $safeKey;
    }

    /**
     * Cache statistics
     */
    public function getStats(): array {
        $stats = [
            'driver' => $this->useRedis ? 'redis' : 'file',
            'redis_available' => $this->useRedis && $this->redis,
            'file_cache_dir' => $this->fallbackDir
        ];

        if ($this->useRedis && $this->redis) {
            try {
                $info = $this->redis->info();
                $stats['redis_info'] = $info;
            } catch (\Exception $e) {
                $stats['redis_error'] = $e->getMessage();
            }
        }

        // File cache stats
        $files = glob($this->fallbackDir . '/*');
        $stats['file_cache_count'] = count($files);
        $stats['file_cache_size'] = $this->getDirectorySize($this->fallbackDir);

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
