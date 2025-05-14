<?php
require_once '../database/config.php';

// Start output buffering to prevent stray output
ob_start();

session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ob_end_clean();
    http_response_code(403);
    exit('Unauthorized access');
}

// Get submission ID from GET parameter
$submission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($submission_id <= 0) {
    error_log("Invalid submission ID received in view_submission.php: " . json_encode($_GET));
    ob_end_clean();
    http_response_code(400);
    exit('Invalid submission ID');
}

// Database connection using Database class
$conn = Database::getInstance();

// Query to fetch the submission
$stmt = $conn->prepare("SELECT file_name, file_data FROM submissions WHERE id = ? AND status = 'approved'");
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    ob_end_clean();
    http_response_code(500);
    exit('Database error');
}

$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("Submission not found or not approved for ID: " . $submission_id);
    ob_end_clean();
    http_response_code(404);
    exit('Submission not found or not approved');
}

$submission = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Determine MIME type based on file extension
$extension = pathinfo($submission['file_name'], PATHINFO_EXTENSION);
$mime_types = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];
$mime_type = $mime_types[strtolower($extension)] ?? 'application/octet-stream';

// Clear output buffer before sending file content
ob_end_clean();

// Output the file content
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . strlen($submission['file_data']));
echo $submission['file_data'];
exit;
?>