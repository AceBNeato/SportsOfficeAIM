<!DOCTYPE html>
<?php
session_start();

// Debug: Check if the user is already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // Debug log
    error_log('Session already active: ' . print_r($_SESSION, true));

    // Check user role and redirect accordingly
    if (isset($_SESSION['user']['role'])) {
        $role = strtolower(trim($_SESSION['user']['role']));
        error_log("User role: $role");

        if ($role === 'admin') {
            header('Location: adminView.php');
            exit();
        } else {
            header('Location: userView.php');
            exit();
        }
    } else {
        error_log("No role defined in session");
    }
}

// Get error message if exists
$errorMessage = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

// Debug
error_log("Login page loaded. Error message: " . ($errorMessage ?: 'none'));
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USeP OSAS-Sports Unit Login</title>
    <link rel="stylesheet" href="../public/CSS/loginStyle.css">
    <link href="https://cdn.jsdelivr.net/npm/boxicons/css/boxicons.min.css" rel="stylesheet" />
    <link rel="icon" href="../public/image/Usep.png" sizes="any" />
    <style>
        .error-message {
            color: #d32f2f;
            text-align: center;
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
            font-weight: bold;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .modal h2 {
            margin-top: 0;
            font-size: 1.5em;
            color: #333;
        }

        .modal p {
            margin: 10px 0;
            line-height: 1.5;
            color: #555;
        }

        .modal .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.5em;
            color: #d32f2f;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            line-height: 1;
        }

        .modal .close-button:hover {
            color: #b71c1c;
        }

        /* Ensure modal is centered */
        .modal-overlay.active {
            display: flex;
        }

        /* Privacy Modal Styles */
        .privacy-modal-overlay {
            display: flex;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .privacy-modal {
            background: #2c2c2c;
            border-radius: 20px;
            max-width: 550px;
            width: 90%;
            color: white;
            overflow: hidden;
            text-align: center;
            box-shadow: 0 0 20px rgba(255, 107, 107, 0.5), 0 0 40px rgba(255, 107, 107, 0.3);
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from {
                box-shadow: 0 0 20px rgba(255, 107, 107, 0.5), 0 0 40px rgba(255, 107, 107, 0.3);
                transform: scale(1);
            }
            to {
                box-shadow: 0 0 30px rgba(255, 107, 107, 0.7), 0 0 60px rgba(255, 107, 107, 0.4);
                transform: scale(1.02);
            }
        }

        .privacy-modal-content {
            padding: 20px;
        }

        .privacy-modal-image {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            width: 100%;
        }

        .privacy-modal-image img {
            width: 90%;
            max-width: 400px;
            height: auto;
            object-fit: contain;
        }

        .privacy-modal h2, .privacy-modal h3 {
            margin: 15px 0;
            font-size: 1.4em;
            color: white;
            font-family: 'Segoe UI', 'Nunito', 'Arial Rounded MT Bold', 'Arial', sans-serif;
            font-weight: 600;
            letter-spacing: 0.5px;
            line-height: 1.4;
        }

        .privacy-link {
            color: #ff6b6b;
            text-decoration: none; /* No underline by default */
            cursor: pointer;
        }

        .privacy-link:hover {
            text-decoration: underline; /* Underline on hover */
        }

        .privacy-button {
            background-color: #e05757;
            color: white;
            border: none;
            padding: 12px 40px;
            font-size: 1em;
            border-radius: 25px;
            cursor: pointer;
            margin-top: 20px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .privacy-button:hover {
            background-color: #c94545;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Left Panel -->
    <div class="left-panel">
        <div class="content-wrapper">
            <div class="logo-container">
                <img src="../public/image/SportOffice.png" alt="Sports Office Logo" class="logo">
                <img src="../public/image/Usep.png" alt="USeP Logo" class="logo">
            </div>
            <h2><span class="highlight">One Data.</span> <span class="highlight">One USeP.</span></h2>
            <h1>USeP OSAS-Sports Unit</h1>
        </div>
        <footer>
            <p>© 2025. All Rights Reserved.</p>
            <a href="#" id="termsLink">Terms of Use</a> | <a href="https://www.usep.edu.ph/usep-data-privacy-statement/">Privacy Policy</a>
        </footer>
    </div>

    <!-- Right Panel / Login -->
    <div class="right-panel">
        <div class="login-box">
            <h1>WELCOME</h1>

            <?php if (!empty($errorMessage)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php else: ?>
                <p>Please log in to get started.</p>
            <?php endif; ?>

            <div id="error-messages" class="error-messages" hidden></div>
            <form method="POST" action="../controller/auth.php" onsubmit="return validateForm(event)" novalidate>
                <label>
                    <input type="email" name="email" placeholder="Enter Email" required>
                </label>
                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="Enter Password" required>
                    <i class="bx bx-show toggle-password" aria-label="Show password" role="button"></i>
                </div>
                <button type="submit">LOGIN</button>
            </form>
            <p class="signup-link">Don't have an account? <a href="../view/signupView.php">Sign Up</a></p>
        </div>
    </div>
</div>

<!-- Terms of Use Modal -->
<div class="modal-overlay" id="termsModal">
    <div class="modal">
        <button class="close-button" id="closeModal" aria-label="Close modal">
            <i class="bx bx-x"></i>
        </button>
        <h2 style="text-align: center;">Terms of Use</h2>

        <p>
            Welcome to the USeP OSAS-Sports Unit system. By accessing or using this website, you agree to be bound by the following terms and conditions:
        </p>
        <p>
            1. <strong>Account Responsibility:</strong> You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.
        </p>
        <p>
            2. <strong>Prohibited Activities:</strong> You may not use this system for any unlawful purpose or in any way that violates these terms, including unauthorized access or data manipulation.
        </p>
        <p>
            3. <strong>Data Privacy:</strong> We are committed to protecting your personal information. Please refer to our <a href="https://www.usep.edu.ph/usep-data-privacy-statement/">Privacy Policy</a> for details.
        </p>
        
        <p>
            For any questions or concerns, please contact the USeP OSAS-Sports Unit administration.
        </p>
    </div>
</div>

<!-- Privacy Statement Modal -->
<div class="privacy-modal-overlay" id="privacyModal">
    <div class="privacy-modal">
        <div class="privacy-modal-content">
            <div class="privacy-modal-image">
                <img src="../public/image/dataPrivacy.png" alt="Student privacy illustration" />
            </div>
            <h3>By continuing to use the Student Portal, you agree to the
                <span class="privacy-link">University of Southeastern Philippines' Data Privacy Statement</span>.
            </h3>
            <button class="privacy-button" id="continueButton">CONTINUE</button>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="../public/JAVASCRIPT/loginScript.js"></script>
<script>
    // Modal functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Get Terms modal elements
        const termsModal = document.getElementById('termsModal');
        const termsLink = document.getElementById('termsLink');
        const closeModal = document.getElementById('closeModal');

        // Open Terms modal when Terms of Use is clicked
        termsLink.addEventListener('click', function(e) {
            e.preventDefault();
            termsModal.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
        });

        // Close Terms modal when close button is clicked
        closeModal.addEventListener('click', function() {
            termsModal.classList.remove('active');
            document.body.style.overflow = ''; // Re-enable scrolling
        });

        // Close Terms modal when clicking outside the modal content
        termsModal.addEventListener('click', function(e) {
            if (e.target === termsModal) {
                termsModal.classList.remove('active');
                document.body.style.overflow = ''; // Re-enable scrolling
            }
        });

        // Close Terms modal when pressing Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && termsModal.classList.contains('active')) {
                termsModal.classList.remove('active');
                document.body.style.overflow = ''; // Re-enable scrolling
            }
        });

        // Password toggle functionality
        const togglePassword = document.querySelector('.toggle-password');
        const passwordInput = document.getElementById('password');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('bx-show');
                this.classList.toggle('bx-hide');
            });
        }

        // Privacy Modal functionality
        const privacyModal = document.getElementById('privacyModal');
        const continueButton = document.getElementById('continueButton');
        const privacyLink = document.querySelector('.privacy-link');

        // The privacy modal is already visible by default

        // Close privacy modal when continue button is clicked
        continueButton.addEventListener('click', function() {
            privacyModal.style.display = 'none';
            document.body.style.overflow = ''; // Re-enable scrolling
        });

        // Link to privacy policy
        if (privacyLink) {
            privacyLink.addEventListener('click', function() {
                window.open('https://www.usep.edu.ph/usep-data-privacy-statement/', '_blank');
            });
        }

        // Check if the privacy modal has been shown before (using sessionStorage)
        if (sessionStorage.getItem('privacyModalShown')) {
            privacyModal.style.display = 'none';
        } else {
            // Modal is already visible
            document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open

            // Set the flag in sessionStorage when the user clicks continue
            continueButton.addEventListener('click', function() {
                sessionStorage.setItem('privacyModalShown', 'true');
            });
        }
    });

    // Form validation function
    function validateForm(event) {
        const email = document.querySelector('input[name="email"]').value;
        const password = document.querySelector('input[name="password"]').value;
        const errorMessages = document.getElementById('error-messages');
        let isValid = true;
        let messages = [];

        // Clear previous error messages
        errorMessages.innerHTML = '';
        errorMessages.hidden = true;

        // Validate email
        if (!email) {
            messages.push('Email is required');
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            messages.push('Please enter a valid email address');
            isValid = false;
        }

        // Validate password
        if (!password) {
            messages.push('Password is required');
            isValid = false;
        }

        // Display error messages if any
        if (!isValid) {
            errorMessages.hidden = false;
            messages.forEach(message => {
                const p = document.createElement('p');
                p.textContent = message;
                errorMessages.appendChild(p);
            });
            event.preventDefault();
        }

        return isValid;
    }
</script>
</body>
</html>


