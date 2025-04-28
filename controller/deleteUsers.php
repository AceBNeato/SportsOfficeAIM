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

$user_id = intval($_POST['id']);

// Prevent self-deletion (optional based on your session)
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
    header("Location: ../view/adminView.php?message=" . urlencode("You cannot delete your own account"));
    exit();
}

// Prepare the stored procedure call
$stmt = $conn->prepare("CALL DeleteUserIfAllowed(?)");
if (!$stmt) {
    header("Location: ../view/adminView.php?message=" . urlencode("Prepare failed: " . $conn->error));
    exit();
}

$stmt->bind_param("i", $user_id);

// Execute and fetch result
if ($stmt->execute()) {
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $message = $row['result'];
    } else {
        $message = "User deleted successfully.";
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
