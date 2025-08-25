<?php
/**
 * Simple Database Connection for Setup Script
 */

function getConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            // XAMPP default database configuration
            $host = 'localhost';
            $dbname = 'bvote_system';
            $username = 'root';
            $password = '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;charset=$charset";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset COLLATE {$charset}_unicode_ci"
            ];

            $pdo = new PDO($dsn, $username, $password, $options);

            // Tạo database nếu chưa có
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET $charset COLLATE {$charset}_unicode_ci");
            $pdo->exec("USE `$dbname`");

        } catch (PDOException $e) {
            die("❌ Không thể kết nối database: " . $e->getMessage() . "\n");
        }
    }

    return $pdo;
}
?>
