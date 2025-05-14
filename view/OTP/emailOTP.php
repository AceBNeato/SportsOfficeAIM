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
    function sendOTPEmail($email, $otp) {
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->SMTPDebug = 0;                      // Enable verbose debug output (set to 2 for debugging)
            $mail->isSMTP();                           // Send using SMTP
            $mail->Host       = 'smtp.gmail.com';      // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                  // Enable SMTP authentication
            $mail->Username   = 'tagummabinisportoffice@gmail.com'; // SMTP username
            $mail->Password   = 'wecx ezju zcin ymmn'; // SMTP password (app password)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  // Enable implicit TLS encryption
            $mail->Port       = 465;                   // TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            // Optional: Add connection timeout to prevent hanging
            $mail->Timeout = 60; // seconds

            // Recipients
            $mail->setFrom('tagummabinisportoffice@gmail.com', 'USeP Sports Office');
            $mail->addAddress($email);
            $mail->addReplyTo('tagummabinisportoffice@gmail.com', 'USeP Sports Office');

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP - USeP Sports Office';
            $mail->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                        .header { background-color: #800000; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
                        .content { padding: 20px; }
                        .otp-code { font-size: 24px; font-weight: bold; text-align: center; margin: 20px 0; padding: 10px; background-color: #f5f5f5; border-radius: 5px; letter-spacing: 5px; }
                        .footer { margin-top: 20px; font-size: 12px; color: #777; text-align: center; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>USeP OSAS-Sports Unit</h2>
                        </div>
                        <div class='content'>
                            <h3>Password Reset Request</h3>
                            <p>You have requested to reset your password. Please use the following OTP code to verify your identity:</p>
                            <div class='otp-code'>$otp</div>
                            <p>This code will expire in 15 minutes. If you did not request this password reset, please ignore this email.</p>
                        </div>
                        <div class='footer'>
                            <p>© " . date('Y') . " USeP OSAS-Sports Unit. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            $mail->AltBody = "Your password reset OTP is: $otp. This code will expire in 15 minutes.";

            // Send the email
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

                // Check if email exists in the database (users table)
                $userStmt = $conn->prepare("SELECT id, email FROM users WHERE email = ?");
                $userStmt->bind_param("s", $email);
                $userStmt->execute();
                $userResult = $userStmt->get_result();

                // Check if email exists in admins table if not found in users
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

                // Generate OTP and store in session
                $otp = generateOTP();
                $_SESSION['reset_otp'] = $otp;
                $_SESSION['reset_otp_email'] = $email;
                $_SESSION['reset_otp_time'] = time();

                // FOR TESTING: Bypass email sending and just return OTP directly
                // This is useful for local testing or if email sending is not working
                if (strpos($email, 'test') !== false) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'OTP sent successfully (TEST MODE)',
                        'otp' => $otp // REMOVE THIS IN PRODUCTION
                    ]);
                    exit;
                }

                // Send OTP email
                $emailSent = sendOTPEmail($email, $otp);

                if ($emailSent) {
                    echo json_encode(['status' => 'success', 'message' => 'OTP sent successfully']);
                } else {
                    // Log the error for debugging
                    error_log("Failed to send OTP email to $email");

                    // For development/testing, you might want to see the OTP even if email fails
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Failed to send OTP email. Please try again or contact support.',
                        'debug_otp' => $otp // REMOVE THIS IN PRODUCTION
                    ]);
                }
                break;

            case 'verify_otp':
                $entered_otp = trim($_POST['otp']);

                // Check if OTP session exists and is not expired (15 minutes)
                if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['reset_otp_time']) ||
                    (time() - $_SESSION['reset_otp_time'] > 900)) {
                    echo json_encode(['status' => 'error', 'message' => 'OTP expired or invalid session']);
                    exit;
                }

                // Verify OTP
                if ($entered_otp == $_SESSION['reset_otp']) {
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
    ?>