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
    <script src="../public/JAVASCRIPT/loginScript.js" defer></script>
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
            <a href="#" class="terms-link">Terms of Use</a> | <a href="https://www.usep.edu.ph/usep-data-privacy-statement/">Privacy Policy</a>
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
            <p class="signup-link">Please contact the admin to create an account.</p>
        </div>
    </div>
</div>

<!-- Terms of Use Modal -->
<div class="modal-overlay" id="termsModal">
    <div class="modal">
        <i class="bx bx-x close-button" aria-label="Close modal"></i>
        <!-- Terms of Use Modal -->
        <div class="modal-overlay" id="termsModal">
            <div class="modal">
                <i class="bx bx-x close-button" aria-label="Close modal"></i>
                <h2>Terms of Use</h2>
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
                    4. <strong>Changes to Terms:</strong> We reserve the right to modify these terms at any time. Continued use of the system constitutes acceptance of the updated terms.
                </p>
                <p>
                    For any questions or concerns, please contact the USeP OSAS-Sports Unit administration.
                </p>
            </div>
        </div>
        <h2>Terms of Use</h2>
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
            4. <strong>Changes to Terms:</strong> We reserve the right to modify these terms at any time. Continued use of the system constitutes acceptance of the updated terms.
        </p>
        <p>
            For any questions or concerns, please contact the USeP OSAS-Sports Unit administration.
        </p>
    </div>
</div>

<script>
    // Show the modal when the Terms of Use link is clicked
    document.querySelector('.terms-link').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('termsModal').classList.add('active');
    });

    // Close the modal
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.remove('active');
        console.log("Terms of Use modal closed");
    }

    // Close modal when clicking the close button
    document.querySelector('.close-button').addEventListener('click', function() {
        closeModal('termsModal');
    });

    // Close modal when clicking outside the modal content
    document.getElementById('termsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal('termsModal');
        }
    });

    // Close modal when pressing the Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('termsModal').classList.contains('active')) {
            closeModal('termsModal');
        }
    });
</script>
</body>
</html>