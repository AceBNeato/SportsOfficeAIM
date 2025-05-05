<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../view/loginView.php");
    exit;
}

// Database configuration
$host = 'localhost';
$db = 'SportOfficeDB';
$user = 'root';
$pass = '';

// Establish connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("System error. Please try again later.");
}

// Process image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $userId = $_SESSION['user']['id'];
    $upload = $_FILES['profile_image'];

    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB

    $response = [
        'success' => false,
        'message' => 'An error occurred during upload.'
    ];

    // Check if upload is valid
    if ($upload['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Upload failed with error code: ' . $upload['error'];
    }
    elseif (!in_array($upload['type'], $allowed_types)) {
        $response['message'] = 'Invalid file type. Please upload JPG, PNG or GIF images only.';
    }
    elseif ($upload['size'] > $max_size) {
        $response['message'] = 'File is too large. Maximum allowed size is 2MB.';
    }
    else {
        // File is valid, process it
        try {
            // Start transaction
            $conn->begin_transaction();

            // Read file data
            $imageData = file_get_contents($upload['tmp_name']);
            $imageType = $upload['type'];

            // Check if user already has an image
            $check_stmt = $conn->prepare("SELECT id FROM user_images WHERE user_id = ?");
            $check_stmt->bind_param("i", $userId);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $check_stmt->close();

            if ($result->num_rows > 0) {
                // Update existing image
                $stmt = $conn->prepare("UPDATE user_images SET image = ?, image_type = ?, uploaded_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                $stmt->bind_param("bsi", $imageData, $imageType, $userId);
            } else {
                // Insert new image
                $stmt = $conn->prepare("INSERT INTO user_images (user_id, image, image_type) VALUES (?, ?, ?)");
                $stmt->bind_param("ibs", $userId, $imageData, $imageType);
            }

            if ($stmt->execute()) {
                $conn->commit();
                $response['success'] = true;
                $response['message'] = 'Profile image updated successfully.';

                // Update session
                $_SESSION['user']['has_profile_image'] = true;

                // Set success message in session
                $_SESSION['profile_update_success'] = true;
                $_SESSION['profile_message'] = 'Profile image updated successfully.';
            } else {
                throw new Exception("Database error: " . $stmt->error);
            }

            $stmt->close();

        } catch (Exception $e) {
            $conn->rollback();
            error_log("Image upload error: " . $e->getMessage());
            $response['message'] = 'System error occurred while saving the image.';
        }
    }

    // Send JSON response for AJAX requests or redirect for form submission
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        if ($response['success']) {
            header("Location: ../view/userView.php?page=Dashboard");
        } else {
            $_SESSION['profile_update_success'] = false;
            $_SESSION['profile_message'] = $response['message'];
            header("Location: ../view/userView.php?page=Dashboard");
        }
    }
    exit;
}

$conn->close();
?>