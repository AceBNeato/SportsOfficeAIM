<?php
session_start();

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    $_SESSION['achievement_message'] = "Database connection failed.";
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/userView.php?page=Achievement");
    exit();
}

// CSRF token validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['achievement_message'] = "Invalid CSRF token.";
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/userView.php?page=Achievement");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user']['id'])) {
    $_SESSION['achievement_message'] = "User not logged in.";
    $_SESSION['message_class'] = "bg-red-100";
    header("Location: ../view/userView.php?page=Achievement");
    exit();
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
        $_SESSION['achievement_message'] = "Failed to submit achievement.";
        $_SESSION['message_class'] = "bg-red-100";
    }
    $stmt->close();
}

$conn->close();
header("Location: ../view/userView.php?page=Achievement");
exit();
?>