<?php
session_start();

$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    $_SESSION['achievement_message'] = "Database connection failed.";
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/userView.php");
    exit();
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['achievement_message'] = "Invalid CSRF token.";
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/userView.php");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user']['id'])) {
    $_SESSION['achievement_message'] = "You must be logged in to resubmit an achievement.";
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/userView.php");
    exit();
}

if (isset($_POST['resubmit_achievement']) && $_POST['resubmit_achievement'] == 1) {
    $user_id = $_SESSION['user']['id'];
    $achievement_id = filter_input(INPUT_POST, 'achievement_id', FILTER_VALIDATE_INT);
    $athlete_name = filter_input(INPUT_POST, 'athlete_name', FILTER_SANITIZE_STRING);
    $level_of_competition = filter_input(INPUT_POST, 'level_of_competition', FILTER_SANITIZE_STRING);
    $performance = filter_input(INPUT_POST, 'performance', FILTER_SANITIZE_STRING);
    $number_of_events = filter_input(INPUT_POST, 'number_of_events', FILTER_SANITIZE_STRING);
    $leadership_role = filter_input(INPUT_POST, 'leadership_role', FILTER_SANITIZE_STRING);
    $sportsmanship = filter_input(INPUT_POST, 'sportsmanship', FILTER_SANITIZE_STRING);
    $community_impact = filter_input(INPUT_POST, 'community_impact', FILTER_SANITIZE_STRING);
    $completeness_of_documents = filter_input(INPUT_POST, 'completeness_of_documents', FILTER_SANITIZE_STRING);

    // Validate inputs
    $valid_levels = ['Local', 'Regional', 'National', 'International'];
    $valid_performances = ['Winner (Gold)', 'Silver', 'Bronze', 'Participant'];
    $valid_events = ['1-2', '3-4', '5+'];
    $valid_roles = ['Team Captain', 'Active Member', 'Reserve'];
    $valid_sportsmanship = ['No violation', 'Minor warnings', 'Major offense'];
    $valid_community = ['Yes', 'No'];
    $valid_documents = ['Complete and verified', 'Incomplete or unclear', 'Not submitted'];

    if (
        !$achievement_id ||
        !$athlete_name ||
        !in_array($level_of_competition, $valid_levels) ||
        !in_array($performance, $valid_performances) ||
        !in_array($number_of_events, $valid_events) ||
        !in_array($leadership_role, $valid_roles) ||
        !in_array($sportsmanship, $valid_sportsmanship) ||
        !in_array($community_impact, $valid_community) ||
        !in_array($completeness_of_documents, $valid_documents)
    ) {
        $_SESSION['achievement_message'] = "Invalid form data.";
        $_SESSION['message_class'] = "bg-red-100";
        header("Location: ../view/userView.php");
        exit();
    }

    // Verify the achievement belongs to the user and is rejected
    $stmt = $conn->prepare("SELECT user_id, status, documents FROM achievements WHERE id = ?");
    $stmt->bind_param("i", $achievement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0 || $result->fetch_assoc()['status'] !== 'Rejected' || $result->fetch_assoc()['user_id'] !== $user_id) {
        $_SESSION['achievement_message'] = "Invalid or unauthorized achievement.";
        $_SESSION['message_class'] = "bg-red-100";
        header("Location: ../view/userView.php");
        exit();
    }
    $stmt->close();

    // Calculate total points
    $points = 0;
    $points += ($level_of_competition === 'Local' ? 5 : ($level_of_competition === 'Regional' ? 10 : ($level_of_competition === 'National' ? 15 : 20)));
    $points += ($performance === 'Winner (Gold)' ? 15 : ($performance === 'Silver' ? 10 : ($performance === 'Bronze' ? 5 : 2)));
    $points += ($number_of_events === '1-2' ? 5 : ($number_of_events === '3-4' ? 10 : 15));
    $points += ($leadership_role === 'Team Captain' ? 10 : ($leadership_role === 'Active Member' ? 5 : 2));
    $points += ($sportsmanship === 'No violation' ? 10 : ($sportsmanship === 'Minor warnings' ? 5 : 0));
    $points += ($community_impact === 'Yes' ? 10 : 0);
    $points += ($completeness_of_documents === 'Complete and verified' ? 15 : ($completeness_of_documents === 'Incomplete or unclear' ? 5 : 0));

    // Handle file uploads
    $upload_dir = "../uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $document_paths = [];
    if (!empty($_FILES['documents']['name'][0])) {
        foreach ($_FILES['documents']['name'] as $key => $name) {
            if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                if (!in_array(strtolower($ext), ['pdf', 'jpg', 'jpeg', 'png'])) {
                    $_SESSION['achievement_message'] = "Invalid file type.";
                    $_SESSION['message_class'] = "bg-red-100";
                    header("Location: ../view/userView.php");
                    exit();
                }
                $new_filename = uniqid() . '.' . $ext;
                $destination = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['documents']['tmp_name'][$key], $destination)) {
                    $document_paths[] = $new_filename;
                } else {
                    $_SESSION['achievement_message'] = "Failed to upload file: $name.";
                    $_SESSION['message_class'] = "bg-red-100";
                    header("Location: ../view/userView.php");
                    exit();
                }
            }
        }
    }

    // Delete old documents if new ones are uploaded
    if (!empty($document_paths)) {
        $stmt = $conn->prepare("SELECT documents FROM achievements WHERE id = ?");
        $stmt->bind_param("i", $achievement_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $old_documents = explode(',', $row['documents']);
        foreach ($old_documents as $doc) {
            if ($doc && file_exists($upload_dir . $doc)) {
                unlink($upload_dir . $doc);
            }
        }
        $stmt->close();
    } else {
        $stmt = $conn->prepare("SELECT documents FROM achievements WHERE id = ?");
        $stmt->bind_param("i", $achievement_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $document_paths = explode(',', $row['documents']);
        $stmt->close();
    }

    // Update achievement in database
    $stmt = $conn->prepare("
        UPDATE achievements SET
            athlete_name = ?,
            level_of_competition = ?,
            performance = ?,
            number_of_events = ?,
            leadership_role = ?,
            sportsmanship = ?,
            community_impact = ?,
            completeness_of_documents = ?,
            total_points = ?,
            submission_date = NOW(),
            status = 'Pending',
            rejection_reason = NULL,
            documents = ?
        WHERE id = ? AND user_id = ?
    ");
    $documents = implode(',', $document_paths);
    $stmt->bind_param(
        "ssssssssisi",
        $athlete_name,
        $level_of_competition,
        $performance,
        $number_of_events,
        $leadership_role,
        $sportsmanship,
        $community_impact,
        $completeness_of_documents,
        $points,
        $documents,
        $achievement_id,
        $user_id
    );

    if ($stmt->execute()) {
        $_SESSION['achievement_message'] = "Achievement resubmitted successfully.";
        $_SESSION['message_class'] = "bg-green-100";
    } else {
        $_SESSION['achievement_message'] = "Failed to resubmit achievement.";
        $_SESSION['message_class'] = "bg-red-100";
    }

    $stmt->close();
    $conn->close();
    header("Location: ../view/userView.php");
    exit();
}
?>