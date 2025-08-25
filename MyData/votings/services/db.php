<?php
// db.php - Chuyển đổi từ dịch vụ db.js
require_once __DIR__ . '/../lib/env.php';
$host = env('DB_HOST', 'localhost');
$dbname = env('DB_DATABASE', 'ten_du_an');
$user = env('DB_USERNAME', 'root');
$pass = env('DB_PASSWORD', '');
try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  return $pdo;
} catch (PDOException $e) {
  die('Kết nối thất bại: ' . $e->getMessage());
}
?>
