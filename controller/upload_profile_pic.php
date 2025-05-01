<?php
session_start();
require_once '../database/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../view/userView.php");
    exit;
}

$studentId = $_POST['student_id'] ?? '';
$uploadDir = '../public/uploads/profiles/';

// Create directory if it doesn't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Validate file upload
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_pic'];

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        $_SESSION['error'] = "Only JPG, PNG, and GIF files are allowed.";
        header("Location: ../view/userView.php");
        exit;
    }

    // Validate file size (max 2MB)
    if ($file['size'] > 2097152) {
        $_SESSION['error'] = "File size must be less than 2MB.";
        header("Location: ../view/userView.php");
        exit;
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $studentId . '_' . time() . '.' . $extension;
    $destination = $uploadDir . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        // Update database with file path
        $stmt = $conn->prepare("UPDATE students SET profile_pic_path = ? WHERE student_id = ?");
        $stmt->bind_param("ss", $destination, $studentId);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Profile picture updated successfully!";
            // Update session with new profile pic path
            $_SESSION['user']['profile_pic_path'] = $destination;
        } else {
            $_SESSION['error'] = "Failed to update database.";
            // Delete the uploaded file if DB update fails
            unlink($destination);
        }
    } else {
        $_SESSION['error'] = "Failed to upload file.";
    }
} else {
    $_SESSION['error'] = "No file uploaded or upload error occurred.";
}

header("Location: ../view/userView.php");
exit;
?>