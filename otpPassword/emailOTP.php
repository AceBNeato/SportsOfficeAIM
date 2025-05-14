<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    header("Location: forgotPassView.php?message=" . urlencode("Database connection failed"));
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

header('Content-Type: application/json');

// Load SMTP credentials from environment variables
$SMTP_USER = getenv('SMTP_USER') ?: 'tagummabinisportoffice@gmail.com';
$SMTP_PASS = getenv('SMTP_PASS') ?: 'wecx ezju zcin ymmn'; // Replace with env variable in production

// Function to generate random OTP
function generateOTP($length = 6) {
    $characters = '0123456789';
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $otp;
}

// Function to send OTP email
function sendOTPEmail($email, $otp, $smtpUser, $smtpPass) {
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->Timeout = 60;

        $mail->setFrom($smtpUser, 'USeP Sports Office');
        $mail->addAddress($email);
        $mail->addReplyTo($smtpUser, 'USeP Sports Office');

        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP - USeP Sports Office';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #333;'>
                <h2>USeP OSAS-Sports Unit</h2>
                <h3>Password Reset Request</h3>
                <p>You have requested to reset your password. Please use the following OTP code to verify your identity:</p>
                <h1 style='color: #ff5e5e;'>$otp</h1>
                <p>This code will expire in 15 minutes. If you did not request this password reset, please ignore this email.</p>
                <p>&copy; " . date('Y') . " USeP OSAS-Sports Unit. All rights reserved.</p>
           شتی
        ";
        $mail->AltBody = "Your password reset OTP is: $otp. This code will expire in 15 minutes.";

        if ($mail->send()) {
            error_log("Email sent successfully to $email");
            return true;
        } else {
            error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    } catch (Exception $e) {
        error_log("PHPMailer Exception: {$e->getMessage()}");
        return false;
    }
}

// Handle AJAX requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'send_otp':
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
                exit;
            }

            // Check if email exists in the database
            $userStmt = $conn->prepare("SELECT id, email FROM users WHERE email = ?");
            $userStmt->bind_param("s", $email);
            $userStmt->execute();
            $userResult = $userStmt->get_result();

            if ($userResult->num_rows == 0) {
                $adminStmt = $conn->prepare("SELECT id, email FROM admins WHERE email = ?");
                $adminStmt->bind_param("s", $email);
                $adminStmt->execute();
                $adminResult = $adminStmt->get_result();

                if ($adminResult->num_rows == 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Email not registered']);
                    exit;
                } else {
                    $user = $adminResult->fetch_assoc();
                    $_SESSION['reset_user_id'] = $user['id'];
                    $_SESSION['reset_user_role'] = 'admin';
                }
                $adminStmt->close();
            } else {
                $user = $userResult->fetch_assoc();
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['reset_user_role'] = 'user';
            }
            $userStmt->close();

            // Generate and store OTP
            $otp = generateOTP();
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['reset_otp_email'] = $email;
            $_SESSION['reset_otp_time'] = time();

            // Send OTP email
            $emailSent = sendOTPEmail($email, $otp, $SMTP_USER, $SMTP_PASS);

            if ($emailSent) {
                echo json_encode(['status' => 'success', 'message' => 'OTP sent successfully']);
            } else {
                error_log("Failed to send OTP email to $email");
                echo json_encode(['status' => 'error', 'message' => 'Failed to send OTP email. Please try again or contact support.']);
            }
            break;

        case 'verify_otp':
            $entered_otp = trim($_POST['otp'] ?? '');

            if (empty($entered_otp)) {
                echo json_encode(['status' => 'error', 'message' => 'OTP is required']);
                exit;
            }

            // Check if OTP session exists and is not expired
            if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['reset_otp_time']) ||
                (time() - $_SESSION['reset_otp_time'] > 900)) {
                echo json_encode(['status' => 'error', 'message' => 'OTP expired or invalid session']);
                exit;
            }

            // Verify OTP
            if ($entered_otp === $_SESSION['reset_otp']) {
                $_SESSION['otp_verified'] = true;
                echo json_encode(['status' => 'success', 'message' => 'OTP verified successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid OTP']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

$conn->close();
?>