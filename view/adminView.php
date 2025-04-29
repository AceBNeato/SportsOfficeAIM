
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
            $currentPage = isset($_GET['page']) ? $_GET['page'] : 'Documents';
            $menu = ['Documents', 'Evaluation', 'Reports', 'Users', 'Log-out'];
            $icon = [
                'Documents' => "<box-icon name='file-doc' type='solid' color='white'></box-icon>",
                'Evaluation' => "<box-icon name='line-chart' color='white'></box-icon>",
                'Reports' => "<box-icon name='report' type='solid' color='white'></box-icon>",
                'Users' => "<box-icon name='user-circle' color='white'></box-icon>",
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
                    <!-- Add User Button -->
                    <button onclick="document.getElementById('addUserModal').classList.remove('hidden')"
                            class="flex items-center text-red-500 font-semibold hover:text-blue-600 sm:self-end w-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor"
                             class="w-5 h-5 mr-1 border-2 border-blue-500 rounded-full p-0.5">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 4v16m8-8H4"/>
                        </svg>
                        Add users
                    </button>


                    <!-- Search Field -->
                    <form method="GET" action=""
                          class="sm:self-end w-auto">
                        <input type="hidden" name="page" value="Users"/>
                        <input type="text" name="search" placeholder="Search user..."
                               class="border border-gray-300 rounded px-3 py-1 text-sm w-full focus:outline-none focus:ring-2 focus:ring-blue-400"/>
                    </form>
                </div>
            <?php endif; ?>




        </div>

        <?php if ($currentPage === 'Users'): ?>

            <div class="hidden sm:block w-full bg-red-500 text-white font-semibold rounded-t-lg px-5 mt-2 mb-4">
                <div class="flex sm:hidden flex-col p-4 space-y-4 text-sm">
                    <div>Student ID</div>
                    <div>Student Name</div>
                    <div>Student Address</div>
                </div>
                <div class="hidden sm:flex items-center px-4 py-6">
                    <div class="w-1/12 text-center"></div>
                    <div class="w-3/12">Student ID</div>
                    <div class="w-4/12">Student Name</div>
                    <div class="w-4/12">Student Address</div>
                </div>
            </div>


        <?php endif; ?>

        <?php if ($currentPage === 'Documents'): ?>
            <!-- Documents Table Header -->
            <div class="w-full bg-red-500 text-white font-semibold rounded-t-lg my-4">
                <div class="hidden sm:grid grid-cols-12 gap-4 items-center px-5 py-4">
                    <!-- Avatar column (empty) -->
                    <div class="col-span-1"></div>

                    <!-- Adjusted Student ID column -->
                    <div class="col-span-5 pl-3">Student ID</div>

                    <!-- Student Name column -->
                    <div class="col-span-6">Student Name</div>
                </div>
            </div>
        <?php endif; ?>
    </div>











    <?php if ($currentPage === 'Users'): ?>
    <?php
    $conn = new mysqli("localhost", "root", "", "SportOfficeDB");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
    $searchTerm = strtolower($searchTerm);

    $stmt = $conn->prepare("CALL SearchUsers(?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = $result->fetch_all(MYSQLI_ASSOC); // Fetch ALL before closing
    $stmt->close();
    $conn->close();
    ?>

    <?php if (count($users) > 0): ?>
    <div class="max-h-[calc(100vh-10rem)] overflow-y-auto overflow-x-hidden scroll-thin">
        <div class="w-full px-4 sm:px-8 lg:px-8 space-y-2">
            <?php foreach ($users as $row): ?>

                <div class="bg-white p-4 rounded-lg shadow-sm space-y-2 sm:space-y-0 sm:grid sm:grid-cols-12 sm:items-center">

                    <div class="text-center text-xl text-gray-600 sm:col-span-1">
                        <button
                                type="button"
                                class="text-blue-500 hover:text-blue-700 focus:outline-none"
                                title="Edit User"
                                data-student-id="<?= htmlspecialchars($row['student_id'], ENT_QUOTES, 'UTF-8') ?>"
                                data-full-name="<?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                                data-address="<?= htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8') ?>"
                                data-status="<?= isset($row['status']) ? htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') : '' ?>"
                                onclick="openEditModal(this)"
                        >
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



                    <!-- Delete Button -->
                    <div class="text-center text-xl text-gray-600 sm:col-span-1">
                        <button
                                    onclick="confirmDeleteUser('<?= htmlspecialchars($row['student_id']) ?>', '<?= htmlspecialchars($row['id'] ?? $row['student_id']) ?>')"
                                class="text-red-500 hover:text-red-700">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>






                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="text-center text-gray-500 py-6 font-semibold">
        No users found matching your search.
    </div>
    <?php endif; ?>

























    <?php elseif ($currentPage === 'Reports'): ?>
        <div class="p-4 sm:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

                <!-- Total Students -->
                <div class="bg-white rounded-xl shadow p-4 flex items-center space-x-4">
                    <div class="flex-shrink-0 w-12 h-12 sm:w-16 sm:h-16 flex justify-center items-center text-red-500 bg-red-100 rounded-full text-2xl">
                        <i class='bx bxs-user-account'></i>
                    </div>
                    <div>
                        <p class="text-gray-800 font-semibold text-sm sm:text-base">Total Students</p>
                        <?php
                        $reportConn = new mysqli("localhost", "root", "", "SportOfficeDB");
                        $totalStudents = 0;
                        if (!$reportConn->connect_error) {
                            // Call the stored procedure
                            if ($result = $reportConn->query("CALL GetTotalStudents()")) {
                                if ($row = $result->fetch_assoc()) {
                                    $totalStudents = $row['total'];
                                }
                                $result->free(); // Important: free result set when using CALL
                            }
                            $reportConn->close();
                        }
                        ?>
                        <p class="text-2xl sm:text-3xl font-bold text-gray-900"><?= $totalStudents ?></p>
                    </div>
                </div>


                <!-- Approved Reports -->
                <div class="bg-white rounded-xl shadow p-4 flex items-center space-x-4">
                    <div class="flex-shrink-0 w-12 h-12 sm:w-16 sm:h-16 flex justify-center items-center text-green-600 bg-green-100 rounded-full text-2xl">
                        <i class='bx bxs-file-doc'></i>
                    </div>
                    <div>
                        <p class="text-gray-800 font-semibold text-sm sm:text-base">Approved Reports</p>
                        <p class="text-2xl sm:text-3xl font-bold text-gray-900">0</p>
                    </div>
                </div>

                <!-- Submission Stats -->
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
    $conn = new mysqli("localhost", "root", "", "SportOfficeDB");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
    $searchTerm = strtolower($searchTerm);

    $stmt = $conn->prepare("CALL SearchUsers(?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    ?>

        <div class="w-full px-4 sm:px-8 lg:px-25 mx-auto">
            <?php if (count($users) > 0): ?>
                <div class="space-y-4">
                    <?php foreach ($users as $user): ?>

                        <div class="userFile cursor-pointer" onclick="showUserDocuments('<?= $user['student_id'] ?>', '<?= htmlspecialchars($user['full_name']) ?>')">
                            <div class="grid grid-cols-12 gap-4 items-center bg-white shadow-md rounded-lg px-5 py-4">
                                <div class="col-span-12 sm:col-span-1 flex justify-center sm:justify-start">
                                    <img src="https://cdn-icons-png.flaticon.com/512/149/149071.png"
                                         alt="Avatar"
                                         class="w-10 h-10 rounded-full">
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
                    <div class="text-gray-500 font-semibold mb-2">
                        No documents found
                    </div>
                    <div class="text-sm text-gray-400">
                        Try adjusting your search criteria
                    </div>
                </div>
            <?php endif; ?>
        </div>



        <script>
            function showUserDocuments(studentId, fullName) {
                const modal = document.getElementById('documentsModal');
                document.getElementById('modalStudentId').textContent = studentId;
                modal.classList.remove('hidden');

                // Here you could also make an AJAX call to fetch specific document status for this user
                // and update the checkboxes accordingly
            }

            function closeModal() {
                document.getElementById('documentsModal').classList.add('hidden');
            }

            // Close modal when clicking outside of it
            window.addEventListener('click', function(event) {
                const modal = document.getElementById('documentsModal');
                if (event.target === modal) {
                    closeModal();
                }
            });
        </script>





            <?php else: ?>
        <p class="text-center">This is the <?php echo htmlspecialchars($currentPage); ?> content area.</p>
    <?php endif; ?>
</div>







<!-- Documents Modal -->
<div id="documentsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-6">
            <!-- Close button -->
            <button onclick="closeModal('documentsModal')" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Centered Student Info Section -->
            <div class="flex flex-col items-center text-center mb-6">
                <!-- Student Icon -->
                <div class="bg-blue-100 p-3 rounded-full mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>

                <!-- Student Name and ID -->
                <h2 class="text-xl font-semibold text-gray-800" id="modalStudentName">Jane Smith</h2>
                <p class="text-gray-600" id="modalStudentId">2023-00002</p>

                <!-- Student Document Title -->
                <div class="mt-4 pt-4 border-t border-gray-200 w-full">
                    <h3 class="text-lg font-medium text-gray-700">Student Document</h3>
                </div>
            </div>

            <!-- Document List -->
            <div class="space-y-4">
                <!-- Medical Certificate -->
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

                <!-- Birth Certificate -->
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

                <!-- Certificate of Enrolment -->
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

<script>
    function showUserDocuments(studentId, fullName) {
        const modal = document.getElementById('documentsModal');
        document.getElementById('modalStudentId').textContent = studentId;
        document.getElementById('modalStudentName').textContent = fullName;
        modal.classList.remove('hidden');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    // Close modal when clicking outside of it
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('documentsModal');
        if (event.target === modal) {
            closeModal('documentsModal');
        }
    });
</script>




















<!-- Edit Modal - Place this OUTSIDE the loop -->
<div id="editUserModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative animate-fadeIn">
        <button onclick="document.getElementById('editUserModal').classList.add('hidden')" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-2xl font-bold">&times;</button>

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

            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white py-3 rounded-lg font-semibold w-full">
                Save Changes
            </button>
        </form>
    </div>
</div>



<!-- Delete Modal - Ensure this is outside the loop -->
<div id="deleteUserModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative animate-fadeIn">

        <!-- Close Button -->
        <button
                onclick="closeDeleteModal()"
                class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-2xl font-bold"
                aria-label="Close">
            &times;
        </button>

        <h2 class="text-2xl font-bold text-center mb-4">DELETE USER</h2>

        <h3 class="text-lg text-center text-gray-700 mb-4">
            Are you sure you want to delete this account?
        </h3>

        <!-- Delete Form -->
        <form id="deleteUserForm" method="POST" action="../controller/deleteUsers.php" class="flex flex-col gap-4">

            <!-- Hidden input to carry user id -->
            <input type="hidden" id="deleteUserId" name="id">

            <!-- Submit Button -->
            <button
                    type="submit"
                    class="bg-red-500 hover:bg-red-600 text-white py-3 rounded-lg font-semibold w-full">
                Delete Account
            </button>
        </form>
    </div>
</div>



<!-- Logout Modal -->
<div id="logoutModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 w-80 text-center">
        <h2 class="text-lg font-semibold mb-4">Are you sure you want to logout?</h2>
        <div class="flex justify-center gap-4">
            <button id="cancelLogout" class="px-4 py-2 bg-gray-300 text-black rounded hover:bg-gray-400">No</button>
            <button id="confirmLogout" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Yes</button>
        </div>
    </div>
</div>

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