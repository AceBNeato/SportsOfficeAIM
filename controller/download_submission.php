<?php
$conn = new mysqli("localhost", "root", "", "SportOfficeDB");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$download = isset($_GET['download']) && $_GET['download'] === 'true';
$convert = isset($_GET['convert']) && $_GET['convert'] === 'pdf';

$stmt = $conn->prepare("SELECT file_name FROM submissions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $filePath = "../uploads/" . $row['file_name'];
    if (file_exists($filePath)) {
        if ($download) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            readfile($filePath);
        } else {
            $mime = mime_content_type($filePath);
            if ($convert && $mime !== 'application/pdf') {
                // Placeholder: Add logic to convert to PDF if needed
                $mime = 'application/pdf';
            }
            header('Content-Type: ' . $mime);
            readfile($filePath);
        }
        exit;
    }
}
http_response_code(404);
echo "File not found.";
?>