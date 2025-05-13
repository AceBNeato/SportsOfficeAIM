<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

// Function to send approval email
function sendApprovalEmail($recipientEmail, $fullName, $studentId) {
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address for approval: $recipientEmail");
        return false;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tagummabinisportoffice@gmail.com';
        $mail->Password = 'wecx ezju zcin ymmn';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('tagummabinisportoffice@gmail.com', 'SportOfficeDB Admin');
        $mail->addAddress($recipientEmail);
        $mail->addReplyTo('tagummabinisportoffice@gmail.com', 'SportOfficeDB Admin');

        $mail->isHTML(true);
        $mail->Subject = 'Account Approval Notification';
        $mail->Body = "
            <h3>Account Approved</h3>
            <p>Dear $fullName,</p>
            <p>Your account request has been approved. You can now log in using the following credentials:</p>
            <ul>
                <li><strong>Student ID:</strong> $studentId</li>
                <li><strong>Email:</strong> $recipientEmail</li>
                <li><strong>Password:</strong> $studentId (Please change your password after logging in for security purposes.)</li>
            </ul>
            <p><a href='SportsOfficeAIM/view/loginView.php'>Log in here</a></p>
            <p>Best regards,<br>SportOfficeDB Team</p>
        ";

        $mail->send();
        error_log("Approval email sent to: $recipientEmail");
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error in sendApprovalEmail: {$mail->ErrorInfo}");
        return false;
    }
}

// Function to send rejection email
function sendRejectionEmail($recipientEmail, $fullName) {
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address for rejection: $recipientEmail");
        return false;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'tagummabinisportoffice@gmail.com';
        $mail->Password = 'wecx ezju zcin ymmn';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('tagummabinisportoffice@gmail.com', 'SportOfficeDB Admin');
        $mail->addAddress($recipientEmail);
        $mail->addReplyTo('tagummabinisportoffice@gmail.com', 'SportOfficeDB Admin');

        $mail->isHTML(true);
        $mail->Subject = 'Account Request Rejected';
        $mail->Body = "
            <h3>Account Request Rejected</h3>
            <p>Dear $fullName,</p>
            <p>We regret to inform you that your account request has been rejected. This may be due to incomplete or invalid documentation.</p>
            <p>Please contact the administrator at <a href='mailto:tagummabinisportoffice@gmail.com'>tagummabinisportoffice@gmail.com</a> for further details or to resubmit your request.</p>
            <p>Best regards,<br>SportOfficeDB Team</p>
        ";

        $mail->send();
        error_log("Rejection email sent to: $recipientEmail");
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error in sendRejectionEmail: {$mail->ErrorInfo}");
        return false;
    }
}
?>