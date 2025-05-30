<?php

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "SportOfficeDB";

        $this->conn = new mysqli($servername, $username, $password, $dbname);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }
}

// Secure session configuration
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600); // 1 hour (overriding 30 min for consistency)
session_set_cookie_params(0, '/'); // Ensure session cookie is available for all paths

// Start session and validate
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug: Log session state
file_put_contents('debug.log', 'Admin View - Session: ' . print_r($_SESSION, true) . "\n", FILE_APPEND);
file_put_contents('debug.log', 'Admin View - Session ID: ' . session_id() . "\n", FILE_APPEND);

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user']['id'])) {
    header("Location: ../view/loginView.php?message=" . urlencode("Please log in."));
    exit;
}

// Session timeout (30 minutes - overridden to 1 hour for consistency)
$session_timeout = 3600; // Changed to 1 hour to match other scripts
if (isset($_SESSION['user']['last_activity']) && (time() - $_SESSION['user']['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: ../view/loginView.php?timeout=1");
    exit;
}
$_SESSION['user']['last_activity'] = time();

// Generate CSRF token if not already set (for the form)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// PHPMailer-based email notification (assuming this is defined elsewhere)

// Helper function for user search
function searchUsers($searchTerm) {
    $conn = Database::getInstance();
    $searchTerm = strtolower(trim($searchTerm));
    $stmt = $conn->prepare("CALL SearchUsers(?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return [];
    }
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $users;
}

// Fetch pending approvals for the table
$conn = Database::getInstance();
$stmt = $conn->prepare("SELECT id, full_name, status FROM account_approvals WHERE approval_status = 'pending'");
$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle status messages from approveRequest.php
$status = $_GET['status'] ?? '';
$action = $_GET['action'] ?? '';
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
    <script src="../public/JAVASCRIPT/evalScipt.js" defer ></script>
    <link rel="icon" href="Usep.png" sizes="any" />
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
            $menu = ['Achievement', 'Approved Docs', 'Evaluation', 'Reports', 'Student Athletes', 'Account Approvals', 'Log-out'];
            $icon = [
                'Achievement' => "<box-icon name='trophy' type='solid' color='white'></box-icon>",
                'Approved Docs' => "<box-icon name='file-doc' type='solid' color='white'></box-icon>",
                'Evaluation' => "<box-icon name='line-chart' color='white'></box-icon>",
                'Reports' => "<box-icon name='report' type='solid' color='white'></box-icon>",
                'Student Athletes' => "<box-icon name='user-circle' color='white'></box-icon>",
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

    <!-- Collapse Button -->
    <div class="w-full px-2 mb-4">
        <button id="collapseBtn" class="menu-item w-full focus:outline-none">
            <box-icon id="collapseBoxIcon" name='collapse-horizontal' color='white'></box-icon>
            <span class="menu-text">Collapse Sidebar</span>
        </button>
    </div>
</div>

<!-- Main Content -->
<div id="mainContent" class="main-content px-1 sm:px-4 lg:px-0">
    <div class="sticky top-0 z-30 bg-gray-100 w-full px-1 sm:px-4 lg:px-3"><div class="border-b-4 border-red-500 px-5 pt-2 pb-1 flex justify-between items-center">
            <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 tracking-tight">
                <?php echo htmlspecialchars($currentPage); ?>
            </h1>
        </div>













































        <?php
        if ($currentPage === 'Student Athletes'):
        // Database configuration
        try {
            $conn = Database::getInstance();
            if (!$conn) {
                throw new Exception('Database connection failed.');
            }
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            echo '<div class="text-center py-12 text-red-500 font-medium">Unable to connect to the database. Please try again later.</div>';
            exit;
        }

        // Search and fetch users function
        function fetchUsers($searchTerm, $sport, $campus, $status, $conn, $page = 1, $perPage = 10) {
            $offset = ($page - 1) * $perPage;
            $searchTerm = trim($searchTerm);
            $sport = trim($sport);
            $campus = trim($campus);
            $status = trim($status);

            // Build base query
            $query = "
            SELECT u.id, u.student_id, u.full_name, u.address, u.sport, u.campus, u.status, ui.image, ui.image_type
            FROM users u
            LEFT JOIN user_images ui ON u.id = ui.user_id
            WHERE 1=1
        ";

            $params = [];
            $types = "";

            // Add search conditions
            if (!empty($searchTerm)) {
                $query .= " AND (u.student_id LIKE ? OR u.full_name LIKE ?)";
                $searchTerm = '%' . $conn->real_escape_string($searchTerm) . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= "ss";
            }

            if (!empty($sport)) {
                $query .= " AND u.sport = ?";
                $params[] = $sport;
                $types .= "s";
            }

            if (!empty($campus)) {
                $query .= " AND u.campus = ?";
                $params[] = $campus;
                $types .= "s";
            }

            if (!empty($status)) {
                $query .= " AND u.status = ?";
                $params[] = $status;
                $types .= "s";
            }

            $query .= " ORDER BY u.full_name ASC LIMIT ? OFFSET ?";
            $params[] = $perPage;
            $params[] = $offset;
            $types .= "ii";

            // Prepare and execute query
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                error_log("Prepare failed: " . $conn->error);
                return ['users' => [], 'total' => 0];
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $users = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Get total count for pagination
            $countQuery = "SELECT COUNT(*) as total FROM users WHERE 1=1";
            $countParams = [];
            $countTypes = "";

            if (!empty($searchTerm)) {
                $countQuery .= " AND (student_id LIKE ? OR full_name LIKE ?)";
                $countParams[] = $searchTerm;
                $countParams[] = $searchTerm;
                $countTypes .= "ss";
            }

            if (!empty($sport)) {
                $countQuery .= " AND sport = ?";
                $countParams[] = $sport;
                $countTypes .= "s";
            }

            if (!empty($campus)) {
                $countQuery .= " AND campus = ?";
                $countParams[] = $campus;
                $countTypes .= "s";
            }

            if (!empty($status)) {
                $countQuery .= " AND status = ?";
                $countParams[] = $status;
                $countTypes .= "s";
            }

            $countStmt = $conn->prepare($countQuery);
            if ($countStmt) {
                if (!empty($countParams)) {
                    $countStmt->bind_param($countTypes, ...$countParams);
                }
                $countStmt->execute();
                $total = $countStmt->get_result()->fetch_assoc()['total'];
                $countStmt->close();
            } else {
                $total = 0;
            }

            return ['users' => $users, 'total' => $total];
        }

        // Get parameters
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        $sport = isset($_GET['sport']) ? trim($_GET['sport']) : '';
        $campus = isset($_GET['campus']) ? trim($_GET['campus']) : '';
        $status = isset($_GET['status']) ? trim($_GET['status']) : '';
        $page = isset($_GET['page_num']) ? max(1, (int)$_GET['page_num']) : 1;
        $perPage = 10;

        // Fetch users
        $result = fetchUsers($searchTerm, $sport, $campus, $status, $conn, $page, $perPage);
        $users = $result['users'];
        $totalUsers = $result['total'];
        $totalPages = ceil($totalUsers / $perPage);
        ?>

        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <!-- Search Form -->
            <form method="GET" class="mb-6 flex flex-col sm:flex-row gap-3 items-center">
                <input type="hidden" name="page" value="Student Athletes"/>
                <div class="flex-1 w-full">
                    <input
                            type="text"
                            name="search"
                            placeholder="Search by Student ID or Name..."
                            value="<?php echo htmlspecialchars($searchTerm); ?>"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm"
                    >
                </div>
                <div class="flex-1 w-full">
                    <select
                            name="sport"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm bg-white"
                    >
                        <option value="" <?php echo empty($sport) ? 'selected' : ''; ?>>Select Sport (Optional)</option>
                        <option value="Athletics" <?php echo $sport === 'Athletics' ? 'selected' : ''; ?>>Athletics</option>
                        <option value="Badminton" <?php echo $sport === 'Badminton' ? 'selected' : ''; ?>>Badminton</option>
                        <option value="Basketball" <?php echo $sport === 'Basketball' ? 'selected' : ''; ?>>Basketball</option>
                        <option value="Chess" <?php echo $sport === 'Chess' ? 'selected' : ''; ?>>Chess</option>
                        <option value="Football" <?php echo $sport === 'Football' ? 'selected' : ''; ?>>Football</option>
                        <option value="Sepak Takraw" <?php echo $sport === 'Sepak Takraw' ? 'selected' : ''; ?>>Sepak Takraw</option>
                        <option value="Swimming" <?php echo $sport === 'Swimming' ? 'selected' : ''; ?>>Swimming</option>
                        <option value="Table Tennis" <?php echo $sport === 'Table Tennis' ? 'selected' : ''; ?>>Table Tennis</option>
                        <option value="Taekwondo" <?php echo $sport === 'Taekwondo' ? 'selected' : ''; ?>>Taekwondo</option>
                        <option value="Tennis" <?php echo $sport === 'Tennis' ? 'selected' : ''; ?>>Tennis</option>
                        <option value="Volleyball" <?php echo $sport === 'Volleyball' ? 'selected' : ''; ?>>Volleyball</option>
                    </select>
                </div>
                <div class="flex-1 w-full">
                    <select
                            name="campus"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm bg-white"
                    >
                        <option value="" <?php echo empty($campus) ? 'selected' : ''; ?>>Select Campus (Optional)</option>
                        <option value="Tagum" <?php echo $campus === 'Tagum' ? 'selected' : ''; ?>>Tagum</option>
                        <option value="Mabini" <?php echo $campus === 'Mabini' ? 'selected' : ''; ?>>Mabini</option>
                    </select>
                </div>
                <div class="flex-1 w-full">
                    <select
                            name="status"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm bg-white"
                    >
                        <option value="" <?php echo empty($status) ? 'selected' : ''; ?>>Select Status (Optional)</option>
                        <option value="undergraduate" <?php echo $status === 'undergraduate' ? 'selected' : ''; ?>>Undergraduate</option>
                        <option value="alumni" <?php echo $status === 'alumni' ? 'selected' : ''; ?>>Alumni</option>
                    </select>
                </div>
                <button
                        type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg flex items-center gap-2 transition-colors text-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Search
                </button>
            </form>

            <!-- Results Header -->
            <div class="hidden sm:grid grid-cols-12 gap-4 bg-gray-100 text-gray-700 font-semibold py-3 px-4 rounded-t-lg text-sm">
                <div class="col-span-2">Profile Picture</div>
                <div class="col-span-2">Student ID</div>
                <div class="col-span-2">Student Name</div>
                <div class="col-span-2">Sport</div>
                <div class="col-span-2">Campus</div>
                <div class="col-span-1">Status</div>

            </div>

            <!-- Results -->
            <div class="max-h-[calc(100vh-20rem)] overflow-y-auto rounded-b-lg border border-gray-200 bg-white">
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $row): ?>
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 p-4 border-b border-gray-100 hover:bg-gray-50 transition-colors text-sm">
                            <div class="col-span-1 sm:col-span-2 flex items-center">
                                <span class="block sm:hidden font-medium text-gray-600 text-xs">Profile Picture:</span>
                                <?php if (!empty($row['image']) && !empty($row['image_type'])): ?>
                                    <img
                                            src="data:<?php echo htmlspecialchars($row['image_type']); ?>;base64,<?php echo base64_encode($row['image']); ?>"
                                            alt="Profile picture of <?php echo htmlspecialchars($row['full_name']); ?>"
                                            class="w-12 h-12 rounded-full object-cover"
                                            onerror="this.src='/images/default-profile.png'"
                                    >
                                <?php else: ?>
                                    <img
                                            src="/images/default-profile.png"
                                            alt="Default profile picture"
                                            class="w-12 h-12 rounded-full object-cover"
                                    >
                                <?php endif; ?>
                            </div>
                            <div class="col-span-1 sm:col-span-2 flex items-center">
                                <span class="block sm:hidden font-medium text-gray-600 text-xs">Student ID:</span>
                                <?php echo htmlspecialchars($row['student_id']); ?>
                            </div>
                            <div class="col-span-1 sm:col-span-2 flex items-center">
                                <span class="block sm:hidden font-medium text-gray-600 text-xs">Name:</span>
                                <?php echo htmlspecialchars($row['full_name']); ?>
                            </div>
                            <div class="col-span-1 sm:col-span-2 flex items-center">
                                <span class="block sm:hidden font-medium text-gray-600 text-xs">Sport:</span>
                                <?php echo htmlspecialchars($row['sport'] ?? 'N/A'); ?>
                            </div>
                            <div class="col-span-1 sm:col-span-2 flex items-center">
                                <span class="block sm:hidden font-medium text-gray-600 text-xs">Campus:</span>
                                <?php echo htmlspecialchars($row['campus'] ?? 'N/A'); ?>
                            </div>
                            <div class="col-span-1 sm:col-span-1 flex items-center">
                                <span class="block sm:hidden font-medium text-gray-600 text-xs">Status:</span>
                                <?php echo htmlspecialchars(ucfirst($row['status'] ?? 'N/A')); ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500 font-medium text-sm">
                        <?php echo ($searchTerm || $sport || $campus || $status) ? 'No students found matching your search.' : 'No students available.'; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex flex-wrap justify-center gap-2">
                    <?php
                    $queryParams = $_GET;
                    $queryParams['page'] = 'Student Athletes';
                    for ($i = 1; $i <= $totalPages; $i++):
                        $queryParams['page_num'] = $i;
                        $isActive = $i === $page;
                        ?>
                        <a
                                href="?<?php echo http_build_query($queryParams); ?>"
                                class="px-4 py-2 rounded-lg text-sm <?php echo $isActive ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> transition-colors"
                        >
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>












        <style>
            /* Base styles */
            .max-w-7xl {
                max-width: 80rem;
            }

            .grid-cols-12 {
                display: grid;
                grid-template-columns: repeat(12, minmax(0, 1fr));
            }

            .col-span-2 {
                grid-column: span 2 / span 2;
            }

            .rounded-lg {
                border-radius: 0.5rem;
            }

            .text-sm {
                font-size: 0.875rem;
                line-height: 1.25rem;
            }

            .w-12 {
                width: 3rem;
            }

            .h-12 {
                height: 3rem;
            }

            /* Form and input styles */
            select, input[type="text"] {
                appearance: none;
                -webkit-appearance: none;
                -moz-appearance: none;
                height: 2.75rem;
            }

            select {
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 0.75rem center;
                background-size: 1rem;
            }

            /* Responsive adjustments */
            @media (max-width: 640px) {
                .max-w-7xl {
                    padding-left: 1rem;
                    padding-right: 1rem;
                }

                .grid-cols-12 {
                    grid-template-columns: 1fr;
                }

                .gap-4 {
                    gap: 0.75rem;
                }

                .p-4 {
                    padding: 1rem;
                }

                .py-3 {
                    padding-top: 0.75rem;
                    padding-bottom: 0.75rem;
                }

                .text-sm {
                    font-size: 0.85rem;
                }

                .w-12, .h-12 {
                    width: 2.5rem;
                    height: 2.5rem;
                }
            }

            @media (min-width: 640px) and (max-width: 1024px) {
                .grid-cols-12 {
                    grid-template-columns: repeat(12, minmax(0, 1fr));
                }
            }

            /* Profile picture styles */
            .rounded-full {
                border-radius: 50%;
            }

            .object-cover {
                object-fit: cover;
            }

            /* Hover and transition effects */
            .hover\:bg-gray-50:hover {
                background-color: #f9fafb;
            }

            .transition-colors {
                transition: background-color 0.2s ease, color 0.2s ease;
            }
        </style>




















        <?php
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        ?>
        <?php elseif ($currentPage === 'Achievement'): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" style="padding-top: 1rem;">
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <!-- Header -->
                <header class="bg-gradient-to-r from-red-600 to-orange-500 shadow-md rounded-lg p-8 mb-8" style="margin: 0;">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-white">Award Recognition</h1>
                            <p class="text-white text-base mt-1 opacity-80">One Data. One USeP.</p>
                        </div>
                        <img src="../public/image/Usep.png" alt="USeP Logo" class="h-16">
                    </div>
                </header>

                <!-- Campus Leaderboard -->
                <section class="p-8">
                    <h2 class="text-xl font-bold mb-4">Campus Leaderboard</h2>
                    <div class="mb-4">
                        <form method="GET" action="../view/adminView.php" class="flex items-center gap-4">
                            <input type="hidden" name="page" value="Achievement">
                            <label for="leaderboard_status" class="text-sm font-medium text-gray-700">Filter by Status:</label>
                            <select id="leaderboard_status" name="leaderboard_status" onchange="this.form.submit()" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                                <option value="undergraduate" <?php echo (!isset($_GET['leaderboard_status']) || $_GET['leaderboard_status'] === 'undergraduate') ? 'selected' : ''; ?>>Undergraduate</option>
                                <option value="alumni" <?php echo isset($_GET['leaderboard_status']) && $_GET['leaderboard_status'] === 'alumni' ? 'selected' : ''; ?>>Alumni</option>
                            </select>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Athlete Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Points</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $host = "localhost";
                            $username = "root";
                            $password = "";
                            $dbname = "SportOfficeDB";

                            $conn = new mysqli($host, $username, $password, $dbname);
                            if ($conn->connect_error) {
                                echo '<tr><td colspan="3" class="px-6 py-4 text-sm text-gray-500 text-center">Database connection failed</td></tr>';
                            } else {
                                $status = isset($_GET['leaderboard_status']) && in_array($_GET['leaderboard_status'], ['undergraduate', 'alumni']) ? $_GET['leaderboard_status'] : 'undergraduate';
                                $stmt = $conn->prepare("CALL GetLeaderboard(?)");
                                $stmt->bind_param("s", $status);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $leaderboard = [];
                                while ($row = $result->fetch_assoc()) {
                                    $leaderboard[] = $row;
                                }
                                if (count($leaderboard) > 0) {
                                    foreach ($leaderboard as $index => $athlete) {
                                        echo '<tr>';
                                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($athlete['athlete_name'] ?? 'N/A') . '</td>';
                                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($athlete['total_points'] ?? '0') . '</td>';
                                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . ($index + 1) . '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="3" class="px-6 py-4 text-sm text-gray-500 text-center">No leaderboard data available for ' . htmlspecialchars($status) . '</td></tr>';
                                }
                                $stmt->close();
                                $conn->close();
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Compact Filter Section with Search Bar -->
                <section class="p-8 border-t border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Filter Achievements</h2>
                    <form method="GET" action="../view/adminView.php" class="flex flex-col sm:flex-row gap-4 items-end">
                        <input type="hidden" name="page" value="Achievement">
                        <div class="w-full sm:w-auto">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search by Athlete Name</label>
                            <input type="text" id="search" name="search" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" placeholder="Enter athlete name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                        </div>
                        <div class="w-full sm:w-auto">
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                                <option value="">All</option>
                                <option value="undergraduate" <?php echo isset($_GET['status']) && $_GET['status'] === 'undergraduate' ? 'selected' : ''; ?>>Undergraduate</option>
                                <option value="alumni" <?php echo isset($_GET['status']) && $_GET['status'] === 'alumni' ? 'selected' : ''; ?>>Alumni</option>
                            </select>
                        </div>
                        <div class="w-full sm:w-auto">
                            <label for="sport" class="block text-sm font-medium text-gray-700 mb-1">Sport</label>
                            <select id="sport" name="sport" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500">
                                <option value="">All</option>
                                <?php
                                $sports = ['Basketball', 'Volleyball', 'Football', 'Swimming', 'Track and Field', 'Tennis', 'Table Tennis','Badminton'];
                                foreach ($sports as $sport) {
                                    echo '<option value="' . htmlspecialchars($sport) . '" ' . (isset($_GET['sport']) && $_GET['sport'] === $sport ? 'selected' : '') . '>' . htmlspecialchars($sport) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="w-full sm:w-auto">
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 w-full sm:w-auto">Apply</button>
                        </div>
                    </form>
                </section>

                <!-- Achievement Evaluation Section -->
                <section class="p-8 border-t border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Achievement Evaluation</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Athlete Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Performance</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submission Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $host = "localhost";
                            $username = "root";
                            $password = "";
                            $dbname = "SportOfficeDB";

                            $conn = new mysqli($host, $username, $password, $dbname);
                            if ($conn->connect_error) {
                                echo '<tr><td colspan="7" class="px-6 py-4 text-sm text-gray-500 text-center">Database connection failed</td></tr>';
                            } else {
                                $query = "SELECT a.achievement_id, a.athlete_name, a.level_of_competition, a.performance, a.total_points, a.submission_date, a.status, a.documents, a.rejection_reason, a.number_of_events, a.leadership_role, a.sportsmanship, a.community_impact, a.completeness_of_documents
                                 FROM achievements a
                                 LEFT JOIN users u ON a.user_id = u.id
                                 WHERE 1=1";
                                $params = [];
                                $types = "";

                                if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
                                    $query .= " AND a.athlete_name LIKE ?";
                                    $params[] = '%' . trim($_GET['search']) . '%';
                                    $types .= "s";
                                }
                                if (isset($_GET['status']) && in_array($_GET['status'], ['undergraduate', 'alumni'])) {
                                    $query .= " AND u.status = ?";
                                    $params[] = $_GET['status'];
                                    $types .= "s";
                                }
                                if (isset($_GET['sport']) && in_array($_GET['sport'], $sports)) {
                                    $query .= " AND u.sport = ?";
                                    $params[] = $_GET['sport'];
                                    $types .= "s";
                                }

                                $stmt = $conn->prepare($query);
                                if (!empty($params)) {
                                    $stmt->bind_param($types, ...$params);
                                }
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($row['athlete_name'] ?? 'N/A') . '</td>';
                                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($row['level_of_competition'] ?? 'N/A') . '</td>';
                                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($row['performance'] ?? 'N/A') . '</td>';
                                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($row['total_points'] ?? '0') . '</td>';
                                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($row['submission_date'] ?? 'N/A') . '</td>';
                                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($row['status'] ?? 'Pending') . '</td>';
                                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">';
                                        echo '<button onclick="openViewModal(' . htmlspecialchars($row['achievement_id']) . ', \'' . htmlspecialchars(json_encode($row, JSON_HEX_QUOT | JSON_HEX_APOS)) . '\')" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 mr-2">View</button>';
                                        if ($row['status'] === 'Pending') {
                                            echo '<button onclick="openApproveModal(' . htmlspecialchars($row['achievement_id']) . ')" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 mr-2">Approve</button>';
                                            echo '<button onclick="openRejectModal(' . htmlspecialchars($row['achievement_id']) . ')" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">Reject</button>';
                                        } elseif ($row['status'] === 'Rejected') {
                                            echo 'Rejected: ' . htmlspecialchars($row['rejection_reason'] ?? 'N/A');
                                        } else {
                                            echo 'Approved';
                                        }
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="px-6 py-4 text-sm text-gray-500 text-center">No achievements found</td></tr>';
                                }
                                $stmt->close();
                                $conn->close();
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- View Achievement Modal -->
                <div id="viewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
                    <div class="bg-white rounded-lg shadow p-6 w-full max-w-2xl max-h-[80vh] overflow-y-auto">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Achievement Details</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Athlete Name</label>
                                <p id="view_athlete_name" class="text-gray-900 font-medium"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Level of Competition</label>
                                <p id="view_level" class="text-gray-900 font-medium"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Performance</label>
                                <p id="view_performance" class="text-gray-900 font-medium"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Number of Events</label>
                                <p id="view_number_of_events" class="text-gray-900 font-medium"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Leadership Role</label>
                                <p id="view_leadership_role" class="text-gray-900 font-medium"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Sportsmanship</label>
                                <p id="view_sportsmanship" class="text-gray-900 font-medium"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Community Impact</label>
                                <p id="view_community_impact" class="text-gray-900 font-medium"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Completeness of Documents</label>
                                <p id="view_completeness_of_documents" class="text-gray-900 font-medium"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Total Points</label>
                                <p id="view_points" class="text-gray-900 font-medium"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Submission Date</label>
                                <p id="view_submission_date" class="text-gray-900 font-medium"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600">Status</label>
                                <p id="view_status" class="text-gray-900 font-medium"></p>
                            </div>
                            <div id="view_rejection_reason" class="hidden">
                                <label class="block text-xs font-medium text-gray-600">Rejection Reason</label>
                                <p id="view_rejection_text" class="text-gray-900 font-medium"></p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Documents</label>
                            <div id="view_documents" class="flex flex-wrap gap-2"></div>
                        </div>
                        <div class="flex justify-end mt-4">
                            <button type="button" onclick="closeViewModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 text-sm">Close</button>
                        </div>
                    </div>
                </div>

                <!-- Document Viewer Modal -->
                <div id="documentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
                    <div class="bg-white rounded-lg shadow p-6 w-full max-w-3xl">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Document Viewer</h2>
                        <div id="documentContent" class="mb-4 max-h-[60vh] overflow-auto">
                            <!-- Document content will be loaded here -->
                        </div>
                        <div class="flex justify-end space-x-4">
                            <a id="downloadLink" href="#" download class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">Download</a>
                            <button type="button" onclick="closeDocumentModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 text-sm">Close</button>
                        </div>
                    </div>
                </div>

                <!-- Approve Achievement Modal -->
                <div id="approveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
                    <div class="bg-white rounded-lg shadow p-6 w-full max-w-md">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Approve Achievement</h2>
                        <p class="text-sm text-gray-600 mb-4">Are you sure you want to approve this achievement?</p>
                        <form id="approveForm" method="POST" action="../controller/handleAdminAchievement.php">
                            <input type="hidden" name="achievement_id" id="approve_achievement_id">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">Confirm</button>
                            <button type="button" onclick="closeApproveModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 text-sm ml-2">Cancel</button>
                        </form>
                    </div>
                </div>

                <!-- Reject Achievement Modal -->
                <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
                    <div class="bg-white rounded-lg shadow p-6 w-full max-w-md">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">Reject Achievement</h2>
                        <p class="text-sm text-gray-600 mb-4">Are you sure you want to reject this achievement?</p>
                        <textarea id="reject_reason" name="reject_reason" class="w-full px-3 py-2 border border-gray-300 rounded-lg mb-4" placeholder="Enter rejection reason (optional)"></textarea>
                        <form id="rejectForm" method="POST" action="../controller/handleAdminAchievement.php">
                            <input type="hidden" name="achievement_id" id="reject_achievement_id">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="reject_reason" id="reject_reason_input">
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">Confirm</button>
                            <button type="button" onclick="closeRejectModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 text-sm ml-2">Cancel</button>
                        </form>
                    </div>
                </div>

                <script>
                    function openViewModal(id, data) {
                        const achievement = JSON.parse(data);
                        document.getElementById('view_athlete_name').textContent = achievement.athlete_name || 'N/A';
                        document.getElementById('view_level').textContent = achievement.level_of_competition || 'N/A';
                        document.getElementById('view_performance').textContent = achievement.performance || 'N/A';
                        document.getElementById('view_number_of_events').textContent = achievement.number_of_events || 'N/A';
                        document.getElementById('view_leadership_role').textContent = achievement.leadership_role || 'N/A';
                        document.getElementById('view_sportsmanship').textContent = achievement.sportsmanship || 'N/A';
                        document.getElementById('view_community_impact').textContent = achievement.community_impact || 'N/A';
                        document.getElementById('view_completeness_of_documents').textContent = achievement.completeness_of_documents || 'N/A';
                        document.getElementById('view_points').textContent = achievement.total_points || '0';
                        document.getElementById('view_submission_date').textContent = achievement.submission_date || 'N/A';
                        document.getElementById('view_status').textContent = achievement.status || 'Pending';

                        const rejectionDiv = document.getElementById('view_rejection_reason');
                        const rejectionText = document.getElementById('view_rejection_text');
                        if (achievement.status === 'Rejected' && achievement.rejection_reason) {
                            rejectionText.textContent = achievement.rejection_reason;
                            rejectionDiv.classList.remove('hidden');
                        } else {
                            rejectionText.textContent = '';
                            rejectionDiv.classList.add('hidden');
                        }

                        const documentsDiv = document.getElementById('view_documents');
                        documentsDiv.innerHTML = '';
                        if (achievement.documents) {
                            const docs = achievement.documents.split(',');
                            if (docs.length > 0 && docs[0] !== '') {
                                docs.forEach(doc => {
                                    const link = document.createElement('a');
                                    link.href = '#';
                                    link.textContent = doc;
                                    link.className = 'inline-block px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs hover:bg-blue-200';
                                    link.onclick = () => openDocumentModal(doc);
                                    documentsDiv.appendChild(link);
                                });
                            } else {
                                documentsDiv.textContent = 'No documents available';
                            }
                        } else {
                            documentsDiv.textContent = 'No documents available';
                        }

                        document.getElementById('viewModal').classList.remove('hidden');
                    }

                    function closeViewModal() {
                        document.getElementById('viewModal').classList.add('hidden');
                        document.getElementById('view_documents').innerHTML = '';
                        document.getElementById('view_rejection_reason').classList.add('hidden');
                    }

                    function openDocumentModal(filename) {
                        const documentContent = document.getElementById('documentContent');
                        const downloadLink = document.getElementById('downloadLink');
                        const filePath = `../Uploads/${filename}`;
                        const ext = filename.split('.').pop().toLowerCase();

                        documentContent.innerHTML = '';
                        downloadLink.href = filePath;

                        if (['jpg', 'jpeg', 'png'].includes(ext)) {
                            const img = document.createElement('img');
                            img.src = filePath;
                            img.alt = filename;
                            img.className = 'max-w-full h-auto';
                            documentContent.appendChild(img);
                        } else if (ext === 'pdf') {
                            const embed = document.createElement('embed');
                            embed.src = filePath;
                            embed.type = 'application/pdf';
                            embed.className = 'w-full h-[50vh]';
                            documentContent.appendChild(embed);
                        } else {
                            documentContent.textContent = 'Preview not available for this file type.';
                        }

                        document.getElementById('documentModal').classList.remove('hidden');
                    }

                    function closeDocumentModal() {
                        document.getElementById('documentModal').classList.add('hidden');
                        document.getElementById('documentContent').innerHTML = '';
                    }

                    function openApproveModal(id) {
                        console.log('Opening approve modal for ID:', id); // Debug log
                        document.getElementById('approve_achievement_id').value = id;
                        document.getElementById('approveModal').classList.remove('hidden');
                    }

                    function closeApproveModal() {
                        document.getElementById('approveModal').classList.add('hidden');
                    }

                    function openRejectModal(id) {
                        console.log('Opening reject modal for ID:', id); // Debug log
                        document.getElementById('reject_achievement_id').value = id;
                        document.getElementById('reject_reason_input').value = '';
                        document.getElementById('rejectModal').classList.remove('hidden');
                    }

                    function closeRejectModal() {
                        document.getElementById('rejectModal').classList.add('hidden');
                    }

                    // Submit handler for reject form to capture textarea value
                    document.getElementById('rejectForm').addEventListener('submit', function(e) {
                        document.getElementById('reject_reason_input').value = document.getElementById('reject_reason').value;
                    });
                </script>









        <?php elseif ($currentPage === 'Reports'): ?>
        <div class="p-4 sm:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <!-- Total Athletes Card -->
                <a href="?page=Student Athletes" class="bg-white rounded-xl shadow p-4 flex items-center space-x-4 hover:bg-gray-50 transition">
                    <div class="flex-shrink-0 w-12 h-12 sm:w-16 sm:h-16 flex justify-center items-center text-red-500 bg-red-100 rounded-full text-2xl">
                        <i class='bx bxs-user-account'></i>
                    </div>
                    <div>
                        <p class="text-gray-800 font-semibold text-sm sm:text-base">Total Athletes</p>
                        <?php
                        $conn = Database::getInstance();
                        $totalStudents = 0;
                        try {
                            if (!$conn || $conn->connect_error) {
                                error_log("Database connection failed in Total Athletes: " . ($conn ? $conn->connect_error : "No connection"));
                                echo "<p class='text-red-600'>Unable to fetch athlete count. Please try again later.</p>";
                            } else {
                                if ($result = $conn->query("CALL GetTotalStudents()")) {
                                    if ($row = $result->fetch_assoc()) {
                                        $totalStudents = $row['total'];
                                    }
                                    $result->free();
                                    while ($conn->more_results() && $conn->next_result()) {
                                        if ($extra_result = $conn->store_result()) {
                                            $extra_result->free();
                                        }
                                    }
                                } else {
                                    error_log("Error calling GetTotalStudents: " . $conn->error);
                                    echo "<p class='text-red-600'>Unable to fetch athlete count. Please try again later.</p>";
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Exception in Total Athletes query: " . $e->getMessage());
                            echo "<p class='text-red-600'>An error occurred. Please try again later.</p>";
                        }
                        ?>
                        <p class="text-2xl sm:text-3xl font-bold text-gray-900"><?= htmlspecialchars($totalStudents) ?></p>
                    </div>
                </a>
                <!-- Approved Reports Card -->



                <a href="?page=Approved Docs" class="bg-white rounded-xl shadow p-4 flex items-center space-x-4 hover:bg-gray-50 transition">
                    <div class="flex-shrink-0 w-12 h-12 sm:w-16 sm:h-16 flex justify-center items-center text-green-600 bg-green-100 rounded-full text-2xl">
                        <i class='bx bxs-file-doc'></i>
                    </div>
                    <div>
                        <p class="text-gray-800 font-semibold text-sm sm:text-base">Verified Documents</p>
                        <?php
                        $approvedCount = 0;
                        try {
                            if (!$conn || $conn->connect_error) {
                                error_log("Database connection failed in Approved Reports: " . ($conn ? $conn->connect_error : "No connection"));
                                echo "<p class='text-red-600'>Unable to fetch approved reports count. Please try again later.</p>";
                            } else {
                                $query = "SELECT COUNT(*) as total FROM submissions WHERE status = 'approved'";
                                if ($result = $conn->query($query)) {
                                    if ($row = $result->fetch_assoc()) {
                                        $approvedCount = $row['total'];
                                    }
                                    $result->free();
                                } else {
                                    error_log("Error fetching approved count: " . $conn->error);
                                    echo "<p class='text-red-600'>Unable to fetch approved reports count. Please try again later.</p>";
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Exception in Approved Reports query: " . $e->getMessage());
                            echo "<p class='text-red-600'>An error occurred. Please try again later.</p>";
                        }
                        ?>
                        <p class="text-2xl sm:text-3xl font-bold text-gray-900"><?= htmlspecialchars($approvedCount) ?></p>
                    </div>
                </a>

















                <!-- Pending Account Approvals Card -->
                <a href="?page=Account Approvals" class="bg-white rounded-xl shadow p-4 flex items-center space-x-4 hover:bg-gray-50 transition">
                    <div class="flex-shrink-0 w-12 h-12 sm:w-16 sm:h-16 flex justify-center items-center text-green-600 bg-green-100 rounded-full text-2xl">
                    <i class='bx bxs-user-check'></i>

                    </div>

                    <div>
                        <p class="text-gray-800 font-semibold text-sm sm:text-base">Pending Account Approvals</p>
                        <?php
                        $pendingApprovals = 0;
                        try {
                            if (!$conn || $conn->connect_error) {
                                error_log("Database connection failed in Pending Approvals: " . ($conn ? $conn->connect_error : "No connection"));
                                echo "<p class='text-red-600'>Unable to fetch pending approvals count. Please try again later.</p>";
                            } else {
                                $query = "SELECT COUNT(*) as total FROM account_approvals WHERE approval_status = 'pending'";
                                if ($result = $conn->query($query)) {
                                    if ($row = $result->fetch_assoc()) {
                                        $pendingApprovals = $row['total'];
                                    }
                                    $result->free();
                                } else {
                                    error_log("Error fetching pending approvals count: " . $conn->error);
                                    echo "<p class='text-red-600'>Unable to fetch pending approvals count. Please try again later.</p>";
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Exception in Pending Approvals query: " . $e->getMessage());
                            echo "<p class='text-red-600'>An error occurred. Please try again later.</p>";
                        }
                        ?>
                        <p class="text-2xl sm:text-3xl font-bold text-gray-900"><?= htmlspecialchars($pendingApprovals) ?></p>
                    </div>
                </a>
















                <a href="?page=Evaluation"class="bg-white rounded-xl shadow p-4 flex items-center space-x-4 hover:bg-gray-50 transition">
                    <div class="flex-shrink-0 w-12 h-12 sm:w-16 sm:h-16 flex justify-center items-center text-green-600 bg-green-100 rounded-full text-2xl">

                    <i class='bx bxs-bar-chart-alt-2'></i>
                    </div>

                    <div>

                        <p class="text-gray-800 font-semibold text-sm sm:text-base">Pending Submissions</p>


                        <?php
                        $pendingCount = 0;

                        try {
                            if (!$conn || $conn->connect_error) {
                                error_log("Database connection failed: " . ($conn ? $conn->connect_error : "No connection"));
                                echo "<p class='text-red-600 text-sm'>Unable to connect to the database. Please try again later.</p>";
                            } else {
                                // Query for pending submissions
                                $pendingQuery = "SELECT COUNT(*) as total FROM submissions WHERE status = 'pending'";
                                if ($pendingResult = $conn->query($pendingQuery)) {
                                    if ($pendingRow = $pendingResult->fetch_assoc()) {
                                        $pendingCount = $pendingRow['total'];
                                    }
                                    $pendingResult->free();
                                } else {
                                    error_log("Error fetching pending count: " . $conn->error);
                                    echo "<p class='text-red-600 text-sm'>Unable to fetch pending submissions count.</p>";
                                }

                                // Query for approved (verified) documents
                                $approvedQuery = "SELECT COUNT(*) as total FROM submissions WHERE status = 'approved'";
                                if ($approvedResult = $conn->query($approvedQuery)) {
                                    if ($approvedRow = $approvedResult->fetch_assoc()) {
                                        $approvedCount = $approvedRow['total'];
                                    }
                                    $approvedResult->free();
                                } else {
                                    error_log("Error fetching approved count: " . $conn->error);
                                    echo "<p class='text-red-600 text-sm'>Unable to fetch verified documents count.</p>";
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Exception in submissions query: " . $e->getMessage());
                            echo "<p class='text-red-600 text-sm'>An error occurred. Please try again later.</p>";
                        }
                        ?>



                        <p class="text-2xl sm:text-3xl font-bold text-gray-900"><?= htmlspecialchars( $pendingCount) ?></p>
                    </div>


                </a>




























                <a href="?page=Achievement" class="bg-white rounded-xl shadow p-4 flex justify-center items-center space-x-4 hover:bg-gray-50 transition col-span-1 md:col-span-2">
                    <div class="flex-shrink-0 w-12 h-12 sm:w-16 sm:h-16 flex justify-center items-center text-blue-600 bg-blue-100 rounded-full text-2xl">
                        <i class='bx bxs-trophy'></i>
                    </div>

                    <div class="flex flex-col justify-center space-y-2 text-center mx-auto">
                        <p class="text-gray-800 font-semibold text-sm sm:text-base">Total Achivements</p>
                        <?php
                        $totalAchievements = 0;
                        try {
                            if (!$conn || $conn->connect_error) {
                                error_log("Database connection failed in Total Achievements: " . ($conn ? $conn->connect_error : "No connection"));
                                echo "<p class='text-red-600'>Unable to fetch achievements count. Please try again later.</p>";
                            } else {
                                $query = "SELECT COUNT(*) as total FROM achievements";
                                if ($result = $conn->query($query)) {
                                    if ($row = $result->fetch_assoc()) {
                                        $totalAchievements = $row['total'];
                                    }
                                    $result->free();
                                } else {
                                    error_log("Error fetching achievements count: " . $conn->error);
                                    echo "<p class='text-red-600'>Unable to fetch achievements count. Please try again later.</p>";
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Exception in Total Achievements query: " . $e->getMessage());
                            echo "<p class='text-red-600'>An error occurred. Please try again later.</p>";
                        }
                        ?>

                    <p class="text-lg sm:text-xl font-bold text-gray-900"><?= htmlspecialchars($totalAchievements) ?></p>
                    </div>
                </a>












                <!-- Athletes by Campus Card (Unchanged) -->
                <a href="?page=Student Athletes" class="bg-white rounded-xl shadow p-4 flex justify-center items-center space-x-4 hover:bg-gray-50 transition col-span-1 md:col-span-2">
                    <div class="flex-shrink-0 w-12 h-12 sm:w-16 sm:h-16 flex justify-center items-center text-blue-600 bg-blue-100 rounded-full text-2xl">
                    <i class='bx bxs-school'></i>
                    </div>

                    <div class="flex flex-col justify-center space-y-2 text-center mx-auto">
                        <p class="text-gray-800 font-semibold text-sm sm:text-base">Athletes by Campus</p>
                        <?php
                        $tagumCount = 0;
                        $mabiniCount = 0;
                        try {
                            if (!$conn || $conn->connect_error) {
                                error_log("Database connection failed in Athletes by Campus: " . ($conn ? $conn->connect_error : "No connection"));
                                echo "<p class='text-red-600'>Unable to fetch campus counts. Please try again later.</p>";
                            } else {
                                $query = "SELECT campus, COUNT(*) as total FROM users WHERE campus IN ('Tagum', 'Mabini') GROUP BY campus";
                                if ($result = $conn->query($query)) {
                                    while ($row = $result->fetch_assoc()) {
                                        if ($row['campus'] === 'Tagum') {
                                            $tagumCount = $row['total'];
                                        } elseif ($row['campus'] === 'Mabini') {
                                            $mabiniCount = $row['total'];
                                        }
                                    }
                                    $result->free();
                                } else {
                                    error_log("Error fetching campus counts: " . $conn->error);
                                    echo "<p class='text-red-600'>Unable to fetch campus counts. Please try again later.</p>";
                                }
                            }
                        } catch (Exception $e) {
                            error_log("Exception in Athletes by Campus query: " . $e->getMessage());
                            echo "<p class='text-red-600'>An error occurred. Please try again later.</p>";
                        }
                        ?>
                        <p class="text-lg sm:text-xl font-bold text-gray-900">Tagum: <?= htmlspecialchars($tagumCount) ?> | Mabini: <?= htmlspecialchars($mabiniCount) ?></p>
                    </div>
                </a>






                <?php
                // Define UPLOAD_BASE_PATH globally
                define('UPLOAD_BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/Uploads/');
                ?>

                <?php elseif ($currentPage === 'Approved Docs'): ?>
                <?php
                // Database configuration (Fallback if Database class is not available)
                if (!class_exists('Database')) {
                    $conn = new mysqli("localhost", "root", "", "SportOfficeDB");
                    if ($conn->connect_error) {
                        die("Connection failed: " . $conn->connect_error);
                    }
                    $conn->set_charset("utf8mb4");
                } else {
                    $conn = Database::getInstance();
                }

                // Search users function with status, sport, and campus filters
                function searchUsersEval($searchTerm, $statusFilter, $sportFilter, $campusFilter, $conn) {
                    $searchTerm = '%' . $conn->real_escape_string(trim($searchTerm)) . '%';
                    $statusFilter = $conn->real_escape_string(trim($statusFilter));
                    $sportFilter = $conn->real_escape_string(trim($sportFilter));
                    $campusFilter = $conn->real_escape_string(trim($campusFilter));

                    $query = "
            SELECT id, student_id, full_name, sport, campus, status
            FROM users
            WHERE (student_id LIKE ? OR full_name LIKE ?)
        ";
                    $params = [$searchTerm, $searchTerm];
                    $types = "ss";

                    if (!empty($statusFilter)) {
                        $query .= " AND status = ?";
                        $params[] = $statusFilter;
                        $types .= "s";
                    }
                    if (!empty($sportFilter)) {
                        $query .= " AND sport = ?";
                        $params[] = $sportFilter;
                        $types .= "s";
                    }
                    if (!empty($campusFilter)) {
                        $query .= " AND campus = ?";
                        $params[] = $campusFilter;
                        $types .= "s";
                    }

                    $stmt = $conn->prepare($query);
                    if (!$stmt) {
                        error_log("Prepare failed: " . $conn->error);
                        return [];
                    }

                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                    return $result;
                }

                // Get search term and filters from GET request
                $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
                $statusFilter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
                $sportFilter = isset($_GET['sports_filter']) ? trim($_GET['sports_filter']) : '';
                $campusFilter = isset($_GET['campus_filter']) ? trim($_GET['campus_filter']) : '';
                $users = searchUsersEval($searchTerm, $statusFilter, $sportFilter, $campusFilter, $conn);

                // Fetch only approved submissions for displayed users
                $userIds = array_column($users, 'id');
                $submissionsByUser = [];
                if (!empty($userIds)) {
                    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                    $stmt = $conn->prepare("
            SELECT id, user_id, document_type, submission_date, status, file_name, other_type, comments
            FROM submissions
            WHERE user_id IN ($placeholders) AND status = 'approved'
            ORDER BY submission_date DESC
        ");
                    if ($stmt) {
                        $stmt->bind_param(str_repeat('i', count($userIds)), ...$userIds);
                        $stmt->execute();
                        $allSubmissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        $stmt->close();

                        foreach ($allSubmissions as $sub) {
                            $submissionsByUser[$sub['user_id']][] = $sub;
                        }
                    } else {
                        error_log("Prepare failed: " . $conn->error);
                    }
                }

                // Count approved submissions for the table display
                $approvedCounts = [];
                foreach ($submissionsByUser as $userId => $subs) {
                    $approvedCounts[$userId] = count($subs);
                }

                // Sample sports options (adjust based on your database)
                $sportsOptions = ['Basketball', 'Volleyball', 'Football', 'Swimming', 'Others'];
                ?>

                <div class="w-full px-4 sm:px-6 lg:px-8 mt-6">
                    <!-- Search and Filter Form -->
                    <form method="GET" action="" class="flex flex-col sm:flex-row items-center gap-4 w-full mb-6">
                        <input type="hidden" name="page" value="Approved Docs"/>
                        <div class="flex-1 min-w-[200px] w-full sm:w-auto">
                            <input type="text" name="search" placeholder="Search by Student ID or Name..."
                                   value="<?php echo htmlspecialchars($searchTerm); ?>"
                                   class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm text-sm">
                        </div>
                        <select name="status_filter" class="w-full sm:w-auto px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm text-sm">
                            <option value="">All Statuses</option>
                            <option value="undergraduate" <?php echo $statusFilter === 'undergraduate' ? 'selected' : ''; ?>>Undergraduate</option>
                            <option value="alumni" <?php echo $statusFilter === 'alumni' ? 'selected' : ''; ?>>Alumni</option>
                        </select>
                        <select name="sports_filter" class="w-full sm:w-auto px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm text-sm">
                            <option value="">All Sports</option>
                            <?php foreach ($sportsOptions as $sport): ?>
                                <option value="<?php echo htmlspecialchars($sport); ?>" <?php echo $sportFilter === $sport ? 'selected' : ''; ?>><?php echo htmlspecialchars($sport); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="campus_filter" class="w-full sm:w-auto px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm text-sm">
                            <option value="">All Campuses</option>
                            <option value="Tagum" <?php echo $campusFilter === 'Tagum' ? 'selected' : ''; ?>>Tagum</option>
                            <option value="Mabini" <?php echo $campusFilter === 'Mabini' ? 'selected' : ''; ?>>Mabini</option>
                        </select>
                        <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 shadow-sm transition-colors duration-200 text-sm">
                            <span>Search</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </form>

                    <!-- Approved Documents Table -->
                    <div class="w-full overflow-x-auto bg-white rounded-lg shadow-md">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Approved Documents</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                    <?php
                                    $userId = $user['id'];
                                    $approvedCount = $approvedCounts[$userId] ?? 0;
                                    $submissions = $submissionsByUser[$userId] ?? [];
                                    ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['student_id']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $approvedCount; ?> Document<?php echo $approvedCount !== 1 ? 's' : ''; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button class="view-details-btn text-blue-600 hover:text-blue-800"
                                                    title="View Details"
                                                    aria-label="View submission details"
                                                    data-student-id="<?php echo htmlspecialchars($user['student_id']); ?>"
                                                    data-full-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                    data-submissions='<?php echo htmlspecialchars(json_encode($submissions, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>'>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                        <div class="font-semibold mb-2">No approved documents found</div>
                                        <div class="text-sm text-gray-400">Try adjusting your search criteria or filters</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Add CSRF token input (for potential future use) -->
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">

                <!-- Evaluation Modal -->
                <div id="evaluationModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden" role="dialog" aria-labelledby="evaluationModalTitle" aria-modal="true">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-3xl mx-4">
                        <div class="relative mb-4">
                            <h2 id="evaluationModalTitle" class="text-lg font-semibold text-gray-800">View Approved Submissions</h2>
                            <button onclick="closeModal('evaluationModal')" class="absolute top-0 right-0 text-gray-400 hover:text-gray-600 transition-colors" aria-label="Close modal">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="mb-4">
                            <p class="text-sm text-gray-700"><strong>Student ID:</strong> <span id="modal_student_id"></span></p>
                            <p class="text-sm text-gray-700"><strong>Name:</strong> <span id="modal_full_name"></span></p>
                        </div>
                        <div id="submissions_list" class="space-y-4 max-h-96 overflow-y-auto">
                            <!-- Submissions will be populated by JavaScript -->
                        </div>
                        <div class="mt-4 flex justify-center">
                            <button onclick="closeModal('evaluationModal')" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 transition-colors">
                                Close
                            </button>
                        </div>
                    </div>
                </div>

                <!-- File View Modal -->
                <div id="fileViewModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden" role="dialog" aria-labelledby="fileViewModalTitle" aria-modal="true">
                    <div class="bg-white rounded-2xl shadow-2xl p-4 w-[95vw] max-w-[1440px] h-[95vh] max-h-[1080px] border border-gray-200 flex flex-col">
                        <div class="modal-header relative mb-2">
                            <h2 id="fileViewModalTitle" class="text-lg font-semibold text-gray-800">View Approved File</h2>
                            <button onclick="closeModal('fileViewModal')" class="modal-close-btn absolute top-0 right-0 text-gray-400 hover:text-gray-600 transition-colors" aria-label="Close modal">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div id="fileViewPreview" class="w-full h-full flex-1 bg-gray-50 rounded-lg overflow-hidden"></div>
                        <div class="modal-footer mt-2 flex justify-center gap-2 bg-gray-100 p-2 rounded-b-2xl">
                            <a id="fileDownloadLink" href="#" class="action-btn bg-red-600 hover:bg-red-700 text-white py-1 px-3 rounded-lg flex items-center gap-1 transition-colors duration-200 focus:outline-none hidden">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Download
                            </a>
                            <button onclick="closeModal('fileViewModal')" class="action-btn bg-blue-600 hover:bg-blue-700 text-white py-1 px-3 rounded-lg flex items-center gap-1 transition-colors duration-200 focus:outline-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Close
                            </button>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        console.log('DOM loaded, initializing Approved Docs script');

                        // Close modal function
                        function closeModal(modalId) {
                            const modal = document.getElementById(modalId);
                            if (modal) {
                                modal.classList.add('hidden');
                                console.log(`Closed modal: ${modalId}`);
                            } else {
                                console.error(`Modal with ID ${modalId} not found`);
                            }
                        }

                        // Handle view details button clicks
                        const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
                        if (!viewDetailsButtons.length) {
                            console.warn('No view-details-btn elements found in the DOM');
                        }

                        viewDetailsButtons.forEach(button => {
                            button.addEventListener('click', function(event) {
                                event.preventDefault();
                                console.log('View Details button clicked for student ID:', this.getAttribute('data-student-id'));

                                const studentId = this.getAttribute('data-student-id');
                                const fullName = this.getAttribute('data-full-name');
                                let submissions = this.getAttribute('data-submissions');

                                console.log('Raw submissions data:', submissions);

                                try {
                                    submissions = JSON.parse(submissions || '[]');
                                    console.log('Parsed submissions:', submissions);
                                } catch (e) {
                                    console.error('Error parsing submissions JSON:', e);
                                    submissions = [];
                                }

                                const modalStudentId = document.getElementById('modal_student_id');
                                const modalFullName = document.getElementById('modal_full_name');
                                if (modalStudentId && modalFullName) {
                                    modalStudentId.textContent = studentId || 'N/A';
                                    modalFullName.textContent = fullName || 'N/A';
                                } else {
                                    console.error('Modal student ID or full name elements not found');
                                }

                                const submissionsList = document.getElementById('submissions_list');
                                if (!submissionsList) {
                                    console.error('Submissions list element not found');
                                    return;
                                }
                                submissionsList.innerHTML = '';

                                if (!Array.isArray(submissions) || submissions.length === 0) {
                                    console.log('No approved submissions to display');
                                    submissionsList.innerHTML = '<p class="text-sm text-gray-500">No approved submissions found for this student.</p>';
                                } else {
                                    submissions.forEach(sub => {
                                        console.log('Processing submission:', sub);
                                        const submissionDiv = document.createElement('div');
                                        submissionDiv.className = 'bg-gray-50 p-4 rounded-lg shadow-sm';
                                        submissionDiv.innerHTML = `
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-700"><strong>Document Type:</strong> ${sub.document_type || 'N/A'}${sub.other_type ? ` (${sub.other_type})` : ''}</p>
                                    <p class="text-sm text-gray-700"><strong>Submission Date:</strong> ${sub.submission_date ? new Date(sub.submission_date).toLocaleDateString() : 'N/A'}</p>
                                    <p class="text-sm text-gray-700"><strong>Status:</strong> Approved</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-700"><strong>File Name:</strong> ${sub.file_name || 'N/A'}</p>
                                    <p class="text-sm text-gray-700"><strong>Comments:</strong> ${sub.comments || 'None'}</p>
                                </div>
                            </div>
                            <div class="mt-2 flex justify-end gap-2">
                                <button class="view-file-btn bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm transition-colors"
                                        data-file-name="${sub.file_name || ''}"
                                        data-submission-id="${sub.id || ''}">
                                    View File
                                </button>
                            </div>
                        `;
                                        submissionsList.appendChild(submissionDiv);
                                    });
                                }

                                const modal = document.getElementById('evaluationModal');
                                if (modal) {
                                    console.log('Showing evaluation modal');
                                    modal.classList.remove('hidden');
                                } else {
                                    console.error('Evaluation modal not found in the DOM');
                                }
                            });
                        });

                        // Handle file view button clicks with event delegation
                        document.body.addEventListener('click', function(e) {
                            if (e.target.classList.contains('view-file-btn')) {
                                console.log('View File button clicked for file:', e.target.getAttribute('data-file-name'));
                                const fileName = e.target.getAttribute('data-file-name');
                                const fileViewPreview = document.getElementById('fileViewPreview');
                                const fileDownloadLink = document.getElementById('fileDownloadLink');
                                const uploadPath = '<?php echo str_replace($_SERVER['DOCUMENT_ROOT'], '', UPLOAD_BASE_PATH); ?>';
                                const filePath = uploadPath + fileName;

                                console.log('File path:', filePath);

                                if (!fileViewPreview || !fileDownloadLink) {
                                    console.error('File view preview or download link elements not found');
                                    return;
                                }

                                fileViewPreview.innerHTML = '';
                                fileDownloadLink.setAttribute('href', filePath);
                                fileDownloadLink.classList.remove('hidden');

                                const ext = fileName.split('.').pop().toLowerCase();
                                if (['jpg', 'jpeg', 'png'].includes(ext)) {
                                    console.log('Rendering image:', filePath);
                                    const img = document.createElement('img');
                                    img.src = filePath;
                                    img.alt = fileName;
                                    img.className = 'max-w-full h-auto';
                                    fileViewPreview.appendChild(img);
                                } else if (ext === 'pdf') {
                                    console.log('Rendering PDF:', filePath);
                                    const embed = document.createElement('embed');
                                    embed.src = filePath;
                                    embed.type = 'application/pdf';
                                    embed.className = 'w-full h-full';
                                    fileViewPreview.appendChild(embed);
                                } else {
                                    console.log('Unsupported file type:', ext);
                                    fileViewPreview.innerHTML = '<p class="text-center text-gray-500">Preview not available for this file type.</p>';
                                }

                                const fileModal = document.getElementById('fileViewModal');
                                if (fileModal) {
                                    console.log('Showing file view modal');
                                    fileModal.classList.remove('hidden');
                                } else {
                                    console.error('File view modal not found in the DOM');
                                }
                            }
                        });
                    });
                </script>




                <?php elseif ($currentPage === 'Evaluation'): ?>
                <?php
                // Database configuration (using your existing Database class)
                $conn = Database::getInstance();

                // Search users function with status, sport, and campus filters
                function searchUsersEval($searchTerm, $statusFilter, $sportFilter, $campusFilter, $conn) {
                    $searchTerm = '%' . $conn->real_escape_string(trim($searchTerm)) . '%';
                    $statusFilter = $conn->real_escape_string(trim($statusFilter));
                    $sportFilter = $conn->real_escape_string(trim($sportFilter));
                    $campusFilter = $conn->real_escape_string(trim($campusFilter));

                    $query = "
            SELECT id, student_id, full_name, sport, campus, status
            FROM users
            WHERE (student_id LIKE ? OR full_name LIKE ?)
        ";
                    $params = [$searchTerm, $searchTerm];
                    $types = "ss";

                    if (!empty($statusFilter)) {
                        $query .= " AND status = ?";
                        $params[] = $statusFilter;
                        $types .= "s";
                    }
                    if (!empty($sportFilter)) {
                        $query .= " AND sport = ?";
                        $params[] = $sportFilter;
                        $types .= "s";
                    }
                    if (!empty($campusFilter)) {
                        $query .= " AND campus = ?";
                        $params[] = $campusFilter;
                        $types .= "s";
                    }

                    $stmt = $conn->prepare($query);
                    if (!$stmt) {
                        error_log("Prepare failed: " . $conn->error);
                        return [];
                    }

                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $stmt->close();
                    return $result;
                }

                // Get search term and filters from GET request
                $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
                $statusFilter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
                $sportFilter = isset($_GET['sports_filter']) ? trim($_GET['sports_filter']) : '';
                $campusFilter = isset($_GET['campus_filter']) ? trim($_GET['campus_filter']) : '';
                $users = searchUsersEval($searchTerm, $statusFilter, $sportFilter, $campusFilter, $conn);

                // Fetch all submissions for displayed users, excluding approved ones
                $userIds = array_column($users, 'id');
                $submissionsByUser = [];
                if (!empty($userIds)) {
                    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                    $stmt = $conn->prepare("
            SELECT id, user_id, document_type, submission_date, status, file_name, other_type, comments
            FROM submissions
            WHERE user_id IN ($placeholders) AND status != 'approved'
            ORDER BY submission_date DESC
        ");
                    if ($stmt) {
                        $stmt->bind_param(str_repeat('i', count($userIds)), ...$userIds);
                        $stmt->execute();
                        $allSubmissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        $stmt->close();

                        // Group submissions by user_id
                        foreach ($allSubmissions as $sub) {
                            $submissionsByUser[$sub['user_id']][] = $sub;
                        }
                    } else {
                        error_log("Prepare failed: " . $conn->error);
                    }
                }

                // Sample sports options (adjust based on your database)
                $sportsOptions = ['Basketball', 'Volleyball', 'Football', 'Swimming', 'Others'];
                ?>

                <div class="w-full px-4 mt-4">
                    <!-- Search and Filter Form -->
                    <form method="GET" action="" class="flex flex-col sm:flex-row items-center gap-4 w-full mb-6">
                        <input type="hidden" name="page" value="Evaluation"/>
                        <div class="flex-1 min-w-[200px] w-full sm:w-auto">
                            <input type="text" name="search" placeholder="Search by Student ID or Name..."
                                   value="<?php echo htmlspecialchars($searchTerm); ?>"
                                   class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm text-sm">
                        </div>
                        <select name="status_filter" class="w-full sm:w-auto px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm text-sm">
                            <option value="">All Statuses</option>
                            <option value="undergraduate" <?php echo $statusFilter === 'undergraduate' ? 'selected' : ''; ?>>Undergraduate</option>
                            <option value="alumni" <?php echo $statusFilter === 'alumni' ? 'selected' : ''; ?>>Alumni</option>
                        </select>
                        <select name="sports_filter" class="w-full sm:w-auto px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm text-sm">
                            <option value="">All Sports</option>
                            <?php foreach ($sportsOptions as $sport): ?>
                                <option value="<?php echo htmlspecialchars($sport); ?>" <?php echo $sportFilter === $sport ? 'selected' : ''; ?>><?php echo htmlspecialchars($sport); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="campus_filter" class="w-full sm:w-auto px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm text-sm">
                            <option value="">All Campuses</option>
                            <option value="Tagum" <?php echo $campusFilter === 'Tagum' ? 'selected' : ''; ?>>Tagum</option>
                            <option value="Mabini" <?php echo $campusFilter === 'Mabini' ? 'selected' : ''; ?>>Mabini</option>
                        </select>
                        <button type="submit" class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 shadow-sm transition-colors duration-200 text-sm">
                            <span>Search</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </form>
                </div>

                <div class="w-full px-4 sm:px-8 lg:px-25 mx-auto mt-6">
                    <?php if (count($users) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($users as $user): ?>
                                <?php
                                $userId = $user['id'];
                                $submissions = $submissionsByUser[$userId] ?? [];
                                $pendingSubmissions = array_filter($submissions, fn($sub) => $sub['status'] === 'pending');
                                ?>
                                <div class="userFile cursor-pointer" data-student-id="<?php echo htmlspecialchars($user['student_id']); ?>"
                                     data-full-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                     data-submissions='<?php echo htmlspecialchars(json_encode($submissions, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>'>
                                    <div class="flex items-center bg-white shadow-md rounded-lg px-5 py-4 hover:bg-gray-50 transition-colors duration-200">
                                        <div class="bg-blue-100 p-3 rounded-lg mr-4">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="flex-grow">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <div class="text-xs font-semibold text-gray-500">Name</div>
                                                    <div class="text-gray-800 font-medium"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                                </div>
                                                <div>
                                                    <div class="text-xs font-semibold text-gray-500">Pending Submissions</div>
                                                    <div class="text-gray-800"><?php echo count($pendingSubmissions); ?> Document<?php echo count($pendingSubmissions) !== 1 ? 's' : ''; ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- View Icon -->
                                        <div class="ml-4">
                                            <button class="view-details-btn" title="View Details" aria-label="View submission details">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 hover:text-blue-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-10">
                            <div class="text-gray-500 font-semibold mb-2">No evaluations found</div>
                            <div class="text-sm text-gray-400">Try adjusting your search criteria or filters</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Add CSRF token input here -->
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? ''); ?>">

                <!-- Evaluation Modal -->
                <div id="evaluationModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden" role="dialog" aria-labelledby="evaluationModalTitle" aria-modal="true">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-3xl mx-4">
                        <div class="relative mb-4">
                            <h2 id="evaluationModalTitle" class="text-lg font-semibold text-gray-800">Evaluate Submissions</h2>
                            <button onclick="closeModal('evaluationModal')" class="absolute top-0 right-0 text-gray-400 hover:text-gray-600 transition-colors" aria-label="Close modal">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="mb-4">
                            <p class="text-sm text-gray-700"><strong>Student ID:</strong> <span id="modal_student_id"></span></p>
                            <p class="text-sm text-gray-700"><strong>Name:</strong> <span id="modal_full_name"></span></p>
                        </div>
                        <div id="submissions_list" class="space-y-4 max-h-96 overflow-y-auto"></div>
                        <div class="mt-4 flex justify-center">
                            <button onclick="closeModal('evaluationModal')" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 transition-colors">
                                Close
                            </button>
                        </div>
                    </div>
                </div>

                <!-- File View Modal -->
                <div id="fileViewModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden" role="dialog" aria-labelledby="fileViewModalTitle" aria-modal="true">
                    <div class="bg-white rounded-2xl shadow-2xl p-4 w-[95vw] max-w-[1440px] h-[95vh] max-h-[1080px] border border-gray-200 flex flex-col">
                        <div class="modal-header relative mb-2">
                            <h2 id="fileViewModalTitle" class="text-lg font-semibold text-gray-800">View File</h2>
                            <button onclick="closeModal('fileViewModal')" class="modal-close-btn absolute top-0 right-0 text-gray-400 hover:text-gray-600 transition-colors" aria-label="Close modal">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div id="fileViewPreview" class="w-full h-full flex-1 bg-gray-50 rounded-lg overflow-hidden"></div>
                        <div class="modal-footer mt-2 flex justify-center gap-2 bg-gray-100 p-2 rounded-b-2xl">
                            <a id="fileDownloadLink" href="#" class="action-btn bg-red-600 hover:bg-red-700 text-white py-1 px-3 rounded-lg flex items-center gap-1 transition-colors duration-200 focus:outline-none hidden">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                Download
                            </a>
                            <button onclick="closeModal('fileViewModal')" class="action-btn bg-blue-600 hover:bg-blue-700 text-white py-1 px-3 rounded-lg flex items-center gap-1 transition-colors duration-200 focus:outline-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Close
                            </button>
                        </div>
                    </div>
                </div>

                <div id="approveModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden" role="dialog" aria-labelledby="approveModalTitle" aria-modal="true">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
                        <div class="relative mb-4">
                            <h2 id="approveModalTitle" class="text-lg font-semibold text-gray-800">Confirm Approval</h2>
                            <button onclick="closeModal('approveModal')" class="absolute top-0 right-0 text-gray-400 hover:text-gray-600 transition-colors" aria-label="Close modal">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <p class="text-sm text-gray-700 mb-4">Are you sure you want to approve this submission?</p>
                        <div class="flex justify-end space-x-2">
                            <button onclick="closeModal('approveModal')" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md text-sm hover:bg-gray-400 transition-colors">
                                Cancel
                            </button>
                            <button id="confirmApprove" class="px-4 py-2 bg-green-500 text-white rounded-md text-sm hover:bg-green-600 transition-colors">
                                Confirm
                            </button>
                        </div>
                    </div>
                </div>

                <div id="rejectModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden" role="dialog" aria-labelledby="rejectModalTitle" aria-modal="true">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
                        <div class="relative mb-4">
                            <h2 id="rejectModalTitle" class="text-lg font-semibold text-gray-800">Confirm Rejection</h2>
                            <button onclick="closeModal('rejectModal')" class="absolute top-0 right-0 text-gray-400 hover:text-gray-600 transition-colors" aria-label="Close modal">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <p class="text-sm text-gray-700 mb-4">Are you sure you want to reject this submission?</p>
                        <div class="mb-4">
                            <label for="rejectComments" class="text-sm text-gray-700">Comments (optional):</label>
                            <textarea id="rejectComments" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400" rows="4"></textarea>
                        </div>
                        <div class="flex justify-end space-x-2">
                            <button onclick="closeModal('rejectModal')" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md text-sm hover:bg-gray-400 transition-colors">
                                Cancel
                            </button>
                            <button id="confirmReject" class="px-4 py-2 bg-red-500 text-white rounded-md text-sm hover:bg-red-600 transition-colors">
                                Confirm
                            </button>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const userFiles = document.querySelectorAll('.userFile');
                        userFiles.forEach(userFile => {
                            userFile.addEventListener('click', function() {
                                const studentId = this.getAttribute('data-student-id');
                                const fullName = this.getAttribute('data-full-name');
                                let submissions = this.getAttribute('data-submissions');

                                try {
                                    submissions = JSON.parse(submissions || '[]');
                                } catch (e) {
                                    console.error('Failed to parse submissions:', e);
                                    submissions = [];
                                }

                                const modalStudentId = document.getElementById('modal_student_id');
                                const modalFullName = document.getElementById('modal_full_name');
                                const submissionsList = document.getElementById('submissions_list');
                                const modal = document.getElementById('evaluationModal');

                                if (modalStudentId && modalFullName && submissionsList && modal) {
                                    modalStudentId.textContent = studentId || 'N/A';
                                    modalFullName.textContent = fullName || 'N/A';
                                    submissionsList.innerHTML = '';

                                    if (submissions.length === 0) {
                                        submissionsList.innerHTML = '<p class="text-sm text-gray-500">No pending submissions found.</p>';
                                    } else {
                                        submissions.forEach(sub => {
                                            const div = document.createElement('div');
                                            div.className = 'bg-gray-50 p-4 rounded-lg shadow-sm';
                                            div.innerHTML = `
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-700"><strong>Document Type:</strong> ${sub.document_type || 'N/A'}${sub.other_type ? ` (${sub.other_type})` : ''}</p>
                                        <p class="text-sm text-gray-700"><strong>Submission Date:</strong> ${sub.submission_date ? new Date(sub.submission_date).toLocaleDateString() : 'N/A'}</p>
                                        <p class="text-sm text-gray-700"><strong>Status:</strong> ${sub.status || 'N/A'}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-700"><strong>File Name:</strong> ${sub.file_name || 'N/A'}</p>
                                        <p class="text-sm text-gray-700"><strong>Comments:</strong> ${sub.comments || 'None'}</p>
                                    </div>
                                </div>
                                <div class="mt-2 flex justify-end gap-2">
                                    <button class="view-file-btn bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm transition-colors"
                                            data-file-name="${sub.file_name || ''}"
                                            data-submission-id="${sub.id || ''}">
                                        View File
                                    </button>
                                    ${sub.status === 'pending' ? `
                                        <button class="approve-btn bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md text-sm transition-colors"
                                                data-submission-id="${sub.id || ''}">
                                            Approve
                                        </button>
                                        <button class="reject-btn bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md text-sm transition-colors"
                                                data-submission-id="${sub.id || ''}">
                                            Reject
                                        </button>
                                    ` : ''}
                                </div>
                            `;
                                            submissionsList.appendChild(div);
                                        });
                                    }

                                    modal.classList.remove('hidden');
                                }
                            });
                        });

                        // Handle file view button clicks
                        document.addEventListener('click', function(e) {
                            if (e.target.classList.contains('view-file-btn')) {
                                const fileName = e.target.getAttribute('data-file-name');
                                const fileViewPreview = document.getElementById('fileViewPreview');
                                const fileDownloadLink = document.getElementById('fileDownloadLink');
                                const fileModal = document.getElementById('fileViewModal');
                                const uploadPath = '<?php echo UPLOAD_BASE_PATH; ?>'.replace(/.*\/public_html/, '');
                                const filePath = uploadPath + fileName;

                                if (fileViewPreview && fileDownloadLink && fileModal) {
                                    fileViewPreview.innerHTML = '';
                                    fileDownloadLink.setAttribute('href', filePath);
                                    fileDownloadLink.classList.remove('hidden');

                                    const ext = fileName.split('.').pop().toLowerCase();
                                    if (['jpg', 'jpeg', 'png'].includes(ext)) {
                                        const img = document.createElement('img');
                                        img.src = filePath;
                                        img.alt = fileName;
                                        img.className = 'max-w-full h-auto';
                                        fileViewPreview.appendChild(img);
                                    } else if (ext === 'pdf') {
                                        const embed = document.createElement('embed');
                                        embed.src = filePath;
                                        embed.type = 'application/pdf';
                                        embed.className = 'w-full h-full';
                                        fileViewPreview.appendChild(embed);
                                    } else {
                                        fileViewPreview.innerHTML = '<p class="text-center text-gray-500">Preview not available for this file type.</p>';
                                    }

                                    fileModal.classList.remove('hidden');
                                }
                            }
                        });

                        // Handle approve button clicks
                        document.addEventListener('click', function(e) {
                            if (e.target.classList.contains('approve-btn')) {
                                const submissionId = e.target.getAttribute('data-submission-id');
                                document.getElementById('approveModal').classList.remove('hidden');
                                document.getElementById('confirmApprove').onclick = function() {
                                    // AJAX call to approve submission
                                    fetch('approve_submission.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                        body: `submission_id=${encodeURIComponent(submissionId)}&csrf_token=${encodeURIComponent(document.getElementById('csrf_token').value)}`
                                    })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success) {
                                                alert('Submission approved successfully');
                                                location.reload();
                                            } else {
                                                alert('Failed to approve submission: ' + (data.message || 'Unknown error'));
                                            }
                                        })
                                        .catch(error => console.error('Error:', error));
                                    closeModal('approveModal');
                                };
                            }
                        });

                        // Handle reject button clicks
                        document.addEventListener('click', function(e) {
                            if (e.target.classList.contains('reject-btn')) {
                                const submissionId = e.target.getAttribute('data-submission-id');
                                document.getElementById('rejectModal').classList.remove('hidden');
                                document.getElementById('confirmReject').onclick = function() {
                                    const comments = document.getElementById('rejectComments').value;
                                    fetch('reject_submission.php', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                        body: `submission_id=${encodeURIComponent(submissionId)}&comments=${encodeURIComponent(comments)}&csrf_token=${encodeURIComponent(document.getElementById('csrf_token').value)}`
                                    })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success) {
                                                alert('Submission rejected successfully');
                                                location.reload();
                                            } else {
                                                alert('Failed to reject submission: ' + (data.message || 'Unknown error'));
                                            }
                                        })
                                        .catch(error => console.error('Error:', error));
                                    closeModal('rejectModal');
                                    document.getElementById('rejectComments').value = '';
                                };
                            }
                        });

                        // Close modal function
                        function closeModal(modalId) {
                            const modal = document.getElementById(modalId);
                            if (modal) modal.classList.add('hidden');
                        }
                    });
                </script>



        <?php elseif ($currentPage === 'Account Approvals'): ?>

            <?php
// Database configuration
            $host = 'localhost';
            $db = 'SportOfficeDB';
            $user = 'root';
            $pass = '';

            $conn = new mysqli($host, $user, $pass, $db);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

// Base upload path (adjust to your server's document root)
            define('UPLOAD_BASE_PATH', $_SERVER['DOCUMENT_ROOT'] . '/Uploads/');

// Check for status messages
            $status = $_GET['status'] ?? null;
            $action = $_GET['action'] ?? null;
            if ($status && $action) {
                $actionText = ucfirst($action) . 'ed';
                if ($status === 'success') {
                    if ($action === 'approve') {
                        echo '<div class="alert success-alert" id="alertMessage">
                    <i class="fas fa-check-circle"></i>
                    <span>Request Approved successfully! An email has been sent to the user.</span>
                    <span class="alert-close" onclick="this.parentElement.style.display=\'none\'">×</span>
                </div>';
                    } else if ($action === 'reject') {
                        echo '<div class="alert success-alert" id="alertMessage">
                    <i class="fas fa-check-circle"></i>
                    <span>Request Rejected successfully! The user has been notified.</span>
                    <span class="alert-close" onclick="this.parentElement.style.display=\'none\'">×</span>
                </div>';
                    }
                } else if ($status === 'error') {
                    echo '<div class="alert error-alert" id="alertMessage">
                <i class="fas fa-exclamation-circle"></i>
                <span>Failed to ' . $action . ' request. Please try again.</span>
                <span class="alert-close" onclick="this.parentElement.style.display=\'none\'">×</span>
            </div>';
                }
            }

// Fetch pending approval requests
            $sql = "SELECT * FROM account_approvals WHERE approval_status = 'pending' ORDER BY request_date DESC";
            $result = $conn->query($sql);
            ?>

            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta http-equiv="X-UA-Compatible" content="IE=edge">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Approval Requests</title>

                <!-- SweetAlert2 CDN -->
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script src="../public/JAVASCRIPT/accappro.js" defer></script>
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
                <style>
                    :root {
                        --primary: #B91C1C;
                        --success: #10B981;
                        --danger: #EF4444;
                        --info: #3B82F6;
                        --gray: #6B7280;
                    }

                    .container {
                        max-width: 100%;
                        margin: 0 auto;
                        padding: 1rem;
                    }

                    .card {
                        background: white;
                        border-radius: 0.5rem;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                        padding: 1rem;
                        margin-bottom: 1rem;
                    }

                    .action-btn {
                        padding: 0.75rem 1.25rem;
                        border-radius: 0.375rem;
                        font-weight: 500;
                        transition: background-color 0.2s;
                        display: inline-flex;
                        align-items: center;
                        gap: 0.5rem;
                        border: none;
                        cursor: pointer;
                        font-size: 0.9rem;
                        touch-action: manipulation;
                    }

                    .approve-btn {
                        background-color: var(--success);
                        color: white;
                    }
                    .approve-btn:hover {
                        background-color: #059669;
                    }

                    .reject-btn {
                        background-color: var(--danger);
                        color: white;
                    }
                    .reject-btn:hover {
                        background-color: #DC2626;
                    }

                    .view-btn {
                        background-color: var(--info);
                        color: white;
                    }
                    .view-btn:hover {
                        background-color: #2563EB;
                    }

                    .alert {
                        padding: 0.75rem;
                        border-radius: 0.375rem;
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                        margin-bottom: 1rem;
                        font-size: 0.9rem;
                    }

                    .success-alert {
                        background-color: #D1FAE5;
                        color: #065F46;
                    }

                    .error-alert {
                        background-color: #FEE2E2;
                        color: #B91C1C;
                    }

                    .alert-close {
                        margin-left: auto;
                        cursor: pointer;
                    }

                    #documentModal {
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background-color: rgba(0, 0, 0, 0.6);
                        z-index: 1000;
                        display: none;
                        align-items: center;
                        justify-content: center;
                        padding: 0.5rem;
                    }

                    #documentModal.show {
                        display: flex;
                        padding: 10vw;
                    }

                    .modal-content {
                        background: white;
                        border-radius: 0.5rem;
                        width: 100%;
                        max-width: 95vw;
                        height: 90vh;
                        max-height: 90vh;
                        display: flex;
                        flex-direction: column;
                        overflow: hidden;
                    }

                    .modal-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 0.75rem 1rem;
                        border-bottom: 1px solid #e5e7eb;
                    }

                    .modal-header h3 {
                        font-size: 1.25rem;
                        text-align: center;
                        flex-grow: 1;
                    }

                    .modal-close-btn {
                        cursor: pointer;
                    }

                    #documentPreview {
                        width: 100%;
                        flex: 1;
                        overflow: auto;
                        background-color: #f9fafb;
                    }

                    #documentPreview iframe,
                    #documentPreview img {
                        width: 100%;
                        height: 100%;
                        max-height: 100%;
                        object-fit: contain;
                        border: none;
                        display: block;
                    }

                    .modal-footer {
                        padding: 0.75rem;
                        background-color: #f3f4f6;
                        display: flex;
                        justify-content: center;
                        gap: 0.75rem;
                        border-bottom-left-radius: 0.5rem;
                        border-bottom-right-radius: 0.5rem;
                        flex-shrink: 0;
                    }

                    /* Loading Overlay */
                    #loadingOverlay {
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background-color: rgba(0, 0, 0, 0.5);
                        z-index: 2000;
                        display: none;
                        align-items: center;
                        justify-content: center;
                    }

                    .loader {
                        border: 4px solid #f3f3f3;
                        border-top: 4px solid var(--primary);
                        border-radius: 50%;
                        width: 40px;
                        height: 40px;
                        animation: spin 1s linear infinite;
                    }

                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }

                    /* Mobile-specific adjustments */
                    @media (max-width: 768px) {
                        .container {
                            padding: 0.5rem;
                        }

                        .card {
                            padding: 0.75rem;
                        }

                        .action-btn {
                            padding: 0.5rem 1rem;
                            font-size: 0.85rem;
                        }

                        .modal-content {
                            width: 98vw;
                            height: 95vh;
                            max-height: 95vh;
                        }

                        .modal-header h3 {
                            font-size: 1.1rem;
                        }

                        .modal-footer {
                            flex-direction: column;
                            gap: 0.5rem;
                        }

                        .modal-footer .action-btn {
                            width: 100%;
                            justify-content: center;
                        }

                        .grid-cols-1.md\\:grid-cols-2 {
                            grid-template-columns: 1fr;
                        }

                        .flex.items-center.justify-between.md\\:justify-end {
                            flex-direction: column;
                            gap: 0.5rem;
                            align-items: stretch;
                        }

                        .action-btn {
                            width: 100%;
                            justify-content: center;
                        }
                    }
                </style>
            </head>
            <body>
            <div class="container">
                <!-- Approval Requests -->
                <div class="grid gap-3">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="card">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="mt-2 space-y-1 text-gray-600 text-sm">
                                        <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                        <p><span class="font-medium">Student ID:</span> <?php echo htmlspecialchars($row['student_id'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p><span class="font-medium">Email:</span> <?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p><span class="font-medium">Status:</span> <?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <p><span class="font-medium">Request Date:</span> <?php echo htmlspecialchars($row['request_date'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>

                                    <div class="flex items-center justify-between md:justify-end space-x-2">
                                        <button class="action-btn view-btn" onclick="showDocument(<?php echo (int)$row['id']; ?>, '<?php echo htmlspecialchars(json_encode($row['file_name']), ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($row['file_type'], ENT_QUOTES, 'UTF-8'); ?>')">
                                            <i class="fas fa-eye"></i> View Document
                                        </button>

                                        <form method="POST" action="../controller/approveRequest.php" onsubmit="return showConfirmation('approve', '<?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo (int)$row['id']; ?>', this)">
                                            <input type="hidden" name="approval_id" value="<?php echo (int)$row['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="action-btn approve-btn">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>

                                        <form method="POST" action="../controller/rejectRequest.php" onsubmit="return showConfirmation('reject', '<?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo (int)$row['id']; ?>', this)">
                                            <input type="hidden" name="approval_id" value="<?php echo (int)$row['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="action-btn reject-btn">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="card text-center text-gray-500 font-medium py-4">
                            No pending approval requests
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Document Modal -->
            <div id="documentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" role="dialog" aria-labelledby="documentModalTitle" aria-modal="true">
                <div class="modal-content flex flex-col bg-white rounded-lg w-full max-w-[95vw] my-2 overflow-hidden">
                    <div class="modal-header flex justify-between items-center p-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800" id="documentModalTitle">Document Preview</h3>
                        <button onclick="closeModal()" class="modal-close-btn text-gray-500 hover:text-gray-700 focus:outline-none" aria-label="Close modal">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div id="documentPreview" class="w-full flex-1 overflow-auto bg-gray-50"></div>
                    <div class="modal-footer p-4 bg-gray-100 flex justify-center gap-3 rounded-b-lg">
                        <a id="downloadLink" href="#" class="action-btn bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg flex items-center gap-2 transition-colors duration-200 focus:outline-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download
                        </a>
                        <button onclick="closeModal()" class="action-btn bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg flex items-center gap-2 transition-colors duration-200 focus:outline-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading Overlay -->
            <div id="loadingOverlay">
                <div class="loader"></div>
            </div>

            </body>
            </html>

            <?php
            $conn->close();
            ?>


































































    <?php else: ?>
        <p class="text-center">This is the <?php echo htmlspecialchars($currentPage); ?> content area.</p>
    <?php endif; ?>
</div>



<!-- Edit Modal -->
<div id="editUserModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative animate-fadeIn">
        <button onclick="document.getElementById('editUserModal').classList.add('hidden')" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-2xl font-bold">×</button>
        <h2 class="text-2xl font-bold text-center mb-4">EDIT USER</h2>
        <form method="POST" action="../controller/editUsers.php" class="flex flex-col gap-4">
            <input type="text" name="student_id" id="edit-student-id" placeholder="Student ID" required class="p-3 border rounded-lg w-full" readonly>
            <input type="text" name="full_name" id="edit-full-name" placeholder="Full Name" required class="p-3 border rounded-lg w-full">
            <input type="text" name="address" id="edit-address" placeholder="Address" required class="p-3 border rounded-lg w-full">
            <select name="status" id="edit-status" required class="p-3 border rounded-lg w-full">
                <option value="" disabled>Select Status</option>
                <option value="undergraduate">Undergraduate</option>
                <option value="alumni">Alumni</option>
            </select>
            <input type="hidden" name="page" value="admin">
            <input type="hidden" name="currentPage" value="Users">
            <input type="hidden" name="source" value="usersPage">
            <input type="hidden" name="_token" value="<?php echo bin2hex(random_bytes(32)); ?>">
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white py-3 rounded-lg font-semibold w-full">Save Changes</button>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteUserModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative animate-fadeIn">
        <button onclick="document.getElementById('deleteUserModal').classList.add('hidden')" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-2xl font-bold">×</button>
        <h2 class="text-2xl font-bold text-center mb-4">DELETE USER</h2>
        <h3 class="text-lg text-center text-gray-700 mb-4">Are you sure you want to delete this account?</h3>
        <form id="deleteUserForm" method="POST" action="../controller/deleteUsers.php" class="flex flex-col gap-4">
            <input type="hidden" id="deleteUserId" name="id">
            <input type="hidden" name="_token" value="<?php echo bin2hex(random_bytes(32)); ?>">
            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white py-3 rounded-lg font-semibold w-full">Delete Account</button>
        </form>
    </div>
</div>

<!-- Logout Modal -->
<div id="logoutModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 w-80 text-center">
        <h2 class="text-lg font-semibold mb-4">Are you sure you want to logout?</h2>
        <div class="flex justify-center gap-4">
            <button id="cancelLogout" class="px-4 py-2 bg-gray-300 text-black rounded hover:bg-gray-400">No</button>
            <a href="../controller/logout.php" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Yes</a>
        </div>
    </div>
</div>

<script>
    // Logout confirmation handling
    document.getElementById('logoutBtn')?.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('logoutModal').classList.remove('hidden');
    });

    document.getElementById('cancelLogout')?.addEventListener('click', function() {
        document.getElementById('logoutModal').classList.add('hidden');
    });
</script>

<!-- Add User Modal -->
<div id="addUserModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative animate-fadeIn">
        <button onclick="document.getElementById('addUserModal').classList.add('hidden')" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-2xl font-bold">&times;</button>

        <h2 class="text-2xl font-bold text-center mb-4">ADD NEW USER</h2>

        <form method="POST" action="../controller/addUsers.php" onsubmit="return validateSignupForm(event)" class="flex flex-col gap-4">
            <input type="text" name="student_id" placeholder="Student ID" required autocomplete="off" class="p-3 border rounded-lg w-full">
            <input type="text" name="full_name" placeholder="Full Name" required autocomplete="name" class="p-3 border rounded-lg w-full">
            <input type="text" name="address" placeholder="Address" required autocomplete="street-address" class="p-3 border rounded-lg w-full">
            <input type="email" name="email" placeholder="Email" required autocomplete="email" class="p-3 border rounded-lg w-full">

            <div class="relative w-full">
                <input type="password" id="admin-password" name="password" placeholder="Enter Password" required autocomplete="new-password" class="p-3 border rounded-lg w-full">
                <i class="bx bx-show toggle-password absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer text-gray-400 transition-all duration-300" onclick="togglePasswordVisibility('admin-password')"></i>
            </div>

            <select name="status" required class="p-3 border rounded-lg w-full">
                <option value="" disabled selected>Select Status</option>
                <option value="undergraduate">Undergraduate</option>
                <option value="alumni">Alumni</option>
            </select>

            <!-- Hidden inputs -->
            <input type="hidden" name="page" value="admin">
            <input type="hidden" name="currentPage" value="Users">
            <input type="hidden" name="source" value="usersPage">

            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white py-3 rounded-lg font-semibold w-full">Add User</button>
        </form>
    </div>
</div>



<?php if (isset($_GET['message'])): ?>

    <!-- Message Modal -->
    <div id="messageModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-sm animate-fadeIn">
            <p class="text-center text-lg font-semibold mb-4">
                <?php echo isset($_GET['message']) ? htmlspecialchars($_GET['message']) : ''; ?>
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