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

$stmt = $conn->prepare("UPDATE achievements SET status = 'Approved' WHERE achievement_id = ?");
$stmt->bind_param("i", $achievement_id);
if ($stmt->execute()) {
    // Update leaderboard
    $stmt_points = $conn->prepare("SELECT user_id, total_points FROM achievements WHERE achievement_id = ?");
    $stmt_points->bind_param("i", $achievement_id);
    $stmt_points->execute();
    $result = $stmt_points->get_result();
    $achievement = $result->fetch_assoc();
    $user_id = $achievement['user_id'];
    $points = $achievement['total_points'];

    $stmt_leaderboard = $conn->prepare("INSERT INTO leaderboard (user_id, total_points) VALUES (?, ?) ON DUPLICATE KEY UPDATE total_points = total_points + ?");
    $stmt_leaderboard->bind_param("iii", $user_id, $points, $points);
    $stmt_leaderboard->execute();
    $stmt_leaderboard->close();
    $stmt_points->close();

    $_SESSION['admin_message'] = "Achievement approved successfully!";
    $_SESSION['admin_message_class'] = "bg-green-100";
} else {
    $_SESSION['admin_message'] = "Failed to approve achievement.";
    $_SESSION['admin_message_class'] = "bg-red-100";
}
$stmt->close();

$conn->close();
header("Location: ../view/adminView.php?page=Achievement");
exit();
?>