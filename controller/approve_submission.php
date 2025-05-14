<?php
session_start();

// Debug: Log session data
file_put_contents('debug.log', 'Session: ' . print_r($_SESSION, true) . "\n", FILE_APPEND);

// Validate admin session
if (!isset($_SESSION['admin']['id']) || !is_numeric($_SESSION['admin']['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// CSRF token validation
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "SportOfficeDB");
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

// Validate input
$submission_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$admin_id = (int)$_SESSION['admin']['id'];
$comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';

if ($submission_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid submission ID']);
    exit;
}

if (strlen($comments) > 1000) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Comments too long (max 1000 characters)']);
    exit;
}

// Check if submission exists and is pending
$stmt = $conn->prepare("SELECT id FROM submissions WHERE id = ? AND status = 'pending'");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Submission not found or already processed']);
    exit;
}
$stmt->close();

// Update submission
$stmt = $conn->prepare("UPDATE submissions SET status = 'approved', approved_by = ?, approval_date = NOW(), comments = ? WHERE id = ?");
$stmt->bind_param("isi", $admin_id, $comments, $submission_id);
$success = $stmt->execute();

$response = [
    'success' => $success,
    'message' => $success ? 'Submission approved successfully' : 'Failed to approve submission: ' . $conn->error
];

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
?>