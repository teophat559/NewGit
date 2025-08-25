<?php
/**
 * BVOTE 2025 - Database Configuration
 * Professional Database Connection & Management
 *
 * Created: 2025-08-04
 * Version: 2.0
 */

// Prevent direct access
if (!defined('BVOTE_INIT')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

/**
 * Database configuration class
 */
class BVoteDatabase {
    private static $instance = null;
    private $connection = null;
    private $config = [];

    private function __construct() {
        $this->loadConfig();
        $this->connect();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load database configuration
     */
    private function loadConfig() {
        // Production configuration
        if (defined('BVOTE_DB_HOST')) {
            $this->config = [
                'host' => BVOTE_DB_HOST,
                'dbname' => BVOTE_DB_NAME,
                'username' => BVOTE_DB_USER,
                'password' => BVOTE_DB_PASS,
                'charset' => BVOTE_DB_CHARSET ?? 'utf8mb4'
            ];
        } else {
            // Development defaults
            $this->config = [
                'host' => 'localhost',
                'dbname' => 'bvote_production_db',
                'username' => 'bvote_system_user',
                'password' => 'BV2025_SecurePass!',
                'charset' => 'utf8mb4'
            ];
        }
    }

    /**
     * Establish database connection
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset={$this->config['charset']}";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->config['charset']} COLLATE {$this->config['charset']}_unicode_ci"
            ];

            $this->connection = new PDO($dsn, $this->config['username'], $this->config['password'], $options);

            // Log successful connection in development
            if (defined('BVOTE_DEBUG') && BVOTE_DEBUG) {
                error_log("BVOTE Database: Connected to {$this->config['dbname']} successfully");
            }

        } catch (PDOException $e) {
            $error_msg = "Database connection failed: " . $e->getMessage();
            error_log("BVOTE Database Error: " . $error_msg);

            if (defined('BVOTE_DEBUG') && BVOTE_DEBUG) {
                die("Database connection error: " . $e->getMessage());
            } else {
                die("Database connection error. Please check configuration.");
            }
        }
    }

    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Execute query with parameters
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("BVOTE Database Query Error: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }

    /**
     * Get single row
     */
    public function fetchRow($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Get all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insert record and return last insert ID
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, $data);

        return $this->connection->lastInsertId();
    }

    /**
     * Update records
     */
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $set);

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);

        return $this->query($sql, $params);
    }

    /**
     * Delete records
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params);
    }

    /**
     * Get table schema
     */
    public function getTableSchema($table) {
        $sql = "DESCRIBE {$table}";
        return $this->fetchAll($sql);
    }

    /**
     * Check if table exists
     */
    public function tableExists($table) {
        $sql = "SHOW TABLES LIKE :table";
        $result = $this->fetchRow($sql, ['table' => $table]);
        return !empty($result);
    }

    /**
     * Get database statistics
     */
    public function getStats() {
        $stats = [];

        // Get table list and row counts
        $tables = $this->fetchAll("SHOW TABLES");
        $stats['tables'] = [];

        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            $count = $this->fetchRow("SELECT COUNT(*) as count FROM {$tableName}");
            $stats['tables'][$tableName] = $count['count'];
        }

        // Get database size
        $size = $this->fetchRow("
            SELECT
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.tables
            WHERE table_schema = :dbname
        ", ['dbname' => $this->config['dbname']]);

        $stats['size_mb'] = $size['size_mb'] ?? 0;
        $stats['total_tables'] = count($stats['tables']);
        $stats['total_records'] = array_sum($stats['tables']);

        return $stats;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollback();
    }
}

// Global functions for easy access
function bvote_db() {
    return BVoteDatabase::getInstance();
}

function bvote_query($sql, $params = []) {
    return bvote_db()->query($sql, $params);
}

function bvote_fetch_row($sql, $params = []) {
    return bvote_db()->fetchRow($sql, $params);
}

function bvote_fetch_all($sql, $params = []) {
    return bvote_db()->fetchAll($sql, $params);
}

function bvote_insert($table, $data) {
    return bvote_db()->insert($table, $data);
}

function bvote_update($table, $data, $where, $whereParams = []) {
    return bvote_db()->update($table, $data, $where, $whereParams);
}

function bvote_delete($table, $where, $params = []) {
    return bvote_db()->delete($table, $where, $params);
}

// Initialize database connection
try {
    $bvote_database = BVoteDatabase::getInstance();
} catch (Exception $e) {
    error_log("BVOTE Database Initialization Error: " . $e->getMessage());
    if (defined('BVOTE_DEBUG') && BVOTE_DEBUG) {
        die("Database initialization failed: " . $e->getMessage());
    }
}
?>
