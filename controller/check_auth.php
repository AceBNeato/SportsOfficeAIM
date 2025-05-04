<?php
// check_auth.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../view/loginView.php");
    exit;
}

// Check session activity timeout (30 minutes)
if (isset($_SESSION['user']['last_activity']) && (time() - $_SESSION['user']['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: ../view/loginView.php?timeout=1");
    exit;
}

// Update last activity time
$_SESSION['user']['last_activity'] = time();
?>