<?php
session_start();

// Database configuration
$host = 'localhost';
$db = 'SportOfficeDB';
$user = 'root';
$pass = '';

// Establish connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user ID from request
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if user is requesting their own image or has permission
if (!isset($_SESSION['user']['id']) || $_SESSION['user']['id'] != $user_id) {
    // You may want to implement additional permission checks here
    http_response_code(403);
    die("Access denied");
}

// Get the latest image for the user
$stmt = $conn->prepare("SELECT image, image_type FROM user_images WHERE user_id = ? ORDER BY uploaded_at DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    header("Content-Type: " . $row['image_type']);
    echo $row['image'];
} else {
    // Return a default image if no profile image exists
    $default_image = file_get_contents('../public/image/default-profile.png');
    header("Content-Type: image/png");
    echo $default_image;
}

$stmt->close();
$conn->close();
?>