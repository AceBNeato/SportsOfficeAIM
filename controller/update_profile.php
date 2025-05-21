<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user']['id'])) {
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
    $_SESSION['profile_message'] = "System error. Please try again later.";
    header("Location: ../view/userView.php?page=Dashboard");
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $full_name = trim($_POST['full_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $address = trim($_POST['address'] ?? '');
    $sport = trim($_POST['sport'] ?? '');
    $campus = trim($_POST['campus'] ?? '');
    $year_section = trim($_POST['year_section'] ?? ''); // Sanitize year_section
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

    // Validate year_section (optional, max 100 characters)
    if (!empty($year_section) && strlen($year_section) > 100) {
        $errors[] = "Year and section must be 100 characters or less.";
    }

    // Password change validation
    if (!empty($password) || !empty($confirm_password)) {
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters.";
        }
    }

    // If no errors, update database
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            $user_id = $_SESSION['user']['id'];
            $updated = false;
            $changes_made = [];

            // Build dynamic query
            $query_parts = [];
            $params = [];
            $types = '';

            if ($full_name !== $_SESSION['user']['full_name']) {
                $query_parts[] = "full_name = ?";
                $params[] = $full_name;
                $types .= 's';
                $changes_made[] = 'name';
            }

            if ($address !== ($_SESSION['user']['address'] ?? '')) {
                $query_parts[] = "address = ?";
                $params[] = $address;
                $types .= 's';
                $changes_made[] = 'address';
            }

            if ($sport !== ($_SESSION['user']['sport'] ?? '')) {
                $query_parts[] = "sport = ?";
                $params[] = $sport;
                $types .= 's';
                $changes_made[] = 'sport';
            }

            if ($campus !== ($_SESSION['user']['campus'] ?? '')) {
                $query_parts[] = "campus = ?";
                $params[] = $campus;
                $types .= 's';
                $changes_made[] = 'campus';
            }

            // Handle year_section (allow empty string to become NULL)
            if ($year_section !== ($_SESSION['user']['year_section'] ?? '')) {
                $query_parts[] = "year_section = ?";
                $params[] = $year_section ?: null; // Convert empty string to NULL
                $types .= 's';
                $changes_made[] = 'year and section';
            }

            // Check email availability
            if ($email !== $_SESSION['user']['email']) {
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

            // Handle password update
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query_parts[] = "password = ?";
                $params[] = $hashed_password;
                $types .= 's';
                $changes_made[] = 'password';
            }

            // Execute update if there are changes
            if (!empty($query_parts)) {
                $query = "UPDATE users SET " . implode(", ", $query_parts) . " WHERE id = ?";
                $params[] = $user_id;
                $types .= 'i';

                $stmt = $conn->prepare($query);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $updated = $stmt->affected_rows > 0;
                $stmt->close();
            }

            // Handle profile image upload
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $upload = $_FILES['profile_image'];

                // Enhanced image validation
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $upload['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mime_type, $allowed_types)) {
                    throw new Exception('Invalid file type. Please upload JPG, PNG, or GIF images only.');
                }
                if ($upload['size'] > $max_size) {
                    throw new Exception('File is too large. Maximum allowed size is 2MB.');
                }
                if (!getimagesize($upload['tmp_name'])) {
                    throw new Exception('File is not a valid image.');
                }

                // Read and store image
                $imageData = file_get_contents($upload['tmp_name']);
                $imageType = $mime_type;

                $check_stmt = $conn->prepare("SELECT id FROM user_images WHERE user_id = ?");
                $check_stmt->bind_param("i", $user_id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $check_stmt->close();

                if ($result->num_rows > 0) {
                    $img_stmt = $conn->prepare("UPDATE user_images SET image = ?, image_type = ?, uploaded_at = CURRENT_TIMESTAMP WHERE user_id = ?");
                    $img_stmt->bind_param("ssi", $imageData, $imageType, $user_id);
                } else {
                    $img_stmt = $conn->prepare("INSERT INTO user_images (user_id, image, image_type) VALUES (?, ?, ?)");
                    $img_stmt->bind_param("iss", $user_id, $imageData, $imageType);
                }

                $img_stmt->send_long_data(1, $imageData);
                $img_stmt->execute();
                $updated = $img_stmt->affected_rows > 0;
                $img_stmt->close();

                $_SESSION['user']['has_profile_image'] = true;
                $changes_made[] = 'profile picture';
            }

            // Commit transaction
            $conn->commit();

            // Update session variables
            if ($updated) {
                $_SESSION['user']['full_name'] = $full_name;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['address'] = $address;
                $_SESSION['user']['sport'] = $sport;
                $_SESSION['user']['campus'] = $campus;
                $_SESSION['user']['year_section'] = $year_section ?: null; // Store as NULL if empty

                $_SESSION['profile_update_success'] = true;
                $_SESSION['profile_message'] = "Profile updated successfully.";

                // Create notification
                if (!empty($changes_made)) {
                    $notification_message = "You updated your profile: " . implode(", ", $changes_made);
                    if (!isset($_SESSION['notifications'])) {
                        $_SESSION['notifications'] = [];
                    }
                    array_unshift($_SESSION['notifications'], [
                        'message' => $notification_message,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                    $_SESSION['notifications'] = array_slice($_SESSION['notifications'], 0, 10);
                }
            } else {
                $_SESSION['profile_update_success'] = false;
                $_SESSION['profile_message'] = "No changes were made to your profile.";
            }

            header("Location: ../view/userView.php?page=Dashboard");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            error_log("Profile update error: " . $e->getMessage());
            $_SESSION['profile_update_success'] = false;
            $_SESSION['profile_message'] = !empty($errors) ? implode(" ", $errors) : "An error occurred: " . $e->getMessage();
            header("Location: ../view/userView.php?page=Dashboard");
            exit;
        }
    } else {
        $_SESSION['profile_update_success'] = false;
        $_SESSION['profile_message'] = implode(" ", $errors);
        header("Location: ../view/userView.php?page=Dashboard");
        exit;
    }
}

$conn->close();
?>