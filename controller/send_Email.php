

<?php
// controller/send_Email.php

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include PHPMailer
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email sending function
function sendEmailNotification($email, $fullName, $status, $password = null) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address: $email");
        return false;
    }

    $fullName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
    $status = htmlspecialchars($status, ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $password = $password ? htmlspecialchars($password, ENT_QUOTES, 'UTF-8') : null;

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@gmail.com'; // Replace with your Gmail address
        $mail->Password = 'your_app_password'; // Replace with your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('your_email@gmail.com', 'Sports Office');
        $mail->addAddress($email, $fullName);
        $mail->isHTML(true);
        $mail->Subject = "Account Approval Status: " . ucfirst($status);
        $body = "<p>Dear $fullName,</p>";
        $body .= "<p>Your account approval request has been <strong>$status</strong>.</p>";
        if ($status === 'approved' && $password) {
            $body .= "<p>You can now log in using the following credentials:</p>";
            $body .= "<p>Email: $email<br>Password: $password</p>";
            $body .= "<p>Please change your password after logging in.</p>";
        }
        $body .= "<p>Thank you,<br>Sports Office Team</p>";
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        $_SESSION['email_error'] = "Failed to send email: {$mail->ErrorInfo}";
        return false;
    }
}
?>