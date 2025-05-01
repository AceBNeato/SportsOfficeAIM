<?php
session_start();

// Enable error reporting for debugging (remove in production)
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
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Email and password are required.";
        header("Location: ../view/loginView.php");
        exit;
    }

    // Check if email exists
    $stmt = $conn->prepare("CALL find_user_by_email(?)");
    if (!$stmt) {
        $_SESSION['login_error'] = "Database error. Please try again later. Error: " . $conn->error;
        header("Location: ../view/loginView.php");
        exit;
    }

    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        $_SESSION['login_error'] = "Database error. Please try again later.";
        header("Location: ../view/loginView.php");
        exit;
    }

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['login_error'] = "Invalid email or password.";
        header("Location: ../view/loginView.php");
        exit;
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Verify password - make sure the 'password' field name matches your database
    if (password_verify($password, $user['password'])) {
        // Regenerate session ID for security
        session_regenerate_id(true);

        // Get additional user info if needed
        $full_name = '';
        if ($user['role'] === 'student') {
            $stmt = $conn->prepare("SELECT full_name FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($full_name);
            $stmt->fetch();
            $stmt->close();
        } else if ($user['role'] === 'admin') {
            $stmt = $conn->prepare("SELECT full_name FROM admins WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($full_name);
            $stmt->fetch();
            $stmt->close();
        }

        // Store user data in session
        $_SESSION['user'] = [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'full_name' => $full_name
        ];

        // Debug output (remove in production)
        error_log("User logged in: " . print_r($_SESSION['user'], true));

        // Redirect based on role - verify these paths exist
        if ($user['role'] === 'admin') {
            header("Location: ../view/adminView.php");
        } else {
            header("Location: ../view/userView.php");
        }
        exit;
    } else {
        $_SESSION['login_error'] = "Invalid email or password.";
        header("Location: ../view/loginView.php");
        exit;
    }
}

$conn->close();
?>