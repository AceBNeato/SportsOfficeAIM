<?php
session_start();

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

// Debug session contents
error_log("Session contents: " . print_r($_SESSION, true));

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    $_SESSION['admin_message'] = "Database connection failed.";
    $_SESSION['admin_message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

// Check if admin is logged in
// Temporary fallback to check for user role
if (!isset($_SESSION['admin']['id']) && !(isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin')) {
    error_log("Admin not logged in: admin_id=" . ($_SESSION['admin']['id'] ?? 'not set') . ", user_role=" . ($_SESSION['user']['role'] ?? 'not set'));
    $_SESSION['admin_message'] = "Admin not logged in.";
    $_SESSION['admin_message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

// CSRF token validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    error_log("Invalid CSRF token: received=" . ($_POST['csrf_token'] ?? 'not set') . ", expected=" . ($_SESSION['csrf_token'] ?? 'not set'));
    $_SESSION['admin_message'] = "Invalid CSRF token.";
    $_SESSION['admin_message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Approve achievement
    if (isset($_POST['approve_achievement'])) {
        $achievement_id = filter_input(INPUT_POST, 'achievement_id', FILTER_VALIDATE_INT);
        if ($achievement_id) {
            // Check if achievement exists and is pending
            $stmt_check = $conn->prepare("SELECT user_id, total_points, status FROM achievements WHERE achievement_id = ?");
            $stmt_check->bind_param("i", $achievement_id);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            if ($result->num_rows === 0) {
                error_log("Achievement ID $achievement_id not found");
                $_SESSION['admin_message'] = "Achievement not found.";
                $_SESSION['admin_message_class'] = "bg-red-100";
                $stmt_check->close();
            } else {
                $achievement = $result->fetch_assoc();
                $stmt_check->close();
                if ($achievement['status'] !== 'Pending') {
                    error_log("Achievement ID $achievement_id is not Pending: " . $achievement['status']);
                    $_SESSION['admin_message'] = "Achievement is not pending.";
                    $_SESSION['admin_message_class'] = "bg-red-100";
                } else {
                    $stmt = $conn->prepare("UPDATE achievements SET status = 'Approved' WHERE achievement_id = ?");
                    $stmt->bind_param("i", $achievement_id);
                    if ($stmt->execute()) {
                        // Update leaderboard
                        $user_id = $achievement['user_id'];
                        $points = $achievement['total_points'];
                        $stmt_leaderboard = $conn->prepare("INSERT INTO leaderboard (user_id, total_points) VALUES (?, ?) ON DUPLICATE KEY UPDATE total_points = total_points + ?");
                        $stmt_leaderboard->bind_param("iii", $user_id, $points, $points);
                        if ($stmt_leaderboard->execute()) {
                            $_SESSION['admin_message'] = "Achievement approved successfully!";
                            $_SESSION['admin_message_class'] = "bg-green-100";
                        } else {
                            error_log("Failed to update leaderboard for user_id $user_id: " . $stmt_leaderboard->error);
                            $_SESSION['admin_message'] = "Failed to update leaderboard.";
                            $_SESSION['admin_message_class'] = "bg-red-100";
                        }
                        $stmt_leaderboard->close();
                    } else {
                        error_log("Failed to approve achievement ID $achievement_id: " . $stmt->error);
                        $_SESSION['admin_message'] = "Failed to approve achievement.";
                        $_SESSION['admin_message_class'] = "bg-red-100";
                    }
                    $stmt->close();
                }
            }
        } else {
            error_log("Invalid achievement ID: " . ($_POST['achievement_id'] ?? 'not set'));
            $_SESSION['admin_message'] = "Invalid achievement ID.";
            $_SESSION['admin_message_class'] = "bg-red-100";
        }
    }

    // Reject achievement
    if (isset($_POST['reject_achievement'])) {
        $achievement_id = filter_input(INPUT_POST, 'achievement_id', FILTER_VALIDATE_INT);
        $rejection_reason = filter_input(INPUT_POST, 'rejection_reason', FILTER_SANITIZE_STRING);
        if ($achievement_id && $rejection_reason) {
            // Check if achievement exists and is pending
            $stmt_check = $conn->prepare("SELECT status FROM achievements WHERE achievement_id = ?");
            $stmt_check->bind_param("i", $achievement_id);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            if ($result->num_rows === 0) {
                error_log("Achievement ID $achievement_id not found");
                $_SESSION['admin_message'] = "Achievement not found.";
                $_SESSION['admin_message_class'] = "bg-red-100";
                $stmt_check->close();
            } else {
                $achievement = $result->fetch_assoc();
                $stmt_check->close();
                if ($achievement['status'] !== 'Pending') {
                    error_log("Achievement ID $achievement_id is not Pending: " . $achievement['status']);
                    $_SESSION['admin_message'] = "Achievement is not pending.";
                    $_SESSION['admin_message_class'] = "bg-red-100";
                } else {
                    $stmt = $conn->prepare("UPDATE achievements SET status = 'Rejected', rejection_reason = ? WHERE achievement_id = ?");
                    $stmt->bind_param("si", $rejection_reason, $achievement_id);
                    if ($stmt->execute()) {
                        $_SESSION['admin_message'] = "Achievement rejected successfully.";
                        $_SESSION['admin_message_class'] = "bg-green-100";
                    } else {
                        error_log("Failed to reject achievement ID $achievement_id: " . $stmt->error);
                        $_SESSION['admin_message'] = "Failed to reject achievement.";
                        $_SESSION['admin_message_class'] = "bg-red-100";
                    }
                    $stmt->close();
                }
            }
        } else {
            error_log("Invalid achievement ID or rejection reason: ID=" . ($_POST['achievement_id'] ?? 'not set') . ", Reason=" . ($_POST['rejection_reason'] ?? 'not set'));
            $_SESSION['admin_message'] = "Invalid achievement ID or rejection reason.";
            $_SESSION['admin_message_class'] = "bg-red-100";
        }
    }
}

$conn->close();
header("Location: ../view/adminView.php?page=Achievement");
exit();
?>