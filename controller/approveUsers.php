<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    header("Location: ../view/signupView.php?status=error&message=" . urlencode("Database connection failed"));
    exit();
}

// Validate form data
$requiredFields = ['student_id', 'full_name', 'email', 'status', 'document'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field]) && empty($_FILES[$field])) {
        header("Location: ../view/signupView.php?status=error&message=" . urlencode("Missing required field: $field"));
        exit();
    }
}

$student_id = trim($_POST['student_id']);
$full_name = trim($_POST['full_name']);
$email = trim($_POST['email']);
$status = trim($_POST['status']);
$page = isset($_POST['page']) ? trim($_POST['page']) : 'signup';

// Check for duplicate student ID or email
$checkStmt = $conn->prepare("SELECT student_id, email FROM account_approvals WHERE student_id = ? OR email = ?");
if (!$checkStmt) {
    header("Location: ../view/signupView.php?status=error&message=" . urlencode("Prepare failed: " . $conn->error));
    exit();
}
$checkStmt->bind_param("ss", $student_id, $email);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    $checkStmt->bind_result($db_student_id, $db_email);
    $checkStmt->fetch();
    if ($db_student_id === $student_id) {
        $message = "This Student ID is already registered.";
    } elseif ($db_email === $email) {
        $message = "This email is already registered.";
    }
    $checkStmt->close();
    $conn->close();
    header("Location: ../view/signupView.php?status=error&message=" . urlencode($message));
    exit();
}
$checkStmt->close();

// Validate file
if (!isset($_FILES['document']) || $_FILES['document']['error'] == UPLOAD_ERR_NO_FILE) {
    header("Location: ../view/signupView.php?status=error&message=" . urlencode("No file uploaded"));
    exit();
}

$file = $_FILES['document'];
$validTypes = ['application/pdf', 'image/jpeg', 'image/png'];
$maxSize = 5 * 1024 * 1024; // 5MB
if (!in_array($file['type'], $validTypes)) {
    header("Location: ../view/signupView.php?status=error&message=" . urlencode("Invalid file type. Use PDF, JPG, or PNG"));
    exit();
}
if ($file['size'] > $maxSize) {
    header("Location: ../view/signupView.php?status=error&message=" . urlencode("File size exceeds 5MB limit"));
    exit();
}
if ($file['error'] != UPLOAD_ERR_OK) {
    header("Location: ../view/signupView.php?status=error&message=" . urlencode("File upload error"));
    exit();
}

// Read file data
$file_data = file_get_contents($file['tmp_name']);
$file_name = $conn->real_escape_string($file['name']);
$file_type = $file['type'];
$file_size = $file['size'];

// Insert into account_approvals
$stmt = $conn->prepare("INSERT INTO account_approvals (student_id, full_name, email, status, file_name, file_data, file_type, file_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    header("Location: ../view/signupView.php?status=error&message=" . urlencode("Prepare failed: " . $conn->error));
    exit();
}
$stmt->bind_param("sssssssi", $student_id, $full_name, $email, $status, $file_name, $file_data, $file_type, $file_size);

if ($stmt->execute()) {
    $message = "Signup request submitted. Awaiting admin approval.";
    $status = "success";
} else {
    $message = "Error submitting request: " . $conn->error;
    $status = "error";
}

$stmt->close();
$conn->close();

header("Location: ../view/signupView.php?status=" . $status . "&message=" . urlencode($message));
exit();
?>