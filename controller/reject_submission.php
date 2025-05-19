<?php
session_start();
$conn = new mysqli("localhost", "root", "", "SportOfficeDB");
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

<<<<<<< HEAD
<<<<<<< HEAD
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

=======
$submission_id = $_POST['id'] ?? null;
$comments = $_POST['comments'] ?? '';

if (!$submission_id || !is_numeric($submission_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid submission ID']);
    exit;
}

// Prepare and execute the update query
$stmt = $conn->prepare("UPDATE submissions SET status = 'rejected', comments = ? WHERE id = ?");
>>>>>>> d71ba2ea996a2c74e499c10c8180ae18088e5673
$stmt->bind_param("si", $comments, $submission_id);
=======
$submission_id = $_POST['id'];

$stmt = $conn->prepare("UPDATE submissions SET status = 'rejected' WHERE id = ?");
$stmt->bind_param("i", $submission_id);
>>>>>>> parent of d71ba2e (ok na jud ang comment sa rejection all goods)
$success = $stmt->execute();

<<<<<<< HEAD
<<<<<<< HEAD
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
=======
echo json_encode([
    'success' => $success,
    'message' => $success ? 'Submission rejected successfully' : 'Failed to reject submission'
]);

>>>>>>> d71ba2ea996a2c74e499c10c8180ae18088e5673
=======
echo json_encode(['success' => $success, 'message' => $success ? 'Submission rejected' : 'Failed to reject submission']);
>>>>>>> parent of d71ba2e (ok na jud ang comment sa rejection all goods)
$conn->close();
?>