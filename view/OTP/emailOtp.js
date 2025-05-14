document.addEventListener('DOMContentLoaded', function() {
    const sendOtpBtn = document.getElementById('send-otp-btn');
    const verifyBtn = document.getElementById('verify-btn');
    const emailInput = document.getElementById('email');
    const otpInput = document.getElementById('otp_inp');
    const otpSection = document.querySelector('.otpverify');
    const resetPasswordModal = document.getElementById('resetPasswordModal');
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');

    // Send OTP button click handler
    sendOtpBtn.addEventListener('click', async function() {
        const email = emailInput.value.trim();

        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Email',
                text: 'Please enter a valid email address.',
                confirmButtonColor: '#800000'
            });
            return;
        }

        try {
            const response = await fetch('emailOtp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=send_otp&email=${encodeURIComponent(email)}`
            });

            const result = await response.json();

            if (result.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'OTP Sent',
                    text: result.message,
                    confirmButtonColor: '#800000'
                });
                otpSection.style.display = 'block';
                sendOtpBtn.style.display = 'none';
                emailInput.disabled = true;
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message,
                    confirmButtonColor: '#800000'
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to send OTP. Please try again.',
                confirmButtonColor: '#800000'
            });
        }
    });

    // Verify OTP button click handler
    verifyBtn.addEventListener('click', async function() {
        const otp = otpInput.value.trim();

        if (!otp || otp.length !== 6 || !/^\d{6}$/.test(otp)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid OTP',
                text: 'Please enter a valid 6-digit OTP.',
                confirmButtonColor: '#800000'
            });
            return;
        }

        try {
            // Fix: Use the same path as for sending OTP
            const response = await fetch('emailOtp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=verify_otp&otp=${encodeURIComponent(otp)}`
            });

            const result = await response.json();

            if (result.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'OTP Verified',
                    text: result.message,
                    confirmButtonColor: '#800000'
                }).then(() => {
                    // Show reset password modal
                    resetPasswordModal.style.display = 'block';
                    forgotPasswordForm.style.display = 'none';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message,
                    confirmButtonColor: '#800000'
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to verify OTP. Please try again.',
                confirmButtonColor: '#800000'
            });
        }
    });

    // Close reset password modal
    window.closeResetModal = function() {
        resetPasswordModal.style.display = 'none';
        // Reset form and show OTP section again
        forgotPasswordForm.style.display = 'block';
        otpSection.style.display = 'none';
        sendOtpBtn.style.display = 'block';
        emailInput.disabled = false;
        emailInput.value = '';
        otpInput.value = '';
    };

    // Handle reset password form submission
    document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const newPassword = this.querySelector('input[name="new_password"]').value;
        const confirmPassword = this.querySelector('input[name="confirm_password"]').value;

        if (newPassword.length < 8) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Password',
                text: 'Password must be at least 8 characters long.',
                confirmButtonColor: '#800000'
            });
            return;
        }

        if (newPassword !== confirmPassword) {
            Swal.fire({
                icon: 'error',
                title: 'Password Mismatch',
                text: 'Passwords do not match.',
                confirmButtonColor: '#800000'
            });
            return;
        }

        try {
            const formData = new FormData(this);
            const response = await fetch('resetPassword.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Password reset successfully! Redirecting to login...',
                    confirmButtonColor: '#800000'
                }).then(() => {
                    window.location.href = 'loginView.php?success=password_reset';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: result.message || 'Failed to reset password.',
                    confirmButtonColor: '#800000'
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to reset password. Please try again.',
                confirmButtonColor: '#800000'
            });
        }
    });
});

// Example for Send OTP button
document.getElementById('send-otp-btn').addEventListener('click', async function() {
    this.classList.add('loading');
    try {
        // Simulate async operation (replace with your actual API call)
        await new Promise(resolve => setTimeout(resolve, 2000));
        // Your OTP sending logic here
    } finally {
        this.classList.remove('loading');
    }
});

// Example for Verify OTP button
document.getElementById('verify-btn').addEventListener('click', async function() {
    this.classList.add('loading');
    try {
        // Simulate async operation (replace with your actual API call)
        await new Promise(resolve => setTimeout(resolve, 2000));
        // Your OTP verification logic here
    } finally {
        this.classList.remove('loading');
    }
});