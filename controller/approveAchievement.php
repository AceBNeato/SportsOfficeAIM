<?php
session_start();

// Start debug logging
$debugLog = fopen('../debug.log', 'a') or die("Can't open debug.log");
fwrite($debugLog, "approveAchievement started at " . date('Y-m-d H:i:s') . " PST\n");

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
    $error = "CSRF token validation failed.";
    fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
    $_SESSION['achievement_message'] = "Invalid CSRF token.";
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

// Check if admin is logged in
if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    $error = "Unauthorized access attempt.";
    fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
    $_SESSION['achievement_message'] = "Unauthorized access.";
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

if (isset($_POST['achievement_id'])) {
    $achievement_id = filter_input(INPUT_POST, 'achievement_id', FILTER_VALIDATE_INT);
    if (!$achievement_id) {
        $error = "Invalid achievement_id: $achievement_id";
        fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
        $_SESSION['achievement_message'] = "Invalid achievement ID.";
        $_SESSION['message_class'] = "bg-red-100";
        header("Location: ../view/adminView.php?page=Achievement");
        exit();
    }

    $conn->begin_transaction();
    try {
        // Fetch current achievement data to recalculate points if edited
        $stmt = $conn->prepare("SELECT user_id, athlete_name, level_of_competition, performance, number_of_events, leadership_role, sportsmanship, community_impact, completeness_of_documents, status FROM achievements WHERE achievement_id = ?");
        $stmt->bind_param("i", $achievement_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Achievement not found: ID $achievement_id");
        }
        $achievement = $result->fetch_assoc();
        $stmt->close();

        if ($achievement['status'] !== 'Pending') {
            throw new Exception("Cannot approve achievement ID $achievement_id - Current status: " . $achievement['status']);
        }

        // Recalculate points (assuming edits might have occurred)
        $points = 0;
        $points += ($achievement['level_of_competition'] === 'Local' ? 5 : ($achievement['level_of_competition'] === 'Regional' ? 10 : ($achievement['level_of_competition'] === 'National' ? 15 : 20)));
        $points += ($achievement['performance'] === 'Winner (Gold)' ? 15 : ($achievement['performance'] === 'Silver' ? 10 : ($achievement['performance'] === 'Bronze' ? 5 : 2)));
        $points += ($achievement['number_of_events'] === '1-2' ? 5 : ($achievement['number_of_events'] === '3-4' ? 10 : 15));
        $points += ($achievement['leadership_role'] === 'Team Captain' ? 10 : ($achievement['leadership_role'] === 'Active Member' ? 5 : 2));
        $points += ($achievement['sportsmanship'] === 'No violation' ? 10 : ($achievement['sportsmanship'] === 'Minor warnings' ? 5 : 0));
        $points += ($achievement['community_impact'] === 'Yes' ? 10 : 0);
        $points += ($achievement['completeness_of_documents'] === 'Complete and verified' ? 15 : ($achievement['completeness_of_documents'] === 'Incomplete or unclear' ? 5 : 0));

        $stmt = $conn->prepare("UPDATE achievements SET status = 'Approved', rejection_reason = NULL, total_points = ? WHERE achievement_id = ?");
        $stmt->bind_param("ii", $points, $achievement_id);
        if (!$stmt->execute()) {
            throw new Exception("Update failed: " . $stmt->error);
        }
        $affected_rows = $stmt->affected_rows;
        if ($affected_rows > 0) {
            $user_id = $achievement['user_id'];
            $athlete_name = $achievement['athlete_name'];
            $message = "Your achievement submission for '$athlete_name' has been approved and added to the leaderboard.";
            $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, created_at, is_read) VALUES (?, ?, NOW(), 0)");
            $notify_stmt->bind_param("is", $user_id, $message);
            if (!$notify_stmt->execute()) {
                throw new Exception("Notification insert failed: " . $notify_stmt->error);
            }
            $notify_stmt->close();
            $conn->commit();
            $_SESSION['achievement_message'] = "Achievement approved successfully.";
            $_SESSION['message_class'] = "bg-green-100";
        } else {
            throw new Exception("No rows updated for approve action - Achievement ID: $achievement_id");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error: " . $e->getMessage();
        fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
        $_SESSION['achievement_message'] = "Failed to approve achievement: " . $e->getMessage();
        $_SESSION['message_class'] = "bg-red-100";
    }
    $stmt->close();
    $conn->close();
    header("Location: ../view/adminView.php?page=Achievement");
    exit();
}

fwrite($debugLog, "approveAchievement ended at " . date('Y-m-d H:i:s') . " PST\n\n");
fclose($debugLog);
?>