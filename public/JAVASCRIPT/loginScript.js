// Toggle password visibility with better accessibility
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.querySelector('.toggle-password');

    if (!passwordInput || !toggleIcon) {
        console.error('Password input or toggle icon not found');
        return;
    }

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.replace('bx-show', 'bx-hide');
        toggleIcon.setAttribute('aria-label', 'Hide password');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.replace('bx-hide', 'bx-show');
        toggleIcon.setAttribute('aria-label', 'Show password');
    }

    // Focus back on password field for better UX
    passwordInput.focus();
}

// Enhanced form validation with better error messages
function validateForm(event) {
    event.preventDefault();

    const form = event.target;
    const emailInput = form.querySelector('input[name="email"]');
    const passwordInput = form.querySelector('input[name="password"]');
    const errorContainer = document.getElementById('error-messages');

    // Clear previous errors
    if (errorContainer) {
        errorContainer.innerHTML = '';
        errorContainer.hidden = true;
    }

    // Validate inputs exist
    if (!emailInput || !passwordInput) {
        console.error('Form inputs not found!');
        showError('Form submission error. Please try again.');
        return false;
    }

    const errors = [];

    // Email validation
    if (!emailInput.value.trim()) {
        errors.push('Please enter your email');
        emailInput.classList.add('error');
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
        errors.push('Please enter a valid email address');
        emailInput.classList.add('error');
    } else {
        emailInput.classList.remove('error');
    }

    // Password validation
    if (!passwordInput.value.trim()) {
        errors.push('Please enter your password');
        passwordInput.classList.add('error');
    } else {
        passwordInput.classList.remove('error');
    }

    // Display errors
    if (errors.length > 0) {
        if (errorContainer) {
            errorContainer.hidden = false;
            errors.forEach(error => {
                const errorElement = document.createElement('p');
                errorElement.textContent = error;
                errorElement.className = 'error-message';
                errorContainer.appendChild(errorElement);
            });
            // Focus on first error field
            if (emailInput.classList.contains('error')) {
                emailInput.focus();
            } else if (passwordInput.classList.contains('error')) {
                passwordInput.focus();
            }
        } else {
            // Fallback to alert if no error container
            alert(errors.join('\n'));
        }
        return false;
    }

    // If valid, submit form
    form.submit();
    return true;
}

// Helper function to show errors
function showError(message) {
    const errorContainer = document.getElementById('error-messages') || document.body;
    const errorElement = document.createElement('div');
    errorElement.textContent = message;
    errorElement.className = 'error-message';
    errorContainer.appendChild(errorElement);
}

// Add event listeners properly
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const toggleButton = document.querySelector('.toggle-password');

    if (form) {
        form.addEventListener('submit', validateForm);
    }

    if (toggleButton) {
        toggleButton.addEventListener('click', togglePasswordVisibility);
        // Initialize aria-label
        toggleButton.setAttribute('aria-label', 'Show password');
        toggleButton.setAttribute('role', 'button');
    }
});