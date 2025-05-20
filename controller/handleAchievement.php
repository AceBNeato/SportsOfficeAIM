<?php
session_start();

// Start debug logging
$debugLog = fopen('../debug.log', 'a') or die("Can't open debug.log");
fwrite($debugLog, "handleAchievement started at " . date('Y-m-d H:i:s') . " PST\n");

$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    $error = "Database connection failed: " . $conn->connect_error;
    fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
    $_SESSION['achievement_message'] = $error;
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/userView.php");
    exit();
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $error = "CSRF token validation failed.";
    fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
    $_SESSION['achievement_message'] = "Invalid CSRF token.";
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/userView.php");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user']['id'])) {
    $error = "You must be logged in to submit or update an achievement.";
    fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
    $_SESSION['achievement_message'] = $error;
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/userView.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$athlete_name = filter_input(INPUT_POST, 'athlete_name', FILTER_SANITIZE_STRING);
$level_of_competition = filter_input(INPUT_POST, 'level_of_competition', FILTER_SANITIZE_STRING);
$performance = filter_input(INPUT_POST, 'performance', FILTER_SANITIZE_STRING);
$number_of_events = filter_input(INPUT_POST, 'number_of_events', FILTER_SANITIZE_STRING);
$leadership_role = filter_input(INPUT_POST, 'leadership_role', FILTER_SANITIZE_STRING);
$sportsmanship = filter_input(INPUT_POST, 'sportsmanship', FILTER_SANITIZE_STRING);
$community_impact = filter_input(INPUT_POST, 'community_impact', FILTER_SANITIZE_STRING);
$completeness_of_documents = filter_input(INPUT_POST, 'completeness_of_documents', FILTER_SANITIZE_STRING);
$achievement_id = filter_input(INPUT_POST, 'achievement_id', FILTER_VALIDATE_INT);

$valid_levels = ['Local', 'Regional', 'National', 'International'];
$valid_performances = ['Winner (Gold)', 'Silver', 'Bronze', 'Participant'];
$valid_events = ['1-2', '3-4', '5+'];
$valid_roles = ['Team Captain', 'Active Member', 'Reserve'];
$valid_sportsmanship = ['No violation', 'Minor warnings', 'Major offense'];
$valid_community = ['Yes', 'No'];
$valid_documents = ['Complete and verified', 'Incomplete or unclear', 'Not submitted'];

if (
    !$athlete_name ||
    !in_array($level_of_competition, $valid_levels) ||
    !in_array($performance, $valid_performances) ||
    !in_array($number_of_events, $valid_events) ||
    !in_array($leadership_role, $valid_roles) ||
    !in_array($sportsmanship, $valid_sportsmanship) ||
    !in_array($community_impact, $valid_community) ||
    !in_array($completeness_of_documents, $valid_documents)
) {
    $error = "Invalid form data: " . print_r($_POST, true);
    fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
    $_SESSION['achievement_message'] = "Invalid form data.";
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/userView.php");
    exit();
}

// Calculate total points
$points = 0;
$points += ($level_of_competition === 'Local' ? 5 : ($level_of_competition === 'Regional' ? 10 : ($level_of_competition === 'National' ? 15 : 20)));
$points += ($performance === 'Winner (Gold)' ? 15 : ($performance === 'Silver' ? 10 : ($performance === 'Bronze' ? 5 : 2)));
$points += ($number_of_events === '1-2' ? 5 : ($number_of_events === '3-4' ? 10 : 15));
$points += ($leadership_role === 'Team Captain' ? 10 : ($leadership_role === 'Active Member' ? 5 : 2));
$points += ($sportsmanship === 'No violation' ? 10 : ($sportsmanship === 'Minor warnings' ? 5 : 0));
$points += ($community_impact === 'Yes' ? 10 : 0);
$points += ($completeness_of_documents === 'Complete and verified' ? 15 : ($completeness_of_documents === 'Incomplete or unclear' ? 5 : 0));

