<?php
// editUsers.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

// --- Reconnect properly (don't trust old $conn) ---
$conn = new mysqli($servername , $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input values
    $student_id = trim($_POST['student_id']);
    $full_name = trim($_POST['full_name']);
    $address = trim($_POST['address']);
    $status = trim($_POST['status']);

    // Simple validation
    if (empty($student_id) || empty($full_name) || empty($address) || empty($status)) {
        header("Location: ../view/adminView.php?message=" . urlencode("Please fill out all required fields"));
        exit();
    }

    try {
        $sql = "CALL UpdateUserByStudentID(?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            header("Location: ../view/adminView.php?message=" . urlencode("Prepare failed: " . $conn->error));
            exit();
        }

// Bind parameters (ssss = all strings)
        $stmt->bind_param('ssss', $student_id, $full_name, $address, $status);



        // Execute
        if ($stmt->execute()) {
            // Redirect after successful update
            header("Location: ../view/adminView.php?page=Users&message=" . urlencode("User updated successfully."));
            exit();
        } else {
            // Error executing statement
            header("Location: ../view/adminView.php?message=" . urlencode("Failed to update user."));
            exit();
        }

    } catch (Exception $e) {
        // Catch any exceptions
        header("Location: ../view/adminView.php?message=" . urlencode("Error: " . $e->getMessage()));
        exit();
    }
} else {
    // If the request method is not POST
    header("Location: ../view/adminView.php?message=" . urlencode("Invalid request method."));
    exit();
}

// Always clean up
$conn->close();
?>
