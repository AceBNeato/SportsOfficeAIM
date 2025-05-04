<?php
// Start session securely
session_start();

// Regenerate session ID first to prevent session fixation
session_regenerate_id(true);

// Unset all session variables
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Completely destroy the session
session_destroy();

// Clear any remaining session data
unset($_SESSION);

// Additional security headers to prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to login page with random parameter to prevent caching
header("Location: ../view/loginView.php?logout=" . bin2hex(random_bytes(4)));
exit;
?>