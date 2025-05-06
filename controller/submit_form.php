<?php
session_start();

$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

// Database connection
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'errors' => ["Database connection failed"]]));
}

$errors = [];
$success = false;
$submission_id = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $fullname = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING);
    $year_section = filter_input(INPUT_POST, 'year_section', FILTER_SANITIZE_STRING);
    $student_id = filter_input(INPUT_POST, 'student_id', FILTER_SANITIZE_STRING);
    $contact_email = filter_input(INPUT_POST, 'contact_email', FILTER_SANITIZE_EMAIL);
    $document_type = filter_input(INPUT_POST, 'document_type', FILTER_SANITIZE_STRING);
    $other_type = isset($_POST['other_type']) ? filter_input(INPUT_POST, 'other_type', FILTER_SANITIZE_STRING) : '';
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

    // Validation
    if (empty($fullname) || !preg_match('/^[A-Za-z\s]{2,100}$/', $fullname)) {
        $errors[] = "Invalid full name (2-100 letters and spaces only)";
    }

    if (empty($year_section) || !preg_match('/^[1-5][A-Za-z]{2,4}\s?-\s?[A-Za-z]{2,10}$/', $year_section)) {
        $errors[] = "Invalid year & section format (e.g., '1IT - BSIT')";
    }

    if (empty($student_id) || !preg_match('/^[A-Za-z0-9-]{5,20}$/', $student_id)) {
        $errors[] = "Invalid student ID (5-20 letters, numbers, hyphens)";
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

    if (empty($description) || strlen($description) < 10) {
        $errors[] = "Description must be at least 10 characters";
    }

    // File validation
    $file_name = '';
    $file_size = 0;
    $file_data = null;
    $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

    if (isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['uploaded_file'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Verify file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $valid_mimes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        ];

        if (!in_array($file_ext, $allowed_types) || !in_array($mime, $valid_mimes)) {
            $errors[] = "Invalid file type. Only PDF, DOC, DOCX, JPG, PNG are allowed.";
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            $errors[] = "File size exceeds 5MB limit";
        }

        $file_data = file_get_contents($file['tmp_name']);
        if ($file_data === false) {
            $errors[] = "Failed to read uploaded file";
        }

        $file_name = uniqid('doc_', true) . '.' . $file_ext;
        $file_size = $file['size'];
    } else {
        $errors[] = "File upload is required";
    }

    // Proceed if no errors
    if (empty($errors)) {
        $conn->begin_transaction();

        try {
            // Check if user exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $contact_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $user_id = $row['id'];
            } else {
                // Create new user
                $temp_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $status = 'undergraduate';
                $address = 'Not specified';

                $stmt = $conn->prepare("INSERT INTO users (student_id, full_name, address, email, password, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $student_id, $fullname, $address, $contact_email, $temp_password, $status);

                if (!$stmt->execute()) {
                    throw new Exception("Failed to create user: " . $stmt->error);
                }

                $user_id = $conn->insert_id;
            }

            // Insert submission
            $final_document_type = ($document_type === 'Others') ? $other_type : $document_type;

            $stmt = $conn->prepare("INSERT INTO submissions 
                (user_id, full_name, year_section, contact_email, 
                 document_type, other_type, file_name, file_data, file_size, description, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

            $null = NULL;
            $stmt->bind_param("issssssbis",
                $user_id,
                $fullname,
                $year_section,
                $contact_email,
                $final_document_type,
                $other_type,
                $file_name,
                $null,
                $file_size,
                $description
            );

            $stmt->send_long_data(7, $file_data);

            if (!$stmt->execute()) {
                throw new Exception("Failed to submit document: " . $stmt->error);
            }

            $submission_id = $conn->insert_id;
            $conn->commit();
            $success = true;

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'errors' => $errors,
    'submission_id' => $submission_id,
    'message' => $success ? "Your document has been submitted successfully!" : ""
]);
exit;