<?php
// Place use statements at the top of the file in the global scope
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../view/loginView.php");
    exit;
}

// Check session timeout (30 minutes)
$session_timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['user']['last_activity']) && (time() - $_SESSION['user']['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: ../view/loginView.php?timeout=1");
    exit;
}

// Update last activity time
$_SESSION['user']['last_activity'] = time();

// Load PHPMailer autoloader
if (!file_exists('../vendor/autoload.php')) {
    die('Error: Composer dependencies not installed. Run "composer install" in the project root.');
}
require '../vendor/autoload.php';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to send email notification
function sendEmailNotification($recipientEmail, $recipientName, $status, $password = null) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Replace with your Gmail address
        $mail->Password = 'your-app-password'; // Replace with your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('your-email@gmail.com', 'USeP OSAS-Sports Unit');
        $mail->addAddress($recipientEmail, $recipientName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Account Approval Status';

        if ($status == 'approved') {
            $mail->Body = "
                <h2>Account Approval Notification</h2>
                <p>Dear {$recipientName},</p>
                <p>Your account request has been <strong>APPROVED</strong>.</p>
                <p>Please use the following credentials to log in:</p>
                <p><strong>Email:</strong> {$recipientEmail}<br>
                   <strong>Password:</strong> {$password}</p>
                <p>You can log in at: <a href='http://your-login-url'>Login Page</a></p>
                <p>Thank you!</p>
                <p>USeP OSAS-Sports Unit</p>
            ";
            $mail->AltBody = "Dear {$recipientName},\n\nYour account request has been APPROVED.\n\nPlease use the following credentials to log in:\nEmail: {$recipientEmail}\nPassword: {$password}\n\nYou can log in at: http://your-login-url\n\nThank you!\nUSeP OSAS-Sports Unit";
        } else {
            $mail->Body = "
                <h2>Account Approval Notification</h2>
                <p>Dear {$recipientName},</p>
                <p>We regret to inform you that your account request has been <strong>REJECTED</strong>.</p>
                <p>For further details, please contact the OSAS-Sports Unit.</p>
                <p>Thank you!</p>
                <p>USeP OSAS-Sports Unit</p>
            ";
            $mail->AltBody = "Dear {$recipientName},\n\nWe regret to inform you that your account request has been REJECTED.\n\nFor further details, please contact the OSAS-Sports Unit.\n\nThank you!\nUSeP OSAS-Sports Unit";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Handle approval/rejection before any output
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_GET['page']) && $_GET['page'] === 'Account Approvals') {
    // Check if admin is logged in and has a valid admin_id
    if (!isset($_SESSION['admin_id'])) {
        $message = "Error: Admin not logged in.";
        header("Location: adminView.php?page=Account Approvals&message=" . urlencode($message));
        exit();
    }

    $admin_id = $_SESSION['admin_id'];
    // Verify admin_id exists in admins table
    $stmt = $conn->prepare("SELECT id FROM admins WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $message = "Error: Invalid admin ID.";
        header("Location: adminView.php?page=Account Approvals&message=" . urlencode($message));
        exit();
    }
    $stmt->close();

    $approval_id = $_POST['approval_id'];
    $action = $_POST['action'];
    $status = $action == 'approve' ? 'approved' : 'rejected';

    // Update approval status
    $stmt = $conn->prepare("UPDATE account_approvals SET approval_status = ?, approved_by = ?, approval_date = NOW() WHERE id = ?");
    $stmt->bind_param("sii", $status, $admin_id, $approval_id);
    $stmt->execute();

    // Fetch approval details for email
    $stmt = $conn->prepare("SELECT student_id, full_name, email, status FROM account_approvals WHERE id = ?");
    $stmt->bind_param("i", $approval_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $approval = $result->fetch_assoc();
    $stmt->close();

    $password = null;
    if ($action == 'approve') {
        // Generate password
        $password = bin2hex(random_bytes(8));
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (student_id, full_name, email, password, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $approval['student_id'], $approval['full_name'], $approval['email'], $hashedPassword, $approval['status']);
        $stmt->execute();
        $stmt->close();
    }

    // Send email notification
    $emailSent = sendEmailNotification($approval['email'], $approval['full_name'], $status, $password);

    $message = $emailSent ? "Request $status and email sent successfully" : "Request $status but email failed to send";
    header("Location: adminView.php?page=Account Approvals&message=" . urlencode($message));
    exit();
}
?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8" />
        <title>Admin Dashboard</title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/boxicons/css/boxicons.min.css" rel="stylesheet" />
        <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
        <link rel="stylesheet" href="../public/CSS/adminStyle.css" />
        <script src="../public/JAVASCRIPT/adminScript.js" defer></script>
        <link rel="icon" href="../public/image/Usep.png" sizes="any" />
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet" />
    </head>
    <body class="flex h-screen w-full relative bg-gray-100">
    <!-- Sidebar -->
    <div id="sidebar" class="sidebar">
        <div class="flex flex-col items-center mt-6 space-y-4">
            <img src="../public/image/SportOffice.png" alt="Logo" class="w-20 h-20" />
            <div class="text-center text-xs leading-tight">
                <p class="font-semibold">One Data. One USeP.</p>
                <p>USeP OSAS-Sports Unit</p>
            </div>
            <nav class="space-y-2 w-full px-2 mt-4">
                <?php
                $currentPage = isset($_GET['page']) ? $_GET['page'] : 'Achievement';
                $menu = ['Achievement', 'Documents', 'Evaluation', 'Reports', 'Users', 'Account Approvals', 'Log-out'];
                $icon = [
                    'Achievement' => "<box-icon name='trophy' type='solid' color='white'></box-icon>",
                    'Documents' => "<box-icon name='file-doc' type='solid' color='white'></box-icon>",
                    'Evaluation' => "<box-icon name='line-chart' color='white'></box-icon>",
                    'Reports' => "<box-icon name='report' type='solid' color='white'></box-icon>",
                    'Users' => "<box-icon name='user-circle' color='white'></box-icon>",
                    'Account Approvals' => "<box-icon name='user-check' color='white'></box-icon>",
                    'Log-out' => "<box-icon name='log-out' color='white'></box-icon>"
                ];
                foreach ($menu as $item) {
                    $isLogout = $item === 'Log-out';
                    $isActive = $item === $currentPage;
                    $class = $isActive ? 'menu-item active-menu' : 'menu-item';
                    $idAttr = $isLogout ? "id='logoutBtn' href='#'" : "href='?page=$item'";
                    echo "<a $idAttr class='$class' data-title='$item'>
                      <span class='menu-icon'>{$icon[$item]}</span>
                      <span class='menu-text'>$item</span>
                  </a>";
                }
                ?>
            </nav>
        </div>
        <div class="w-full px-2 mb-4">
            <button id="collapseBtn" class="menu-item w-full focus:outline-none">
                <box-icon id="collapseBoxIcon" name='collapse-horizontal' color='white'></box-icon>
                <span class="menu-text">Collapse Sidebar</span>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div id="mainContent" class="main-content px-1 sm:px-4 lg:px-0">
        <div class="sticky top-0 z-30 bg-gray-100 w-full px-1 sm:px-4 lg:px-3">
            <div class="border-b-4 border-red-500 px-5 pt-2 pb-1 flex justify-between items-center">
                <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 tracking-tight">
                    <?php echo htmlspecialchars($currentPage); ?>
                </h1>
            </div>
        </div>

        <?php if ($currentPage === 'Account Approvals'): ?>
            <?php
            // Fetch pending approvals
            $result = $conn->query("SELECT a.id, a.student_id, a.full_name, a.email, a.status, a.file_name, a.file_type, a.request_date FROM account_approvals a WHERE a.approval_status = 'pending'");
            ?>
            <div class="container p-4 sm:p-6">
                <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 mb-6">Pending Account Approvals</h1>
                <?php if (isset($_GET['message'])): ?>
                    <div class="message bg-green-100 border-l-4 border-green-500 p-4 mb-6 rounded-md">
                        <?php echo htmlspecialchars($_GET['message']); ?>
                    </div>
                <?php endif; ?>
                <table class="w-full border-collapse">
                    <thead>
                    <tr class="bg-gray-100">
                        <th class="p-3 text-left">Student ID</th>
                        <th class="p-3 text-left">Full Name</th>
                        <th class="p-3 text-left">Email</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-left">Document</th>
                        <th class="p-3 text-left">Request Date</th>
                        <th class="p-3 text-left">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="border-b">
                                <td class="p-3"><?php echo htmlspecialchars($row['student_id']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($row['email']); ?></td>
                                <td class="p-3"><?php echo htmlspecialchars($row['status']); ?></td>
                                <td class="p-3">
                                    <button class="view-btn bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded" onclick="showDocument(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['file_name'])); ?>', '<?php echo $row['file_type']; ?>')">View Document</button>
                                </td>
                                <td class="p-3"><?php echo htmlspecialchars($row['request_date']); ?></td>
                                <td class="p-3">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="approval_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="action-btn bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded mr-2">Approve</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="approval_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="action-btn bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="p-3 text-center text-gray-500">No pending approvals found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Modal for Document Preview -->
            <div id="documentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
                <div class="bg-white rounded-lg shadow-lg w-11/12 max-w-4xl p-6 relative">
                    <div id="documentPreview" class="overflow-auto max-h-[70vh]"></div>
                    <div class="flex justify-center mt-4">
                        <a id="downloadLink" href="#" class="modal-btn bg-cyan-500 hover:bg-cyan-600 text-white px-4 py-2 rounded mr-2">Download</a>
                        <button class="modal-btn bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded" onclick="closeModal()">Close</button>
                    </div>
                </div>
            </div>

            <script>
                async function showDocument(id, fileName, fileType) {
                    const modal = document.getElementById('documentModal');
                    const preview = document.getElementById('documentPreview');
                    const downloadLink = document.getElementById('downloadLink');

                    downloadLink.href = `../controller/downloadDocument.php?id=${id}&download=true`;
                    preview.innerHTML = '<p class="text-center">Loading...</p>';

                    try {
                        const response = await fetch(`../controller/downloadDocument.php?id=${id}`);
                        if (!response.ok) throw new Error('Failed to load document');

                        const blob = await response.blob();
                        const url = URL.createObjectURL(blob);

                        if (fileType === 'application/pdf') {
                            preview.innerHTML = `<iframe src="${url}" class="w-full h-[60vh]"></iframe>`;
                        } else if (fileType.startsWith('image/')) {
                            preview.innerHTML = `<img src="${url}" alt="${fileName}" class="max-w-full h-auto mx-auto" />`;
                        } else {
                            preview.innerHTML = '<p class="text-red-600 text-center">Preview not available for this file type.</p>';
                        }

                        modal.classList.remove('hidden');
                    } catch (error) {
                        preview.innerHTML = `<p class="text-red-600 text-center">Error loading document: ${error.message}</p>`;
                        modal.classList.remove('hidden');
                    }
                }

                function closeModal() {
                    const modal = document.getElementById('documentModal');
                    const preview = document.getElementById('documentPreview');
                    modal.classList.add('hidden');

                    const iframes = preview.getElementsByTagName('iframe');
                    const images = preview.getElementsByTagName('img');
                    for (let iframe of iframes) URL.revokeObjectURL(iframe.src);
                    for (let img of images) URL.revokeObjectURL(img.src);
                    preview.innerHTML = '';
                }

                window.onclick = function(event) {
                    const modal = document.getElementById('documentModal');
                    if (event.target === modal) closeModal();
                };
            </script>
        <?php endif; ?>

        <!-- Include other page sections (Users, Documents, etc.) here -->
        <!-- For brevity, only the Account Approvals section is shown. Replace this section in your original file. -->

        <!-- Message Modal -->
        <?php if (isset($_GET['message'])): ?>
            <div id="messageModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
                <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm animate-fadeIn">
                    <p class="text-center text-lg font-semibold mb-4">
                        <?php echo htmlspecialchars($_GET['message']); ?>
                    </p>
                    <button class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg w-full"
                            onclick="document.getElementById('messageModal').classList.add('hidden')">
                        OK
                    </button>
                </div>
            </div>
        <?php endif; ?>

    </body>
    </html>
<?php $conn->close(); ?>