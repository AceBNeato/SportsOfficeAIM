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

// Start session and validate
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user']['id'])) {
    header("Location: ../view/loginView.php?message=" . urlencode("Please log in."));
    exit;
}

// Session timeout (30 minutes)
$session_timeout = 1800;
if (isset($_SESSION['user']['last_activity']) && (time() - $_SESSION['user']['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: ../view/loginView.php?timeout=1");
    exit;
}
$_SESSION['user']['last_activity'] = time();

// PHPMailer-based email notification


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
    <script src="../public/JAVASCRIPT/docadminScripy.js" defer></script>

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
    <div class="sticky top-0 z-30 bg-gray-100 w-full px-1 sm:px-4 lg:px-3">
        <div class="border-b-4 border-red-500 px-5 pt-2 pb-1 flex justify-between items-center">
            <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 tracking-tight">
                <?php echo htmlspecialchars($currentPage); ?>
            </h1>

            <?php if ($currentPage === 'Users'): ?>
                <div class="w-full px-4 sm:px-0 flex flex-col items-center sm:items-end space-y-2 sm:space-y-4">
                    <button onclick="document.getElementById('addUserModal').classList.remove('hidden')"
                            class="flex items-center text-red-500 font-semibold hover:text-blue-600 sm:self-end w-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             class="w-5 h-5 mr-1 border-2 border-blue-500 rounded-full p-0.5">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add users
                    </button>
                    <div class="w-full px-4">
                        <div class="flex justify-end">
                            <form method="GET" action="" class="flex items-center gap-2 -mr-2">
                                <input type="hidden" name="page" value="Users"/>
                                <input type="text" name="search" placeholder="Search..."
                                       class="w-40 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-lg whitespace-nowrap flex items-center justify-center text-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <span class="ml-1 hidden sm:inline">Search</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($currentPage === 'Users'): ?>
            <div class="hidden sm:block w-full bg-red-500 text-white font-semibold rounded-t-lg px-5 mt-2 mb-4">
                <div class="hidden sm:flex items-center px-4 py-6">
                    <div class="w-1/12 text-center"></div>
                    <div class="w-3/12">Student ID</div>
                    <div class="w-4/12">Student Name</div>
                    <div class="w-4/12">Student Address</div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($currentPage === 'Documents'): ?>
            <div class="w-full bg-red-500 text-white font-semibold rounded-t-lg my-4">
                <div class="hidden sm:grid grid-cols-12 gap-4 items-center px-5 py-4">
                    <div class="col-span-1"></div>
                    <div class="col-span-5 pl-3">Student ID</div>
                    <div class="col-span-6">Student Name</div>
                </div>
            </div>
            <div class="w-full px-4">
                <form method="GET" action="" class="flex flex-row items-center gap-2 w-full">
                    <input type="hidden" name="page" value="Documents"/>
                    <input type="text" name="search" placeholder="Search Student..."
                           class="flex-1 min-w-0 border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded whitespace-nowrap flex items-center justify-center">
                        <span class="hidden sm:inline">Search</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </form>
            </div>
        <?php endif; ?>

    <?php if ($currentPage === 'Users'): ?>
        <?php
        $users = searchUsers(isset($_GET['search']) ? $_GET['search'] : '');
        ?>
        <div class="max-h-[calc(100vh-10rem)] overflow-y-auto overflow-x-hidden scroll-thin">
            <div class="w-full px-4 sm:px-8 lg:px-8 space-y-2">
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $row): ?>
                        <div class="bg-white p-4 rounded-lg shadow-sm space-y-2 sm:space-y-0 sm:grid sm:grid-cols-12 sm:items-center">
                            <div class="text-center text-xl text-gray-600 sm:col-span-1">
                                <button type="button" class="text-blue-500 hover:text-blue-700 focus:outline-none"
                                        title="Edit User"
                                        data-student-id="<?= htmlspecialchars($row['student_id'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-full-name="<?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-address="<?= htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-status="<?= isset($row['status']) ? htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') : '' ?>"
                                        onclick="openEditModal(this)">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                            <div class="text-gray-800 font-medium sm:col-span-3">
                                <span class="block sm:hidden font-semibold text-gray-600">Student ID:</span>
                                <?= htmlspecialchars($row['student_id']) ?>
                            </div>
                            <div class="text-gray-800 sm:col-span-4">
                                <span class="block sm:hidden font-semibold text-gray-600">Name:</span>
                                <?= htmlspecialchars($row['full_name']) ?>
                            </div>
                            <div class="text-gray-700 sm:col-span-4">
                                <span class="block sm:hidden font-semibold text-gray-600">Address:</span>
                                <?= htmlspecialchars($row['address']) ?>
                            </div>
                            <div class="text-center text-xl text-gray-600 sm:col-span-1">
                                <button onclick="confirmDeleteUser('<?= htmlspecialchars($row['student_id']) ?>', '<?= htmlspecialchars($row['id'] ?? $row['student_id']) ?>')"
                                        class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-gray-500 py-6 font-semibold">
                        No users found matching your search.
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($currentPage === 'Achievement'): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" style="padding-top: 1rem;">
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <header class="bg-gradient-to-r from-red-600 to-orange-500 shadow-md rounded-lg p-8 mb-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-white">Award Recognition</h1>
                            <p class="text-white text-base mt-1 opacity-80">One Data. One USeP.</p>
                        </div>
                        <img src="../public/image/Usep.png" alt="USeP Logo" class="h-16">
                    </div>
                </header>
                <form method="POST" action="../controller/handleAchievement.php" class="p-8 space-y-8 w-full" enctype="multipart/form-data" id="achievementForm" aria-labelledby="awardRecognitionHeading">
                    <?php if (isset($_SESSION['achievement_message'])): ?>
                        <div class="bg-<?php echo $_SESSION['achievement_status'] === 'success' ? 'green' : 'red'; ?>-100 border-l-4 border-<?php echo $_SESSION['achievement_status'] === 'success' ? 'green' : 'red'; ?>-500 p-4 mb-6 rounded-md">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-<?php echo $_SESSION['achievement_status'] === 'success' ? 'green' : 'red'; ?>-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $_SESSION['achievement_status'] === 'success' ? 'M5 13l4 4L19 7' : 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'; ?>"></path>
                                </svg>
                                <p class="text-<?php echo $_SESSION['achievement_status'] === 'success' ? 'green' : 'red'; ?>-700"><?php echo htmlspecialchars($_SESSION['achievement_message']); ?></p>
                            </div>
                        </div>
                        <?php unset($_SESSION['achievement_message'], $_SESSION['achievement_status']); ?>
                    <?php endif; ?>
                    <div class="space-y-8">
                        <section class="border border-gray-200 rounded-lg p-6" style="width: 75vw;">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4" id="studentInfoHeading">Student Information</h2>
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                <div class="lg:col-span-2">
                                    <label for="studentSearch" class="block text-sm font-medium text-gray-700 mb-1">Search Student</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                        <input type="text" id="studentSearch" name="student_id" placeholder="Enter Student ID or Name"
                                               class="pl-10 w-full border border-gray-300 rounded-md px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                               required autocomplete="off" aria-autocomplete="list" aria-controls="searchResults" aria-describedby="searchHint"/>
                                        <div id="searchResults" class="hidden absolute z-20 mt-1 w-full bg-white shadow-lg rounded-md border border-gray-200 max-h-60 overflow-auto" role="listbox"></div>
                                    </div>
                                    <p id="searchHint" class="mt-1 text-xs text-gray-500">Minimum 3 characters to search</p>
                                </div>
                            </div>
                        </section>
                    <input type="hidden" name="page" value="Achievement">
                    <input type="hidden" name="_token" value="<?php echo bin2hex(random_bytes(32)); ?>">
                </form>
            </div>
        </div>
        <script>
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            document.getElementById('fileUpload').addEventListener('change', function(e) {
                const fileNameDisplay = document.getElementById('fileNameDisplay');
                if (this.files.length > 0) {
                    const file = this.files[0];
                    const fileSize = (file.size / (1024 * 1024)).toFixed(2);
                    if (file.size > 10 * 1024 * 1024) {
                        fileNameDisplay.innerHTML = `
                            <div class="flex items-center justify-center bg-red-50 text-red-600 p-3 rounded-md border border-red-100">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                File too large (${fileSize}MB). Max 10MB allowed.
                            </div>`;
                        this.value = '';
                    } else {
                        fileNameDisplay.innerHTML = `
                            <div class="flex items-center justify-between bg-green-50 text-green-700 p-3 rounded-md border border-green-100">
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    ${file.name} (${fileSize}MB)
                                </div>
                                <button type="button" onclick="clearFileInput()" class="text-gray-500 hover:text-gray-700" aria-label="Clear file">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>`;
                    }
                } else {
                    fileNameDisplay.textContent = '';
                }
            });
            function clearFileInput() {
                document.getElementById('fileUpload').value = '';
                document.getElementById('fileNameDisplay').textContent = '';
            }
            document.getElementById('studentSearch').addEventListener('input', debounce(async function(e) {
                const query = this.value.trim();
                const resultsContainer = document.getElementById('searchResults');
                if (query.length > 2) {
                    resultsContainer.innerHTML = `
                        <div class="p-4 text-center text-gray-500">
                            <svg class="animate-spin h-5 w-5 mx-auto text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="mt-1 text-sm">Searching...</p>
                        </div>`;
                    resultsContainer.classList.remove('hidden');
                    try {
                        const response = await fetch(`../controller/searchStudents.php?query=${encodeURIComponent(query)}`);
                        const results = await response.json();
                        if (results.length > 0) {
                            resultsContainer.innerHTML = results.map(item => `
                                <div class="p-3 hover:bg-red-50 cursor-pointer border-b border-gray-100 last:border-b-0 flex items-center"
                                    onclick="selectStudent('${item.id}', '${item.name}')"
                                    role="option"
                                    aria-selected="false">
                                    <div class="bg-red-100 p-1.5 rounded-full mr-3">
                                        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-medium text-red-600">${item.id}</div>
                                        <div class="text-sm text-gray-800">${item.name}</div>
                                    </div>
                                </div>
                            `).join('');
                        } else {
                            resultsContainer.innerHTML = `
                                <div class="p-4 text-center text-gray-500">
                                    <p class="text-sm">No students found matching "${query}"</p>
                                </div>`;
                        }
                    } catch (error) {
                        resultsContainer.innerHTML = `
                            <div class="p-4 text-center text-gray-500">
                                <p class="text-sm">Error searching students. Please try again.</p>
                            </div>`;
                    }
                } else if (query.length === 0) {
                    resultsContainer.classList.add('hidden');
                } else {
                    resultsContainer.innerHTML = `
                        <div class="p-4 text-center text-gray-500">
                            <p class="text-sm">Please enter at least 3 characters</p>
                        </div>`;
                    resultsContainer.classList.remove('hidden');
                }
            }, 300));
            function selectStudent(id, name) {
                document.getElementById('studentSearch').value = `${id} - ${name}`;
                document.getElementById('searchResults').classList.add('hidden');
            }
            function debounce(func, wait) {
                let timeout;
                return function() {
                    const context = this, args = arguments;
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(context, args), wait);
                };
            }
            document.getElementById('studentSearch').addEventListener('keydown', function(e) {
                const resultsContainer = document.getElementById('searchResults');
                const items = resultsContainer.querySelectorAll('[role="option"]');
                if (!items.length) return;
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    const current = document.activeElement;
                    let index = Array.from(items).indexOf(current);
                    if (e.key === 'ArrowDown') {
                        index = (index + 1) % items.length;
                    } else if (e.key === 'ArrowUp') {
                        index = (index - 1 + items.length) % items.length;
                    }
                    items[index].focus();
                } else if (e.key === 'Enter' && document.activeElement.role === 'option') {
                    e.preventDefault();
                    document.activeElement.click();
                }
            });
        </script>

    <?php elseif ($currentPage === 'Reports'): ?>
        <div class="p-4 sm:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-white rounded-xl shadow p-4 flex items-center space-x-4">
                    <div class="flex-shrink-0 w-12 h-12 sm:w-16 sm:h-16 flex justify-center items-center text-red-500 bg-red-100 rounded-full text-2xl">
                        <i class='bx bxs-user-account'></i>
                    </div>
                    <div>
                        <p class="text-gray-800 font-semibold text-sm sm:text-base">Total Athletes</p>
                        <?php
                        $conn = Database::getInstance();
                        $totalStudents = 0;
                        if ($result = $conn->query("CALL GetTotalStudents()")) {
                            if ($row = $result->fetch_assoc()) {
                                $totalStudents = $row['total'];
                            }
                            $result->free();
                        }
                        ?>
                        <p class="text-2xl sm:text-3xl font-bold text-gray-900"><?= $totalStudents ?></p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-4 flex items-center space-x-4">
                    <div class="flex-shrink-0 w-12 h-12 sm:w-16 sm:h-16 flex justify-center items-center text-green-600 bg-green-100 rounded-full text-2xl">
                        <i class='bx bxs-file-doc'></i>
                    </div>
                    <div>
                        <p class="text-gray-800 font-semibold text-sm sm:text-base">Approved Reports</p>
                        <p class="text-2xl sm:text-3xl font-bold text-gray-900">0</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-4 sm:p-8 flex flex-col md:flex-row items-center justify-center col-span-1 md:col-span-2 space-y-4 md:space-y-0 md:space-x-8">
                    <div class="flex justify-center items-center w-24 sm:w-32 h-24 sm:h-32 text-blue-600 bg-blue-100 rounded-full text-5xl">
                        <i class='bx bxs-bar-chart-alt-2'></i>
                    </div>
                    <div class="flex flex-col justify-center space-y-4 text-center md:text-left">
                        <div class="flex flex-col md:flex-row items-center md:space-x-4">
                            <div class="text-2xl sm:text-3xl font-bold text-gray-800">0</div>
                            <div class="text-gray-800 font-semibold text-sm sm:text-base">Ongoing Submission</div>
                        </div>
                        <div class="flex flex-col md:flex-row items-center md:space-x-4">
                            <div class="text-2xl sm:text-3xl font-bold text-gray-800">0</div>
                            <div class="text-gray-800 font-semibold text-sm sm:text-base">Verified Document</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($currentPage === 'Documents'): ?>
    <?php
    $users = searchUsers(isset($_GET['search']) ? $_GET['search'] : '');
    ?>
        <div class="w-full px-4 sm:px-8 lg:px-25 mx-auto mt-6">
            <?php if (count($users) > 0): ?>
                <div class="space-y-4">
                    <?php foreach ($users as $user): ?>
                        <div class="userFile cursor-pointer" onclick="showUserDocuments('<?= htmlspecialchars($user['student_id']) ?>', '<?= htmlspecialchars($user['full_name']) ?>')">
                            <div class="grid grid-cols-12 gap-4 items-center bg-white shadow-md rounded-lg px-5 py-4">
                                <div class="col-span-12 sm:col-span-1 flex justify-center sm:justify-start">
                                    <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png" alt="Avatar" class="w-10 h-10 rounded-full">
                                </div>
                                <div class="col-span-12 sm:col-span-5">
                                    <div class="block sm:hidden text-xs font-semibold text-gray-500">Student ID</div>
                                    <div class="font-medium text-gray-800"><?= htmlspecialchars($user['student_id']) ?></div>
                                </div>
                                <div class="col-span-12 sm:col-span-6">
                                    <div class="block sm:hidden text-xs font-semibold text-gray-500">Name</div>
                                    <div class="text-gray-800"><?= htmlspecialchars($user['full_name']) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-10">
                    <div class="text-gray-500 font-semibold mb-2">No documents found</div>
                    <div class="text-sm text-gray-400">Try adjusting your search criteria</div>
                </div>
            <?php endif; ?>
        </div>







        <?php elseif ($currentPage === 'Evaluation'): ?>
        <?php
        // Database configuration (using your existing Database class)
        $conn = Database::getInstance();

        // Search users function
        function searchUsersEval($searchTerm, $conn) {
            $searchTerm = '%' . $conn->real_escape_string(trim($searchTerm)) . '%';
            $stmt = $conn->prepare("
            SELECT id, student_id, full_name
            FROM users
            WHERE student_id LIKE ? OR full_name LIKE ?
        ");
            if (!$stmt) {
                error_log("Prepare failed: " . $conn->error);
                return [];
            }
            $stmt->bind_param("ss", $searchTerm, $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            return $result;
        }

        // Get search term from GET request
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        $users = searchUsersEval($searchTerm, $conn);

        // Fetch all submissions for displayed users to avoid N+1 queries
        $userIds = array_column($users, 'id');
        $submissionsByUser = [];
        if (!empty($userIds)) {
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $stmt = $conn->prepare("
            SELECT id, user_id, document_type, submission_date, status, file_name, other_type, comments
            FROM submissions
            WHERE user_id IN ($placeholders)
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
        ?>

        <div class="w-full px-4 mt-4">
            <!-- Search Form -->
            <form method="GET" action="" class="flex flex-row items-center gap-2 w-full">
                <input type="hidden" name="page" value="Evaluation"/>
                <input type="text" name="search" placeholder="Search Student..."
                       value="<?php echo htmlspecialchars($searchTerm); ?>"
                       class="flex-1 min-w-0 border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded whitespace-nowrap flex items-center justify-center">
                    <span class="hidden sm:inline">Search</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
                    <div class="text-sm text-gray-400">Try adjusting your search criteria</div>
                </div>
            <?php endif; ?>
        </div>

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
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-3xl border border-gray-200 mx-4 flex flex-col">
                <div class="modal-header relative mb-4">
                    <h2 id="fileViewModalTitle" class="text-lg font-semibold text-gray-800">View File</h2>
                    <button onclick="closeModal('fileViewModal')" class="modal-close-btn absolute top-0 right-0 text-gray-400 hover:text-gray-600 transition-colors" aria-label="Close modal">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div id="fileViewPreview" class="w-full flex-1 overflow-auto bg-gray-50"></div>
                <div class="modal-footer mt-4 flex justify-center gap-3 bg-gray-100 p-4 rounded-b-2xl">
                    <a id="fileDownloadLink" href="#" class="action-btn bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg flex items-center gap-2 transition-colors duration-200 focus:outline-none hidden">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download
                    </a>
                    <button onclick="closeModal('fileViewModal')" class="action-btn bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg flex items-center gap-2 transition-colors duration-200 focus:outline-none">
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
                <div class="mb-4">
                    <label for="approveComments" class="text-sm text-gray-700">Comments (optional):</label>
                    <textarea id="approveComments" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400" rows="4"></textarea>
                </div>
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
                    touch-action: manipulation; /* Improve touch accuracy */
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

                .confirmation-modal {
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

                .confirmation-modal.show {
                    display: flex;
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

        <!-- Confirmation Modal -->
        <div id="confirmationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden" role="dialog" aria-labelledby="confirmationTitle" aria-modal="true">
            <div class="bg-white rounded-lg shadow-lg p-4 w-full max-w-xs">
                <h3 class="text-base font-semibold text-gray-800 mb-3" id="confirmationTitle"></h3>
                <div class="flex justify-end space-x-3">
                    <button onclick="cancelConfirmation()" class="action-btn bg-gray-500 hover:bg-gray-600 text-white">
                        Cancel
                    </button>
                    <button id="confirmActionBtn" class="action-btn" onclick="confirmAction()">
                        Confirm
                    </button>
                </div>
            </div>
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

<!-- Documents Modal -->
<div id="documentsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-6">
            <button onclick="closeModal('documentsModal')" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <div class="flex flex-col items-center text-center mb-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="bg-blue-100 p-3 rounded-full w-14 h-14 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="text-left">
                        <h2 class="text-xl font-semibold text-gray-800" id="modalStudentName"></h2>
                        <p class="text-gray-600" id="modalStudentId"></p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200 w-full">
                    <h3 class="text-lg font-medium text-gray-700">Student Document</h3>
                </div>
            </div>
            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="bg-green-100 p-2 rounded-lg mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="font-medium text-gray-800">Medical Certificate</span>
                    </div>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 bg-blue-50 text-blue-600 rounded-md hover:bg-blue-100 text-sm font-medium">View</button>
                        <button class="px-3 py-1 bg-gray-50 text-gray-600 rounded-md hover:bg-gray-100 text-sm font-medium">Download</button>
                    </div>
                </div>
                <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="bg-purple-100 p-2 rounded-lg mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="font-medium text-gray-800">Birth Certificate</span>
                    </div>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 bg-blue-50 text-blue-600 rounded-md hover:bg-blue-100 text-sm font-medium">View</button>
                        <button class="px-3 py-1 bg-gray-50 text-gray-600 rounded-md hover:bg-gray-100 text-sm font-medium">Download</button>
                    </div>
                </div>
                <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="bg-amber-100 p-2 rounded-lg mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="font-medium text-gray-800">Certificate of Enrolment</span>
                    </div>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 bg-blue-50 text-blue-600 rounded-md hover:bg-blue-100 text-sm font-medium">View</button>
                        <button class="px-3 py-1 bg-gray-50 text-gray-600 rounded-md hover:bg-gray-100 text-sm font-medium">Download</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
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