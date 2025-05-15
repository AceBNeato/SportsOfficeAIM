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
        this.classList.add('loading'); // Add loading state

        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            this.classList.remove('loading'); // Remove loading state
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
        } finally {
            this.classList.remove('loading'); // Remove loading state
        }
    });

    // Verify OTP button click handler
    verifyBtn.addEventListener('click', async function() {
        const otp = otpInput.value.trim();
        this.classList.add('loading'); // Add loading state

        if (!otp || otp.length !== 6 || !/^\d{6}$/.test(otp)) {
            this.classList.remove('loading'); // Remove loading state
            Swal.fire({
                icon: 'error',
                title: 'Invalid OTP',
                text: 'Please enter a valid 6-digit OTP.',
                confirmButtonColor: '#800000'
            });
            return;
        }

        try {
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
        } finally {
            this.classList.remove('loading'); // Remove loading state
        }
    });

    // Close reset password modal
    window.closeResetModal = function() {
        resetPasswordModal.style.display = 'none';
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
                    window.location.href = '../loginView.php?success=password_reset';
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

    // Display any error messages from querystring
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


