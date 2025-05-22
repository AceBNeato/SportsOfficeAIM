<?php
session_start(); // Ensure session is started for admin_id
require_once '../view/mail.php'; // Include PHPMailer functions

// Secure session configuration
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600); // 1 hour
session_set_cookie_params(0, '/'); // Ensure session cookie is available for all paths

// Debug: Log session data and request details
file_put_contents('debug.log', 'Reject Request - Session: ' . print_r($_SESSION, true) . "\n", FILE_APPEND);
file_put_contents('debug.log', 'Reject Request - POST Data: ' . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents('debug.log', 'Reject Request - Session ID: ' . session_id() . "\n", FILE_APPEND);

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true ||
    !isset($_SESSION['user']) || !isset($_SESSION['user']['id']) || (int)$_SESSION['user']['id'] <= 0 ||
    !isset($_SESSION['user']['role']) || strtolower(trim($_SESSION['user']['role'])) !== 'admin') {
    header('Location: ../view/loginView.php?status=error&message=admin_not_logged_in');
    exit;
}

// Database configuration
$host = 'localhost';
$db = 'SportOfficeDB';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $approvalId = (int)($_POST['approval_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $adminId = (int)$_SESSION['user']['id']; // Use session-based user ID

    if ($approvalId <= 0 || !in_array($action, ['reject'])) {
        error_log("Invalid approval ID or action: approvalId=$approvalId, action=$action");
        header("Location: ../view/adminView.php?page=Account%20Approvals&status=error&action=reject");
        exit;
    }

    // Fetch request details
    $stmt = $conn->prepare("SELECT student_id, full_name, email, status FROM account_approvals WHERE id = ?");
    $stmt->bind_param("i", $approvalId);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $stmt->close();

    if ($request) {
        if ($action === 'reject') {
            // Optional: Insert notification into notifications table
            $notification_message = "Your account approval request has been rejected.";
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) SELECT id, ? FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("ss", $notification_message, $request['email']);
            $stmt->execute();
            $stmt->close();

            // Delete the record from account_approvals
            $stmt = $conn->prepare("DELETE FROM account_approvals WHERE id = ?");
            $stmt->bind_param("i", $approvalId);
            $deleted = $stmt->execute();
            $stmt->close();

            if ($deleted) {
                // Send rejection email
                if (sendRejectionEmail($request['email'], $request['full_name'])) {
                    header("Location: ../view/adminView.php?page=Account%20Approvals&status=success&action=reject");
                } else {
                    error_log("Failed to send rejection email for user: {$request['email']}");
                    header("Location: ../view/adminView.php?page=Account%20Approvals&status=error&action=reject");
                }
            } else {
                error_log("Failed to delete account_approvals record for ID: $approvalId");
                header("Location: ../view/adminView.php?page=Account%20Approvals&status=error&action=reject");
            }
        }
    } else {
        error_log("No request found for approval ID: $approvalId");
        header("Location: ../view/adminView.php?page=Account%20Approvals&status=error&action=reject");
    }
} else {
    header("Location: ../view/adminView.php?page=Account%20Approvals&status=error&action=reject");
}

$conn->close();
exit;
?>