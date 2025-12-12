<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Clear browser cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to admin login page
header("Location: index.php");
exit();

// ADD THIS JAVASCRIPT CODE ONLY - Start
?>
<script>
    // Force redirect to home page after logout
    window.onload = function() {
        // Clear browser history
        if (window.history.replaceState) {
            window.history.replaceState(null, null, '../../index.php');
        }
        
        // Redirect to home page
        window.location.href = '../../index.php';
    };
</script>