<?php
// AuthContext.php - Chuyển đổi từ AuthContext.jsx
?>
<?php
// Xử lý context xác thực bằng PHP
function getAuthUser() {
  return $_SESSION['user'] ?? null;
}
?>
