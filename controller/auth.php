<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Secure session configuration
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_samesite', 'Strict');

// Database configuration
$host = 'localhost';
$db = 'SportOfficeDB';
$user = 'root';
$pass = '';

// Establish connection
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    $_SESSION['login_error'] = "System error. Please try again later.";
    header("Location: ../view/loginView.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("CSRF Token Mismatch: POST=" . ($_POST['csrf_token'] ?? 'Not set') . ", Session=" . ($_SESSION['csrf_token'] ?? 'Not set'));
        $_SESSION['login_error'] = "Invalid session. Please try again.";
        header("Location: ../view/loginView.php");
        exit;
    }

    // Sanitize inputs
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['login_error'] = "Invalid email format";
        header("Location: ../view/loginView.php");
        exit;
    }

    if (empty($password) || strlen($password) < 8) {
        $_SESSION['login_error'] = "Password must be at least 8 characters";
        header("Location: ../view/loginView.php");
        exit;
    }

    // Brute force protection
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_login_attempt'] = time();
    }

    $max_attempts = 5;
    $lockout_time = 300; // 5 minutes

    if ($_SESSION['login_attempts'] >= $max_attempts) {
        $time_since_last_attempt = time() - $_SESSION['last_login_attempt'];
        if ($time_since_last_attempt < $lockout_time) {
            $_SESSION['login_error'] = "Too many attempts. Please try again later.";
            header("Location: ../view/loginView.php");
            exit;
        } else {
            $_SESSION['login_attempts'] = 0;
        }
    }

    // Database query
    $stmt = $conn->prepare("CALL find_user_by_email(?)");
    if (!$stmt) {
        error_log("Database error: " . $conn->error);
        $_SESSION['login_error'] = "System error. Please try again later.";
        header("Location: ../view/loginView.php");
        exit;
    }

    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        error_log("Execution error: " . $stmt->error);
        $_SESSION['login_error'] = "System error. Please try again later.";
        header("Location: ../view/loginView.php");
        exit;
    }

    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows === 0) {
        $_SESSION['login_attempts']++;
        $_SESSION['last_login_attempt'] = time();
        $_SESSION['login_error'] = "Invalid email or password";
        header("Location: ../view/loginView.php");
        exit;
    }

    $user = $result->fetch_assoc();

    // Verify password
    if (!password_verify($password, $user['password'])) {
        $_SESSION['login_attempts']++;
        $_SESSION['last_login_attempt'] = time();
        $_SESSION['login_error'] = "Invalid email or password";
        header("Location: ../view/loginView.php");
        exit;
    }

    // Check for password rehashing
    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update_stmt->bind_param("ss", $newHash, $email);
        $update_stmt->execute();
        $update_stmt->close();
    }

    // Reset login attempts
    unset($_SESSION['login_attempts']);
    unset($_SESSION['last_login_attempt']);

    // Normalize role value
    $role = strtolower(trim($user['role'] ?? 'user'));

    // Get user details based on role
    if ($role === 'admin') {
        $query = "SELECT id, full_name, address, email, password FROM admins WHERE email = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Database error: " . $conn->error);
            $_SESSION['login_error'] = "System error. Please try again later.";
            header("Location: ../view/loginView.php");
            exit;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_details = $result->fetch_assoc();
        $stmt->close();

        if (!$user_details) {
            $_SESSION['login_error'] = "Admin account error. Please contact support.";
            header("Location: ../view/loginView.php");
            exit;
        }

        $full_name = $user_details['full_name'];
        $address = $user_details['address'];
        $id = $user_details['id'];
        $student_id = null;
        $year_section = null;
        $sport = null;
        $campus = null;
    } else {
        $query = "SELECT id, student_id, full_name, address, email, password, sport, campus FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Database error: " . $conn->error);
            $_SESSION['login_error'] = "System error. Please try again later.";
            header("Location: ../view/loginView.php");
            exit;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_details = $result->fetch_assoc();
        $stmt->close();

        if (!$user_details) {
            $_SESSION['login_error'] = "User account error. Please contact support.";
            header("Location: ../view/loginView.php");
            exit;
        }

        $full_name = $user_details['full_name'];
        $address = $user_details['address'];
        $id = $user_details['id'];
        $student_id = $user_details['student_id'];
        $sport = $user_details['sport'];
        $campus = $user_details['campus'];
        $password = $user_details['password'];

        // Get year_section from submissions table
        $year_section = null;
        $submission_query = "SELECT year_section FROM submissions WHERE user_id = ? LIMIT 1";
        $submission_stmt = $conn->prepare($submission_query);
        if ($submission_stmt) {
            $submission_stmt->bind_param("i", $id);
            $submission_stmt->execute();
            $submission_result = $submission_stmt->get_result();
            if ($submission_result->num_rows > 0) {
                $submission_data = $submission_result->fetch_assoc();
                $year_section = $submission_data['year_section'];
            }
            $submission_stmt->close();
        }
    }

    // Check if user has a profile image
    $has_profile_image = false;
    $image_stmt = $conn->prepare("SELECT 1 FROM user_images WHERE user_id = ?");
    $image_stmt->bind_param("i", $id);
    $image_stmt->execute();
    $image_result = $image_stmt->get_result();
    if ($image_result->num_rows > 0) {
        $has_profile_image = true;
    }
    $image_stmt->close();

    // Regenerate session ID for security
    session_regenerate_id(true);

    // Set the session variables
    $_SESSION['logged_in'] = true;
    $_SESSION['user'] = [
        'id' => $id,
        'student_id' => $student_id,
        'email' => $email,
        'password' => $password,
        'address' => $address,
        'role' => $role,
        'full_name' => $full_name,
        'year_section' => $year_section,
        'sport' => $sport,
        'campus' => $campus,
        'last_activity' => time(),
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'has_profile_image' => $has_profile_image
    ];

    // Also store in submissions session for form autofill
    $_SESSION['submissions']['year_section'] = $year_section ?? '';

    // Regenerate CSRF token after successful login
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Debug log
    error_log("User logged in - Email: $email, Role: $role, Has Image: " . ($has_profile_image ? 'Yes' : 'No') .
        ", Year/Section: " . ($year_section ?? 'Not set') .
        ", Sport: " . ($sport ?? 'Not set') .
        ", Campus: " . ($campus ?? 'Not set') .
        ", Redirecting to: " . ($role === 'admin' ? 'adminView.php' : 'userView.php'));

    // Redirect based on role
    if ($role === 'admin') {
        header("Location: ../view/adminView.php");
    } else {
        header("Location: ../view/userView.php");
    }
    exit;
}

$conn->close();
?>