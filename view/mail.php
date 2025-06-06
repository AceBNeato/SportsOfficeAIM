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
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <style>
                    body {
                        margin: 0;
                        padding: 0;
                        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        background-color: #f5f7fa;
                        color: #1a202c;
                    }
                    .container {
                        max-width: 600px;
                        margin: 40px auto;
                        background: linear-gradient(145deg, #ffffff, #f7fafc);
                        border-radius: 16px;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                        overflow: hidden;
                    }
                    .header {
                        background: #2d3748;
                        padding: 24px;
                        text-align: center;
                        color: #ffffff;
                    }
                    .header h1 {
                        margin: 0;
                        font-size: 24px;
                        font-weight: 600;
                    }
                    .content {
                        padding: 32px;
                    }
                    .content p {
                        font-size: 16px;
                        line-height: 1.6;
                        margin: 0 0 16px;
                    }
                    .credentials {
                        background: #edf2f7;
                        padding: 20px;
                        border-radius: 12px;
                        margin: 20px 0;
                    }
                    .credentials li {
                        font-size: 15px;
                        line-height: 1.8;
                        margin-bottom: 8px;
                    }
                    .button {
                        display: inline-block;
                        padding: 12px 24px;
                        background: #3182ce;
                        color: #ffffff;
                        text-decoration: none;
                        border-radius: 8px;
                        font-size: 16px;
                        font-weight: 500;
                        transition: background 0.3s ease;
                    }
                    .button:hover {
                        background: #2b6cb0;
                    }
                    .footer {
                        background: #e2e8f0;
                        padding: 20px;
                        text-align: center;
                        font-size: 14px;
                        color: #4a5568;
                    }
                    @media (max-width: 600px) {
                        .container {
                            margin: 20px;
                            border-radius: 12px;
                        }
                        .content {
                            padding: 24px;
                        }
                        .header h1 {
                            font-size: 20px;
                        }
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Account Approved</h1>
                    </div>
                    <div class='content'>
                        <p>Dear $fullName,</p>
                        <p>Congratulations! Your account request has been approved. You can now log in using the credentials below:</p>
                        <div class='credentials'>
                            <ul>
                                <li><strong>Student ID:</strong> $studentId</li>
                                <li><strong>Email:</strong> $recipientEmail</li>
                                <li><strong>Password:</strong> $studentId <em>(Please change your password after logging in for security purposes.)</em></li>
                            </ul>
                        </div>
                        <p style='text-align: center;'>
                            <a href='http://localhost/SportsOfficeAIM/view/loginView.php' class='button'>Log In Now</a>
                        </p>
                    </div>
                    <div class='footer'>    
                        <p>Best regards,<br>SportOfficeDB Team</p>
                    </div>
                </div>
            </body>
            </html>
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
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <style>
                    body {
                        margin: 0;
                        padding: 0;
                        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        background-color: #f5f7fa;
                        color: #1a202c;
                    }
                    .container {
                        max-width: 600px;
                        margin: 40px auto;
                        background: linear-gradient(145deg, #ffffff, #f7fafc);
                        border-radius: 16px;
                        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                        overflow: hidden;
                    }
                    .header {
                        background: #c53030;
                        padding: 24px;
                        text-align: center;
                        color: #ffffff;
                    }
                    .header h1 {
                        margin: 0;
                        font-size: 24px;
                        font-weight: 600;
                    }
                    .content {
                        padding: 32px;
                    }
                    .content p {
                        font-size: 16px;
                        line-height: 1.6;
                        margin: 0 0 16px;
                    }
                    .button {
                        display: inline-block;
                        padding: 12px 24px;
                        background: #3182ce;
                        color: #ffffff;
                        text-decoration: none;
                        border-radius: 8px;
                        font-size: 16px;
                        font-weight: 500;
                        transition: background 0.3s ease;
                    }
                    .button:hover {
                        background: #2b6cb0;
                    }
                    .footer {
                        background: #e2e8f0;
                        padding: 20px;
                        text-align: center;
                        font-size: 14px;
                        color: #4a5568;
                    }
                    @media (max-width: 600px) {
                        .container {
                            margin: 20px;
                            border-radius: 12px;
                        }
                        .content {
                            padding: 24px;
                        }
                        .header h1 {
                            font-size: 20px;
                        }
                    }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>Account Request Rejected</h1>
                    </div>
                    <div class='content'>
                        <p>Dear $fullName,</p>
                        <p>We regret to inform you that your account request has been rejected, possibly due to incomplete or invalid documentation.</p>
                        <p>Please contact our administrator for further details or to resubmit your request:</p>
                        <p style='text-align: center;'>
                            <a href='mailto:tagummabinisportoffice@gmail.com' class='button'>Contact Administrator</a>
                        </p>
                    </div>
                    <div class='footer'>
                        <p>Best regards,<br>SportOfficeDB Team</p>
                    </div>
                </div>
            </body>
            </html>
        ";

        $mail->send();
        error_log("Rejection email sent to: $recipientEmail");
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error in sendRejectionEmail: {$mail->ErrorInfo}");
        return false;
    }
}


// Function to send notification email
function sendNotificationEmail($recipientEmail, $fullName, $notifications) {
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address for notifications: $recipientEmail");
        return false;
    }

    $unread_count = 0;
    $notification_list = '';
    foreach ($notifications as $notification) {
        if (!$notification['is_read']) {
            $unread_count++;
            $formatted_date = date('M d, Y h:i A', strtotime($notification['timestamp']));
            $notification_list .= "<li style='font-size: 14px; line-height: 1.6; margin-bottom: 10px;'><strong>" . htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8') . "</strong> at $formatted_date</li>";
        }
    }

    if ($unread_count === 0) {
        error_log("No unread notifications to email for: $recipientEmail");
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
        $mail->addAddress($recipientEmail, $fullName);
        $mail->addReplyTo('tagummabinisportoffice@gmail.com', 'SportOfficeDB Admin');

        $mail->isHTML(true);
        $mail->Subject = 'New Notifications from Sports Office';
        $mail->Body = "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>New Notifications</title>
            </head>
            <body style='margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4; color: #333333;'>
                <table role='presentation' width='100%' cellspacing='0' cellpadding='0' style='max-width: 600px; margin: 20px auto;'>
                    <tr>
                        <td style='background-color: #ffffff; border-radius: 8px; overflow: hidden;'>
                            <!-- Header -->
                            <table role='presentation' width='100%' cellspacing='0' cellpadding='0'>
                                <tr>
                                    <td style='background-color: #1a3c6d; padding: 20px; text-align: center;'>
                                        <h1 style='margin: 0; font-size: 24px; font-weight: bold; color: #ffffff;'>New Notifications</h1>
                                    </td>
                                </tr>
                            </table>
                            <!-- Content -->
                            <table role='presentation' width='100%' cellspacing='0' cellpadding='0'>
                                <tr>
                                    <td style='padding: 30px;'>
                                        <p style='font-size: 16px; line-height: 1.5; margin: 0 0 15px;'>Dear $fullName,</p>
                                        <p style='font-size: 16px; line-height: 1.5; margin: 0 0 15px;'>You have $unread_count new notification(s) from the Sports Office:</p>
                                        <table role='presentation' width='100%' cellspacing='0' cellpadding='0' style='background-color: #f8fafc; padding: 20px; border-radius: 6px; margin: 20px 0;'>
                                            <tr>
                                                <td>
                                                    <ul style='margin: 0; padding-left: 20px;'>
                                                        $notification_list
                                                    </ul>
                                                </td>
                                            </tr>
                                        </table>
                                        <p style='text-align: center; margin: 20px 0;'>
                                            <a href='http://192.168.199.137/SportsOfficeAIM/view/loginView.php' style='display: inline-block; padding: 12px 24px; background-color: #2563eb; color: #ffffff; text-decoration: none; border-radius: 6px; font-size: 16px; font-weight: bold;' role='button' aria-label='View Notifications'>View Notifications</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <!-- Footer -->
                            <table role='presentation' width='100%' cellspacing='0' cellpadding='0'>
                                <tr>
                                    <td style='background-color: #e5e7eb; padding: 15px; text-align: center; font-size: 12px; color: #4b5563;'>
                                        <p style='margin: 0;'>Best regards,<br>SportOfficeDB Team</p>
                                        <p style='margin: 10px 0 0;'><a href='mailto:tagummabinisportoffice@gmail.com' style='color: #2563eb; text-decoration: none;'>Contact Us</a></p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
        ";

        $mail->AltBody = strip_tags(str_replace('</li>', "\n", $notification_list));

        $mail->send();
        error_log("Notification email sent to: $recipientEmail with $unread_count unread notifications");
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error in sendNotificationEmail: {$mail->ErrorInfo}");
        return false;
    }
}

