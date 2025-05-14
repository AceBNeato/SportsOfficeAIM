<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

// Ensure JSON response
header('Content-Type: application/json');

// Check if user is authorized to reset password (OTP verified)
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    error_log("Unauthorized access: OTP not verified");
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Process password reset
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = $_SESSION['reset_otp_email'] ?? '';
    $user_role = $_SESSION['reset_user_role'] ?? '';

    // Log input data for debugging (remove sensitive data in production)
    error_log("Reset password attempt for email: $email, role: $user_role");

    // Validate inputs
    $errors = [];

    if (empty($new_password)) {
        $errors[] = "New password is required";
    } elseif (strlen($new_password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }

    if ($new_password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (empty($email)) {
        $errors[] = "Email session expired";
    }

    if (empty($user_role)) {
        $errors[] = "User role not found";
    }

    // If no errors, update password
    if (empty($errors)) {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        if (!$hashed_password) {
            error_log("Password hashing failed");
            echo json_encode(['status' => 'error', 'message' => 'Password hashing failed']);
            exit();
        }

        // Update password in the appropriate table
        $table = ($user_role === 'admin') ? 'admins' : 'users';

        // Prepare and execute update query
        $update_stmt = $conn->prepare("UPDATE $table SET password = ? WHERE email = ?");
        if (!$update_stmt) {
            error_log("Prepare failed: " . $conn->error);
            echo json_encode(['status' => 'error', 'message' => 'Database prepare error']);
            exit();
        }

        $update_stmt->bind_param("ss", $hashed_password, $email);
        if ($update_stmt->execute()) {
            // Clear all session variables
            unset($_SESSION['reset_otp']);
            unset($_SESSION['reset_otp_email']);
            unset($_SESSION['reset_otp_time']);
            unset($_SESSION['otp_verified']);
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_user_role']);

            error_log("Password reset successful for email: $email");
            echo json_encode(['status' => 'success', 'message' => 'Password reset successfully']);
        } else {
            error_log("Update failed: " . $update_stmt->error);
            echo json_encode(['status' => 'error', 'message' => 'Database update failed: ' . $update_stmt->error]);
        }
        $update_stmt->close();
    } else {
        error_log("Validation errors: " . implode(', ', $errors));
        echo json_encode(['status' => 'error', 'message' => implode(', ', $errors)]);
    }
} else {
    error_log("Invalid request method: " . $_SERVER["REQUEST_METHOD"]);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

$conn->close();
?>