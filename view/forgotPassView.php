
<!-- File: /view/forgotPasswordView.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - USeP OSAS-Sports Unit</title>
    <link rel="stylesheet" href="../public/CSS/forgotpass.css">
    <link rel="icon" href="../public/image/Usep.png" sizes="any">
    <link href="https://cdn.jsdelivr.net/npm/boxicons/css/boxicons.min.css" rel="stylesheet" />
    <script src="https://smtpjs.com/v3/smtp.js"></script>
    <script src="../public/JAVASCRIPT/emailOtp.js" defer></script>
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
                <input type="email" id="email" name="email" placeholder="Enter your Email" required autocomplete="email">
                <div class="otpverify" style="display:none;">
                    <input type="text" id="otp_inp" name="otp" placeholder="Enter OTP" required>
                    <button type="button" id="verify-btn" class="btn">Verify OTP</button>
                </div>
                <button type="button" id="send-otp-btn">Send OTP</button>
            </form>

            <p class="signup-link">
                Back to login? <a href="loginView.php">Log In</a>
            </p>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h2>Reset Your Password</h2>
        <form method="POST" action="../controller/resetPassword.php" id="resetPasswordForm">
            <input type="password" name="new_password" placeholder="Enter New Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            <button type="submit" class="btn">Reset Password</button>
        </form>
        <button class="close-btn" onclick="closeResetModal()">Close</button>
    </div>
</div>

<script>
    // Handle Send OTP
    const sendOtpBtn = document.getElementById('send-otp-btn');
    sendOtpBtn.addEventListener('click', function () {
        const email = document.getElementById('email').value;

        fetch('../controller/emailOTP.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=send_otp&email=' + encodeURIComponent(email)
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('OTP sent successfully!');
                    document.querySelector('.otpverify').style.display = 'flex';
                    sendOtpBtn.disabled = true;
                } else {
                    alert(data.message);
                }
            });
    });

    // Handle Verify OTP
    const verifyBtn = document.getElementById('verify-btn');
    verifyBtn.addEventListener('click', function () {
        const otp = document.getElementById('otp_inp').value;

        fetch('../controller/emailOTP.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=verify_otp&otp=' + encodeURIComponent(otp)
        })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('OTP Verified! Please reset your password.');
                    document.getElementById('resetPasswordModal').style.display = 'block';
                } else {
                    alert('Invalid OTP. Try again.');
                }
            });
    });

    // Close Reset Password Modal
    function closeResetModal() {
        document.getElementById('resetPasswordModal').style.display = 'none';
    }
</script>

</body>
</html>