// Handle file uploads or updates
$upload_dir = "../uploads/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}
$document_paths = [];
if (!empty($_FILES['documents']['name'][0])) {
    foreach ($_FILES['documents']['name'] as $key => $name) {
        if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            if (!in_array(strtolower($ext), ['pdf', 'jpg', 'jpeg', 'png'])) {
                $error = "Invalid file type for $name.";
                fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
                $_SESSION['achievement_message'] = $error;
                $_SESSION['message_class'] = "bg-red-100";
                header("Location: ../view/userView.php");
                exit();
            }
            $new_filename = uniqid() . '.' . $ext;
            $destination = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['documents']['tmp_name'][$key], $destination)) {
                $document_paths[] = $new_filename;
            } else {
                $error = "Failed to upload file: $name.";
                fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
                $_SESSION['achievement_message'] = $error;
                $_SESSION['message_class'] = "bg-red-100";
                header("Location: ../view/userView.php");
                exit();
            }
        }
    }
}

$conn->begin_transaction();
try {
    if ($achievement_id) {
        // Update existing achievement
        $stmt = $conn->prepare("
            UPDATE achievements 
            SET athlete_name = ?, level_of_competition = ?, performance = ?, number_of_events = ?, 
                leadership_role = ?, sportsmanship = ?, community_impact = ?, completeness_of_documents = ?, 
                total_points = ?, documents = ?, submission_date = NOW()
            WHERE achievement_id = ? AND user_id = ? AND status IN ('Pending', 'Rejected')
        ");
        $documents = $document_paths ? implode(',', $document_paths) : (isset($_POST['existing_documents']) ? $_POST['existing_documents'] : '');
        $stmt->bind_param(
            "sssssssssisi",
            $athlete_name, $level_of_competition, $performance, $number_of_events,
            $leadership_role, $sportsmanship, $community_impact, $completeness_of_documents,
            $points, $documents, $achievement_id, $user_id
        );
        if (!$stmt->execute()) {
            throw new Exception("Update failed: " . $stmt->error);
        }
        $affected_rows = $stmt->affected_rows;
        if ($affected_rows > 0) {
            $conn->commit();
            $_SESSION['achievement_message'] = "Achievement updated successfully.";
            $_SESSION['message_class'] = "bg-green-100";
        } else {
            throw new Exception("No rows updated or unauthorized.");
        }
    } else {
        // New submission
        $stmt = $conn->prepare("
            INSERT INTO achievements (
                user_id, athlete_name, level_of_competition, performance, number_of_events,
                leadership_role, sportsmanship, community_impact, completeness_of_documents,
                total_points, submission_date, status, documents
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Pending', ?)
        ");
        $documents = implode(',', $document_paths);
        $stmt->bind_param(
            "issssssssis",
            $user_id, $athlete_name, $level_of_competition, $performance, $number_of_events,
            $leadership_role, $sportsmanship, $community_impact, $completeness_of_documents,
            $points, $documents
        );
        if (!$stmt->execute()) {
            throw new Exception("Insert failed: " . $stmt->error);
        }
        $conn->commit();
        $_SESSION['achievement_message'] = "Achievement submitted successfully.";
        $_SESSION['message_class'] = "bg-green-100";
    }
} catch (Exception $e) {
    $conn->rollback();
    $error = "Error: " . $e->getMessage();
    fwrite($debugLog, $error . " at " . date('Y-m-d H:i:s') . " PST\n");
    $_SESSION['achievement_message'] = "Failed to process achievement: " . $e->getMessage();
    $_SESSION['message_class'] = "bg-red-100";
}

$stmt->close();
$conn->close();
header("Location: ../view/userView.php");
exit();

fwrite($debugLog, "handleAchievement ended at " . date('Y-m-d H:i:s') . " PST\n\n");
fclose($debugLog);
?>