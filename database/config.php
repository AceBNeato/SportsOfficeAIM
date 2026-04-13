<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

// 1. Connect to MySQL server
$conn = new mysqli($host, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Create the database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (!$conn->query($sql)) {
    die("Error creating database: " . $conn->error);
}

// 3. Select the database
$conn->select_db($dbname);

// 4. Create users table with sport and campus columns
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('undergraduate', 'alumni') DEFAULT 'undergraduate',
    sport VARCHAR(100),
    campus VARCHAR(100),
    year_section VARCHAR(100) NULL
)";
if (!$conn->query($sql)) {
    die("Error creating users table: " . $conn->error);
}

// 5. Create admins table
$sql = "CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('undergraduate', 'alumni') DEFAULT 'undergraduate'
)";
if (!$conn->query($sql)) {
    die("Error creating admins table: " . $conn->error);
}

// 6. Create user_images table connected to users
$sql = "CREATE TABLE IF NOT EXISTS user_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image LONGBLOB NOT NULL,
    image_type VARCHAR(50),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if (!$conn->query($sql)) {
    die("Error creating user_images table: " . $conn->error);
}

// 7. Create submissions table
$sql = "CREATE TABLE IF NOT EXISTS submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    year_section VARCHAR(100) NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    other_type VARCHAR(100),
    file_name VARCHAR(255) NOT NULL,
    file_data LONGBLOB NOT NULL,
    file_size INT NOT NULL,
    description TEXT NOT NULL,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    comments TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if (!$conn->query($sql)) {
    die("Error creating submissions table: " . $conn->error);
}

// 8. Create account_approvals table
$sql = "CREATE TABLE IF NOT EXISTS account_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    status ENUM('undergraduate', 'alumni') DEFAULT 'undergraduate',
    sport VARCHAR(100),
    campus VARCHAR(100),
    year_section VARCHAR(100) NULL,
    file_name VARCHAR(255) NOT NULL,
    file_data LONGBLOB NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    approval_date TIMESTAMP NULL,
    FOREIGN KEY (approved_by) REFERENCES admins(id) ON DELETE SET NULL,
    UNIQUE (student_id, email)
)";
if (!$conn->query($sql)) {
    die("Error creating account_approvals table: " . $conn->error);
}



// 9. Create notifications table
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
if (!$conn->query($sql)) {
    die("Error creating notifications table: " . $conn->error);
}

// 10. Create achievements table
$sql = "CREATE TABLE IF NOT EXISTS achievements (
    achievement_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    athlete_name VARCHAR(255) NOT NULL,
    level_of_competition VARCHAR(50) NOT NULL,
    performance VARCHAR(50) NOT NULL,
    number_of_events VARCHAR(50),
    leadership_role VARCHAR(50),
    sportsmanship VARCHAR(50),
    community_impact VARCHAR(50),
    completeness_of_documents VARCHAR(50),
    total_points INT NOT NULL,
    submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    rejection_reason TEXT,
    documents TEXT, -- To store JSON array of file paths
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
if (!$conn->query($sql)) {
    error_log("Error creating achievements table: " . $conn->error);
    die("Error creating achievements table: " . $conn->error);
} else {
    error_log("Achievements table created or already exists");
}


$sql = "CREATE TABLE IF NOT EXISTS leaderboard (
    user_id INT PRIMARY KEY,
    total_points INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
if (!$conn->query($sql)) {
    error_log("Error creating achievements table: " . $conn->error);
    die("Error creating achievements leaderboard: " . $conn->error);
} else {
    error_log("Achievements leaderboard created or already exists");
}

// Create stored procedures with DEFINER
$conn->query("DROP PROCEDURE IF EXISTS find_user_by_email");
$conn->query("CREATE DEFINER=`root`@`localhost` PROCEDURE `find_user_by_email`(IN user_email VARCHAR(255))
BEGIN
    SELECT id, email, password, 'admin' AS role, full_name, address FROM admins WHERE email = user_email
    UNION
    SELECT id, email, password, 'user' AS role, full_name, address FROM users WHERE email = user_email;
END");

$conn->query("DROP PROCEDURE IF EXISTS AddAdminIfAllowed");
$conn->query("CREATE DEFINER=`root`@`localhost` PROCEDURE `AddAdminIfAllowed`(
    IN p_full_name VARCHAR(255),
    IN p_address VARCHAR(255),
    IN p_email VARCHAR(255),
    IN p_password VARCHAR(255),
    IN p_status ENUM('undergraduate', 'alumni')
)
BEGIN
    DECLARE admin_count INT DEFAULT 0;
    SELECT COUNT(*) INTO admin_count FROM admins;
    IF admin_count < 2 THEN
        IF NOT EXISTS (SELECT 1 FROM admins WHERE email = p_email) THEN
            INSERT INTO admins (full_name, address, email, password, status)
            VALUES (p_full_name, p_address, p_email, p_password, p_status);
            SELECT 'Admin created successfully' AS result;
        ELSE
            SELECT 'Admin already exists' AS result;
        END IF;
    ELSE
        SELECT 'Admin limit reached' AS result;
    END IF;
END");

$conn->query("DROP PROCEDURE IF EXISTS GetTotalStudents");
$conn->query("CREATE DEFINER=`root`@`localhost` PROCEDURE `GetTotalStudents`()
BEGIN
    SELECT COUNT(*) AS total FROM users;
END");

// 11. Insert sample admin
$fullName = "Gian Glen Vincent Garcia";
$address = "Tagum City";
$sampleEmail = "admin@usep.edu.ph";
$samplePassword = "admin123";
$hashedPassword = password_hash($samplePassword, PASSWORD_DEFAULT);
$status = "alumni";

$stmt = $conn->prepare("CALL AddAdminIfAllowed(?, ?, ?, ?, ?)");
if ($stmt) {
    $stmt->bind_param("sssss", $fullName, $address, $sampleEmail, $hashedPassword, $status);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        echo $row['result'] . "<br>";
    }
    $stmt->close();
} else {
    echo "AddAdminIfAllowed procedure not found or prepare() failed: " . $conn->error;
}

// 12. Call stored procedure to count students
$result = $conn->query("CALL GetTotalStudents()");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Total number of students: " . $row['total'] . "<br>";
} else {
    echo "Error calling GetTotalStudents: " . $conn->error;
}

$conn->close();
?>