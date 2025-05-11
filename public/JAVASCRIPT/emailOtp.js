// Handle Send OTP
const sendOtpBtn = document.getElementById('send-otp-btn');
sendOtpBtn.addEventListener('click', function () {
    const email = document.getElementById('email').value;
    if (!email) {
        alert('Please enter your email address!');
        return;
    }

    fetch('../controller/emailOTP.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=send_otp&email=' + encodeURIComponent(email)
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('OTP sent to your email!');
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
                alert('OTP Verified! You can now reset your password.');
                document.getElementById('resetPasswordModal').style.display = 'block';
            } else {
                alert('Invalid OTP. Please try again.');
            }
        });
});

// Close Reset Modal
function closeResetModal() {
    document.getElementById('resetPasswordModal').style.display = 'none';
}