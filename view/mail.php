
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

// Function to send approval email
function sendApprovalEmail($recipientEmail, $fullName, $studentId) {
    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aumicaroz00066@usep.edu.ph'; // Your Gmail address
        $mail->Password = 'phaijpfzdlesvmjy'; // Your Gmail App Password
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        // Recipients
        $mail->setFrom('aumicaroz00066@usep.edu.ph', 'SportOfficeDB Admin');
        $mail->addAddress('aumicaroz00066@usep.edu.ph');
        $mail->addReplyTo('aumicaroz00066@usep.edu.ph', 'SportOfficeDB Admin');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Account Approval Notification';
        $mail->Body = "
            <h3>Account Approved</h3>
            <p>Dear $fullName,</p>
            <p>Your account request has been approved. You can now log in using the following credentials:</p>
            <ul>
                <li><strong>Student ID:</strong> $studentId</li>
                <li><strong>Email:</strong> $recipientEmail</li>
                <li><strong>Password:</strong> $studentId (Your default password is your student ID. Please change it after logging in for security purposes.)</li>
            </ul>
            <p>Best regards,<br>SportOfficeDB Team</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Function to send rejection email
function sendRejectionEmail($recipientEmail, $fullName) {
    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aumicaroz00066@usep.edu.ph';
        $mail->Password = 'phaijpfzdlesvmjy';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        // Recipients
        $mail->setFrom('aumicaroz00066@usep.edu.ph', 'SportOfficeDB Admin');
        $mail->addAddress('aumicaroz00066@usep.edu.ph');
        $mail->addReplyTo('aumicaroz00066@usep.edu.ph', 'SportOfficeDB Admin');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Account Request Rejected';
        $mail->Body = "
            <h3>Account Request Rejected</h3>
            <p>Dear $fullName,</p>
            <p>Your account request has been rejected. If you have any questions, please contact the administrator.</p>
            <p>Best regards,<br>SportOfficeDB Team</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Handle direct POST requests (for testing or manual email sending)
if (isset($_POST["send"])) {
    $recipientEmail = $_POST["email"];
    $fullName = $_POST["name"];
    $subject = $_POST["subject"];
    $message = $_POST["message"];

    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aumicaroz00066@usep.edu.ph';
        $mail->Password = 'phaijpfzdlesvmjy';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        // Recipients
        $mail->setFrom($recipientEmail, $fullName);
        $mail->addAddress($recipientEmail);
        $mail->addReplyTo($recipientEmail, $fullName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
        echo "<script>
            alert('Message was sent successfully!');
            document.location.href = 'index.php';
        </script>";
    } catch (Exception $e) {
        echo "<script>
            alert('Failed to send message. Error: {$mail->ErrorInfo}');
            document.location.href = '../view/adminView.php?page=Account%20Approvals';
        </script>";
    }
}
?>