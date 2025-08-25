<?php
// auth.php - Chuyển đổi từ dịch vụ xác thực React
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'] ?? '';
  $password = $_POST['password'] ?? '';
  $action = $_POST['action'] ?? '';
  if ($action === 'create') {
    // Xử lý tạo tài khoản
    $_SESSION['user'] = $username;
    echo 'Tạo tài khoản thành công!';
  } else {
    // Xử lý đăng nhập
    if ($username === 'admin' && $password === '123') {
      $_SESSION['user'] = $username;
      echo 'Đăng nhập thành công!';
    } else {
      echo 'Sai thông tin đăng nhập!';
    }
  }
  exit;
}
?>
