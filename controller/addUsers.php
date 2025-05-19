<?php
// File: /controller/addUsers.php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    header("Location: ../view/adminView.php?message=" . urlencode("Database connection failed"));
    exit();
}

// Validate and get form data
$requiredFields = ['student_id', 'full_name', 'email', 'password', 'status'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field])) {
        header("Location: ../view/adminView.php?message=" . urlencode("Missing required field: $field"));
        exit();
    }
}

$student_id = trim($_POST['student_id']);
$full_name = trim($_POST['full_name']);
// Set address to "N/A" if empty or not provided
$address = !empty(trim($_POST['address'])) ? trim($_POST['address']) : 'N/A';
$email = trim($_POST['email']);
$password = $_POST['password'];
$status = isset($_POST['status']) && !empty(trim($_POST['status'])) ? trim($_POST['status']) : 'User';

$page = isset($_POST['page']) ? trim($_POST['page']) : '';
$currentPage = isset($_POST['currentPage']) ? trim($_POST['currentPage']) : '';

// Hash the password securely
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Prepare the CALL to stored procedure
$stmt = $conn->prepare("CALL AddUserIfAllowed(?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    header("Location: ../view/adminView.php?message=" . urlencode("Prepare failed: " . $conn->error));
    exit();
}

$stmt->bind_param("ssssss", $student_id, $full_name, $address, $email, $hashedPassword, $status);

// Execute and fetch result
if ($stmt->execute()) {
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        $message = $row['result'];
    } else {
        $message = "User added successfully.";
    }

    // After successful/failed user addition
    if ($page === 'signup') {
        header("Location: ../view/signupView.php?message=" . urlencode($message));
    } else {
        // For admin additions
        if (isset($_POST['source']) && $_POST['source'] === 'usersPage') {
            header("Location: ../view/adminView.php?page=Users&message=" . urlencode($message));
        } else {
            header("Location: ../view/adminView.php?page=Users&message=" . urlencode($message));
        }
    }
    exit();
} else {
    header("Location: ../view/adminView.php?message=" . urlencode("Execution failed: " . $stmt->error));
}

$stmt->close();
$conn->close();
?>