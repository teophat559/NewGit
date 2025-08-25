<?php
// auth.php - Chuyển đổi từ lib/auth
function checkAuth($username, $password) {
  // Kiểm tra tài khoản mẫu
  return $username === 'admin' && $password === '123';
}
?>
