<?php
session_start();
session_unset();
session_destroy();

// Xóa cookie session nếu có
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect hoặc return JSON response
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Đăng xuất thành công']);
} else {
    header('Location: index.html');
}
exit;
?>
