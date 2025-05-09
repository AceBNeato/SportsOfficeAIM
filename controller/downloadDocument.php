<?php
session_start(); // Optional: Use if you need to restrict access to authenticated admins
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    exit("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit("Document ID not provided");
}

$approval_id = intval($_GET['id']);
$is_download = isset($_GET['download']) && $_GET['download'] === 'true';

// Fetch file details
$stmt = $conn->prepare("SELECT file_name, file_data, file_type FROM account_approvals WHERE id = ?");
$stmt->bind_param("i", $approval_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    exit("Document not found");
}

$file = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Set headers based on whether it's a download or preview
if ($is_download) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file['file_name']) . '"');
    header('Content-Length: ' . strlen($file['file_data']));
} else {
    header('Content-Type: ' . $file['file_type']);
    header('Content-Length: ' . strlen($file['file_data']));
}

// Output the file data
echo $file['file_data'];
exit;
?>