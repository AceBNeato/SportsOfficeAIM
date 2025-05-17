<?php
session_start(); // Ensure session is started for admin_id
require_once '../view/mail.php'; // Include PHPMailer functions

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
    $adminId = $_SESSION['admin_id'] ?? 1; // Replace with session-based admin ID

    if ($approvalId <= 0 || !in_array($action, ['reject'])) {
        error_log("Invalid approval ID or action: approvalId=$approvalId, action=$action");
        header("Location: ../view/adminView.php?status=error&action=invalid");
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
                    header("Location: ../view/adminView.php?status=success&action=reject");
                } else {
                    error_log("Failed to send rejection email for user: {$request['email']}");
                    header("Location: ../view/adminView.php?status=error&action=reject");
                }
            } else {
                error_log("Failed to delete account_approvals record for ID: $approvalId");
                header("Location: ../view/adminView.php?status=error&action=reject");
            }
        }
    } else {
        error_log("No request found for approval ID: $approvalId");
        header("Location: ../view/adminView.php?status=error&action=invalid");
    }
} else {
    header("Location: ../view/adminView.php?status=error&action=invalid");
}

$conn->close();
exit;
?>