<?php
session_start();
$conn = new mysqli("localhost", "root", "", "SportOfficeDB");
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$submission_id = $_POST['id'];

$stmt = $conn->prepare("UPDATE submissions SET status = 'rejected' WHERE id = ?");
$stmt->bind_param("i", $submission_id);
$success = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $success, 'message' => $success ? 'Submission rejected' : 'Failed to reject submission']);
$conn->close();
?>