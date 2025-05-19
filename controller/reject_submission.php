<?php
session_start();
$conn = new mysqli("localhost", "root", "", "SportOfficeDB");
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$submission_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';

if ($submission_id <= 0) {
    die(json_encode(['success' => false, 'message' => 'Invalid submission ID']));
}

// Prepare the update query to set status and comments
$stmt = $conn->prepare("UPDATE submissions SET status = 'rejected', comments = ? WHERE id = ?");
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]));
}

$stmt->bind_param("si", $comments, $submission_id);
$success = $stmt->execute();

if ($success) {
    // Optionally, create a notification for the user
    $submission = $conn->query("SELECT user_id FROM submissions WHERE id = $submission_id")->fetch_assoc();
    if ($submission) {
        $user_id = $submission['user_id'];
        $message = "Your submission has been rejected." . ($comments ? " Reason: $comments" : "");
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
        $stmt->close();
    }
}

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Submission rejected successfully' : 'Failed to reject submission: ' . $conn->error
]);

$stmt->close();
$conn->close();
?>