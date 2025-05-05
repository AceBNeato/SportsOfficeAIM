

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
            $role = $_SESSION['user']['role'];
            $updated = false;

            // Determine which table to update based on user role
            $table = ($role === 'admin') ? 'admins' : 'users';

            // Build the query dynamically based on which fields to update
            $query_parts = [];
            $params = [];
            $types = '';

            if (!empty($full_name)) {
                $query_parts[] = "full_name = ?";
                $params[] = $full_name;
                $types .= 's';
            }

            if (!empty($address)) {
                $query_parts[] = "address = ?";
                $params[] = $address;
                $types .= 's';
            }

            // Re-check role and user table
            $table = ($role === 'admin') ? 'admins' : 'users';

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
            }

            // Add password hash update if provided
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query_parts[] = "password = ?";
                $params[] = $hashed_password;
                $types .= 's';
            }

            // Final update query (users table always holds login info)
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

                    $img_stmt->execute();
                    $img_stmt->close();

                    // Flag that user has a profile image
                    $_SESSION['user']['has_profile_image'] = true;
                    $updated = true;
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


<?php









session_start();

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

// Connect to database
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$message = '';
$user_data = null;
$user_image = null;

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // User Registration
    if (isset($_POST['register'])) {
        $student_id = $_POST['student_id'];
        $full_name = $_POST['full_name'];
        $address = $_POST['address'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $status = $_POST['status'];

        try {
            $stmt = $conn->prepare("INSERT INTO users (student_id, full_name, address, email, password, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $student_id, $full_name, $address, $email, $password, $status);
            $stmt->execute();
            $user_id = $stmt->insert_id;
            $message = "Registration successful! User ID: $user_id";
            $_SESSION['user_id'] = $user_id;
            $_SESSION['full_name'] = $full_name;
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    }

    // Image Upload
    if (isset($_FILES["user_image"]) && !empty($_FILES["user_image"]["tmp_name"])) {
        if (!isset($_SESSION['user_id'])) {
            $message = "Please register or log in first before uploading an image.";
        } else {
            $user_id = $_SESSION['user_id'];
            $image = $_FILES['user_image']['tmp_name'];
            $image_type = $_FILES['user_image']['type'];

            $allowed_types = ['image/jpeg', 'image/png'];
            if (in_array($image_type, $allowed_types) && getimagesize($image)) {
                $imgData = file_get_contents($image);
                $stmt = $conn->prepare("INSERT INTO user_images (user_id, image, image_type) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user_id, $imgData, $image_type);

                if ($stmt->execute()) {
                    $message = "Image uploaded successfully!";
                } else {
                    $message = "Error uploading image: " . $conn->error;
                }
                $stmt->close();
            } else {
                $message = "Invalid image file. Please upload JPEG or PNG.";
            }
        }
    }

    // User Login
    if (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, full_name, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $message = "Login successful! Welcome back, " . htmlspecialchars($user['full_name']);
            } else {
                $message = "Invalid password.";
            }
        } else {
            $message = "User not found.";
        }
        $stmt->close();
    }
}

// Get user data if logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Get latest user image if exists
    $stmt = $conn->prepare("SELECT image_type, image FROM user_images WHERE user_id = ? ORDER BY uploaded_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $image_result = $stmt->get_result();
    if ($image_result->num_rows > 0) {
        $user_image = $image_result->fetch_assoc();
    }
    $stmt->close();
}
