<?php
// Agar session start nahi hua hai to hi session_start() call karo
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in - dono conditions check karo
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_logged_in'])) {
    header("Location: index.php");
    exit;
}

// CSRF protection ke liye
if (!isset($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}
?>