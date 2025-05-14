<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - USeP OSAS-Sports Unit</title>
    <link rel="icon" href="../public/image/Usep.png" sizes="any">
    <link href="https://cdn.jsdelivr.net/npm/boxicons/css/boxicons.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="emailOtp.js" defer></script>
    <link rel="stylesheet" href="forgotpass.css">


</head>
<body>

<div class="container">
    <nav class="top-bar">
        <div class="top-bar-content">
            <div class="logo-container">
                <img src="../public/image/SportOffice.png" alt="Sports Office Logo" class="logo">
                <img src="../public/image/Usep.png" alt="USeP Logo" class="logo">
            </div>
            <div class="title-container">
                <h1>USeP OSAS-Sports Unit</h1>
            </div>
        </div>
    </nav>

    <div class="center-panel">
        <div class="login-box">
            <h1>Forgot Password</h1>
            <form id="forgotPasswordForm" onsubmit="return false;">
                <div class="input-group">
                    <i class="bx bx-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="Enter your Email" required autocomplete="email">
                </div>
                <div class="otpverify" style="display:none;">
                    <div class="input-group">
                        <i class="bx bx-lock-alt"></i>
                        <input type="text" id="otp_inp" name="otp" placeholder="Enter 6-digit OTP" maxlength="6" pattern="\d{6}" required>
                    </div>
                    <button type="button" id="verify-btn" class="btn">Verify OTP</button>
                </div>
                <button type="button" id="send-otp-btn" class="btn">Send OTP</button>
            </form>

            <p class="signup-link">
                Back to login? <a href="../view/loginView.php">Log In</a>
            </p>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close-button" onclick="closeResetModal()">&times;</span>
        <h2>Reset Your Password</h2>
        <form method="POST" action=" resetPassword.php" id="resetPasswordForm">
            <div class="input-group">
                <i class="bx bx-lock"></i>
                <input type="password" name="new_password" placeholder="Enter New Password" required minlength="8">
            </div>
            <div class="input-group">
                <i class="bx bx-lock-alt"></i>
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="8">
            </div>
            <button type="submit" class="btn">Reset Password</button>
        </form>
    </div>
</div>

<script>
    // Display any error messages from querystring
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const error = urlParams.get('error');
        const success = urlParams.get('success');

        if (error === 'unauthorized') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Unauthorized access. Please complete the verification process.',
                confirmButtonColor: '#800000'
            });
        } else if (error === 'reset_failed') {
            Swal.fire({
                icon: 'error',
                title: 'Password Reset Failed',
                text: 'There was an error resetting your password. Please try again.',
                confirmButtonColor: '#800000'
            });
        }

        if (success === 'email_sent') {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Email sent successfully. Please check your inbox.',
                confirmButtonColor: '#800000'
            });
        }
    });
</script>

</body>
</html>