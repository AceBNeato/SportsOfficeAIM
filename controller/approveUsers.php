<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../view/phpmailer/src/Exception.php';
require '../view/phpmailer/src/PHPMailer.php';
require '../view/phpmailer/src/SMTP.php';

// Function to send signup confirmation email
function sendSignupConfirmationEmail($recipientEmail, $fullName) {
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address for signup confirmation: $recipientEmail");
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
        $mail->Subject = 'Signup Confirmation - USeP OSAS-Sports Unit';
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
                        <h1>Signup Confirmation</h1>
                    </div>
                    <div class='content'>
                        <p>Dear $fullName,</p>
                        <p>Thank you for signing up with the USeP OSAS-Sports Unit! Your account request has been received and is currently pending approval by our administrators.</p>
                        <p>You will receive another email once your account has been approved or if further action is required.</p>
                        <p style='text-align: center;'>
                            <a href='http://localhost/SportsOfficeAIM/view/loginView.php' class='button'>Visit Login Page</a>
                        </p>
                    </div>
                    <div class='footer'>
                        <p>Best regards,<br>SportOfficeDB Team</p>
                        <p><a href='mailto:tagummabinisportoffice@gmail.com' style='color: #3182ce; text-decoration: none;'>Contact Us</a></p>
                    </div>
                </div>
            </body>
            </html>
        ";

        $mail->send();
        error_log("Signup confirmation email sent to: $recipientEmail");
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error in sendSignupConfirmationEmail: {$mail->ErrorInfo}");
        return false;
    }
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    header("Location: ../view/signupView.php?status=error&message=" . urlencode("Database connection failed"));
    exit();
}

// Validate form data
$requiredFields = ['student_id', 'full_name', 'email', 'status', 'document'];
foreach ($requiredFields as $field) {
    if (empty($_POST[$field]) && empty($_FILES[$field])) {
        header("Location: ../view/signupView.php?status=error&message=" . urlencode("Missing required field: $field"));
        exit();
    }
}

$student_id = trim($_POST['student_id']);
$full_name = trim($_POST['full_name']);
$email = trim($_POST['email']);
$status = trim($_POST['status']);
$sport = isset($_POST['sport']) ? trim($_POST['sport']) : null;
$campus = isset($_POST['campus']) ? trim($_POST['campus']) : null;
$page = isset($_POST['page']) ? trim($_POST['page']) : 'signup';

// Check for existing student ID or email
$checkStmt = $conn->prepare("SELECT student_id, email, approval_status FROM account_approvals WHERE student_id = ? OR email = ?");
if (!$checkStmt) {
    header("Location: ../view/signupView.php?status=error&message=" . urlencode("Prepare failed: " . $conn->error));
    exit();
}
$checkStmt->bind_param("ss", $student_id, $email);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    $checkStmt->bind_result($db_student_id, $db_email, $approval_status);
    $checkStmt->fetch();

    // Handle based on approval_status
    if ($approval_status === 'approved') {
        $message = ($db_student_id === $student_id) ?
            "This Student ID is already registered and approved." :
            "This email is already registered and approved.";
        $checkStmt->close();
        $conn->close();
        header("Location: ../view/signupView.php?status=error&message=" . urlencode($message));
        exit();
    } elseif ($approval_status === 'pending' || $approval_status === 'rejected') {
        // Update existing record for resubmission
        $checkStmt->close();

        // Validate file
        if (!isset($_FILES['document']) || $_FILES['document']['error'] == UPLOAD_ERR_NO_FILE) {
            header("Location: ../view/signupView.php?status=error&message=" . urlencode("No file uploaded"));
            exit();
        }

        $file = $_FILES['document'];
        $validTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        if (!in_array($file['type'], $validTypes)) {
            header("Location: ../view/signupView.php?status=error&message=" . urlencode("Invalid file type. Use PDF, JPG, or PNG"));
            exit();
        }
        if ($file['size'] > $maxSize) {
            header("Location: ../view/signupView.php?status=error&message=" . urlencode("File size exceeds 5MB limit"));
            exit();
        }
        if ($file['error'] != UPLOAD_ERR_OK) {
            header("Location: ../view/signupView.php?status=error&message=" . urlencode("File upload error"));
            exit();
        }

        // Read file data
        $file_data = file_get_contents($file['tmp_name']);
        $file_name = $conn->real_escape_string($file['name']);
        $file_type = $file['type'];
        $file_size = $file['size'];

        // Update record
        $updateStmt = $conn->prepare("UPDATE account_approvals SET full_name = ?, email = ?, status = ?, sport = ?, campus = ?, file_name = ?, file_data = ?, file_type = ?, file_size = ?, approval_status = 'pending' WHERE student_id = ? OR email = ?");
        if (!$updateStmt) {
            header("Location: ../view/signupView.php?status=error&message=" . urlencode("Prepare failed: " . $conn->error));
            exit();
        }
        $updateStmt->bind_param("ssssssssiss", $full_name, $email, $status, $sport, $campus, $file_name, $file_data, $file_type, $file_size, $student_id, $email);

        if ($updateStmt->execute()) {
            // Send confirmation email for resubmission
            sendSignupConfirmationEmail($email, $full_name);
            $message = "Your signup request has been resubmitted. Awaiting admin approval.";
            $status = "success";
        } else {
            $message = "Error resubmitting request: " . $conn->error;
            $status = "error";
        }
        $updateStmt->close();
        $conn->close();
        header("Location: ../view/signupView.php?status=" . $status . "&message=" . urlencode($message));
        exit();
    }
}
$checkStmt->close();

// Proceed with new submission (no existing record)
if (!isset($_FILES['document']) || $_FILES['document']['error'] == UPLOAD_ERR_NO_FILE) {
    header("Location: ../view/signupView.php?status=error&message=" . urlencode("No file uploaded"));
    exit();
}

$file = $_FILES['document'];
$validTypes = ['application/pdf', 'image/jpeg', 'image/png'];
$maxSize = 5 * 1024 * 1024; // 5MB
if (!in_array($file['type'], $validTypes)) {
    header("Location: ../view/signupView.php?status=error&message=" . urlencode("Invalid file type. Use PDF, JPG, or PNG"));
    exit();
}
if ($file['size'] > $maxSize) {
    header("Location: ../view/signupView.php?status=error&message=" . urlencode("File size exceeds 5MB limit"));
    exit();
}
if ($file['error'] != UPLOAD_ERR_OK) {
    header("Location: ../view/signupView.php?status=error&message=" . urlencode("File upload error"));
    exit();
}

// Read file data
$file_data = file_get_contents($file['tmp_name']);
$file_name = $conn->real_escape_string($file['name']);
$file_type = $file['type'];
$file_size = $file['size'];

// Insert into account_approvals
$stmt = $conn->prepare("INSERT INTO account_approvals (student_id, full_name, email, status, sport, campus, file_name, file_data, file_type, file_size, approval_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
if (!$stmt) {
    header("Location: ../view/signupView.php?status=error&message=" . urlencode("Prepare failed: " . $conn->error));
    exit();
}
$stmt->bind_param("sssssssssi", $student_id, $full_name, $email, $status, $sport, $campus, $file_name, $file_data, $file_type, $file_size);

if ($stmt->execute()) {
    // Send confirmation email for new submission
    sendSignupConfirmationEmail($email, $full_name);
    $message = "Signup request submitted. A confirmation email has been sent to your email address.";
    $status = "success";
} else {
    $message = "Error submitting request: " . $conn->error;
    $status = "error";
}

$stmt->close();
$conn->close();

header("Location: ../view/signupView.php?status=" . $status . "&message=" . urlencode($message));
exit();
?>