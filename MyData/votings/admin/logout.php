<?php
/**
 * BVOTE 2025 - Admin Logout
 * Secure logout with session cleanup
 */

session_start();

// Log the logout
if (isset($_SESSION['bvote_admin_username'])) {
    error_log("BVOTE Admin Logout: " . $_SESSION['bvote_admin_username'] . " logged out at " . date('Y-m-d H:i:s'));
}

// Clear all session data
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>
