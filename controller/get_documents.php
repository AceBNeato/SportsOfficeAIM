<?php
header('Content-Type: application/json');

try {
    $conn = new mysqli("localhost", "root", "", "SportOfficeDB");
    $conn->set_charset("utf8mb4");

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $studentId = $_GET['student_id'] ?? '';
    $studentId = $conn->real_escape_string($studentId);

    $stmt = $conn->prepare("SELECT * FROM student_documents WHERE student_id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();

    $documents = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($documents);

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}