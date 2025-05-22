<?php
session_start();

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    $_SESSION['achievement_message'] = "Database connection failed.";
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/userView.php?page=Achievement");
    exit();
}

// CSRF token validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    error_log("Invalid CSRF token: received=" . ($_POST['csrf_token'] ?? 'not set'));
    $_SESSION['achievement_message'] = "Invalid CSRF token.";
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/userView.php?page=Achievement");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user']['id'])) {
    error_log("User not logged in: user_id=" . ($_SESSION['user']['id'] ?? 'not set'));
    $_SESSION['achievement_message'] = "User not logged in.";
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/userView.php?page=Achievement");
    exit();
}

// Function to calculate points
function calculatePoints($level_of_competition, $performance, $number_of_events, $leadership_role, $sportsmanship, $community_impact, $completeness_of_documents) {
    $points = 0;
    $points += match ($level_of_competition) {
        'Local' => 5,
        'Regional' => 10,
        'National' => 15,
        'International' => 20,
        default => 0
    };
    $points += match ($performance) {
        'Winner (Gold)' => 15,
        'Silver' => 10,
        'Bronze' => 5,
        'Participant' => 2,
        default => 0
    };
    $points += match ($number_of_events) {
        '1-2' => 5,
        '3-4' => 10,
        '5+' => 15,
        default => 0
    };
    $points += match ($leadership_role) {
        'Team Captain' => 10,
        'Active Member' => 5,
        'Reserve' => 2,
        default => 0
    };
    $points += match ($sportsmanship) {
        'No violation' => 10,
        'Minor warnings' => 5,
        'Major offense' => 0,
        default => 0
    };
    $points += match ($community_impact) {
        'Yes' => 10,
        'No' => 0,
        default => 0
    };
    $points += match ($completeness_of_documents) {
        'Complete and verified' => 15,
        'Incomplete or unclear' => 5,
        'Not submitted' => 0,
        default => 0
    };
    return $points;
}

// Handle achievement submission
if (isset($_POST['submit_achievement'])) {
    $user_id = $_SESSION['user']['id'];
    $athlete_name = filter_input(INPUT_POST, 'athlete_name', FILTER_SANITIZE_STRING);
    $level_of_competition = filter_input(INPUT_POST, 'level_of_competition', FILTER_SANITIZE_STRING);
    $performance = filter_input(INPUT_POST, 'performance', FILTER_SANITIZE_STRING);
    $number_of_events = filter_input(INPUT_POST, 'number_of_events', FILTER_SANITIZE_STRING);
    $leadership_role = filter_input(INPUT_POST, 'leadership_role', FILTER_SANITIZE_STRING);
    $sportsmanship = filter_input(INPUT_POST, 'sportsmanship', FILTER_SANITIZE_STRING);
    $community_impact = filter_input(INPUT_POST, 'community_impact', FILTER_SANITIZE_STRING);
    $completeness_of_documents = filter_input(INPUT_POST, 'completeness_of_documents', FILTER_SANITIZE_STRING);

    // Validate required fields
    if (!$athlete_name || !$level_of_competition || !$performance || !$number_of_events || !$leadership_role || !$sportsmanship || !$community_impact || !$completeness_of_documents) {
        $_SESSION['achievement_message'] = "All fields are required.";
        $_SESSION['message_class'] = "bg-red-100";
        header("Location: ../view/userView.php?page=Achievement");
        exit();
    }

    // Calculate total points
    $points = calculatePoints($level_of_competition, $performance, $number_of_events, $leadership_role, $sportsmanship, $community_impact, $completeness_of_documents);

    // Handle file uploads
    $upload_dir = '../Uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $document_paths = [];
    if (!empty($_FILES['documents']['name'][0])) {
        foreach ($_FILES['documents']['name'] as $key => $name) {
            if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $ext;
                $destination = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['documents']['tmp_name'][$key], $destination)) {
                    $document_paths[] = $filename;
                }
            }
        }
    }
    $documents = implode(',', $document_paths);

    // Insert achievement into database
    $stmt = $conn->prepare("INSERT INTO achievements (user_id, athlete_name, level_of_competition, performance, number_of_events, leadership_role, sportsmanship, community_impact, completeness_of_documents, documents, total_points, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("isssssssssi", $user_id, $athlete_name, $level_of_competition, $performance, $number_of_events, $leadership_role, $sportsmanship, $community_impact, $completeness_of_documents, $documents, $points);

    if ($stmt->execute()) {
        $_SESSION['achievement_message'] = "Achievement submitted successfully!";
        $_SESSION['message_class'] = "bg-green-100";
    } else {
        error_log("Failed to submit achievement: " . $stmt->error);
        $_SESSION['achievement_message'] = "Failed to submit achievement.";
        $_SESSION['message_class'] = "bg-red-100";
    }
    $stmt->close();
}

