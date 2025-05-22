<?php
session_start();
$conn = new mysqli("localhost", "root", "", "SportOfficeDB");
if ($conn->connect_error) die("Connection failed");

if (isset($_POST['action']) && isset($_POST['achievement_id'])) {
    $id = $_POST['achievement_id'];
    $action = $_POST['action'];
    $reason = $_POST['reject_reason'] ?? null;

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE achievements SET status = 'Approved' WHERE achievement_id = ?");
        $stmt->bind_param("i", $id);
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE achievements SET status = 'Rejected', rejection_reason = ? WHERE achievement_id = ?");
        $stmt->bind_param("si", $reason, $id);
    }

    if ($stmt->execute()) {
        header("Location: ../view/adminView.php?page=Achievement");
    } else {
        error_log("Failed to $action achievement $id: " . $stmt->error);
    }
    $stmt->close();
}
$conn->close();
exit();
?>