function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.querySelector('.toggle-password');

    if (passwordInput && toggleIcon) {
        const isHidden = passwordInput.type === 'password';
        passwordInput.type = isHidden ? 'text' : 'password';
        toggleIcon.classList.replace(isHidden ? 'bx-show' : 'bx-hide', isHidden ? 'bx-hide' : 'bx-show');
        toggleIcon.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        passwordInput.focus();
    }
}

function validateForm(event) {
    event.preventDefault();

    const form = event.target;
    const emailInput = form.querySelector('input[name="email"]');
    const passwordInput = form.querySelector('input[name="password"]');
    const errorContainer = document.getElementById('error-messages');

    if (errorContainer) {
        errorContainer.innerHTML = '';
        errorContainer.hidden = true;
    }

    if (!emailInput || !passwordInput) {
        console.error('Form inputs not found!');
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
    const passwordValue = passwordInput.value.trim();
    if (!passwordValue) {
        errors.push('Please enter your password');
        passwordInput.classList.add('error');
    } else if (passwordValue.length < 8) {
        errors.push('Password must be at least 8 characters long');
        passwordInput.classList.add('error');
    } else {
        passwordInput.classList.remove('error');
    }

    // Display errors if any
    if (errors.length > 0) {
        if (errorContainer) {
            errorContainer.hidden = false;
            errors.forEach(error => {
                const p = document.createElement('p');
                p.textContent = error;
                p.className = 'error-message';
                errorContainer.appendChild(p);
            });
            (emailInput.classList.contains('error') ? emailInput : passwordInput).focus();
        } else {
            alert(errors.join('\n'));
        }
        return false;
    }

    form.submit();
    return true;
}

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    const toggleButton = document.querySelector('.toggle-password');

    if (form) {
        form.addEventListener('submit', validateForm);
    }

    if (toggleButton) {
        toggleButton.addEventListener('click', togglePasswordVisibility);
        toggleButton.setAttribute('aria-label', 'Show password');
        toggleButton.setAttribute('role', 'button');
    }
});

document.getElementById('messageModal').style.display = 'block';

function closeModal() {
    document.getElementById('messageModal').style.display = 'none';
}