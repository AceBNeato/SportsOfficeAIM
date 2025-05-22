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

// Approve achievement
$achievement_id = filter_input(INPUT_POST, 'achievement_id', FILTER_VALIDATE_INT);
if (!$achievement_id) {
    $_SESSION['admin_message'] = "Invalid achievement ID.";
    $_SESSION['admin_message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

// Fetch achievement details to get user_id and additional info
$stmt = $conn->prepare("SELECT user_id, total_points, achievement_name FROM achievements WHERE achievement_id = ?");
$stmt->bind_param("i", $achievement_id);
$stmt->execute();
$result = $stmt->get_result();
$achievement = $result->fetch_assoc();
$stmt->close();

if (!$achievement) {
    $_SESSION['admin_message'] = "Achievement not found.";
    $_SESSION['admin_message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

$user_id = (int)$achievement['user_id'];
$points = (int)$achievement['total_points'];
$achievement_name = $achievement['achievement_name'] ?? 'Achievement #' . $achievement_id;

// Update achievement status
$stmt = $conn->prepare("UPDATE achievements SET status = 'Approved' WHERE achievement_id = ?");
$stmt->bind_param("i", $achievement_id);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    // Update leaderboard
    $stmt_leaderboard = $conn->prepare("INSERT INTO leaderboard (user_id, total_points) VALUES (?, ?) ON DUPLICATE KEY UPDATE total_points = total_points + ?");
    $stmt_leaderboard->bind_param("iii", $user_id, $points, $points);
    $stmt_leaderboard->execute();
    $stmt_leaderboard->close();

    // Create notification for the user
    $message = "Your achievement '$achievement_name' (ID: $achievement_id) has been approved! You earned $points points.";
    $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, timestamp, is_read) VALUES (?, ?, NOW(), FALSE)");
    $stmt_notify->bind_param("is", $user_id, $message);
    $notify_success = $stmt_notify->execute();
    $stmt_notify->close();

    if ($notify_success) {
        error_log("approveAchievement.php: Notified user_id=$user_id for approved achievement_id=$achievement_id");
    } else {
        error_log("approveAchievement.php: Failed to create notification for user_id=$user_id");
    }

    $_SESSION['admin_message'] = "Achievement approved successfully!";
    $_SESSION['admin_message_class'] = "bg-green-100";
} else {
    $_SESSION['admin_message'] = "Failed to approve achievement.";
    $_SESSION['admin_message_class'] = "bg-red-100";
}

$conn->close();
header("Location: ../view/adminView.php?page=Achievement");
exit();
?>