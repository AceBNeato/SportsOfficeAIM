<?php
// /auth/login.php
session_start();

// Enable full error reporting during development
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$db = 'SportOfficeDB';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Email and password are required.";
        header("Location: ../view/loginView.php");
        exit;
    }

    // Check for brute force attempts
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_login_attempt'] = time();
    }



    $stmt = $conn->prepare("CALL find_user_by_email(?)");
    if (!$stmt) {
        $_SESSION['login_error'] = "Database error: " . $conn->error;
        header("Location: ../view/loginView.php");
        exit;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result || $result->num_rows === 0) {
        $_SESSION['login_attempts']++;
        $_SESSION['last_login_attempt'] = time();
        $_SESSION['login_error'] = "Invalid email or password.";
        header("Location: ../view/loginView.php");
        exit;
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    if (!password_verify($password, $user['password'])) {
        $_SESSION['login_attempts']++;
        $_SESSION['last_login_attempt'] = time();
        $_SESSION['login_error'] = "Invalid email or password.";
        header("Location: ../view/loginView.php");
        exit;
    }

    // Reset login attempts on successful login
    unset($_SESSION['login_attempts']);
    unset($_SESSION['last_login_attempt']);

    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    // Get user details based on role
    $full_name = '';
    $role = strtolower(trim($user['role'] ?? ''));

    if ($role === 'admin') {
        $stmt = $conn->prepare("SELECT full_name FROM admins WHERE email = ?");
    } else {
        $stmt = $conn->prepare("SELECT full_name FROM users WHERE email = ?");
    }

    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($full_name);
        $stmt->fetch();
        $stmt->close();
    }

    // Set session variables
    $_SESSION['logged_in'] = true;
    $_SESSION['user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'role' => $role,
        'full_name' => $full_name,
        'last_activity' => time()
    ];

    // Set session cookie parameters for security
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $cookieParams["lifetime"],
        'path' => '/',
        'domain' => $cookieParams["domain"],
        'secure' => true,    // Should be true in production (HTTPS)
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    error_log("User logged in: " . print_r($_SESSION['user'], true));

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