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

// 6. Create user_images table connected to users table
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

// 7. Create submissions table (file stored in DB)
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (!$conn->query($sql)) {
    die("Error creating submissions table: " . $conn->error);
}

$conn->multi_query($sql);

// Wait for procedure creation to finish
do {
    if ($result = $conn->store_result()) {
        $result->free();
    }
} while ($conn->more_results() && $conn->next_result());

// 9. Now add an admin using stored procedure
$fullName = "Gian Glen Vincent Garcia";
$address = "Tagum City";
$sampleEmail = "admin@usep.edu.ph";
$samplePassword = "admin123";
$hashedPassword = password_hash($samplePassword, PASSWORD_DEFAULT);
$status = "alumni";

$stmt = $conn->prepare("CALL AddAdminIfAllowed(?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $fullName, $address, $sampleEmail, $hashedPassword, $status);
$stmt->execute();

// Fetch and display result message
$result = $stmt->get_result();
if ($result) {
    $row = $result->fetch_assoc();
    echo $row['result'] . "<br>";
}
$stmt->close();

// 10. Count students
$result = $conn->query("CALL GetTotalStudents()");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Total number of students: " . $row['total'] . "<br>";
} else {
    echo "Error counting students: " . $conn->error;
}

$conn->close();
?>