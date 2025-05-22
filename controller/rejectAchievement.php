<?php
session_start();

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    $_SESSION['admin_message'] = "Database connection failed.";
    $_SESSION['admin_message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

// CSRF token validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['admin_message'] = "Invalid CSRF token.";
    $_SESSION['admin_message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

// Check if admin is logged in
if (!isset($_SESSION['admin']['id'])) {
    $_SESSION['admin_message'] = "Admin not logged in.";
    $_SESSION['admin_message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

// Reject achievement
$achievement_id = filter_input(INPUT_POST, 'achievement_id', FILTER_VALIDATE_INT);
$rejection_reason = filter_input(INPUT_POST, 'rejection_reason', FILTER_SANITIZE_STRING);
if (!$achievement_id || !$rejection_reason) {
    $_SESSION['admin_message'] = "Invalid achievement ID or rejection reason.";
    $_SESSION['admin_message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

$stmt = $conn->prepare("UPDATE achievements SET status = 'Rejected', rejection_reason = ? WHERE achievement_id = ?");
$stmt->bind_param("si", $rejection_reason, $achievement_id);
if ($stmt->execute()) {
    $_SESSION['admin_message'] = "Achievement rejected successfully.";
    $_SESSION['admin_message_class'] = "bg-green-100";
} else {
    $_SESSION['admin_message'] = "Failed to reject achievement.";
    $_SESSION['admin_message_class'] = "bg-red-100";
}
$stmt->close();

$conn->close();
header("Location: ../view/adminView.php?page=Achievement");
exit();
?>