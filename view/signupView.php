<!-- File: /view/signupView.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - USeP OSAS-Sports Unit</title>
    <link rel="stylesheet" href="../public/CSS/signup.css">
    <link rel="icon" href="../public/image/Usep.png" sizes="any">
    <script src="../public/JAVASCRIPT/loginScript.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/boxicons/css/boxicons.min.css" rel="stylesheet" />
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
                <h2><span class="highlight">Join</span> <span class="highlight">USeP Sports</span></h2>
                <h1>USeP OSAS-Sports Unit</h1>
            </div>
        </div>
    </nav>

    <div class="center-panel">
        <div class="login-box">
            <h1>CREATE ACCOUNT</h1>
            <form method="POST" action="../controller/addUsers.php" onsubmit="return validateSignupForm(event)">
                <input type="text" name="student_id" placeholder="Student ID" required autocomplete="off">
                <input type="text" name="full_name" placeholder="Full Name" required autocomplete="name">
                <input type="text" name="address" placeholder="Address" required autocomplete="street-address">
                <input type="email" name="email" placeholder="Email" required autocomplete="email">

                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="Enter Password" required>
                    <i class="bx bx-show toggle-password" aria-label="Show password" role="button"></i>
                </div>

                <select name="status" required>
                    <option value="" disabled selected>Select Status</option>
                    <option value="undergraduate">Undergraduate</option>
                    <option value="alumni">Alumni</option>
                </select>

                <!-- 🔥 Add this hidden input -->
                <input type="hidden" name="page" value="signup">

                <button type="submit">SIGN UP</button>
            </form>
            <p class="signup-link">Already have an account? <a href="loginView.php">Log In</a></p>
        </div>
    </div>
</div>

<?php if (isset($_GET['message'])): ?>
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <p><?php echo htmlspecialchars($_GET['message']); ?></p>
            <button class="close-btn" onclick="closeModal()">OK</button>
        </div>
    </div>

    <script>
        document.getElementById('messageModal').style.display = 'block';

        function closeModal() {
            document.getElementById('messageModal').style.display = 'none';
        }
    </script>
<?php endif; ?>

</body>
</html>
