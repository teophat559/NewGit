<?php
/**
 * Admin Logout Page - BVOTE
 * Trang đăng xuất cho admin với xóa session và ghi audit log
 */
session_start();

if (isset($_SESSION['admin_id'])) {
    require_once __DIR__ . '/../../includes/database.php';

    try {
        $db = getConnection();

        // Ghi audit log trước khi đăng xuất
        $stmt = $db->prepare("
            INSERT INTO audit_logs (actor_type, actor_id, action, details_json)
            VALUES ('admin', ?, 'logout', ?)
        ");
        $stmt->execute([
            $_SESSION['admin_id'],
            json_encode([
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'logout_time' => date('Y-m-d H:i:s')
            ])
        ]);

        // Cập nhật last_logout trong users table
        $stmt = $db->prepare("UPDATE users SET last_logout = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);

    } catch (Exception $e) {
        // Log error nếu có vấn đề với database
        error_log("Admin logout error: " . $e->getMessage());
    }
}

// Xóa tất cả session data
session_unset();
session_destroy();

// Chuyển hướng về trang đăng nhập admin
header('Location: /admin/login?message=logged_out');
exit;
?>