// Handle achievement edit
if (isset($_POST['edit_achievement'])) {
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
    $existing_documents = filter_input(INPUT_POST, 'existing_documents', FILTER_SANITIZE_STRING);

    // Validate required fields
    if (!$achievement_id || !$athlete_name || !$level_of_competition || !$performance || !$number_of_events || !$leadership_role || !$sportsmanship || !$community_impact || !$completeness_of_documents) {
        $_SESSION['achievement_message'] = "All fields are required.";
        $_SESSION['message_class'] = "bg-red-100";
        header("Location: ../view/userView.php?page=Achievement");
        exit();
    }

    // Verify achievement belongs to user and is editable
    $stmt_check = $conn->prepare("SELECT status, documents FROM achievements WHERE achievement_id = ? AND user_id = ?");
    $stmt_check->bind_param("ii", $achievement_id, $user_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    if ($result->num_rows === 0) {
        error_log("Achievement ID $achievement_id not found or not owned by user $user_id");
        $_SESSION['achievement_message'] = "Achievement not found.";
        $_SESSION['message_class'] = "bg-red-100";
        $stmt_check->close();
        header("Location: ../view/userView.php?page=Achievement");
        exit();
    }
    $achievement = $result->fetch_assoc();
    $stmt_check->close();

    if ($achievement['status'] === 'Approved') {
        error_log("Cannot edit approved achievement ID $achievement_id");
        $_SESSION['achievement_message'] = "Approved achievements cannot be edited.";
        $_SESSION['message_class'] = "bg-red-100";
        header("Location: ../view/userView.php?page=Achievement");
        exit();
    }

    // Calculate total points
    $points = calculatePoints($level_of_competition, $performance, $number_of_events, $leadership_role, $sportsmanship, $community_impact, $completeness_of_documents);

    // Handle file uploads
    $upload_dir = '../Uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $document_paths = $existing_documents ? explode(',', $existing_documents) : [];
    if (!empty($_FILES['documents']['name'][0])) {
        foreach ($_FILES['documents']['name'] as $key => $name) {
            if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $ext;
                $destination = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['documents']['tmp_name'][$key], $destination)) {
                    $document_paths[] = $filename;
                }
            }
        }
    }
    $documents = implode(',', $document_paths);

    // Update achievement in database
    $stmt = $conn->prepare("UPDATE achievements SET athlete_name = ?, level_of_competition = ?, performance = ?, number_of_events = ?, leadership_role = ?, sportsmanship = ?, community_impact = ?, completeness_of_documents = ?, documents = ?, total_points = ?, status = 'Pending', rejection_reason = NULL WHERE achievement_id = ? AND user_id = ?");
    $stmt->bind_param("sssssssssiii", $athlete_name, $level_of_competition, $performance, $number_of_events, $leadership_role, $sportsmanship, $community_impact, $completeness_of_documents, $documents, $points, $achievement_id, $user_id);

    if ($stmt->execute()) {
        $_SESSION['achievement_message'] = "Achievement updated successfully!";
        $_SESSION['message_class'] = "bg-green-100";
    } else {
        error_log("Failed to update achievement ID $achievement_id: " . $stmt->error);
        $_SESSION['achievement_message'] = "Failed to update achievement.";
        $_SESSION['message_class'] = "bg-red-100";
    }
    $stmt->close();
}

// Handle achievement deletion
if (isset($_POST['delete_achievement'])) {
    $user_id = $_SESSION['user']['id'];
    $achievement_id = filter_input(INPUT_POST, 'achievement_id', FILTER_VALIDATE_INT);

    if (!$achievement_id) {
        error_log("Invalid achievement ID for deletion: " . ($_POST['achievement_id'] ?? 'not set'));
        $_SESSION['achievement_message'] = "Invalid achievement ID.";
        $_SESSION['message_class'] = "bg-red-100";
        header("Location: ../view/userView.php?page=Achievement");
        exit();
    }

    // Verify achievement belongs to user and is deletable
    $stmt_check = $conn->prepare("SELECT status, documents FROM achievements WHERE achievement_id = ? AND user_id = ?");
    $stmt_check->bind_param("ii", $achievement_id, $user_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    if ($result->num_rows === 0) {
        error_log("Achievement ID $achievement_id not found or not owned by user $user_id");
        $_SESSION['achievement_message'] = "Achievement not found.";
        $_SESSION['message_class'] = "bg-red-100";
        $stmt_check->close();
        header("Location: ../view/userView.php?page=Achievement");
        exit();
    }
    $achievement = $result->fetch_assoc();
    $stmt_check->close();

    if ($achievement['status'] === 'Approved') {
        error_log("Cannot delete approved achievement ID $achievement_id");
        $_SESSION['achievement_message'] = "Approved achievements cannot be deleted.";
        $_SESSION['message_class'] = "bg-red-100";
        header("Location: ../view/userView.php?page=Achievement");
        exit();
    }

    // Delete associated documents
    $upload_dir = '../Uploads/';
    if ($achievement['documents']) {
        $documents = explode(',', $achievement['documents']);
        foreach ($documents as $doc) {
            $file_path = $upload_dir . $doc;
            if (file_exists($file_path)) {
                if (!unlink($file_path)) {
                    error_log("Failed to delete file: $file_path");
                }
            }
        }
    }

    // Delete achievement from database
    $stmt = $conn->prepare("DELETE FROM achievements WHERE achievement_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $achievement_id, $user_id);

    if ($stmt->execute()) {
        $_SESSION['achievement_message'] = "Achievement deleted successfully!";
        $_SESSION['message_class'] = "bg-green-100";
    } else {
        error_log("Failed to delete achievement ID $achievement_id: " . $stmt->error);
        $_SESSION['achievement_message'] = "Failed to delete achievement.";
        $_SESSION['message_class'] = "bg-red-100";
    }
    $stmt->close();
}

$conn->close();
header("Location: ../view/userView.php?page=Achievement");
exit();
?>