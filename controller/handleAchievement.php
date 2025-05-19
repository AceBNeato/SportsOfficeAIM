<?php
// Start the session
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 0); // Disable display of errors to users
ini_set('log_errors', 1);
ini_set('error_log', '../logs/error.log'); // Log errors to a file

// Handle form submission
if (isset($_POST['submit_achievement'])) {
    $message = '';
    $message_class = 'bg-red-100';

    // Check if user is logged in
    if (!isset($_SESSION['user']['id'])) {
        error_log("User not logged in for achievement submission at " . date('Y-m-d H:i:s'));
        $message = 'User not logged in';
    } elseif (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        error_log("CSRF validation failed at " . date('Y-m-d H:i:s'));
        $message = 'Invalid CSRF token';
    } else {
        // Sanitize form inputs
        $athlete_name = filter_input(INPUT_POST, 'athlete_name', FILTER_SANITIZE_STRING);
        $level_of_competition = filter_input(INPUT_POST, 'level_of_competition', FILTER_SANITIZE_STRING);
        $performance = filter_input(INPUT_POST, 'performance', FILTER_SANITIZE_STRING);
        $number_of_events = filter_input(INPUT_POST, 'number_of_events', FILTER_SANITIZE_STRING);
        $leadership_role = filter_input(INPUT_POST, 'leadership_role', FILTER_SANITIZE_STRING);
        $sportsmanship = filter_input(INPUT_POST, 'sportsmanship', FILTER_SANITIZE_STRING);
        $community_impact = filter_input(INPUT_POST, 'community_impact', FILTER_SANITIZE_STRING);
        $completeness_of_documents = filter_input(INPUT_POST, 'completeness_of_documents', FILTER_SANITIZE_STRING);

        // Validate required fields
        if (empty($athlete_name) || empty($level_of_competition) || empty($performance)) {
            error_log("Missing required fields: athlete_name=$athlete_name, level_of_competition=$level_of_competition, performance=$performance at " . date('Y-m-d H:i:s'));
            $message = 'Missing required fields';
        } else {
            // Database connection
            $host = "localhost";
            $username = "root";
            $password = "";
            $dbname = "SportOfficeDB";

            $conn = new mysqli($host, $username, $password, $dbname);
            if ($conn->connect_error) {
                error_log("Connection failed: " . $conn->connect_error . " at " . date('Y-m-d H:i:s'));
                $message = 'Database connection failed';
            } else {
                // Enable autocommit to ensure changes are saved
                $conn->autocommit(TRUE);

                // Points mapping
                $levelOfCompetitionPoints = ['Local' => 5, 'Regional' => 10, 'National' => 15, 'International' => 20];
                $performancePoints = ['Winner (Gold)' => 15, 'Silver' => 10, 'Bronze' => 5, 'Participant' => 2];
                $numberOfEventsPoints = ['1-2' => 5, '3-4' => 10, '5+' => 15];
                $leadershipRolePoints = ['Team Captain' => 10, 'Active Member' => 5, 'Reserve' => 2];
                $sportsmanshipPoints = ['No violation' => 10, 'Minor warnings' => 5, 'Major offense' => 0];
                $communityImpactPoints = ['Yes' => 10, 'No' => 0];
                $completenessPoints = ['Complete and verified' => 15, 'Incomplete or unclear' => 5, 'Not submitted' => 0];

                $points = 0;
                $points += $levelOfCompetitionPoints[$level_of_competition] ?? 0;
                $points += $performancePoints[$performance] ?? 0;
                $points += $numberOfEventsPoints[$number_of_events] ?? 0;
                $points += $leadershipRolePoints[$leadership_role] ?? 0;
                $points += $sportsmanshipPoints[$sportsmanship] ?? 0;
                $points += $communityImpactPoints[$community_impact] ?? 0;
                $points += $completenessPoints[$completeness_of_documents] ?? 0;

                $file_paths = [];
                $upload_dir = "../Uploads/";
                if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                    error_log("Failed to create upload directory: $upload_dir at " . date('Y-m-d H:i:s'));
                    $message = 'Failed to create upload directory';
                } else {
                    if (!empty($_FILES['documents']['name'][0])) {
                        foreach ($_FILES['documents']['tmp_name'] as $key => $tmp_name) {
                            if ($_FILES['documents']['error'][$key] === UPLOAD_ERR_OK) {
                                $file_name = basename($_FILES['documents']['name'][$key]);
                                $file_path = $upload_dir . time() . '_' . $file_name;
                                if (move_uploaded_file($tmp_name, $file_path)) {
                                    $file_paths[] = $file_path;
                                } else {
                                    error_log("Failed to upload file: $file_name at " . date('Y-m-d H:i:s'));
                                    $message = 'Failed to upload file: ' . $file_name;
                                    break;
                                }
                            } else {
                                error_log("File upload error for file $key: " . $_FILES['documents']['error'][$key] . " at " . date('Y-m-d H:i:s'));
                                $message = 'File upload error: ' . $_FILES['documents']['error'][$key];
                                break;
                            }
                        }
                    }

                    if (empty($message)) {
                        $documents_json = json_encode($file_paths);

                        // Log the values being passed to the stored procedure
                        error_log("Preparing to call AddAchievement with: user_id={$_SESSION['user']['id']}, athlete_name=$athlete_name, level_of_competition=$level_of_competition, performance=$performance, number_of_events=$number_of_events, leadership_role=$leadership_role, sportsmanship=$sportsmanship, community_impact=$community_impact, completeness_of_documents=$completeness_of_documents, points=$points, documents_json=$documents_json at " . date('Y-m-d H:i:s'));

                        $stmt = $conn->prepare("CALL AddAchievement(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        if (!$stmt) {
                            error_log("Prepare failed: " . $conn->error . " at " . date('Y-m-d H:i:s'));
                            $message = 'Prepare failed: ' . $conn->error;
                        } else {
                            $status = 'Pending';
                            $rejection_reason = '';
                            $stmt->bind_param("issssssssisss", $_SESSION['user']['id'], $athlete_name, $level_of_competition, $performance, $number_of_events, $leadership_role, $sportsmanship, $community_impact, $completeness_of_documents, $points, $documents_json, $status, $rejection_reason);

                            if ($stmt->execute()) {
                                // Verify the data was actually inserted
                                $verify_stmt = $conn->prepare("SELECT COUNT(*) as count FROM achievements WHERE user_id = ? AND athlete_name = ? AND DATE(submission_date) = CURDATE()");
                                if ($verify_stmt) {
                                    $verify_stmt->bind_param("is", $_SESSION['user']['id'], $athlete_name);
                                    $verify_stmt->execute();
                                    $result = $verify_stmt->get_result();
                                    $row = $result->fetch_assoc();
                                    if ($row['count'] > 0) {
                                        $message = 'Achievement submitted successfully';
                                        $message_class = 'bg-green-100';
                                    } else {
                                        // Fallback to direct insert if stored procedure fails
                                        error_log("Stored procedure executed but no data inserted, attempting direct insert at " . date('Y-m-d H:i:s'));
                                        $insert_stmt = $conn->prepare("INSERT INTO achievements (user_id, athlete_name, level_of_competition, performance, number_of_events, leadership_role, sportsmanship, community_impact, completeness_of_documents, total_points, documents, status, rejection_reason, submission_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                                        if ($insert_stmt) {
                                            $insert_stmt->bind_param("issssssssisss", $_SESSION['user']['id'], $athlete_name, $level_of_competition, $performance, $number_of_events, $leadership_role, $sportsmanship, $community_impact, $completeness_of_documents, $points, $documents_json, $status, $rejection_reason);
                                            if ($insert_stmt->execute()) {
                                                $message = 'Achievement submitted successfully';
                                                $message_class = 'bg-green-100';
                                            } else {
                                                error_log("Direct insert failed: " . $insert_stmt->error . " at " . date('Y-m-d H:i:s'));
                                                $message = 'Failed to save achievement: ' . $insert_stmt->error;
                                            }
                                            $insert_stmt->close();
                                        } else {
                                            error_log("Prepare for direct insert failed: " . $conn->error . " at " . date('Y-m-d H:i:s'));
                                            $message = 'Failed to prepare direct insert: ' . $conn->error;
                                        }
                                    }
                                    $verify_stmt->close();
                                } else {
                                    error_log("Failed to prepare verification query: " . $conn->error . " at " . date('Y-m-d H:i:s'));
                                    $message = 'Failed to verify insertion: ' . $conn->error;
                                }
                            } else {
                                error_log("Failed to execute stored procedure: " . $stmt->error . " at " . date('Y-m-d H:i:s'));
                                $message = 'Failed to save achievement: ' . $stmt->error;
                            }
                            $stmt->close();
                        }
                    }
                }
                $conn->close();
            }
        }
    }

    // Store message in session and redirect
    $_SESSION['achievement_message'] = $message;
    $_SESSION['message_class'] = $message_class;
    header("Location: ../view/userView.php?page=Achievement");
    exit;
}

// If no valid action is detected, redirect to userView.php
header("Location: ../view/userView.php?page=Achievement");
exit;
?>