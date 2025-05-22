<?php
session_start();

header('Content-Type: application/json');

$requestMethod = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$data = $requestMethod === 'POST' ? ($input ?: $_POST) : $_GET;

$conn = new mysqli("localhost", "root", "", "SportOfficeDB");
if ($conn->connect_error) {
    error_log("notifications.php: Database connection failed: " . $conn->connect_error);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$user_id = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
if ($user_id === 0) {
    error_log("notifications.php: User not authenticated");
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

if ($requestMethod === 'GET') {
    try {
        $stmt = $conn->prepare("SELECT id, message, timestamp, is_read FROM notifications 
                               WHERE user_id = ? 
                               ORDER BY timestamp DESC 
                               LIMIT 50");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'id' => $row['id'],
                'message' => $row['message'],
                'timestamp' => date('c', strtotime($row['timestamp'])), // ISO 8601 format
                'is_read' => (bool)$row['is_read']
            ];
        }

        $stmt->close();
        error_log("notifications.php: Fetched " . count($notifications) . " notifications for user_id=$user_id");
        echo json_encode(['notifications' => $notifications]);
    } catch (Exception $e) {
        error_log("notifications.php: Error fetching notifications: " . $e->getMessage());
        echo json_encode(['error' => 'Failed to fetch notifications']);
    }
} elseif ($requestMethod === 'POST') {
    $action = $data['action'] ?? '';

    if ($action === 'mark_read') {
        try {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
            $stmt->bind_param("i", $user_id);
            $success = $stmt->execute();
            $stmt->close();

            if ($success) {
                error_log("notifications.php: Marked notifications as read for user_id=$user_id");
                echo json_encode(['success' => true]);
            } else {
                throw new Exception("Failed to mark notifications as read");
            }
        } catch (Exception $e) {
            error_log("notifications.php: Error marking notifications as read: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to mark notifications as read']);
        }
    } elseif ($action === 'clear_all') {
        try {
            $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $success = $stmt->execute();
            $stmt->close();

            if ($success) {
                error_log("notifications.php: Cleared all notifications for user_id=$user_id");
                echo json_encode(['success' => true]);
            } else {
                throw new Exception("Failed to clear notifications");
            }
        } catch (Exception $e) {
            error_log("notifications.php: Error clearing notifications: " . $e->getMessage());
            echo json_encode(['error' => 'Failed to clear notifications']);
        }
    }
}

$conn->close();
?>