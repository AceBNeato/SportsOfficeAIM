<?php
// Logout handling must happen before any output
if (isset($_GET['logout'])) {
    session_start();
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

session_start();

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

// Connect to database
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$message = '';
$user_data = null;
$user_image = null;

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // User Registration
    if (isset($_POST['register'])) {
        $student_id = $_POST['student_id'];
        $full_name = $_POST['full_name'];
        $address = $_POST['address'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $status = $_POST['status'];

        try {
            $stmt = $conn->prepare("INSERT INTO users (student_id, full_name, address, email, password, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $student_id, $full_name, $address, $email, $password, $status);
            $stmt->execute();
            $user_id = $stmt->insert_id;
            $message = "Registration successful! User ID: $user_id";
            $_SESSION['user_id'] = $user_id;
            $_SESSION['full_name'] = $full_name;
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $message = "Error: " . $e->getMessage();
        }
    }

    // Image Upload
    if (isset($_FILES["user_image"]) && !empty($_FILES["user_image"]["tmp_name"])) {
        if (!isset($_SESSION['user_id'])) {
            $message = "Please register or log in first before uploading an image.";
        } else {
            $user_id = $_SESSION['user_id'];
            $image = $_FILES['user_image']['tmp_name'];
            $image_type = $_FILES['user_image']['type'];

            $allowed_types = ['image/jpeg', 'image/png'];
            if (in_array($image_type, $allowed_types) && getimagesize($image)) {
                $imgData = file_get_contents($image);
                $stmt = $conn->prepare("INSERT INTO user_images (user_id, image, image_type) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user_id, $imgData, $image_type);

                if ($stmt->execute()) {
                    $message = "Image uploaded successfully!";
                } else {
                    $message = "Error uploading image: " . $conn->error;
                }
                $stmt->close();
            } else {
                $message = "Invalid image file. Please upload JPEG or PNG.";
            }
        }
    }

    // User Login
    if (isset($_POST['login'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, full_name, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $message = "Login successful! Welcome back, " . htmlspecialchars($user['full_name']);
            } else {
                $message = "Invalid password.";
            }
        } else {
            $message = "User not found.";
        }
        $stmt->close();
    }
}

// Get user data if logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Get latest user image if exists
    $stmt = $conn->prepare("SELECT image_type, image FROM user_images WHERE user_id = ? ORDER BY uploaded_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $image_result = $stmt->get_result();
    if ($image_result->num_rows > 0) {
        $user_image = $image_result->fetch_assoc();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sport Office Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333;
            padding: 20px; margin: 0;
        }
        .container {
            max-width: 1000px; margin: auto; background: white;
            padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select {
            width: 100%; padding: 8px;
            border: 1px solid #ccc; border-radius: 4px;
        }
        button {
            background: #3498db; color: white;
            padding: 10px 15px; border: none;
            border-radius: 4px; cursor: pointer;
            font-size: 16px;
        }
        button:hover { background: #2980b9; }
        .message {
            margin: 15px 0; padding: 10px;
            border-radius: 4px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .user-profile {
            display: flex; align-items: center;
            background: #f8f9fa; padding: 20px;
            border-radius: 8px; margin-top: 20px;
        }
        .profile-image {
            width: 150px; height: 150px; border-radius: 50%;
            object-fit: cover; margin-right: 20px;
            border: 3px solid #3498db;
        }
        .profile-info { flex: 1; }
        .section {
            margin-bottom: 30px; padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .nav {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="nav">
        <h1>Sport Office Portal</h1>
        <?php if (isset($_SESSION['user_id'])): ?>
            <p>Welcome, <?= htmlspecialchars($_SESSION['full_name']); ?>! <a href="?logout=1">Logout</a></p>
        <?php endif; ?>
    </div>

    <?php if (!empty($message)): ?>
        <div class="message <?= str_starts_with($message, 'Error') ? 'error' : 'success' ?>">
            <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="section">
            <h2>Your Profile</h2>
            <div class="user-profile">
                <?php if ($user_image): ?>
                    <img src="data:<?= $user_image['image_type']; ?>;base64,<?= base64_encode($user_image['image']); ?>"
                         alt="Profile Image" class="profile-image">
                <?php else: ?>
                    <div class="profile-image" style="background: #ddd; display: flex; align-items: center; justify-content: center;">
                        No Image
                    </div>
                <?php endif; ?>

                <div class="profile-info">
                    <h3><?= htmlspecialchars($user_data['full_name']); ?></h3>
                    <p><strong>Student ID:</strong> <?= htmlspecialchars($user_data['student_id']); ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user_data['email']); ?></p>
                    <p><strong>Status:</strong> <?= ucfirst(htmlspecialchars($user_data['status'])); ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($user_data['address']); ?></p>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>Upload Profile Image</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="user_image">Select image (JPEG or PNG):</label>
                    <input type="file" name="user_image" id="user_image" accept="image/jpeg, image/png" required>
                </div>
                <button type="submit">Upload Image</button>
            </form>
        </div>

    <?php else: ?>
        <div class="section">
            <h2>User Login</h2>
            <form method="post">
                <div class="form-group">
                    <label for="login_email">Email:</label>
                    <input type="email" name="email" id="login_email" required>
                </div>
                <div class="form-group">
                    <label for="login_password">Password:</label>
                    <input type="password" name="password" id="login_password" required>
                </div>
                <button type="submit" name="login">Login</button>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
<?php
$conn->close();
?>
