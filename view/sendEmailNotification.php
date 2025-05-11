<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



function sendEmailNotification($recipientEmail, $recipientName, $status, $password = null) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP host
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Replace with your SMTP username
        $mail->Password = 'your-app-password'; // Replace with your SMTP password or app-specific password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Sender and recipient
        $mail->setFrom('no-reply@yourdomain.com', 'Account Approval System');
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->addReplyTo('no-reply@yourdomain.com', 'No Reply');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Account Approval Status';

        if ($status === 'approved') {
            $mail->Body = "
                <h2>Account Approval Notification</h2>
                <p>Dear {$recipientName},</p>
                <p>Your account request has been <strong>approved</strong>.</p>
                <p>Your login credentials:</p>
                <ul>
                    <li><strong>Email:</strong> {$recipientEmail}</li>
                    <li><strong>Password:</strong> {$password}</li>
                </ul>
                <p>Please change your password after your first login.</p>
                <p>If you have any questions, please contact support@yourdomain.com.</p>
                <p>Best regards,<br>Account Approval Team</p>
            ";
            $mail->AltBody = "Dear {$recipientName},\n\nYour account request has been approved.\n\nYour login credentials:\nEmail: {$recipientEmail}\nPassword: {$password}\n\nPlease change your password after your first login.\n\nIf you have any questions, please contact support@yourdomain.com.\n\nBest regards,\nAccount Approval Team";
        } else {
            $mail->Body = "
                <h2>Account Approval Notification</h2>
                <p>Dear {$recipientName},</p>
                <p>We regret to inform you that your account request has been <strong>rejected</strong>.</p>
                <p>For more information, please contact support@yourdomain.com.</p>
                <p>Best regards,<br>Account Approval Team</p>
            ";
            $mail->AltBody = "Dear {$recipientName},\n\nWe regret to inform you that your account request has been rejected.\n\nFor more information, please contact support@yourdomain.com.\n\nBest regards,\nAccount Approval Team";
        }

        $mail->send();
        error_log("Email sent successfully to {$recipientEmail}");
        return true;
    } catch (Exception $e) {
        error_log("Failed to send email to {$recipientEmail}: {$mail->ErrorInfo}");
        return false;
    }
}
?>