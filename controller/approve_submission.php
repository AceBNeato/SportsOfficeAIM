<?php
// Secure session configuration
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600, '/'); // Set cookie lifetime to 1 hour

// Suppress notices and warnings
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Start session
session_start();

// Debug: Log detailed session and request details
$debug_log = [
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'post_data' => $_POST,
    'session_check' => [
        'logged_in' => isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : null,
        'user_exists' => isset($_SESSION['user']),
        'user_id' => isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null,
        'user_role' => isset($_SESSION['user']['role']) ? strtolower(trim($_SESSION['user']['role'])) : null
    ],
    'csrf_check' => [
        'session_csrf' => isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : null,
        'post_csrf' => $_POST['csrf_token'] ?? null
    ]
];
file_put_contents('debug.log', 'Approve Submission - Debug: ' . print_r($debug_log, true) . "\n", FILE_APPEND);

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true ||
    !isset($_SESSION['user']) || !isset($_SESSION['user']['id']) || (int)$_SESSION['user']['id'] <= 0 ||
    !isset($_SESSION['user']['role']) || strtolower(trim($_SESSION['user']['role'])) !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Admin not logged in or invalid admin ID']);
    exit;
}

// Validate CSRF token
$csrf_token = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    $csrf_error = [
        'session_csrf_missing' => !isset($_SESSION['csrf_token']),
        'post_csrf_missing' => !isset($_POST['csrf_token']),
        'tokens_match' => isset($_SESSION['csrf_token'], $_POST['csrf_token']) ? hash_equals($_SESSION['csrf_token'], $csrf_token) : false
    ];
    file_put_contents('debug.log', 'CSRF Error Details: ' . print_r($csrf_error, true) . "\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Database connection
$conn = new mysqli("localhost", "root", "", "SportOfficeDB");
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset("utf8mb4");

// Validate input
$submission_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($submission_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid submission ID']);
    exit;
}

// Check if submission exists and is pending
$stmt = $conn->prepare("SELECT id FROM submissions WHERE id = ? AND status = 'pending'");
if ($stmt === false) {
    error_log("Prepare failed: " . $conn->error);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database query preparation failed']);
    exit;
}
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Submission not found or already processed']);
    exit;
}
$stmt->close();

// Update submission
$stmt = $conn->prepare("UPDATE submissions SET status = 'approved' WHERE id = ?");
if ($stmt === false) {
    error_log("Prepare failed: " . $conn->error);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database query preparation failed']);
    exit;
}
$stmt->bind_param("i", $submission_id);
$success = $stmt->execute();

$response = [
    'success' => $success,
    'message' => $success ? 'Submission approved successfully' : 'Failed to approve submission: ' . $conn->error
];

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
?>