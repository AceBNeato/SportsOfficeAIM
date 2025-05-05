<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../view/loginView.php");
    exit;
}

// Database configuration
$host = 'localhost';
$db = 'SportOfficeDB';
$user = 'root';
$pass = '';

// Establish connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("System error. Please try again later.");
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $full_name = trim($_POST['full_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $profile_pic_path = $_POST['profile_pic_path'] ?? null;

    // Input validation
    $errors = [];

    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }

    // Password change is optional, but if provided must match and meet requirements
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters.";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
    }

    // If there are no errors, update the database
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            $user_id = $_SESSION['user']['id'];
            $role = $_SESSION['user']['role'];
            $updated = false;

            // Determine which table to update based on user role
            $table = ($role === 'admin') ? 'admins' : 'users';

            // Build the query dynamically based on which fields to update
            $query_parts = [];
            $params = [];
            $types = '';

            if (!empty($full_name)) {
                $query_parts[] = "full_name = ?";
                $params[] = $full_name;
                $types .= 's';
            }

            if (!empty($address)) {
                $query_parts[] = "address = ?";
                $params[] = $address;
                $types .= 's';
            }

            if (!empty($profile_pic_path)) {
                $query_parts[] = "profile_pic = ?";
                $params[] = $profile_pic_path;
                $types .= 's';
            }

            // Re-check role and user table
            $table = ($role === 'admin') ? 'admins' : 'users';

// Check if new email already in use
            if (!empty($email) && $email !== $_SESSION['user']['email']) {
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $check_stmt->bind_param("si", $email, $user_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $check_stmt->close();

                if ($result->num_rows > 0) {
                    $errors[] = "Email already in use by another account.";
                    throw new Exception("Email already in use");
                }

                $query_parts[] = "email = ?";
                $params[] = $email;
                $types .= 's';
            }

// Add password hash update if provided
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query_parts[] = "password = ?";
                $params[] = $hashed_password;
                $types .= 's';
            }

// Final update query (users table always holds login info)
            if (!empty($query_parts)) {
                $query = "UPDATE users SET " . implode(", ", $query_parts) . " WHERE id = ?";
                $params[] = $user_id;
                $types .= 'i';

                $stmt = $conn->prepare($query);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->close();
                $updated = true;
            }


            // Update password if provided
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $pass_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $pass_stmt->bind_param("ss", $hashed_password, $_SESSION['user']['email']);
                $pass_stmt->execute();
                $pass_stmt->close();
                $updated = true;
            }

            // Commit transaction if we got here
            $conn->commit();

            // Update session variables with new values
            if ($updated) {
                $_SESSION['user']['full_name'] = $full_name;
                if (!empty($email)) $_SESSION['user']['email'] = $email;
                $_SESSION['user']['address'] = $address;
                if (!empty($profile_pic_path)) $_SESSION['user']['profile_pic'] = $profile_pic_path;

                $_SESSION['profile_update_success'] = true;
                $_SESSION['profile_message'] = "Profile updated successfully.";
            }


            // Redirect back to user view
            header("Location: ../view/userView.php?page=Dashboard");
            exit;

        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            error_log("Profile update error: " . $e->getMessage());
            $_SESSION['profile_update_success'] = false;
            $_SESSION['profile_message'] = !empty($errors) ? implode(" ", $errors) : "An error occurred while updating your profile.";
            header("Location: ../view/userView.php?page=Dashboard");
            exit;
        }
    } else {
        // Store errors in session
        $_SESSION['profile_update_success'] = false;
        $_SESSION['profile_message'] = implode(" ", $errors);
        header("Location: ../view/userView.php?page=Dashboard");
        exit;
    }
}

$conn->close();
?>