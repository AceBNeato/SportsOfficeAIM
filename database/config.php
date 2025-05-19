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

// 4. Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    address VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('undergraduate', 'alumni') DEFAULT 'undergraduate'
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

<<<<<<< HEAD


<<<<<<< HEAD
=======
>>>>>>> parent of d613f21 (achievement merge with notification)
=======

$sql = "CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
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
    submission_date DATETIME NOT NULL,
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



>>>>>>> 59aec3e9f9389d334d59671d188fca365e087b6d
// 10. Add admin using stored procedure
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

// 11. Call stored procedure to count students
$result = $conn->query("CALL GetTotalStudents()");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Total number of students: " . $row['total'] . "<br>";
} else {
    echo "Error calling GetTotalStudents: " . $conn->error;
}

$conn->close();
?>