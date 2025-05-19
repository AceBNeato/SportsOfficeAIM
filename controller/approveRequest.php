<?php
require_once '../view/mail.php'; // Include PHPMailer functions

// Secure session configuration
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600); // 1 hour
session_set_cookie_params(0, '/'); // Ensure session cookie is available for all paths

// Start session
session_start();

// Debug: Log session data and request details
file_put_contents('debug.log', 'Approve Request - Session: ' . print_r($_SESSION, true) . "\n", FILE_APPEND);
file_put_contents('debug.log', 'Approve Request - POST Data: ' . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents('debug.log', 'Approve Request - Session ID: ' . session_id() . "\n", FILE_APPEND);

// Check if user is logged in and is an admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true ||
    !isset($_SESSION['user']) || !isset($_SESSION['user']['id']) || (int)$_SESSION['user']['id'] <= 0 ||
    !isset($_SESSION['user']['role']) || strtolower(trim($_SESSION['user']['role'])) !== 'admin') {
    header('Location: ../view/loginView.php?status=error&message=admin_not_logged_in');
    exit;
}

// Database configuration
$host = 'localhost';
$db = 'SportOfficeDB';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $approvalId = (int)($_POST['approval_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $adminId = (int)$_SESSION['user']['id']; // Use the session-based user ID

    if ($approvalId <= 0) {
        error_log("Invalid approval ID: $approvalId");
        header("Location: ../view/adminView.php?status=error&action=invalid");
        exit;
    }

    // Fetch request details, including sport and campus
    $stmt = $conn->prepare("SELECT student_id, full_name, email, status, sport, campus FROM account_approvals WHERE id = ?");
    $stmt->bind_param("i", $approvalId);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $stmt->close();

    if ($request) {
        if ($action === 'approve') {
            // Approval logic
            $plainPassword = $request['student_id'];
            $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

            // Insert into users table, including sport and campus
            $stmt = $conn->prepare("INSERT INTO users (student_id, full_name, address, email, password, status, sport, campus) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $address = 'Unknown'; // Update if address is collected
            $stmt->bind_param("ssssssss", $request['student_id'], $request['full_name'], $address, $request['email'], $hashedPassword, $request['status'], $request['sport'], $request['campus']);
            $userInserted = $stmt->execute();
            $newUserId = $conn->insert_id; // Get the ID of the newly inserted user
            $stmt->close();

            if ($userInserted) {
                // Insert default profile image
                $defaultImagePath = '../public/image/user.png';
                if (file_exists($defaultImagePath)) {
                    $imageData = file_get_contents($defaultImagePath);
                    $imageType = mime_content_type($defaultImagePath);

                    $stmt = $conn->prepare("INSERT INTO user_images (user_id, image, image_type, uploaded_at) VALUES (?, ?, ?, NOW())");
                    $stmt->bind_param("iss", $newUserId, $imageData, $imageType);
                    $imageInserted = $stmt->execute();
                    $stmt->close();

                    if (!$imageInserted) {
                        error_log("Failed to insert default profile image for user ID: $newUserId");
                        // Optionally, handle this error (e.g., proceed anyway or rollback)
                    }
                } else {
                    error_log("Default profile image not found at: $defaultImagePath");
                }

                // Update account_approvals
                $stmt = $conn->prepare("UPDATE account_approvals SET approval_status = 'approved', approved_by = ?, approval_date = NOW() WHERE id = ?");
                $stmt->bind_param("ii", $adminId, $approvalId);
                $updated = $stmt->execute();
                $stmt->close();

                if ($updated) {
                    // Send approval email
                    if (sendApprovalEmail($request['email'], $request['full_name'], $request['student_id'])) {
                        header("Location: ../view/adminView.php?status=success&action=approve");
                    } else {
                        error_log("Failed to send approval email for user: {$request['email']}");
                        header("Location: ../view/adminView.php?status=error&action=approve");
                    }
                } else {
                    error_log("Failed to update account_approvals for approval ID: $approvalId");
                    header("Location: ../view/adminView.php?status=error&action=approve");
                }
            } else {
                error_log("Failed to insert user for approval ID: $approvalId");
                header("Location: ../view/adminView.php?status=error&action=approve");
            }
        }
    } else {
        error_log("No request found for approval ID: $approvalId");
        header("Location: ../view/adminView.php?status=error&action=invalid");
    }
} else {
    header("Location: ../view/adminView.php?status=error&action=invalid");
}

$conn->close();
exit;
?>