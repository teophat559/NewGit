<?php
/**
 * User Logout Page - BVOTE
 */
session_start();

// Xóa session
session_destroy();

// Chuyển hướng về trang đăng nhập
header('Location: /user/login');
exit;
?>
