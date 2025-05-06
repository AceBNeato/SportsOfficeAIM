<?php
// submit_form.php

// Start session to access user information
session_start();

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$errors = [];
$success = false;
$submission_id = null;

// Process form when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input data
    $fullname = trim($_POST['fullname']);
    $year_section = trim($_POST['year_section']);
    $student_id = trim($_POST['student_id']);
    $contact_email = trim($_POST['contact_email']);
    $document_type = trim($_POST['document_type']);
    $other_type = isset($_POST['other_type']) ? trim($_POST['other_type']) : '';
    $description = trim($_POST['description']);

    // Basic validation
    if (empty($fullname)) {
        $errors[] = "Full name is required";
    }
    if (empty($year_section)) {
        $errors[] = "Year & section is required";
    }
    if (empty($student_id)) {
        $errors[] = "Student ID is required";
    }
    if (empty($contact_email) || !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    if (empty($document_type)) {
        $errors[] = "Document type is required";
    }
    if ($document_type === 'Others' && empty($other_type)) {
        $errors[] = "Please specify document type";
    }
    if (empty($description)) {
        $errors[] = "Description is required";
    }

    // Handle file upload
    $file_name = '';
    $file_path = '';
    $file_size = 0;

    if (isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['uploaded_file'];

        // Validate file
        $allowed_types = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'image/jpeg',
            'image/png',
            'image/jpg'
        ];
        $max_size = 5 * 1024 * 1024; // 5MB

        // Get file extension and validate
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'doc', 'docx', 'jpeg', 'png', 'jpg'];

        if (!in_array($file_ext, $allowed_extensions)) {
            $errors[] = "Invalid file type. Only PDF, DOC, DOCX, JPG, PNG are allowed.";
        }

        if ($file['size'] > $max_size) {
            $errors[] = "File size exceeds 5MB limit";
        }

        // If no errors, process the file
        if (empty($errors)) {
            $upload_dir = '../uploads/'; // Changed to relative path from form location

            // Create upload directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename while preserving extension
            $file_name = uniqid('doc_', true) . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            $file_size = $file['size'];

            // Move the file
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                $errors[] = "Failed to upload file. Please try again.";
                error_log("File upload failed. Check directory permissions for: " . $upload_dir);
            }
        }
    } else {
        $file_error = $_FILES['uploaded_file']['error'] ?? 'unknown';
        $errors[] = "File upload is required. Error code: " . $file_error;
    }

    // If no errors, proceed with database insertion
    if (empty($errors)) {
        // Check if the user exists in the users table based on the student ID
        // If not, create a new user first
        $user_id = null;

        // First, try to find the user by email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $contact_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User exists, get their ID
            $row = $result->fetch_assoc();
            $user_id = $row['id'];
        } else {
            // User doesn't exist, create a new user
            $temp_password = password_hash(uniqid(), PASSWORD_DEFAULT); // Generate temporary password
            $status = 'undergraduate';
            $address = 'Not specified'; // Default address

            $stmt = $conn->prepare("INSERT INTO users (student_id, full_name, address, email, password, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss",
                $student_id,
                $fullname,
                $address,
                $contact_email,
                $temp_password,
                $status
            );

            if ($stmt->execute()) {
                $user_id = $conn->insert_id;
                error_log("Created new user with ID: $user_id");
            } else {
                $errors[] = "Failed to create user account: " . $stmt->error;
            }
        }
        $stmt->close();

        // Only proceed if we have a valid user_id
        if ($user_id && empty($errors)) {
            // If document type is "Others", use the specified type
            $final_document_type = ($document_type === 'Others') ? $other_type : $document_type;

            // Prepare the SQL statement using prepared statements
            $stmt = $conn->prepare("INSERT INTO submissions 
                           (user_id, full_name, year_section, contact_email, 
                            document_type, other_type, file_name, file_path, file_size, description, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

            if ($stmt === false) {
                $errors[] = "Database error: " . $conn->error;
            } else {
                $stmt->bind_param("isssssssis",
                    $user_id,
                    $fullname,
                    $year_section,
                    $contact_email,
                    $final_document_type,
                    $other_type,
                    $file_name,
                    $file_path,
                    $file_size,
                    $description
                );

                if ($stmt->execute()) {
                    $submission_id = $conn->insert_id;
                    $success = true;

                    // Log successful submission
                    error_log("Document submitted successfully. ID: $submission_id");
                } else {
                    $errors[] = "Error submitting document: " . $stmt->error;

                    // Clean up uploaded file if DB insert failed
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                $stmt->close();
            }
        } else {
            if (empty($errors)) {
                $errors[] = "Failed to identify or create user account";
            }
        }
    }

    // Return JSON response for AJAX requests
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'errors' => $errors,
        'submission_id' => $submission_id,
        'message' => $success ? "Your document has been submitted successfully!" : ""
    ]);
    exit;
}

$conn->close();