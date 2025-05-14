<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(403);
    exit;
}

$conn = new mysqli("localhost", "root", "", "SportOfficeDB");
if ($conn->connect_error) {
    http_response_code(500);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$download = isset($_GET['download']) && $_GET['download'] === 'true';

$stmt = $conn->prepare("SELECT file_name, file_data FROM submissions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $file_name = $row['file_name'];
    $file_data = $row['file_data'];
    $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Detect MIME type from BLOB data
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->buffer($file_data);

    $content_type = match (true) {
        str_contains($mime_type, 'pdf') || $extension === 'pdf' => 'application/pdf',
        str_contains($mime_type, 'jpeg') || $extension === 'jpg' || $extension === 'jpeg' => 'image/jpeg',
        str_contains($mime_type, 'png') || $extension === 'png' => 'image/png',
        default => 'application/octet-stream',
    };

    // Fix filename for download if extension is malformed
    if (!in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'])) {
        $extension = match (true) {
            str_contains($mime_type, 'pdf') => 'pdf',
            str_contains($mime_type, 'jpeg') => 'jpg',
            str_contains($mime_type, 'png') => 'png',
            default => 'bin',
        };
        $file_name = pathinfo($file_name, PATHINFO_FILENAME) . '.' . $extension;
    }

    if ($download) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
    } else {
        header('Content-Type: ' . $content_type);
    }
    header('Content-Length: ' . strlen($file_data));
    echo $file_data;
    exit;
}

http_response_code(404);
echo "File not found.";
?>