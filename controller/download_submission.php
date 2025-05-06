<?php
session_start();

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    die("Unauthorized access");
}

$submission_id = (int)$_GET['id'];
$user_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("SELECT file_name, file_data FROM submissions WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $submission_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $file_name = $row['file_name'];
    $file_data = $row['file_data'];

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Content-Length: ' . strlen($file_data));
    echo $file_data;
} else {
    die("File not found or access denied");
}

$stmt->close();
$conn->close();
?><?php
