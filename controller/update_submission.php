<?php
session_start();

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Validate required fields
if (!isset($_POST['submission_id']) || !isset($_POST['document_type']) || !isset($_POST['description'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$submission_id = (int)$_POST['submission_id'];
$document_type = $_POST['document_type'];
$other_type = $_POST['document_type'] === 'Others' ? ($_POST['other_type'] ?? '') : null;
$description = $_POST['description'];
$user_id = $_SESSION['user']['id'];

// Validate description length
if (strlen(trim($description)) < 10) {
    echo json_encode(['success' => false, 'message' => 'Description must be at least 10 characters long']);
    exit;
}

// Check if the submission belongs to the user
$stmt = $conn->prepare("SELECT user_id FROM submissions WHERE id = ?");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0 || $result->fetch_assoc()['user_id'] !== $user_id) {
    echo json_encode(['success' => false, 'message' => 'Submission not found or access denied']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Handle file upload if a new file is provided
$file_data = null;
$file_name = null;
$file_size = null;

if (isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['uploaded_file'];
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];

    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, DOC, DOCX, JPG, PNG are allowed']);
        $conn->close();
        exit;
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
        $conn->close();
        exit;
    }

    $file_data = file_get_contents($file['tmp_name']);
    $file_name = $file['name'];
    $file_size = $file['size'];
}

// Update the submission
if ($file_data) {
    // Update with new file
    $stmt = $conn->prepare("
        UPDATE submissions 
        SET document_type = ?, other_type = ?, description = ?, file_name = ?, file_data = ?, file_size = ?, status = 'pending'
        WHERE id = ?
    ");
    $stmt->bind_param("sssssii", $document_type, $other_type, $description, $file_name, $file_data, $file_size, $submission_id);
} else {
    // Update without changing the file
    $stmt = $conn->prepare("
        UPDATE submissions 
        SET document_type = ?, other_type = ?, description = ?, status = 'pending'
        WHERE id = ?
    ");
    $stmt->bind_param("sssi", $document_type, $other_type, $description, $submission_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Submission updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update submission']);
}

$stmt->close();
$conn->close();
?>