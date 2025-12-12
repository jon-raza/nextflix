<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Prevent page caching - IMPORTANT FOR BACK BUTTON FIX
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store the current URL for redirection after login
    $current_url = $_SERVER['REQUEST_URI'];

    // Determine if it's admin or user area
    if (strpos($current_url, '/admin/') !== false) {
        // Admin area - redirect to admin login
        header("Location: ../admin/index.php");
    } else {
        // User area - redirect to user login
        header("Location: ../user/login.php");
    }
    exit();
}

// Optional: Session timeout (30 minutes - 1800 seconds)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    // Session expired
    session_unset();
    session_destroy();
    header("Location: ../user/login.php");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Optional: Regenerate session ID periodically for security
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    // Session started more than 30 minutes ago
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}
