<?php
session_start();

// Enable error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start debug logging
$debugLog = fopen('../debug.log', 'a') or die("Can't open debug.log");
fwrite($debugLog, "handleAchievementAction started at " . date('Y-m-d H:i:s') . " PST\n");

$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    $error = "Database connection failed: " . $conn->connect_error;
    fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
    $_SESSION['achievement_message'] = $error;
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $error = "CSRF token validation failed. Received: " . ($_POST['csrf_token'] ?? 'none') . ", Expected: " . ($_SESSION['csrf_token'] ?? 'none');
    fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
    $_SESSION['achievement_message'] = "Invalid CSRF token.";
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

// Check if admin is logged in
if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    $error = "Unauthorized access attempt by user: " . ($_SESSION['user']['role'] ?? 'none');
    fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
    $_SESSION['achievement_message'] = "Unauthorized access.";
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

if (isset($_POST['action']) && isset($_POST['achievement_id'])) {
    $achievement_id = filter_input(INPUT_POST, 'achievement_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'];

    fwrite($debugLog, "Received POST data: " . print_r($_POST, true) . " at " . date('Y-m-d H:i:s') . " PST\n");

    if (!$achievement_id || !in_array($action, ['approve', 'reject', 'resubmit'])) {
        $error = "Invalid request - achievement_id: $achievement_id, action: $action";
        fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
        $_SESSION['achievement_message'] = "Invalid request.";
        $_SESSION['message_class'] = "bg-red-100";
        header("Location: ../view/adminView.php?page=Achievement");
        exit();
    }

    // Get user_id and athlete_name for notification
    $stmt = $conn->prepare("SELECT user_id, athlete_name, status FROM achievements WHERE achievement_id = ?");
    $stmt->bind_param("i", $achievement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    fwrite($debugLog, "Query executed: SELECT user_id, athlete_name, status FROM achievements WHERE achievement_id = $achievement_id at " . date('Y-m-d H:i:s') . " PST\n");
    if ($result->num_rows === 0) {
        $error = "Achievement not found: ID $achievement_id";
        fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
        $_SESSION['achievement_message'] = "Achievement not found.";
        $_SESSION['message_class'] = "bg-red-100";
        $stmt->close();
        $conn->close();
        header("Location: ../view/adminView.php?page=Achievement");
        exit();
    }
    $achievement = $result->fetch_assoc();
    $user_id = $achievement['user_id'];
    $athlete_name = $achievement['athlete_name'];
    $current_status = $achievement['status'];
    $stmt->close();

    fwrite($debugLog, "Fetched achievement - ID: $achievement_id, User ID: $user_id, Athlete: $athlete_name, Current Status: $current_status at " . date('Y-m-d H:i:s') . " PST\n");

    // Process the action
    if ($action === 'approve') {
        if ($current_status !== 'Pending') {
            $error = "Cannot approve achievement ID $achievement_id - Current status: $current_status";
            fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
            $_SESSION['achievement_message'] = "Cannot approve: Achievement is not Pending.";
            $_SESSION['message_class'] = "bg-red-100";
            $conn->close();
            header("Location: ../view/adminView.php?page=Achievement");
            exit();
        }

        $stmt = $conn->prepare("UPDATE achievements SET status = 'Approved', rejection_reason = NULL WHERE achievement_id = ?");
        $stmt->bind_param("i", $achievement_id);
        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            fwrite($debugLog, "Approve action - Achievement ID: $achievement_id, Affected rows: $affected_rows at " . date('Y-m-d H:i:s') . " PST\n");
            if ($affected_rows > 0) {
                // Insert notification for user
                $message = "Your achievement submission for '$athlete_name' has been approved and added to the leaderboard.";
                $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)");
                $notify_stmt->bind_param("is", $user_id, $message);
                if ($notify_stmt->execute()) {
                    fwrite($debugLog, "Notification inserted for user ID: $user_id at " . date('Y-m-d H:i:s') . " PST\n");
                } else {
                    $error = "Failed to insert notification: " . $notify_stmt->error;
                    fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
                }
                $notify_stmt->close();

                $_SESSION['achievement_message'] = "Achievement approved successfully.";
                $_SESSION['message_class'] = "bg-green-100";
            } else {
                $error = "No rows updated for approve action - Achievement ID: $achievement_id";
                fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
                $_SESSION['achievement_message'] = "Failed to approve achievement: No changes made.";
                $_SESSION['message_class'] = "bg-red-100";
            }
        } else {
            $error = "Approve query failed: " . $stmt->error;
            fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
            $_SESSION['achievement_message'] = "Failed to approve achievement: " . $stmt->error;
            $_SESSION['message_class'] = "bg-red-100";
        }
        $stmt->close();
    } elseif ($action === 'reject') {
        if ($current_status !== 'Pending') {
            $error = "Cannot reject achievement ID $achievement_id - Current status: $current_status";
            fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
            $_SESSION['achievement_message'] = "Cannot reject: Achievement is not Pending.";
            $_SESSION['message_class'] = "bg-red-100";
            $conn->close();
            header("Location: ../view/adminView.php?page=Achievement");
            exit();
        }

        $rejection_reason = filter_input(INPUT_POST, 'rejection_reason', FILTER_SANITIZE_STRING);
        if (!$rejection_reason) {
            $error = "Rejection reason missing for achievement ID: $achievement_id";
            fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
            $_SESSION['achievement_message'] = "Rejection reason is required.";
            $_SESSION['message_class'] = "bg-red-100";
            $conn->close();
            header("Location: ../view/adminView.php?page=Achievement");
            exit();
        }

        $stmt = $conn->prepare("UPDATE achievements SET status = 'Rejected', rejection_reason = ? WHERE achievement_id = ?");
        $stmt->bind_param("si", $rejection_reason, $achievement_id);
        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            fwrite($debugLog, "Reject action - Achievement ID: $achievement_id, Affected rows: $affected_rows at " . date('Y-m-d H:i:s') . " PST\n");
            if ($affected_rows > 0) {
                // Insert notification for user
                $message = "Your achievement submission for '$athlete_name' was rejected. Reason: $rejection_reason. You may resubmit after making corrections.";
                $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)");
                $notify_stmt->bind_param("is", $user_id, $message);
                if ($notify_stmt->execute()) {
                    fwrite($debugLog, "Notification inserted for user ID: $user_id at " . date('Y-m-d H:i:s') . " PST\n");
                } else {
                    $error = "Failed to insert notification: " . $notify_stmt->error;
                    fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
                }
                $notify_stmt->close();

                $_SESSION['achievement_message'] = "Achievement rejected successfully.";
                $_SESSION['message_class'] = "bg-green-100";
            } else {
                $error = "No rows updated for reject action - Achievement ID: $achievement_id";
                fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
                $_SESSION['achievement_message'] = "Failed to reject achievement: No changes made.";
                $_SESSION['message_class'] = "bg-red-100";
            }
        } else {
            $error = "Reject query failed: " . $stmt->error;
            fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
            $_SESSION['achievement_message'] = "Failed to reject achievement: " . $stmt->error;
            $_SESSION['message_class'] = "bg-red-100";
        }
        $stmt->close();
    } elseif ($action === 'resubmit') {
        if ($current_status !== 'Rejected') {
            $error = "Cannot allow resubmission for achievement ID $achievement_id - Current status: $current_status";
            fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
            $_SESSION['achievement_message'] = "Cannot allow resubmission: Achievement is not Rejected.";
            $_SESSION['message_class'] = "bg-red-100";
            $conn->close();
            header("Location: ../view/adminView.php?page=Achievement");
            exit();
        }

        $stmt = $conn->prepare("UPDATE achievements SET status = 'Pending', rejection_reason = NULL WHERE achievement_id = ?");
        $stmt->bind_param("i", $achievement_id);
        if ($stmt->execute()) {
            $affected_rows = $stmt->affected_rows;
            fwrite($debugLog, "Resubmit action - Achievement ID: $achievement_id, Affected rows: $affected_rows at " . date('Y-m-d H:i:s') . " PST\n");
            if ($affected_rows > 0) {
                // Insert notification for user
                $message = "Your achievement submission for '$athlete_name' has been reopened for resubmission.";
                $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)");
                $notify_stmt->bind_param("is", $user_id, $message);
                if ($notify_stmt->execute()) {
                    fwrite($debugLog, "Notification inserted for user ID: $user_id at " . date('Y-m-d H:i:s') . " PST\n");
                } else {
                    $error = "Failed to insert notification: " . $notify_stmt->error;
                    fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
                }
                $notify_stmt->close();

                $_SESSION['achievement_message'] = "Achievement reopened for resubmission.";
                $_SESSION['message_class'] = "bg-green-100";
            } else {
                $error = "No rows updated for resubmit action - Achievement ID: $achievement_id";
                fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
                $_SESSION['achievement_message'] = "Failed to reopen achievement for resubmission.";
                $_SESSION['message_class'] = "bg-red-100";
            }
        } else {
            $error = "Resubmit query failed: " . $stmt->error;
            fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
            $_SESSION['achievement_message'] = "Failed to reopen achievement: " . $stmt->error;
            $_SESSION['message_class'] = "bg-red-100";
        }
        $stmt->close();
    }

    $conn->close();
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
} else {
    $error = "Missing action or achievement_id in POST data: " . print_r($_POST, true);
    fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
    $_SESSION['achievement_message'] = "Missing action or achievement ID.";
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

// Close debug log
fwrite($debugLog, "handleAchievementAction ended at " . date('Y-m-d H:i:s') . " PST\n\n");
fclose($debugLog);
?>