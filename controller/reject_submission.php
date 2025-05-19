<?php
$conn = new mysqli("localhost", "root", "", "SportOfficeDB");
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$submission_id = $_POST['id'] ?? null;
$comments = $_POST['comments'] ?? '';

if (!$submission_id || !is_numeric($submission_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid submission ID']);
    exit;
}

// Prepare and execute the update query
$stmt = $conn->prepare("UPDATE submissions SET status = 'rejected', comments = ? WHERE id = ?");
$stmt->bind_param("si", $comments, $submission_id);
$success = $stmt->execute();
$stmt->close();

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Submission rejected successfully' : 'Failed to reject submission'
]);

$conn->close();
?>