<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    // Get user details
    $role = strtolower(trim($user['role'] ?? 'user'));
    $full_name = '';

    $query = ($role === 'admin')
        ? "SELECT full_name FROM admins WHERE email = ?"
        : "SELECT full_name, student_id FROM users WHERE email = ?";

    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();

        if ($role === 'admin') {
            $stmt->bind_result($full_name);
        } else {
            $stmt->bind_result($full_name, $student_id);
        }

        $stmt->fetch();
        $stmt->close();
    }

    // Secure session configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_regenerate_id(true);

    // Set session data
    $_SESSION['logged_in'] = true;
    $_SESSION['user'] = [
        'id' => $user['id'],
        'student_id' => $student_id ?? $user['student_id'] ?? null,
        'email' => $user['email'],
        'role' => $role,
        'full_name' => $full_name ?: $user['full_name'] ?? '',
        'last_activity' => time(),
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ];

    // Redirect based on role
    $redirect = ($role === 'admin') ? '../view/adminView.php' : '../view/userView.php';
    header("Location: $redirect");
    exit;
}

$conn->close();
?>