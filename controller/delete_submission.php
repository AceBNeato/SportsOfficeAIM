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

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized or missing ID']);
    exit;
}

$submission_id = (int)$_POST['id'];
$user_id = $_SESSION['user']['id'];

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

$stmt = $conn->prepare("DELETE FROM submissions WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $submission_id, $user_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Submission deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete submission']);
}

$stmt->close();
$conn->close();
?>