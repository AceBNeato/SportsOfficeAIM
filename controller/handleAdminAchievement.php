<?php
session_start();

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

// Debug session contents
error_log("Session contents: " . print_r($_SESSION, true));

// Database connection
$conn = new mysqli("localhost", "root", "", "SportOfficeDB");
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    $_SESSION['admin_message'] = "Database connection failed.";
    $_SESSION['admin_message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

// Check if admin is logged in
if (!isset($_SESSION['admin']['id']) && !(isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin')) {
    error_log("Admin not logged in: admin_id=" . ($_SESSION['admin']['id'] ?? 'not set') . ", user_role=" . ($_SESSION['user']['role'] ?? 'not set'));
    $_SESSION['admin_message'] = "Admin not logged in.";
    $_SESSION['admin_message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

// Validate input
if (!isset($_POST['action']) || !isset($_POST['achievement_id'])) {
    error_log("Missing action or achievement_id: action=" . ($_POST['action'] ?? 'not set') . ", achievement_id=" . ($_POST['achievement_id'] ?? 'not set'));
    $_SESSION['admin_message'] = "Invalid request.";
    $_SESSION['admin_message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

$achievement_id = filter_input(INPUT_POST, 'achievement_id', FILTER_VALIDATE_INT);
$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
$rejection_reason = filter_input(INPUT_POST, 'reject_reason', FILTER_SANITIZE_STRING) ?? 'No reason provided';

if (!$achievement_id || !in_array($action, ['approve', 'reject'])) {
    error_log("Invalid achievement ID or action: ID=" . ($_POST['achievement_id'] ?? 'not set') . ", action=" . ($_POST['action'] ?? 'not set'));
    $_SESSION['admin_message'] = "Invalid achievement ID or action.";
    $_SESSION['admin_message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

// Fetch achievement details
$stmt = $conn->prepare("SELECT user_id, total_points, athlete_name, status FROM achievements WHERE achievement_id = ?");
$stmt->bind_param("i", $achievement_id);
$stmt->execute();
$result = $stmt->get_result();
$achievement = $result->fetch_assoc();
$stmt->close();

if (!$achievement) {
    error_log("Achievement ID $achievement_id not found");
    $_SESSION['admin_message'] = "Achievement not found.";
    $_SESSION['admin_message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

if ($achievement['status'] !== 'Pending') {
    error_log("Achievement ID $achievement_id is not Pending: " . $achievement['status']);
    $_SESSION['admin_message'] = "Achievement is not pending.";
    $_SESSION['admin_message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

$user_id = (int)$achievement['user_id'];
$points = (int)$achievement['total_points'];
$athlete_name = $achievement['athlete_name'] ?? 'Achievement #' . $achievement_id;

// Process action in a transaction
$conn->begin_transaction();
try {
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE achievements SET status = 'Approved' WHERE achievement_id = ?");
        $stmt->bind_param("i", $achievement_id);
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE achievements SET status = 'Rejected', rejection_reason = ? WHERE achievement_id = ?");
        $stmt->bind_param("si", $rejection_reason, $achievement_id);
    }

    if ($stmt->execute()) {
        // Notification
        $message = "Your achievement '$athlete_name'  has been " . ($action === 'approve' ? 'approved' . ($points > 0 ? " with $points points" : '') : 'rejected') . ($action === 'reject' ? ". Reason: $rejection_reason" : '');
        $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, timestamp, is_read) VALUES (?, ?, NOW(), FALSE)");
        $stmt_notify->bind_param("is", $user_id, $message);
        if (!$stmt_notify->execute()) {
            error_log("Failed to create notification for user_id=$user_id: " . $stmt_notify->error);
            throw new Exception("Notification failed");
        }
        $stmt_notify->close();
    } else {
        error_log("Failed to $action achievement ID $achievement_id: " . $stmt->error);
        throw new Exception("Update failed");
    }
    $stmt->close();
    $conn->commit();
    $_SESSION['admin_message'] = "Achievement " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully!";
    $_SESSION['admin_message_class'] = $action === 'approve' ? "bg-green-100" : "bg-yellow-100";
} catch (Exception $e) {
    $conn->rollback();
    error_log("Transaction failed for achievement ID $achievement_id: " . $e->getMessage());
    $_SESSION['admin_message'] = "Failed to " . ($action === 'approve' ? 'approve' : 'reject') . " achievement.";
    $_SESSION['admin_message_class'] = "bg-red-100";
}

$conn->close();
header("Location: ../view/adminView.php?page=Achievement");
exit();
?>