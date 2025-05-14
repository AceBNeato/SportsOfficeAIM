<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - USeP Sports Office</title>
    <link rel="stylesheet" href="forgotpass.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="top-bar">
    <div class="top-bar-content">
        <div class="logo-container">
            <img src="../image/Logo.png" alt="USeP Logo" class="logo">
            <div class="title-container">
                <h1>USeP OSAS-Sports Unit</h1>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="center-panel">
        <div class="login-box">
            <h1>Forgot Password</h1>
            <form id="forgotPasswordForm">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" placeholder="Email Address" required>
                </div>
                <div class="otpverify" style="display: none;">
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="text" id="otp_inp" placeholder="Enter OTP" maxlength="6" required>
                    </div>
                    <button type="button" id="verify-btn">Verify OTP</button>
                </div>
                <button type="button" id="send-otp-btn">Send OTP</button>
            </form>
            <div class="signup-link">
                <p>Back to login? <a href="loginView.php">Log In</a></p>
            </div>
        </div>
    </div>
</div>

<div id="resetPasswordModal" class="modal">
    <div class="modal-content">
        <span class="close-button" onclick="closeResetModal()">&times;</span>
        <h2>Reset Your Password</h2>
        <form id="resetPasswordForm">
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="new_password" id="new_password" placeholder="New Password" required>
            </div>
            <div class="password-strength" id="password-strength"></div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            </div>
            <button type="submit" class="btn">Reset Password</button>
        </form>
    </div>
</div>

<script src="emailOtp.js"></script>
</body>
</html>