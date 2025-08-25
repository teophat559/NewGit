<?php
// UserAuthContext.php - Chuyển đổi từ UserAuthContext.jsx
?>
<?php
// Xử lý xác thực người dùng bằng PHP
session_start();
function isUserLoggedIn() {
  return isset($_SESSION['user']);
}
?>
