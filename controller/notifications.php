<?php
session_start();
header('Content-Type: application/json');

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

// Get user ID from session
$user_id = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
error_log("notifications.php: Fetching for user_id=$user_id, Session: " . print_r($_SESSION, true));

if ($user_id === 0) {
    error_log("notifications.php: User not authenticated");
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$requestMethod = $_SERVER['REQUEST_METHOD'];

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
                'timestamp' => $row['timestamp'],
                'is_read' => (bool)$row['is_read']
            ];
        }

        $stmt->close();
        error_log("notifications.php: Fetched " . count($notifications) . " notifications for user_id=$user_id");
        echo json_encode(['notifications' => $notifications]);
    } catch (Exception $e) {
        error_log("notifications.php: Error fetching notifications: " . $e->getMessage());
        echo json_encode(['error' => 'Failed to fetch notifications: ' . $e->getMessage()]);
    }
}

if ($requestMethod === 'POST') {
    try {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON input");
        }

        $action = $data['action'] ?? '';

        if ($action === 'mark_read') {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
            $stmt->bind_param("i", $user_id);
            $success = $stmt->execute();
            $stmt->close();

            if ($success) {
                error_log("notifications.php: Marked notifications as read for user_id=$user_id");
                echo json_encode(['success' => true]);
            } else {
                throw new Exception("Database update failed");
            }
        } elseif ($action === 'clear_all') {
            $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $success = $stmt->execute();
            $stmt->close();

            if ($success) {
                error_log("notifications.php: Cleared all notifications for user_id=$user_id");
                echo json_encode(['success' => true]);
            } else {
                throw new Exception("Database delete failed");
            }
        } else {
            throw new Exception("Unknown action: $action");
        }
    } catch (Exception $e) {
        error_log("notifications.php: Error processing POST request: " . $e->getMessage());
        echo json_encode(['error' => 'Request failed: ' . $e->getMessage()]);
    }
}

$conn->close();
?>