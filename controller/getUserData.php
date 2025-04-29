<?php
// getUserData.php

global $conn;
require_once '../database/config.php'; // Your PDO connection

header('Content-Type: application/json');

if (isset($_GET['student_id'])) {
    $student_id = trim($_GET['student_id']);

    try {
        $stmt = $conn->prepare("CALL GetUserByStudentID(:student_id)");
        $stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
        $stmt->execute();


        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor(); // Important when using stored procedures


        if ($user) {
            echo json_encode([
                'success' => true,
                'user' => $user
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'User not found.'
            ]);
        }

    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);
}
?>
