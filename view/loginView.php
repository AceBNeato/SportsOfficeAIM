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
            font-weight: bold;  /* Changed from 500 to bold */
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
            <p>&copy; 2025. All Rights Reserved.</p>
            <a href="#">Terms of Use</a> | <a href="#">Privacy Policy</a>
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
</body>
</html>