<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../view/loginView.php");
    exit;
}

// Database configuration
$host = 'localhost';
$db = 'SportOfficeDB';
$user = 'root';
$pass = '';

// Establish connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("System error. Please try again later.");
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $full_name = trim($_POST['full_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Input validation
    $errors = [];

    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }

    // Password change is optional, but if provided must match and meet requirements
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters.";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
    }

    // If there are no errors, update the database
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            $user_id = $_SESSION['user']['id'];
            $updated = false;
            $changes_made = []; // Track what was changed for notifications

            // Build the query dynamically based on which fields to update
            $query_parts = [];
            $params = [];
            $types = '';

            if (!empty($full_name) && $full_name !== $_SESSION['user']['full_name']) {
                $query_parts[] = "full_name = ?";
                $params[] = $full_name;
                $types .= 's';
                $changes_made[] = 'name';
            }

            if (!empty($address) && $address !== $_SESSION['user']['address']) {
                $query_parts[] = "address = ?";
                $params[] = $address;
                $types .= 's';
                $changes_made[] = 'address';
            }

            // Check if new email already in use
            if (!empty($email) && $email !== $_SESSION['user']['email']) {
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $check_stmt->bind_param("si", $email, $user_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $check_stmt->close();

                if ($result->num_rows > 0) {
                    $errors[] = "Email already in use by another account.";
                    throw new Exception("Email already in use");
                }

                $query_parts[] = "email = ?";
                $params[] = $email;
                $types .= 's';
                $changes_made[] = 'email';
            }

            // Add password hash update if provided
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query_parts[] = "password = ?";
                $params[] = $hashed_password;
                $types .= 's';
                $changes_made[] = 'password';
            }

            // Final update query
            if (!empty($query_parts)) {
                $query = "UPDATE users SET " . implode(", ", $query_parts) . " WHERE id = ?";
                $params[] = $user_id;
                $types .= 'i';

                $stmt = $conn->prepare($query);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->close();
                $updated = true;
            }

            // Handle profile image upload if present
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $upload = $_FILES['profile_image'];

                // Validate file
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB

                if (!in_array($upload['type'], $allowed_types)) {
                    throw new Exception('Invalid file type. Please upload JPG, PNG or GIF images only.');
                }
                elseif ($upload['size'] > $max_size) {
                    throw new Exception('File is too large. Maximum allowed size is 2MB.');
                }
                else {
                    // Read file data
                    $imageData = file_get_contents($upload['tmp_name']);
                    $imageType = $upload['type'];

                    // Check if user already has an image
                    $check_stmt = $conn->prepare("SELECT id FROM user_images WHERE user_id = ?");
                    $check_stmt->bind_param("i", $user_id);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    $check_stmt->close();

                    if ($result->num_rows > 0) {
                        // Update existing image
                        $img_stmt = $conn->prepare("UPDATE user_images SET image = ?, image_type = ?, uploaded_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                        $img_stmt->bind_param("bsi", $imageData, $imageType, $user_id);
                    } else {
                        // Insert new image
                        $img_stmt = $conn->prepare("INSERT INTO user_images (user_id, image, image_type) VALUES (?, ?, ?)");
                        $img_stmt->bind_param("ibs", $user_id, $imageData, $imageType);
                    }

                    $img_stmt->send_long_data(0, $imageData); // Important for large blobs
                    $img_stmt->execute();
                    $img_stmt->close();

                    // Flag that user has a profile image
                    $_SESSION['user']['has_profile_image'] = true;
                    $updated = true;
                    $changes_made[] = 'profile picture';
                }
            }

            // Commit transaction if we got here
            $conn->commit();

            // Update session variables with new values
            if ($updated) {
                $_SESSION['user']['full_name'] = $full_name;
                if (!empty($email)) $_SESSION['user']['email'] = $email;
                $_SESSION['user']['address'] = $address;

                $_SESSION['profile_update_success'] = true;
                $_SESSION['profile_message'] = "Profile updated successfully.";

                // Create notification message based on changes
                if (!empty($changes_made)) {
                    $notification_message = "You updated your profile: " . implode(", ", $changes_made);
                    // Store notification in session (will be processed by JavaScript)
                    if (!isset($_SESSION['notifications'])) {
                        $_SESSION['notifications'] = [];
                    }
                    array_unshift($_SESSION['notifications'], [
                        'message' => $notification_message,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);

                    // Keep only the last 10 notifications in session
                    $_SESSION['notifications'] = array_slice($_SESSION['notifications'], 0, 10);
                }
            }

            // Redirect back to user view
            header("Location: ../view/userView.php?page=Dashboard");
            exit;

        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            error_log("Profile update error: " . $e->getMessage());
            $_SESSION['profile_update_success'] = false;
            $_SESSION['profile_message'] = !empty($errors) ? implode(" ", $errors) : "An error occurred while updating your profile: " . $e->getMessage();
            header("Location: ../view/userView.php?page=Dashboard");
            exit;
        }
    } else {
        // Store errors in session
        $_SESSION['profile_update_success'] = false;
        $_SESSION['profile_message'] = implode(" ", $errors);
        header("Location: ../view/userView.php?page=Dashboard");
        exit;
    }
}

$conn->close();
?>