<?php
session_start();
$conn = new mysqli("localhost", "root", "", "SportOfficeDB");
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$submission_id = $_POST['id'];
$admin_id = $_SESSION['admin']['id']; // Assuming admin ID is stored in session

$stmt = $conn->prepare("UPDATE submissions SET status = 'approved', approved_by = ?, approval_date = NOW() WHERE id = ?");
$stmt->bind_param("ii", $admin_id, $submission_id);
$success = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $success, 'message' => $success ? 'Submission approved' : 'Failed to approve submission']);
$conn->close();
?>