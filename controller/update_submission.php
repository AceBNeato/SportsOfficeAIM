<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$submission_id = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : 0;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$user_id = $_SESSION['user']['id'];

if ($submission_id <= 0 || empty($description) || strlen($description) < 10) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Verify the submission belongs to the user
$stmt = $conn->prepare("SELECT user_id, file_name FROM submissions WHERE id = ?");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Submission not found']);
    $stmt->close();
    $conn->close();
    exit;
}

$row = $result->fetch_assoc();
if ($row['user_id'] !== $user_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    $stmt->close();
    $conn->close();
    exit;
}

$old_file_name = $row['file_name'];
$file_path = null;
$file_name = null;

// Handle file upload if provided
if (isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
    $file_type = $_FILES['uploaded_file']['type'];
    $file_size = $_FILES['uploaded_file']['size'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type']);
        $stmt->close();
        $conn->close();
        exit;
    }

    if ($file_size > $max_size) {
        echo json_encode(['success' => false, 'message' => 'File too large']);
        $stmt->close();
        $conn->close();
        exit;
    }

    $upload_dir = '../Uploads/';
    $file_ext = pathinfo($_FILES['uploaded_file']['name'], PATHINFO_EXTENSION);
    $file_name = uniqid() . '.' . $file_ext;
    $file_path = $upload_dir . $file_name;

    if (!move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $file_path)) {
        echo json_encode(['success' => false, 'message' => 'File upload failed']);
        $stmt->close();
        $conn->close();
        exit;
    }

    // Delete old file if it exists
    if ($old_file_name && file_exists($upload_dir . $old_file_name)) {
        unlink($upload_dir . $old_file_name);
    }
}

// Update submission
$update_query = "UPDATE submissions SET description = ?, file_name = COALESCE(?, file_name), submission_date = NOW() WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($update_query);
$file_name_to_bind = $file_name; // Use a variable to hold the value (null or file name)
$stmt->bind_param("ssii", $description, $file_name_to_bind, $submission_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    // Rollback file upload if query fails
    if ($file_path && file_exists($file_path)) {
        unlink($file_path);
    }
    echo json_encode(['success' => false, 'message' => 'Failed to update submission']);
}

$stmt->close();
$conn->close();
?>