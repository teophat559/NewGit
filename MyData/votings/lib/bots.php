<?php
// bots.php - Chuyển đổi từ lib/bots
function runBot($type) {
  if ($type === 'login') {
    return 'Bot đăng nhập đã chạy!';
  }
  return 'Bot không xác định!';
}
?>
