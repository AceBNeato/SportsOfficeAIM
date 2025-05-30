<?php
header('Content-Type: application/json');

session_start();

// Log session data for debugging
file_put_contents('debug.log', "Session Data: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    file_put_contents('debug.log', "DB Connection Error: " . $conn->connect_error . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$submission_id = isset($_POST['submission_id']) ? (int)$_POST['submission_id'] : 0;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$is_resubmission = isset($_POST['is_resubmission']) ? (int)$_POST['is_resubmission'] : 0;

file_put_contents('debug.log', "Input Data: submission_id=$submission_id, description='$description', is_resubmission=$is_resubmission\n", FILE_APPEND);

if ($submission_id <= 0 || empty($description)) {
    echo json_encode(['success' => false, 'message' => "Invalid input: submission_id=$submission_id, description='$description'"]);
    exit;
}

if (strlen($description) < 10) {
    echo json_encode(['success' => false, 'message' => 'Description must be at least 10 characters long']);
    exit;
}

// Verify ownership
$user_id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT user_id, status FROM submissions WHERE id = ?");
if (!$stmt) {
    file_put_contents('debug.log', "Prepare Error: " . $conn->error . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'SQL Prepare Error: ' . $conn->error]);
    exit;
}
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
if ($result->num_rows === 0 || $row['user_id'] != $user_id) {
    file_put_contents('debug.log', "Ownership Check Failed: session_user_id=$user_id, db_user_id=" . ($row['user_id'] ?? 'null') . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => "Unauthorized access to submission: session_user_id=$user_id, db_user_id=" . ($row['user_id'] ?? 'null')]);
    exit;
}
$current_status = $row['status'];
$stmt->close();

// Handle document type for resubmissions
$document_type = null;
$other_type = null;
if ($is_resubmission) {
    if (isset($_POST['document_type']) && $_POST['document_type'] === 'other' && isset($_POST['other_type'])) {
        $other_type = trim($_POST['other_type']);
        if (empty($other_type)) {
            echo json_encode(['success' => false, 'message' => 'Please specify the document type']);
            exit;
        }
        $document_type = 'Other';
    } else {
        $document_type = 'Standard Document';
    }
}

// Handle file upload if present
$file_data = null;
$file_name = null;
$file_size = null;

if (isset($_FILES['uploaded_file']) && $_FILES['uploaded_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['uploaded_file'];
    $valid_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
    $max_size = 5 * 1024 * 1024; // 5MB

    file_put_contents('debug.log', "File Data: name=" . $file['name'] . ", size=" . $file['size'] . ", type=" . $file['type'] . ", error=" . $file['error'] . "\n", FILE_APPEND);

    if (!in_array($file['type'], $valid_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only PDF, DOC, DOCX, JPG, PNG are allowed.']);
        exit;
    }

    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit.']);
        exit;
    }

    $file_data = file_get_contents($file['tmp_name']);
    if ($file_data === false) {
        file_put_contents('debug.log', "File Read Error: Failed to read " . $file['tmp_name'] . "\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Failed to read uploaded file data.']);
        exit;
    }
    $file_name = $file['name'];
    $file_size = $file['size'];
}

// Prepare the SQL query based on what needs to be updated
$sql = "UPDATE submissions SET description = ?, status = ?, submission_date = NOW()";
$params = [$description];
$types = "s";

// For resubmissions, change status to pending and update document type if needed
if ($is_resubmission) {
    $new_status = 'pending';
    $params[] = $new_status;
    $types .= "s";

    if ($document_type !== null) {
        $sql .= ", document_type = ?";
        $params[] = $document_type;
        $types .= "s";
    }

    if ($other_type !== null) {
        $sql .= ", other_type = ?";
        $params[] = $other_type;
        $types .= "s";
    }
} else {
    // Regular update - keep current status
    $params[] = $current_status;
    $types .= "s";
}

// Add file data if present
if ($file_data !== null) {
    $sql .= ", file_data = ?, file_name = ?, file_size = ?";
    array_push($params, $file_data, $file_name, $file_size);
    $types .= "ssi";
}

$sql .= " WHERE id = ?";
$params[] = $submission_id;
$types .= "i";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    file_put_contents('debug.log', "Prepare Error: " . $conn->error . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'SQL Prepare Error: ' . $conn->error]);
    exit;
}

$stmt->bind_param($types, ...$params);

if (!$stmt->execute()) {
    file_put_contents('debug.log', "Execute Error: " . $stmt->error . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'SQL Execute Error: ' . $stmt->error]);
    exit;
}


// At the end of the file, before the json_encode response:
if ($is_resubmission) {
    $_SESSION['submission_success'] = 'Document resubmitted successfully!';
} else {
    $_SESSION['submission_success'] = 'Changes saved successfully!';
}

file_put_contents('debug.log', "Update Successful: submission_id=$submission_id, is_resubmission=$is_resubmission\n", FILE_APPEND);
echo json_encode(['success' => true, 'message' => $_SESSION['submission_success']]);



$stmt->close();
$conn->close();
?>