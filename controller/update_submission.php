<?php
header('Content-Type: application/json');

session_start();

// Log session data for debugging
file_put_contents('debug.log', "Session Data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    file_put_contents('debug.log', "DB Connection Error: " . $conn->connect_error . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$submission_id = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : 0;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

file_put_contents('debug.log', "Input Data: submission_id=$submission_id, description='$description'\n", FILE_APPEND);

if ($submission_id <= 0 || empty($description)) {
    echo json_encode(['success' => false, 'message' => "Invalid input: submission_id=$submission_id, description='$description'"]);
    exit;
}

if (strlen($description) < 10) {
    echo json_encode(['success' => false, 'message' => 'Description must be at least 10 characters long']);
    exit;
}

// Verify ownership
$user_id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT user_id FROM submissions WHERE id = ?");
if (!$stmt) {
    file_put_contents('debug.log', "Prepare Error: " . $conn->error . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'SQL Prepare Error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
if ($result->num_rows === 0 || $row['user_id'] != $user_id) {
    file_put_contents('debug.log', "Ownership Check Failed: session_user_id=$user_id, db_user_id=" . ($row['user_id'] ?? 'null') . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => "Unauthorized access to submission: session_user_id=$user_id, db_user_id=" . ($row['user_id'] ?? 'null')]);
    exit;
}
$stmt->close();

// Handle file upload if present
$file_data = null;
$file_name = null;
$file_size = null;

if (isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['uploaded_file'];
    $valid_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
    $max_size = 5 * 1024 * 1024; // 5MB

    file_put_contents('debug.log', "File Data: name=" . $file['name'] . ", size=" . $file['size'] . ", type=" . $file['type'] . ", error=" . $file['error'] . "\n", FILE_APPEND);

    if (!in_array($file['type'], $valid_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, DOC, DOCX, JPG, PNG are allowed.']);
        exit;
    }

    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit.']);
        exit;
    }

    $file_data = file_get_contents($file['tmp_name']);
    if ($file_data === false) {
        file_put_contents('debug.log', "File Read Error: Failed to read " . $file['tmp_name'] . "\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Failed to read uploaded file data.']);
        exit;
    }
    $file_name = $file['name'];
    $file_size = $file['size'];
}

// Update submission
if ($file_data !== null) {
    $stmt = $conn->prepare("UPDATE submissions SET description = ?, file_data = ?, file_name = ?, file_size = ? WHERE id = ?");
    if (!$stmt) {
        file_put_contents('debug.log', "Prepare Error (File Update): " . $conn->error . "\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'SQL Prepare Error (File Update): ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("sssis", $description, $file_data, $file_name, $file_size, $submission_id);
} else {
    $stmt = $conn->prepare("UPDATE submissions SET description = ? WHERE id = ?");
    if (!$stmt) {
        file_put_contents('debug.log', "Prepare Error (No File): " . $conn->error . "\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'SQL Prepare Error (No File): ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("si", $description, $submission_id);
}

if (!$stmt->execute()) {
    file_put_contents('debug.log', "Execute Error: " . $stmt->error . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'SQL Execute Error: ' . $stmt->error]);
    exit;
}

file_put_contents('debug.log', "Update Successful: submission_id=$submission_id\n", FILE_APPEND);
echo json_encode(['success' => true, 'message' => 'Submission updated successfully']);

$stmt->close();
$conn->close();
?>