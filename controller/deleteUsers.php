<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    header("Location: ../view/adminView.php?message=" . urlencode("Database connection failed"));
    exit();
}

// Validate and get form data
if (empty($_POST['id'])) {
    header("Location: ../view/adminView.php?message=" . urlencode("Missing user ID"));
    exit();
}

$student_id = $_POST['id']; // This is the student_id, not converting to int

// Direct deletion query instead of stored procedure
$delete_query = "DELETE FROM users WHERE student_id = ?";
$stmt = $conn->prepare($delete_query);

if (!$stmt) {
    header("Location: ../view/adminView.php?message=" . urlencode("Prepare failed: " . $conn->error));
    exit();
}

$stmt->bind_param("s", $student_id); // Binding as string since student_id is likely a string

if ($stmt->execute()) {
    // Check if any rows were affected
    if ($stmt->affected_rows > 0) {
        $message = "User deleted successfully.";
    } else {
        $message = "No user found with the given ID.";
    }

    header("Location: ../view/adminView.php?page=Users&message=" . urlencode($message));
    exit();
} else {
    header("Location: ../view/adminView.php?message=" . urlencode("Execution failed: " . $stmt->error));
    exit();
}

$stmt->close();
$conn->close();
?>