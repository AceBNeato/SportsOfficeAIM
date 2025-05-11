<?php
require_once '../view/mail.php'; // Adjusted path to match your structure

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
    $adminId = 1; // Replace with actual logged-in admin ID (from session or auth system)

    if ($approvalId && in_array($action, ['approve', 'reject'])) {
        // Fetch request details
        $stmt = $conn->prepare("SELECT student_id, full_name, email, status, file_data, file_type FROM account_approvals WHERE id = ?");
        $stmt->bind_param("i", $approvalId);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();
        $stmt->close();

        if ($request) {
            if ($action === 'approve') {
                // Use student ID as the default password
                $plainPassword = $request['student_id'];
                $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

                // Insert into users table
                $stmt = $conn->prepare("INSERT INTO users (student_id, full_name, address, email, password, status) VALUES (?, ?, ?, ?, ?, ?)");
                $address = 'Unknown'; // Update if you collect address during approval
                $stmt->bind_param("ssssss", $request['student_id'], $request['full_name'], $address, $request['email'], $hashedPassword, $request['status']);
                $userInserted = $stmt->execute();
                $stmt->close();

                if ($userInserted) {
                    // Update account_approvals
                    $stmt = $conn->prepare("UPDATE account_approvals SET approval_status = 'approved', approved_by = ?, approval_date = NOW() WHERE id = ?");
                    $stmt->bind_param("ii", $adminId, $approvalId);
                    $updated = $stmt->execute();
                    $stmt->close();

                    if ($updated) {
                        // Send approval email
                        if (sendApprovalEmail($request['email'], $request['full_name'], $request['student_id'])) {
                            header("Location: ../view/adminView.php?status=success&action=approve");
                            exit;
                        } else {
                            error_log("Failed to send approval email for user: {$request['email']}");
                            header("Location: ../view/adminView.php?status=error&action=approve");
                            exit;
                        }
                    }
                }
                header("Location: ../view/adminView.php?status=error&action=approve");
                exit;
            } else {
                // Reject request
                $stmt = $conn->prepare("UPDATE account_approvals SET approval_status = 'rejected', approved_by = ?, approval_date = NOW() WHERE id = ?");
                $stmt->bind_param("ii", $adminId, $approvalId);
                $updated = $stmt->execute();
                $stmt->close();

                if ($updated) {
                    // Send rejection email
                    if (sendRejectionEmail($request['email'], $request['full_name'])) {
                        header("Location: ../view/adminView.php?status=success&action=reject");
                        exit;
                    } else {
                        error_log("Failed to send rejection email for user: {$request['email']}");
                        header("Location: ../view/adminView.php?status=error&action=reject");
                        exit;
                    }
                }
                header("Location: ../view/adminView.php?status=error&action=reject");
                exit;
            }
        }
    }
}

header("Location: ../view/adminView.php?status=error&action=invalid");
$conn->close();
?>