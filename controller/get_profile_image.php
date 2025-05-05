<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$db = 'SportOfficeDB';
$user = 'root';
$pass = '';

// Establish connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    header("HTTP/1.1 500 Internal Server Error");
    exit;
}

// Get user ID from URL parameter with validation
$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Verify user is logged in and has permission to view this image
$authorized = false;

// If user is logged in and requesting their own image
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if ($_SESSION['user']['id'] === $userId) {
        $authorized = true;
    }
    // Admin can view any image
    elseif ($_SESSION['user']['role'] === 'admin') {
        $authorized = true;
    }
}

if (!$authorized || !$userId) {
    // Return a placeholder or default image if not authorized
    header("HTTP/1.1 403 Forbidden");
    header("Location: ../public/image/default_profile.png");
    exit;
}

// Query to fetch the image
$stmt = $conn->prepare("SELECT image, image_type FROM user_images WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $image = $result->fetch_assoc();

    // Output image with correct content type
    header("Content-Type: " . $image['image_type']);
    echo $image['image'];
} else {
    // No image found, return default
    header("HTTP/1.1 404 Not Found");
    header("Location: ../public/image/default_profile.png");
}

$stmt->close();
$conn->close();
?>