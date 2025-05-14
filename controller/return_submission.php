<?php
header('Content-Type: application/json');

session_start();

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
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$submission_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($submission_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

// Verify ownership
$user_id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT user_id, status FROM submissions WHERE id = ?");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0 || $result->fetch_assoc()['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access to submission']);
    exit;
}
$stmt->close();

// Update status to pending
$stmt = $conn->prepare("UPDATE submissions SET status = 'pending', submission_date = NOW() WHERE id = ?");
$stmt->bind_param("i", $submission_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Submission resubmitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to resubmit submission']);
}

$stmt->close();
$conn->close();
?>