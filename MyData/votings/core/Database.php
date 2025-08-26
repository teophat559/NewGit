<?php
namespace BVOTE\Core;

use PDO;
use PDOException;
use BVOTE\Core\Logger;

/**
 * BVOTE Core Database Class
 * Quản lý kết nối và thao tác database
 */
class Database {
    private $pdo;
    private $host;
    private $database;
    private $username;
    private $password;
    private $port;
    private $charset;
    private $options;

    public function __construct(
        string $host,
        string $database,
        string $username,
        string $password,
        int $port = 3306,
        string $charset = 'utf8mb4'
    ) {
        $this->host = $host;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
        $this->charset = $charset;

        $this->options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
            PDO::ATTR_PERSISTENT => true,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ];

        $this->connect();
    }

    /**
     * Kết nối database
     */
    private function connect(): void {
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset={$this->charset}";
            $this->pdo = new PDO($dsn, $this->username, $this->password, $this->options);

            Logger::info('Database connected successfully', [
                'host' => $this->host,
                'database' => $this->database,
                'port' => $this->port
            ]);

        } catch (PDOException $e) {
            Logger::error('Database connection failed: ' . $e->getMessage(), [
                'host' => $this->host,
                'database' => $this->database,
                'port' => $this->port
            ]);

            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Get PDO instance
     */
    public function getPdo(): PDO {
        return $this->pdo;
    }

    /**
     * Execute query
     */
    public function query(string $sql, array $params = []): \PDOStatement {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt;

        } catch (PDOException $e) {
            Logger::error('Database query failed: ' . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);

            throw new \Exception('Database query failed: ' . $e->getMessage());
        }
    }

    /**
     * Execute insert query
     */
    public function insert(string $table, array $data): int {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $this->query($sql, $data);

        return $this->pdo->lastInsertId();
    }

    /**
     * Execute update query
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }

        $setClause = implode(', ', $setParts);
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";

        $params = array_merge($data, $whereParams);
        $stmt = $this->query($sql, $params);

        return $stmt->rowCount();
    }

    /**
     * Execute delete query
     */
    public function delete(string $table, string $where, array $params = []): int {
        $sql = "DELETE FROM {$table} WHERE {$where}";

        $stmt = $this->query($sql, $params);

        return $stmt->rowCount();
    }

    /**
     * Select single record
     */
    public function selectOne(string $table, string $where = '1', array $params = [], array $columns = ['*']): ?array {
        $columnList = implode(', ', $columns);
        $sql = "SELECT {$columnList} FROM {$table} WHERE {$where} LIMIT 1";

        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();

        return $result ?: null;
    }

    /**
     * Select multiple records
     */
    public function select(string $table, string $where = '1', array $params = [], array $columns = ['*'], string $orderBy = '', int $limit = 0, int $offset = 0): array {
        $columnList = implode(', ', $columns);
        $sql = "SELECT {$columnList} FROM {$table} WHERE {$where}";

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
            if ($offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }

        $stmt = $this->query($sql, $params);

        return $stmt->fetchAll();
    }

    /**
     * Count records
     */
    public function count(string $table, string $where = '1', array $params = []): int {
        $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$where}";

        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();

        return (int)($result['count'] ?? 0);
    }

    /**
     * Check if table exists
     */
    public function tableExists(string $table): bool {
        $sql = "SHOW TABLES LIKE :table";

        try {
            $stmt = $this->query($sql, ['table' => $table]);
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get table structure
     */
    public function getTableStructure(string $table): array {
        $sql = "DESCRIBE {$table}";

        $stmt = $this->query($sql);

        return $stmt->fetchAll();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool {
        return $this->pdo->rollback();
    }

    /**
     * Check if in transaction
     */
    public function inTransaction(): bool {
        return $this->pdo->inTransaction();
    }

    /**
     * Execute raw SQL
     */
    public function raw(string $sql, array $params = []): \PDOStatement {
        return $this->query($sql, $params);
    }

    /**
     * Close connection
     */
    public function close(): void {
        $this->pdo = null;
        Logger::info('Database connection closed');
    }

    /**
     * Ping database
     */
    public function ping(): bool {
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get database info
     */
    public function getInfo(): array {
        return [
            'host' => $this->host,
            'database' => $this->database,
            'port' => $this->port,
            'charset' => $this->charset,
            'connected' => $this->pdo !== null,
            'in_transaction' => $this->inTransaction()
        ];
    }
}
