<?php
    // Start the session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

    // Check if user is logged in
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header("Location: ../view/loginView.php");
            exit;
        }

    // Check session timeout (30 minutes)
        $session_timeout = 1800;
        if (isset($_SESSION['user']['last_activity']) && (time() - $_SESSION['user']['last_activity'] > $session_timeout)) {
            session_unset();
            session_destroy();
            header("Location: ../view/loginView.php?timeout=1");
            exit;
        }

    // Update last activity
        $_SESSION['user']['last_activity'] = time();

    // OPTIONAL: Debug year_section value
        if (!isset($_SESSION['submissions']['year_section'])) {
            error_log("DEBUG: year_section not set in session.");
        } else {
            error_log("DEBUG: year_section = " . $_SESSION['submissions']['year_section']);
        }


    }

    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: ../view/loginView.php");
        exit;
    }

    // Check session timeout (30 minutes)
    $session_timeout = 1800; // 30 minutes in seconds
    if (isset($_SESSION['user']['last_activity']) && (time() - $_SESSION['user']['last_activity'] > $session_timeout)) {
        // Session expired
        session_unset();
        session_destroy();
        header("Location: ../view/loginView.php?timeout=1");
        exit;
    }

    // Update last activity time
    $_SESSION['user']['last_activity'] = time();
    ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" href="Usep.png"/>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons/css/boxicons.min.css" rel="stylesheet">
    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
    <link rel="stylesheet" href="../public/CSS/userStyle.css">
    <script src="../public/JAVASCRIPT/userScript.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>


<body class="flex h-screen w-full relative bg-gray-100">















<!-- Sidebar -->
<div id="sidebar" class="sidebar collapsed"> <!-- Add collapsed class by default -->
    <div class="flex flex-col items-center mt-6 space-y-4">
        <img src="../public/image/SportOffice.png" alt="Logo" class="w-20 h-20">
        <div class="text-center text-xs leading-tight">
            <p class="font-semibold">One Data. One USeP.</p>
            <p>USeP OSAS-Sports Unit</p>
        </div>

        <nav class="space-y-2 w-full px-2 mt-4">
            <?php

            // Get current page from query parameter with security validation

            $currentPage = isset($_GET['page']) ? $_GET['page'] : 'Dashboard';
            $menu = ['Dashboard', 'Achievement','Submissions', 'Track', 'Log-out'];
            $icon = [
                'Dashboard' => "<box-icon type='solid' name='Dashboard' color='white'></box-icon>",
                'Achievement' => "<box-icon name='trophy' type='solid' color='white'></box-icon>",
                'Submissions' => "<box-icon type='solid' name='file-export' color='white'></box-icon>",
                'Track' => "<box-icon type='solid' name='file' color='white'></box-icon>",
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


    <!-- Collapse Button - Hidden on mobile -->
    <div class="w-full px-2 mb-4 mt-auto hidden md:block"> <!-- Add hidden md:block -->
        <button id="collapseBtn" class="menu-item w-full focus:outline-none">
            <box-icon id="collapseBoxIcon" name='expand-horizontal' color='white'></box-icon>
            <span class="menu-text">Collapse Sidebar</span>
        </button>
    </div>
</div>























<!-- Main Content -->
<div id="mainContent" class="main-content px-1 sm:px-4 lg:px-0">

    <div class="sticky top-0 z-30 bg-gray-100 w-full px-1 sm:px-4 lg:px-3">

            <div class="sticky top-0 z-30 bg-gray-100 w-full px-1 sm:px-4 lg:px-3">
            <div class="border-b-4 border-red-500 px-5 pt-2 pb-1 flex justify-between items-center">
                <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 tracking-tight">
                    <?php echo htmlspecialchars($currentPage); ?>
                </h1>

                <?php if ($currentPage === 'Dashboard'): ?>
                <?php elseif ($currentPage === 'Achievement'): ?>
                <?php elseif ($currentPage === 'Submissions'): ?>
                <?php elseif ($currentPage === 'Track'): ?>
                <?php endif; ?>
        </div>
        </div>




        <?php if ($currentPage === 'Dashboard'): ?>
            <div class="flex flex-col md:flex-row gap-4 p-4">
                <!-- Left Column -->
                <div class="w-full md:w-1/2 space-y-4">
                    <!-- Profile Info Card -->
                    <div class="bg-white rounded-lg shadow p-6 relative">
                        <div class="flex justify-between items-start mb-6">
                            <h2 class="text-xl font-bold">Profile</h2>
                            <button class="text-gray-500 hover:text-blue-500 transition" onclick="document.getElementById('edit-profile-modal').classList.remove('hidden')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                            </button>
                        </div>

                        <div class="flex flex-col sm:flex-row items-start gap-6">
                            <div class="flex-shrink-0 relative group">
                                <div class="w-32 h-32 rounded-full bg-gray-200 overflow-hidden border-4 border-blue-100">
                                    <?php if ($_SESSION['user']['has_profile_image']): ?>
                                        <img src="../controller/get_profile_image.php?id=<?php echo $_SESSION['user']['id']; ?>"
                                             onerror="this.onerror=null; this.src='../public/image/user.png'"
                                             alt="Profile Image" class="profile-image">
                                    <?php else: ?>
                                        <div class="profile-initials">
                                            <?php
                                            $name = $_SESSION['user']['full_name'];
                                            $initials = '';
                                            $parts = explode(' ', $name);
                                            if (count($parts) > 1) {
                                                $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
                                            } else {
                                                $initials = strtoupper(substr($name, 0, 1));
                                            }
                                            echo $initials;
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="space-y-4 flex-1">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Student ID </p>
                                        <p class="font-semibold"><?php echo htmlspecialchars($_SESSION['user']['student_id'] ?? 'N/A'); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Full Name</p>
                                        <p class="font-semibold">
                                            <?php
                                            echo htmlspecialchars(
                                                ($_SESSION['user']['full_name'] ?? 'N/A'));
                                            ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Email</p>
                                        <p class="font-semibold"><?php echo htmlspecialchars($_SESSION['user']['email'] ?? 'N/A'); ?></p>
                                    </div>
                                </div>
                             </div>
                            </div>
                        </div>


































                    <!-- Track Card -->
                    <div class="bg-white rounded-lg shadow p-6 relative">
                        <!-- Edit button positioned top right -->
                        <button class="absolute top-4 right-4 text-gray-500 hover:text-blue-500 transition"
                                onclick="window.location.href='?page=Track'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                            </svg>
                        </button>

                        <div class="flex justify-between items-start mb-6">
                            <h2 class="text-xl font-bold">Track</h2>
                        </div>

                        <div class="flex items-center justify-center">
                            <div class="mr-6 text-blue-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="text-center">
                                <?php
                                // Database connection
                                $host = "localhost";
                                $username = "root";
                                $password = "";
                                $dbname = "SportOfficeDB";

                                // Connect to MySQL server
                                $conn = new mysqli($host, $username, $password, $dbname);
                                if ($conn->connect_error) {
                                    die("Connection failed: " . $conn->connect_error);
                                }

                                // Fetch count of submissions for the logged-in user
                                $user_id = $_SESSION['user']['id']; // Assuming user ID is stored in session
                                $stmt = $conn->prepare("SELECT COUNT(*) as document_count FROM submissions WHERE user_id = ?");
                                $stmt->bind_param("i", $user_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $document_count = 0;
                                if ($result->num_rows > 0) {
                                    $row = $result->fetch_assoc();
                                    $document_count = $row['document_count'];
                                }

                                // Store document count in session for potential reuse
                                $_SESSION['user']['document_count'] = $document_count;

                                // Clean up
                                $stmt->close();
                                $conn->close();
                                ?>
                                <div class="text-5xl font-bold text-gray-800">
                                    <?php echo htmlspecialchars($document_count); ?>
                                </div>
                                <div class="text-lg text-gray-600 mt-2">Submitted Documents</div>
                            </div>
                        </div>
                    </div>






                </div>






















                <!-- Right Column - Notifications -->
                <!-- Right Column - Notifications -->
                <div class="w-full md:w-1/2 h-[calc(100vh-150px)]">
                    <div class="bg-white rounded-lg shadow h-full flex flex-col">
                        <!-- Header with buttons (fixed height) -->
                        <div class="flex justify-between items-center p-4 border-b">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                                </svg>
                                <h2 class="text-xl font-bold">Notifications</h2>
                            </div>
                            <div>
                                <button id="refreshNotifications" class="text-blue-500 hover:text-blue-700 text-sm mr-3">
                                    <i class="fas fa-sync-alt mr-1"></i>Refresh
                                </button>
                                <button id="clearNotifications" class="text-blue-500 hover:text-blue-700 text-sm">
                                    <i class="fas fa-trash-alt mr-1"></i>Clear All
                                </button>
                            </div>
                        </div>

                        <!-- Scrollable notifications container -->
                        <div class="flex-1 overflow-y-auto" style="max-height: calc(100vh - 200px);">
                            <div id="notificationsContainer" class="space-y-3 p-4">
                                <!-- Notifications will be dynamically inserted here -->
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const notificationsContainer = document.getElementById('notificationsContainer');
                        const refreshBtn = document.getElementById('refreshNotifications');
                        const clearBtn = document.getElementById('clearNotifications');

                        let notifications = JSON.parse(localStorage.getItem('notifications')) || [];
                        let timestampUpdaters = {};

                        function fetchNotifications() {
                            fetch('../controller/notifications.php')
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Network response was not ok');
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    if (data.notifications && data.notifications.length > 0) {
                                        data.notifications.forEach(notification => {
                                            if (!notifications.some(n => n.id === notification.id)) {
                                                notifications.unshift(notification);
                                                if (notifications.length > 50) {
                                                    const removed = notifications.pop();
                                                    if (timestampUpdaters[removed.timestamp]) {
                                                        clearInterval(timestampUpdaters[removed.timestamp]);
                                                        delete timestampUpdaters[removed.timestamp];
                                                    }
                                                }
                                            }
                                        });
                                        localStorage.setItem('notifications', JSON.stringify(notifications));
                                        displayNotifications();
                                        markNotificationsAsRead();
                                    }
                                })
                                .catch(error => console.error('Error fetching notifications:', error));
                        }

                        function markNotificationsAsRead() {
                            fetch('../controller/notifications.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ action: 'mark_read' })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (!data.success) {
                                        console.warn('Failed to mark notifications as read:', data.error);
                                    }
                                })
                                .catch(error => console.error('Error marking notifications as read:', error));
                        }

                        function formatTime(timestamp, element) {
                            const updateTime = () => {
                                const now = new Date();
                                const notificationTime = new Date(timestamp);
                                const diffInSeconds = Math.floor((now - notificationTime) / 1000);

                                if (diffInSeconds < 60) {
                                    element.textContent = 'Just now';
                                } else if (diffInSeconds < 3600) {
                                    const minutes = Math.floor(diffInSeconds / 60);
                                    element.textContent = `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
                                } else if (diffInSeconds < 86400) {
                                    const hours = Math.floor(diffInSeconds / 3600);
                                    element.textContent = `${hours} hour${hours !== 1 ? 's' : ''} ago`;
                                } else {
                                    element.textContent = notificationTime.toLocaleDateString();
                                    clearInterval(timestampUpdaters[timestamp]);
                                    delete timestampUpdaters[timestamp];
                                }
                            };

                            updateTime();
                            if (!timestampUpdaters[timestamp]) {
                                timestampUpdaters[timestamp] = setInterval(updateTime, 60000);
                            }
                        }

                        function displayNotifications() {
                            notificationsContainer.innerHTML = '';

                            if (notifications.length === 0) {
                                notificationsContainer.innerHTML = '<p class="text-gray-500 text-center py-4">No notifications</p>';
                                return;
                            }

                            notifications.forEach((notification, index) => {
                                const notificationElement = document.createElement('div');
                                notificationElement.className = 'p-3 bg-gray-50 rounded border-l-4 border-blue-500 flex justify-between items-start';
                                notificationElement.innerHTML = `
                                        <div>
                                            <p class="text-sm">${notification.message}</p>
                                            <p class="text-xs text-gray-500 mt-1 timestamp" data-timestamp="${notification.timestamp}"></p>
                                        </div>
                                        <button class="text-gray-400 hover:text-gray-600 delete-notification" data-index="${index}">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    `;
                                notificationsContainer.appendChild(notificationElement);

                                const timestampElement = notificationElement.querySelector('.timestamp');
                                formatTime(notification.timestamp, timestampElement);
                            });

                            document.querySelectorAll('.delete-notification').forEach(btn => {
                                btn.addEventListener('click', function() {
                                    const index = parseInt(this.getAttribute('data-index'));
                                    const timestamp = notifications[index].timestamp;
                                    if (timestampUpdaters[timestamp]) {
                                        clearInterval(timestampUpdaters[timestamp]);
                                        delete timestampUpdaters[timestamp];
                                    }
                                    notifications.splice(index, 1);
                                    localStorage.setItem('notifications', JSON.stringify(notifications));
                                    displayNotifications();
                                });
                            });
                        }

                        clearBtn.addEventListener('click', function() {
                            if (confirm('Are you sure you want to clear all notifications?')) {
                                Object.values(timestampUpdaters).forEach(interval => clearInterval(interval));
                                timestampUpdaters = {};
                                notifications = [];
                                localStorage.setItem('notifications', JSON.stringify(notifications));
                                fetch('../controller/notifications.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ action: 'clear_all' })
                                })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            displayNotifications();
                                        } else {
                                            console.warn('Failed to clear notifications:', data.error);
                                        }
                                    })
                                    .catch(error => console.error('Error clearing notifications:', error));
                            }
                        });

                        refreshBtn.addEventListener('click', function() {
                            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Refreshing';
                            fetchNotifications();
                            setTimeout(() => {
                                refreshBtn.innerHTML = '<i class="fas fa-sync-alt mr-1"></i>Refresh';
                            }, 1000);
                        });

                        function fetchNotifications() {
                            fetch('../controller/notifications.php')
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Network response was not ok');
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    if (data.notifications && data.notifications.length > 0) {
                                        // Add new notifications to the array
                                        data.notifications.forEach(notification => {
                                            if (!notifications.some(n => n.id === notification.id)) {
                                                notifications.push(notification); // Use push instead of unshift
                                            }
                                        });
                                        // Sort notifications by timestamp in descending order (newest first)
                                        notifications.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
                                        // Limit to 50 notifications, removing the oldest
                                        if (notifications.length > 50) {
                                            const removed = notifications.splice(50);
                                            removed.forEach(notif => {
                                                if (timestampUpdaters[notif.timestamp]) {
                                                    clearInterval(timestampUpdaters[notif.timestamp]);
                                                    delete timestampUpdaters[notif.timestamp];
                                                }
                                            });
                                        }
                                        localStorage.setItem('notifications', JSON.stringify(notifications));
                                        displayNotifications();
                                        markNotificationsAsRead();
                                    }
                                })
                                .catch(error => console.error('Error fetching notifications:', error));
                        }
                        displayNotifications();

                        window.addEventListener('beforeunload', function() {
                            Object.values(timestampUpdaters).forEach(interval => clearInterval(interval));
                        });
                    });
                </script>






                <!-- Edit Profile Modal -->
                <div id="edit-profile-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden p-4">
                    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 overflow-y-auto max-h-screen">
                        <!-- Flex direction changes based on screen size -->
                        <div class="flex flex-col md:flex-row">
                            <!-- Profile Picture Section - Full width on mobile, 1/3 on desktop -->
                            <div class="bg-blue-600 text-white p-6 md:w-1/3 flex flex-col items-center justify-center">
                                <div class="relative group mb-6">
                                    <div class="w-40 h-40 rounded-full bg-gray-200 overflow-hidden border-[6px] border-blue-100 relative">
                                        <?php if (isset($_SESSION['user']['has_profile_image'])): ?>
                                            <img id="profile-image"
                                                 src="../controller/get_profile_image.php?id=<?php echo $_SESSION['user']['id']; ?>&t=<?php echo time(); ?>"
                                                 onerror="this.onerror=null; this.src='../public/image/user.png'"
                                                 alt="Profile Image"
                                                 class="w-full h-full object-cover">
                                            <div id="profile-initials" class="hidden"></div>
                                        <?php else: ?>
                                            <div id="profile-initials" class="w-full h-full flex items-center justify-center text-center bg-blue-500 text-white text-5xl font-bold">
                                                <?php
                                                $initials = '';
                                                $fullName = $_SESSION['user']['full_name'] ?? '';
                                                $nameParts = explode(' ', $fullName);
                                                if (count($nameParts) >= 2) {
                                                    $initials = substr($nameParts[0], 0, 1) . substr($nameParts[count($nameParts)-1], 0, 1);
                                                } elseif (count($nameParts) === 1) {
                                                    $initials = substr($nameParts[0], 0, 1);
                                                }
                                                echo strtoupper($initials ?: 'U');
                                                ?>
                                            </div>
                                            <img id="profile-image" src="" alt="Profile" class="hidden w-full h-full object-cover">
                                        <?php endif; ?>
                                    </div>
                                    <label for="modal-profile-upload" class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-full bg-black/50 cursor-pointer">
                                        <span class="text-white text-sm font-medium bg-blue-600 px-2 py-1 rounded">Change Photo</span>
                                        <input id="modal-profile-upload" type="file" accept="image/*" class="hidden">
                                    </label>
                                </div>
                                <h3 class="text-xl font-semibold mb-1 text-center break-words"><?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? 'User'); ?></h3>
                                <p class="text-blue-100 text-base text-center break-words"><?php echo htmlspecialchars($_SESSION['user']['student_id'] ?? ''); ?></p>
                            </div>
                            <!-- Form Section - Full width on mobile, 2/3 on desktop -->
                            <div class="p-4 md:p-6 md:w-2/3 mx-auto">
                                <div class="flex justify-between items-center mb-4 md:mb-6">
                                    <h3 class="text-lg md:text-xl font-semibold text-gray-800">Edit Profile</h3>
                                    <button onclick="closeModal('edit-profile-modal')" class="text-gray-500 hover:text-gray-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 md:h-6 md:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <form id="profile-edit-form" class="space-y-4 md:space-y-6" method="post" action="../controller/update_profile.php" enctype="multipart/form-data">
                                    <!-- Single column on mobile, two columns on desktop -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                                        <!-- Full Name -->
                                        <div>
                                            <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                            <input type="text" id="full_name" name="full_name"
                                                   value="<?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? 'N/A'); ?>"
                                                   class="w-full px-3 py-2 md:px-4 md:py-2.5 text-sm md:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        </div>

                                        <!-- Email -->
                                        <div>
                                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                            <input type="email" id="email" name="email"
                                                   value="<?php echo htmlspecialchars($_SESSION['user']['email'] ?? 'N/A'); ?>"
                                                   class="w-full px-3 py-2 md:px-4 md:py-2.5 text-sm md:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        </div>

                                        <!-- Address (Wider) -->
                                        <div class="w-full md:w-[110%]">
                                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                            <input type="text" id="address" name="address"
                                                   value="<?php echo htmlspecialchars($_SESSION['user']['address'] ?? 'N/A'); ?>"
                                                   class="w-full px-4 py-2.5 md:px-5 md:py-3 text-sm md:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        </div>
                                        <!-- Sport -->
                                        <div>
                                            <label for="sport" class="block text-sm font-medium text-gray-700 mb-1">Sport</label>
                                            <select id="sport" name="sport" class="w-full px-3 py-2 md:px-4 md:py-2.5 text-sm md:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <option value="" disabled <?php echo (!isset($_SESSION['user']['sport']) || $_SESSION['user']['sport'] === 'N/A') ? 'selected' : ''; ?>>Select Sport (Optional)</option>
                                                <option value="Athletics" <?php echo (isset($_SESSION['user']['sport']) && $_SESSION['user']['sport'] === 'Athletics') ? 'selected' : ''; ?>>Athletics</option>
                                                <option value="Badminton" <?php echo (isset($_SESSION['user']['sport']) && $_SESSION['user']['sport'] === 'Badminton') ? 'selected' : ''; ?>>Badminton</option>
                                                <option value="Basketball" <?php echo (isset($_SESSION['user']['sport']) && $_SESSION['user']['sport'] === 'Basketball') ? 'selected' : ''; ?>>Basketball</option>
                                                <option value="Chess" <?php echo (isset($_SESSION['user']['sport']) && $_SESSION['user']['sport'] === 'Chess') ? 'selected' : ''; ?>>Chess</option>
                                                <option value="Football" <?php echo (isset($_SESSION['user']['sport']) && $_SESSION['user']['sport'] === 'Football') ? 'selected' : ''; ?>>Football</option>
                                                <option value="Sepak Takraw" <?php echo (isset($_SESSION['user']['sport']) && $_SESSION['user']['sport'] === 'Sepak Takraw') ? 'selected' : ''; ?>>Sepak Takraw</option>
                                                <option value="Swimming" <?php echo (isset($_SESSION['user']['sport']) && $_SESSION['user']['sport'] === 'Swimming') ? 'selected' : ''; ?>>Swimming</option>
                                                <option value="Table Tennis" <?php echo (isset($_SESSION['user']['sport']) && $_SESSION['user']['sport'] === 'Table Tennis') ? 'selected' : ''; ?>>Table Tennis</option>
                                                <option value="Taekwondo" <?php echo (isset($_SESSION['user']['sport']) && $_SESSION['user']['sport'] === 'Taekwondo') ? 'selected' : ''; ?>>Taekwondo</option>
                                                <option value="Tennis" <?php echo (isset($_SESSION['user']['sport']) && $_SESSION['user']['sport'] === 'Tennis') ? 'selected' : ''; ?>>Tennis</option>
                                                <option value="Volleyball" <?php echo (isset($_SESSION['user']['sport']) && $_SESSION['user']['sport'] === 'Volleyball') ? 'selected' : ''; ?>>Volleyball</option>
                                            </select>
                                        </div>


                                        <!-- Year Section -->
                                        <div>
                                            <label for="year_section" class="block text-sm font-medium text-gray-700 mb-1">Year & Section</label>
                                            <input type="text" id="year_section" name="year_section"
                                                   value="<?php echo htmlspecialchars($_SESSION['user']['year_section'] ?? ''); ?>"
                                                   class="w-full px-3 py-2 md:px-4 md:py-2.5 text-sm md:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="Ex: 1IT-BSIT">
                                        </div>


                                        <!-- Campus -->
                                        <div>
                                            <label for="campus" class="block text-sm font-medium text-gray-700 mb-1">Campus</label>
                                            <select id="campus" name="campus" class="w-full px-3 py-2 md:px-4 md:py-2.5 text-sm md:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <option value="Tagum" <?php echo (isset($_SESSION['user']['campus']) && $_SESSION['user']['campus'] === 'Tagum') ? 'selected' : ''; ?>>Tagum</option>
                                                <option value="Mabini" <?php echo (isset($_SESSION['user']['campus']) && $_SESSION['user']['campus'] === 'Mabini') ? 'selected' : ''; ?>>Mabini</option>
                                            </select>
                                        </div>


                                        <!-- Password Fields - Vertically aligned -->
                                        <div class="space-y-4">
                                            <!-- New Password -->
                                            <div>
                                                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                                <div class="relative">
                                                    <input type="password" id="password" name="password" value=""
                                                           class="w-full px-3 py-2 md:px-4 md:py-2.5 text-sm md:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-10"
                                                           placeholder="Enter new password">
                                                    <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700" onclick="togglePassword('password')">
                                                        <svg class="h-4 w-4 md:h-5 md:w-5 eye-icon" fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        <svg class="h-4 w-4 md:h-5 md:w-5 eye-slash-icon hidden" fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Confirm Password -->
                                            <div>
                                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                                                <div class="relative">
                                                    <input type="password" id="confirm_password" name="confirm_password" value=""
                                                           class="w-full px-3 py-2 md:px-4 md:py-2.5 text-sm md:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-10"
                                                           placeholder="Confirm password">
                                                    <button type="button" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700" onclick="togglePassword('confirm_password')">
                                                        <svg class="h-4 w-4 md:h-5 md:w-5 eye-icon" fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                        <svg class="h-4 w-4 md:h-5 md:w-5 eye-slash-icon hidden" fill="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Buttons -->
                                    <div class="flex flex-col sm:flex-row justify-end gap-3 mt-6">
                                        <button type="button" onclick="closeModal('edit-profile-modal')"
                                                class="w-full sm:w-auto px-4 py-2 md:px-5 md:py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                                class="w-full sm:w-auto px-4 py-2 md:px-5 md:py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                                            Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>


                        </div>
                    </div>
                </div>
            </div>

            <!-- JavaScript to handle profile image upload and preview -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Handle profile image upload in the edit profile modal
                    const profileUpload = document.getElementById('modal-profile-upload');
                    const profileForm = document.getElementById('profile-edit-form');

                    if (profileUpload) {
                        profileUpload.addEventListener('change', function(e) {
                            if (e.target.files && e.target.files[0]) {
                                const file = e.target.files[0];

                                // Validate file
                                if (file.size > 2 * 1024 * 1024) {
                                    alert('Image size exceeds 2MB. Please choose a smaller file.');
                                    return;
                                }

                                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                                if (!validTypes.includes(file.type)) {
                                    alert('Only JPG, PNG and GIF images are allowed.');
                                    return;
                                }

                                // Create a preview
                                const reader = new FileReader();
                                reader.onload = function(e) {
                                    const profileImage = document.getElementById('profile-image');
                                    const profileInitials = document.getElementById('profile-initials');

                                    profileImage.src = e.target.result;
                                    profileImage.classList.remove('hidden');
                                    profileInitials.classList.add('hidden');
                                };
                                reader.readAsDataURL(file);

                                // Create a hidden file input if it doesn't exist
                                let fileInput = profileForm.querySelector('input[name="profile_image"]');
                                if (!fileInput) {
                                    fileInput = document.createElement('input');
                                    fileInput.type = 'file';
                                    fileInput.name = 'profile_image';
                                    fileInput.classList.add('hidden');
                                    profileForm.appendChild(fileInput);
                                }

                                // Create a new FileList-like object with our file
                                const dataTransfer = new DataTransfer();
                                dataTransfer.items.add(file);
                                fileInput.files = dataTransfer.files;
                            }
                        });
                    }

                    // Handle form submission
                    if (profileForm) {
                        profileForm.addEventListener('submit', function(e) {
                            const password = document.getElementById('password').value;
                            const confirmPassword = document.getElementById('confirm_password').value;

                            // If password fields are not empty, check if they match
                            if (password || confirmPassword) {
                                if (password !== confirmPassword) {
                                    e.preventDefault();
                                    document.getElementById('passwordModal').classList.remove('hidden');
                                    return false;
                                }

                                // Check password length if provided
                                if (password.length < 8) {
                                    e.preventDefault();
                                    alert('Password must be at least 8 characters long.');
                                    return false;
                                }
                            }

                            // Add enctype attribute to ensure file upload works
                            profileForm.enctype = "multipart/form-data";
                        });
                    }
                });

                function refreshProfileImage() {
                    const profileImage = document.getElementById('profile-image');
                    if (profileImage) {
                        // Add timestamp to prevent caching
                        profileImage.src = profileImage.src.split('?')[0] + '?t=' + new Date().getTime();
                    }
                }

                // Helper function to get user ID from the URL or data attribute
                function getUserId() {
                    // Try to get from data attribute first
                    const userIdElement = document.querySelector('[data-user-id]');
                    if (userIdElement && userIdElement.dataset.userId) {
                        return userIdElement.dataset.userId;
                    }

                    // Default to session user ID (this should be set as a JS variable in your PHP)
                    return window.currentUserId || '';
                }

                // Function to toggle password visibility
                function togglePassword(inputId) {
                    const passwordInput = document.getElementById(inputId);
                    const eyeIcon = passwordInput.nextElementSibling.querySelector('.eye-icon');
                    const eyeSlashIcon = passwordInput.nextElementSibling.querySelector('.eye-slash-icon');

                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        eyeIcon.classList.add('hidden');
                        eyeSlashIcon.classList.remove('hidden');
                    } else {
                        passwordInput.type = 'password';
                        eyeIcon.classList.remove('hidden');
                        eyeSlashIcon.classList.add('hidden');
                    }
                }

                // Function to close modal
                function closeModal(modalId) {
                    document.getElementById(modalId).classList.add('hidden');
                }

                // Function to show success modal
                function showSuccessModal(message) {
                    const modal = document.getElementById('success-modal');
                    const messageElement = document.getElementById('success-message');

                    if (messageElement && message) {
                        messageElement.textContent = message;
                    }

                    if (modal) {
                        modal.classList.remove('hidden');
                    }
                }
            </script>




















            <!-- Add this modal at the end of the body tag, before the closing </body> -->

            <!-- Success Modal -->
            <div id="success-modal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
                <div class="bg-white rounded-lg shadow-lg p-6 max-w-md text-center">
                    <div class="mb-4 flex justify-center">
                        <div class="rounded-full bg-green-100 p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <h2 class="text-lg font-semibold mb-2">Success!</h2>
                    <p id="success-message" class="text-gray-600 mb-4">Your profile has been updated successfully.</p>
                    <button id="close-success-modal" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors">
                        OK
                    </button>
                </div>
            </div>

            <!-- Add this script at the end of the body tag, before the closing </body> -->
            <script>
                // Function to show success modal
                function showSuccessModal(message) {
                    const modal = document.getElementById('success-modal');
                    const messageElement = document.getElementById('success-message');

                    if (messageElement && message) {
                        messageElement.textContent = message;
                    }

                    if (modal) {
                        modal.classList.remove('hidden');
                    }
                }

                // Function to close success modal
                function closeSuccessModal() {
                    const modal = document.getElementById('success-modal');
                    if (modal) {
                        modal.classList.add('hidden');
                    }
                }

                // Add event listener to close button
                document.addEventListener('DOMContentLoaded', function() {
                    const closeButton = document.getElementById('close-success-modal');
                    if (closeButton) {
                        closeButton.addEventListener('click', closeSuccessModal);
                    }

                    // Check for success message in PHP session
                    <?php if (isset($_SESSION['profile_update_success']) && $_SESSION['profile_update_success'] === true): ?>
                    showSuccessModal(<?php echo json_encode($_SESSION['profile_message'] ?? 'Profile updated successfully.'); ?>);
                    <?php
                    // Clear the message from session so it doesn't show again on refresh
                    unset($_SESSION['profile_update_success']);
                    unset($_SESSION['profile_message']);
                    ?>
                    <?php endif; ?>
                });
            </script>


            <!-- Modal HTML -->
            <div id="passwordModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center hidden z-50">
                <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-5">
                    <h2 class="text-lg font-semibold mb-4 text-red-600">Password Mismatch</h2>
                    <p class="text-sm text-gray-600 mb-4">New password and confirm password do not match.</p>
                    <div class="flex justify-end">
                        <button onclick="closeModal('passwordModal')" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">OK</button>
                    </div>
                </div>
            </div>

            <!-- JavaScript -->
            <script>
                function togglePassword(id) {
                    const input = document.getElementById(id);
                    const icon = input.nextElementSibling;
                    const [eye, eyeSlash] = icon.querySelectorAll('svg');

                    if (input.type === "password") {
                        input.type = "text";
                        eye.classList.add("hidden");
                        eyeSlash.classList.remove("hidden");
                    } else {
                        input.type = "password";
                        eye.classList.remove("hidden");
                        eyeSlash.classList.add("hidden");
                    }
                }

                function validatePasswords() {
                    const password = document.getElementById('password').value;
                    const confirm = document.getElementById('confirm_password').value;
                    if (password !== confirm) {
                        document.getElementById('passwordModal').classList.remove('hidden');
                        return false;
                    }
                    return true;
                }

                function closeModal() {
                    document.getElementById('passwordModal').classList.add('hidden');
                }

                // Attach to your form (you can adjust selector as needed)
                document.querySelector('form')?.addEventListener('submit', function(e) {
                    if (!validatePasswords()) {
                        e.preventDefault();
                    }
                });
            </script>

        <?php if (isset($_GET['message'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('messageModal').classList.remove('hidden');
            });
        <?php endif; ?>


            <!-- Submission Success Modal -->
            <div id="submissionSuccessModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
                <div class="bg-white rounded-lg shadow-lg p-6 w-80 text-center">
                    <div class="mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-green-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold mb-2">Submission Successful!</h2>
                    <p id="submissionSuccessMessage" class="text-gray-600 mb-4">Your document has been submitted successfully!</p>
                    <div class="flex justify-between mt-4 gap-2">
                        <button id="goToDashboardBtn" class="px-3 py-1.5 text-sm bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors">
                            Back to Dashboard
                        </button>
                        <button id="submitAnotherBtn" class="px-3 py-1.5 text-sm bg-green-500 text-white rounded hover:bg-green-600 transition-colors">
                            Submit Another
                        </button>
                    </div>
                </div>
            </div>

            <script>
                // Handle form submission with AJAX
                document.querySelector('.submissions-form')?.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const form = e.target;
                    const formData = new FormData(form);

                    // Show loading state
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalBtnText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Submitting...';
                    submitBtn.disabled = true;

                    fetch(form.action, {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Show success modal
                                document.getElementById('submissionSuccessMessage').textContent = data.message;
                                document.getElementById('submissionSuccessModal').classList.remove('hidden');

                                // Reset form
                                form.reset();
                                document.getElementById('file_info').classList.add('hidden');
                            } else {
                                // Show error modal
                                const errorContainer = document.getElementById('submissionErrorMessages');
                                errorContainer.innerHTML = '';

                                data.errors.forEach(error => {
                                    const errorElement = document.createElement('p');
                                    errorElement.className = 'text-sm mb-1';
                                    errorElement.textContent = `• ${error}`;
                                    errorContainer.appendChild(errorElement);
                                });

                                document.getElementById('submissionErrorModal').classList.remove('hidden');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            // Show error modal
                            const errorContainer = document.getElementById('submissionErrorMessages');
                            errorContainer.innerHTML = '<p class="text-sm mb-1">• An unexpected error occurred. Please try again.</p>';
                            document.getElementById('submissionErrorModal').classList.remove('hidden');
                        })
                        .finally(() => {
                            submitBtn.innerHTML = originalBtnText;
                            submitBtn.disabled = false;
                        });
                });

                // Close buttons for modals
                document.getElementById('closeSubmissionError')?.addEventListener('click', function() {
                    document.getElementById('submissionErrorModal').classList.add('hidden');
                });

                // Success modal buttons
                document.getElementById('goToDashboardBtn')?.addEventListener('click', function() {
                    window.location.href = 'userView.php?page=Dashboard';
                });

                document.getElementById('submitAnotherBtn')?.addEventListener('click', function() {
                    document.getElementById('submissionSuccessModal').classList.add('hidden');
                    // Optionally focus on the first form field if needed
                    // document.querySelector('.submissions-form input').focus();
                });
            </script>





















                <?php elseif ($currentPage === 'Achievement'): ?>
                    <!-- Achievement Section -->
                    <div class="p-6 bg-gray-100 min-h-screen">
                        <!-- Campus Leaderboard Section -->
                        <div class="bg-white rounded-lg shadow p-6 mb-6">
                            <h2 class="text-xl font-bold mb-4">Campus Leaderboard</h2>
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
                                        $result = $conn->query("CALL GetLeaderboard()");
                                        if ($result) {
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
                                                echo '<tr><td colspan="3" class="px-6 py-4 text-sm text-gray-500 text-center">No leaderboard data available</td></tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="3" class="px-6 py-4 text-sm text-gray-500 text-center">Failed to load leaderboard</td></tr>';
                                        }
                                        $conn->close();
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Button to Open Achievement Form Modal -->
                        <div class="mb-6">
                            <button id="openAchievementModal" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Submit New Achievement</button>
                        </div>

                        <!-- Achievement Form Modal -->
                        <div id="achievementModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center <?php echo isset($_SESSION['achievement_message']) ? '' : 'hidden'; ?>">
                            <div class="bg-white rounded-lg shadow p-6 w-full max-w-2xl">
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-xl font-bold">Athlete Achievement Form</h2>
                                    <button id="closeAchievementModal" class="text-gray-500 hover:text-gray-700">×</button>
                                </div>
                                <form method="POST" enctype="multipart/form-data" action="../controller/handleAchievement.php" id="achievementForm" class="space-y-4">
                                    <input type="hidden" name="submit_achievement" value="1">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="athlete_name" class="block text-sm font-medium text-gray-700">Athlete Name</label>
                                            <input type="text" id="athlete_name" name="athlete_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" value="<?php echo isset($_SESSION['user']['full_name']) ? htmlspecialchars($_SESSION['user']['full_name']) : ''; ?>">
                                        </div>
                                        <div>
                                            <label for="level_of_competition" class="block text-sm font-medium text-gray-700">Level of Competition</label>
                                            <select id="level_of_competition" name="level_of_competition" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                <option value="">Select</option>
                                                <option value="Local">Local (5 pts)</option>
                                                <option value="Regional">Regional (10 pts)</option>
                                                <option value="National">National (15 pts)</option>
                                                <option value="International">International (20 pts)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="performance" class="block text-sm font-medium text-gray-700">Performance</label>
                                            <select id="performance" name="performance" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                <option value="">Select</option>
                                                <option value="Winner (Gold)">Winner (Gold - 15 pts)</option>
                                                <option value="Silver">Silver (10 pts)</option>
                                                <option value="Bronze">Bronze (5 pts)</option>
                                                <option value="Participant">Participant (2 pts)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="number_of_events" class="block text-sm font-medium text-gray-700">Number of Events</label>
                                            <select id="number_of_events" name="number_of_events" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                <option value="">Select</option>
                                                <option value="1-2">1-2 (5 pts)</option>
                                                <option value="3-4">3-4 (10 pts)</option>
                                                <option value="5+">5+ (15 pts)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="leadership_role" class="block text-sm font-medium text-gray-700">Leadership Role</label>
                                            <select id="leadership_role" name="leadership_role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                <option value="">Select</option>
                                                <option value="Team Captain">Team Captain (10 pts)</option>
                                                <option value="Active Member">Active Member (5 pts)</option>
                                                <option value="Reserve">Reserve (2 pts)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="sportsmanship" class="block text-sm font-medium text-gray-700">Sportsmanship</label>
                                            <select id="sportsmanship" name="sportsmanship" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                <option value="">Select</option>
                                                <option value="No violation">No violation (10 pts)</option>
                                                <option value="Minor warnings">Minor warnings (5 pts)</option>
                                                <option value="Major offense">Major offense (0 pts)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="community_impact" class="block text-sm font-medium text-gray-700">Community Impact</label>
                                            <select id="community_impact" name="community_impact" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                <option value="">Select</option>
                                                <option value="Yes">Yes (10 pts)</option>
                                                <option value="No">No (0 pts)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="completeness_of_documents" class="block text-sm font-medium text-gray-700">Completeness of Documents</label>
                                            <select id="completeness_of_documents" name="completeness_of_documents" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                <option value="">Select</option>
                                                <option value="Complete and verified">Complete and verified (15 pts)</option>
                                                <option value="Incomplete or unclear">Incomplete or unclear (5 pts)</option>
                                                <option value="Not submitted">Not submitted (0 pts)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="documents" class="block text-sm font-medium text-gray-700">Upload Documents</label>
                                            <input type="file" id="documents" name="documents[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Submit Achievement</button>
                                </form>
                                <?php
                                if (isset($_SESSION['achievement_message'])) {
                                    echo '<div id="achievementMessage" class="mt-4 p-4 rounded-lg ' . $_SESSION['message_class'] . '">';
                                    echo htmlspecialchars($_SESSION['achievement_message']);
                                    echo '</div>';
                                    unset($_SESSION['achievement_message']);
                                    unset($_SESSION['message_class']);
                                }
                                ?>
                            </div>
                        </div>

                        <!-- Edit Achievement Form Modal (Replaces Resubmit Modal) -->
                        <div id="editAchievementModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
                            <div class="bg-white rounded-lg shadow p-6 w-full max-w-2xl">
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-xl font-bold">Edit Achievement</h2>
                                    <button id="closeEditModal" class="text-gray-500 hover:text-gray-700">×</button>
                                </div>
                                <form method="POST" enctype="multipart/form-data" action="../controller/handleAchievements.php" id="editAchievementForm" class="space-y-4">
                                    <input type="hidden" name="achievement_id" id="edit_achievement_id">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="existing_documents" id="edit_existing_documents">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="edit_athlete_name" class="block text-sm font-medium text-gray-700">Athlete Name</label>
                                            <input type="text" id="edit_athlete_name" name="athlete_name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" value="<?php echo isset($_SESSION['user']['full_name']) ? htmlspecialchars($_SESSION['user']['full_name']) : ''; ?>">
                                        </div>
                                        <div>
                                            <label for="edit_level_of_competition" class="block text-sm font-medium text-gray-700">Level of Competition</label>
                                            <select id="edit_level_of_competition" name="level_of_competition" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                <option value="Local">Local (5 pts)</option>
                                                <option value="Regional">Regional (10 pts)</option>
                                                <option value="National">National (15 pts)</option>
                                                <option value="International">International (20 pts)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="edit_performance" class="block text-sm font-medium text-gray-700">Performance</label>
                                            <select id="edit_performance" name="performance" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                <option value="Winner (Gold)">Winner (Gold - 15 pts)</option>
                                                <option value="Silver">Silver (10 pts)</option>
                                                <option value="Bronze">Bronze (5 pts)</option>
                                                <option value="Participant">Participant (2 pts)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="edit_number_of_events" class="block text-sm font-medium text-gray-700">Number of Events</label>
                                            <select id="edit_number_of_events" name="number_of_events" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                <option value="1-2">1-2 (5 pts)</option>
                                                <option value="3-4">3-4 (10 pts)</option>
                                                <option value="5+">5+ (15 pts)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="edit_leadership_role" class="block text-sm font-medium text-gray-700">Leadership Role</label>
                                            <select id="edit_leadership_role" name="leadership_role" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                <option value="Team Captain">Team Captain (10 pts)</option>
                                                <option value="Active Member">Active Member (5 pts)</option>
                                                <option value="Reserve">Reserve (2 pts)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="edit_sportsmanship" class="block text-sm font-medium text-gray-700">Sportsmanship</label>
                                            <select id="edit_sportsmanship" name="sportsmanship" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                <option value="No violation">No violation (10 pts)</option>
                                                <option value="Minor warnings">Minor warnings (5 pts)</option>
                                                <option value="Major offense">Major offense (0 pts)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="edit_community_impact" class="block text-sm font-medium text-gray-700">Community Impact</label>
                                            <select id="edit_community_impact" name="community_impact" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                <option value="Yes">Yes (10 pts)</option>
                                                <option value="No">No (0 pts)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="edit_completeness_of_documents" class="block text-sm font-medium text-gray-700">Completeness of Documents</label>
                                            <select id="edit_completeness_of_documents" name="completeness_of_documents" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                <option value="Complete and verified">Complete and verified (15 pts)</option>
                                                <option value="Incomplete or unclear">Incomplete or unclear (5 pts)</option>
                                                <option value="Not submitted">Not submitted (0 pts)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="edit_documents" class="block text-sm font-medium text-gray-700">Upload Documents</label>
                                            <input type="file" id="edit_documents" name="documents[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update Achievement</button>
                                </form>
                            </div>
                        </div>

                        <!-- History Section -->
                        <div class="bg-white rounded-lg shadow p-6 mb-6">
                            <h2 class="text-xl font-bold mb-4">History</h2>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Athlete Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Performance</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Points</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submission Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                    </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                    <?php
                                    $conn = new mysqli($host, $username, $password, $dbname);
                                    if ($conn->connect_error) {
                                        echo '<tr><td colspan="8" class="px-6 py-4 text-sm text-gray-500 text-center">Database connection failed</td></tr>';
                                    } else {
                                        if (isset($_SESSION['user']['id'])) {
                                            $user_id = $_SESSION['user']['id'];
                                            $stmt = $conn->prepare("CALL GetUserAchievements(?)");
                                            $stmt->bind_param("i", $user_id);
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
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">' . htmlspecialchars($row['rejection_reason'] ?? 'N/A') . '</td>';
                                                    echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">';
                                                    if ($row['status'] === 'Pending' || $row['status'] === 'Rejected') {
                                                        echo '<button class="editAchievementBtn px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700" data-id="' . htmlspecialchars($row['achievement_id']) . '" data-athlete="' . htmlspecialchars($row['athlete_name']) . '" data-level="' . htmlspecialchars($row['level_of_competition']) . '" data-performance="' . htmlspecialchars($row['performance']) . '" data-events="' . htmlspecialchars($row['number_of_events']) . '" data-leadership="' . htmlspecialchars($row['leadership_role']) . '" data-sportsmanship="' . htmlspecialchars($row['sportsmanship']) . '" data-community="' . htmlspecialchars($row['community_impact']) . '" data-documents="' . htmlspecialchars($row['completeness_of_documents']) . '" data-docs="' . htmlspecialchars($row['documents'] ?? '') . '">Edit</button>';
                                                    }
                                                    echo '</td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="8" class="px-6 py-4 text-sm text-gray-500 text-center">No achievements found</td></tr>';
                                            }
                                            $stmt->close();
                                        } else {
                                            echo '<tr><td colspan="8" class="px-6 py-4 text-sm text-gray-500 text-center">User not logged in</td></tr>';
                                        }
                                        $conn->close();
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <script>
                        // Open/Close Achievement Modal
                        document.getElementById('openAchievementModal').addEventListener('click', function() {
                            document.getElementById('achievementModal').classList.remove('hidden');
                        });

                        document.getElementById('closeAchievementModal').addEventListener('click', function() {
                            document.getElementById('achievementModal').classList.add('hidden');
                            document.getElementById('achievementForm').reset();
                            document.getElementById('athlete_name').value = '<?php echo isset($_SESSION['user']['full_name']) ? addslashes(htmlspecialchars($_SESSION['user']['full_name'])) : ''; ?>';
                            document.getElementById('achievementMessage')?.classList.add('hidden');
                        });

                        // Open/Close Edit Modal
                        document.querySelectorAll('.editAchievementBtn').forEach(button => {
                            button.addEventListener('click', function() {
                                const modal = document.getElementById('editAchievementModal');
                                document.getElementById('edit_achievement_id').value = this.dataset.id;
                                document.getElementById('edit_athlete_name').value = this.dataset.athlete;
                                document.getElementById('edit_level_of_competition').value = this.dataset.level;
                                document.getElementById('edit_performance').value = this.dataset.performance;
                                document.getElementById('edit_number_of_events').value = this.dataset.events;
                                document.getElementById('edit_leadership_role').value = this.dataset.leadership;
                                document.getElementById('edit_sportsmanship').value = this.dataset.sportsmanship;
                                document.getElementById('edit_community_impact').value = this.dataset.community;
                                document.getElementById('edit_completeness_of_documents').value = this.dataset.documents;
                                document.getElementById('edit_existing_documents').value = this.dataset.docs;
                                modal.classList.remove('hidden');
                            });
                        });

                        document.getElementById('closeEditModal').addEventListener('click', function() {
                            document.getElementById('editAchievementModal').classList.add('hidden');
                            document.getElementById('editAchievementForm').reset();
                            document.getElementById('edit_athlete_name').value = '<?php echo isset($_SESSION['user']['full_name']) ? addslashes(htmlspecialchars($_SESSION['user']['full_name'])) : ''; ?>';
                            document.getElementById('edit_existing_documents').value = '';
                        });

                        // Automatically close the modal after a successful submission
                        <?php if (isset($_SESSION['achievement_message']) && $_SESSION['message_class'] === 'bg-green-100'): ?>
                        setTimeout(function() {
                            document.getElementById('achievementModal').classList.add('hidden');
                            document.getElementById('achievementForm').reset();
                            document.getElementById('athlete_name').value = '<?php echo isset($_SESSION['user']['full_name']) ? addslashes(htmlspecialchars($_SESSION['user']['full_name'])) : ''; ?>';
                            document.getElementById('achievementMessage').classList.add('hidden');
                        }, 2000);
                        <?php endif; ?>
                    </script>









                <?php elseif ($currentPage === 'Submissions'): ?> <!-- Enhanced Submissions Content -->
            <div class="submissions-container">
                <!-- Header Section with Icon -->
                <div class="submissions-header">
                    <div class="header-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h2>Submit Your Documents</h2>
                    <p>One Data. One USeP. OSAS-Sports Unit</p>
                </div>

                <!-- Card Container -->
                <div class="submissions-card">
                    <!-- Progress Indicator -->
                    <div class="progress-indicator">
                        <div class="progress-steps">
                            <div class="step">
                                <div class="step-number">1</div>
                                <p class="step-label">Personal Info</p>
                            </div>
                            <div class="progress-line">
                                <div class="progress-completed"></div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <p class="step-label">Document Details</p>
                            </div>
                            <div class="progress-line">
                                <div class="progress-completed"></div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <p class="step-label">Submit</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form -->
                    <form action="../controller/submit_form.php" method="POST" enctype="multipart/form-data" class="submissions-form" id="submissionForm">
                        <!-- Section: Personal Information -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Personal Information
                            </h3>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="fullname">Full Name</label>
                                    <input type="text" id="fullname" name="fullname"
                                           placeholder="Enter your full name" required
                                           value="<?php echo isset($_SESSION['user']['full_name']) ? htmlspecialchars($_SESSION['user']['full_name']) : ''; ?>">
                                </div>

                                <div class="form-group">
                                    <label for="year_section">Year & Section</label>
                                    <input type="text" id="year_section" name="year_section"
                                           placeholder="Ex: 1IT - BSIT" required
                                           value="<?php echo htmlspecialchars($_SESSION['submissions']['year_section'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="student_id">Student Id</label>
                                    <input type="text" id="student_id" name="student_id"
                                           placeholder="Enter your student id" required
                                           value="<?php echo htmlspecialchars($_SESSION['user']['student_id'] ?? ''); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="contact_email">Email</label>
                                    <input type="email" id="contact_email" name="contact_email"
                                           placeholder="Enter your email" required
                                           value="<?php echo htmlspecialchars($_SESSION['user']['email'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Section: Document Information -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Document Information
                            </h3>

                            <div class="form-stack">
                                <div class="form-group">
                                    <label for="document_type">Document Type</label>
                                    <select id="document_type" name="document_type" required>
                                        <option value="" disabled selected>Select document type</option>
                                        <option value="Medical Certificate">Medical Certificate</option>
                                        <option value="Certification">Certification</option>
                                        <option value="Recommendation Letter">Recommendation Letter</option>
                                        <option value="Sports Clearance">Sports Clearance</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>

                                <div id="other_type_container" class="form-group hidden">
                                    <label for="other_type">Specify Other Document Type</label>
                                    <input type="text" id="other_type" name="other_type" placeholder="Specify document type">
                                </div>

                                <div class="form-group">
                                    <label for="uploaded_file">Upload Document</label>
                                    <div class="file-upload-area">
                                        <input type="file" id="uploaded_file" name="uploaded_file" class="hidden" required>
                                        <label for="uploaded_file" class="file-upload-label">
                                            <div class="upload-icon-container">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                </svg>
                                            </div>
                                            <span class="upload-instruction">Click to upload or drag and drop</span>
                                            <span class="upload-requirements">PDF, DOC, DOCX, JPG, PNG (Max 5MB)</span>
                                        </label>
                                        <div id="file_info" class="file-info hidden">
                                            <span class="file-info-label">Selected file:</span> <span id="file_name"></span>
                                            <button type="button" id="preview_button" class="ml-2 px-2 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600 transition-colors" onclick="previewFile()">Preview</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Description -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                                </svg>
                                Description
                            </h3>

                            <div class="form-group">
                                <label for="description">Document Description</label>
                                <textarea id="description" name="description" placeholder="Provide a brief description of the document" required></textarea>
                                <p class="form-hint">Please provide any relevant details about your document submission.</p>
                                <p id="desc-warning" style="color: red; display: none;">Description must be at least 10 characters long.</p>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="form-submit">
                            <button type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Submit Document
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- File Preview Modal -->
            <div id="filePreviewModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden" role="dialog" aria-labelledby="filePreviewModalTitle" aria-modal="true">
                <div id="fileDownloadLink" class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-3xl border border-gray-200 mx-4 flex flex-col" style="height: 45vw">
                    <div class="modal-header relative mb-4">
                        <h2 id="filePreviewModalTitle" class="text-lg font-semibold text-gray-800">Preview File</h2>
                        <button onclick="closeModal('filePreviewModal')" class="modal-close-btn absolute top-0 right-0 text-gray-400 hover:text-gray-600 transition-colors" aria-label="Close modal">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div id="filePreviewContent" class="w-full flex-1 overflow-auto bg-gray-50">
                        <!-- Content will be injected here -->
                    </div>
                </div>
            </div>

            <!-- Submission Confirmation Modal -->
            <div id="submissionConfirmationModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 <?php echo isset($_SESSION['show_submission_modal']) && $_SESSION['show_submission_modal'] ? '' : 'hidden'; ?>" role="dialog" aria-labelledby="submissionConfirmationModalTitle" aria-modal="true">
                <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md border border-gray-200 mx-4">
                    <div class="modal-header relative mb-4">
                        <h2 id="submissionConfirmationModalTitle" class="text-lg font-semibold text-gray-800">Submission Complete</h2>
                        <button onclick="closeModal('submissionConfirmationModal')" class="modal-close-btn absolute top-0 right-0 text-gray-400 hover:text-gray-600 transition-colors" aria-label="Close modal">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="text-gray-600 mb-4">Your document has been submitted successfully! Would you like to submit another document or go to the dashboard?</p>
                    </div>
                    <div class="modal-footer">
                        <button onclick="submitAnother()" class="action-btn bg-blue-500 text-white hover:bg-blue-600">Submit Another</button>
                        <button onclick="goToDashboard()" class="action-btn bg-gray-500 text-white hover:bg-gray-600">Go to Dashboard</button>
                    </div>
                </div>
            </div>

            <style>
                /* Submissions Page Responsive Styles */
                .submissions-container {
                    padding: 1rem;
                    max-width: 1200px;
                    margin: 0 auto;
                }

                .submissions-header {
                    text-align: center;
                    margin-bottom: 2rem;
                }

                .submissions-card {
                    background-color: white;
                    border-radius: 0.75rem;
                    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                    border: 1px solid #e5e7eb;
                    padding: 1.25rem;
                    margin-bottom: 2rem;
                }

                /* Progress Indicator */
                .progress-steps {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 2rem;
                }

                .step {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    position: relative;
                    z-index: 1;
                    flex: 1;
                }

                .step-number {
                    width: 2.5rem;
                    height: 2.5rem;
                    background-color: #ef4444;
                    border-radius: 9999px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: white;
                    font-weight: 700;
                    margin-bottom: 0.5rem;
                }

                .step-label {
                    font-size: 0.875rem;
                    font-weight: 500;
                    text-align: center;
                    color: #1f2937;
                }

                .progress-line {
                    flex: 1;
                    height: 0.25rem;
                    margin: 0 0.5rem;
                    background-color: #fecaca;
                    position: relative;
                }

                .progress-completed {
                    height: 100%;
                    width: 100%;
                    background-color: #ef4444;
                }

                /* Form Sections */
                .form-section {
                    background-color: #f9fafb;
                    padding: 1.5rem;
                    border-radius: 0.5rem;
                    margin-bottom: 1.5rem;
                }

                .section-title {
                    font-size: 1.125rem;
                    font-weight: 600;
                    color: #1f2937;
                    margin-bottom: 1rem;
                    display: flex;
                    align-items: center;
                }

                .section-title svg {
                    height: 1.25rem;
                    width: 1.25rem;
                    color: #ef4444;
                    margin-right: 0.5rem;
                }

                /* Form Grid Layout */
                .form-grid {
                    display: grid;
                    gap: 1rem;
                }

                .form-stack {
                    display: flex;
                    flex-direction: column;
                    gap: 1rem;
                }

                .form-group {
                    margin-bottom: 1rem;
                }

                .form-group label {
                    display: block;
                    font-size: 0.875rem;
                    font-weight: 500;
                    color: #374151;
                    margin-bottom: 0.5rem;
                }

                .form-group input,
                .form-group select,
                .form-group textarea {
                    width: 100%;
                    border: 1px solid #d1d5db;
                    border-radius: 0.5rem;
                    padding: 0.75rem;
                    font-size: 1rem;
                    transition: all 0.2s ease;
                }

                .form-group textarea {
                    min-height: 120px;
                    resize: vertical;
                }

                /* File Upload Area */
                .file-upload-area {
                    border: 2px dashed #d1d5db;
                    border-radius: 0.5rem;
                    padding: 2rem;
                    text-align: center;
                    cursor: pointer;
                    transition: all 0.2s ease;
                }

                .file-upload-area:hover {
                    border-color: #ef4444;
                    background-color: #fef2f2;
                }

                .file-upload-label {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                }

                .file-upload-label svg {
                    height: 2.5rem;
                    width: 2.5rem;
                    color: #9ca3af;
                    margin-bottom: 0.5rem;
                }

                .upload-instruction {
                    font-size: 1rem;
                    font-weight: 500;
                    color: #374151;
                    margin-bottom: 0.25rem;
                }

                .upload-requirements {
                    font-size: 0.875rem;
                    color: #6b7280;
                }

                /* Submit Button */
                .form-submit {
                    text-align: center;
                    margin-top: 1.5rem;
                }

                .form-submit button {
                    background-color: #ef4444;
                    color: white;
                    font-weight: 700;
                    padding: 0.75rem 2rem;
                    border-radius: 0.5rem;
                    font-size: 1rem;
                    border: none;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }

                .form-submit button:hover {
                    background-color: #dc2626;
                }

                .form-submit button svg {
                    height: 1.25rem;
                    width: 1.25rem;
                    margin-right: 0.5rem;
                }

                /* Utility Classes */
                .hidden {
                    display: none;
                }

                /* Responsive Adjustments */
                @media (min-width: 768px) {
                    .submissions-container {
                        padding: 2rem;
                    }

                    .submissions-card {
                        padding: 2rem;
                    }

                    .form-grid {
                        grid-template-columns: repeat(2, 1fr);
                    }

                    .file-upload-area {
                        padding: 3rem;
                    }
                }

                @media (max-width: 767px) {
                    .progress-steps {
                        flex-wrap: wrap;
                        justify-content: center;
                    }

                    .step {
                        min-width: 80px;
                        margin-bottom: 1rem;
                    }

                    .progress-line {
                        display: none;
                    }

                    .file-upload-area {
                        padding: 1.5rem;
                    }
                }

                /* Modal Styles */
                .modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 0.75rem 1rem;
                    border-bottom: 1px solid #e5e7eb;
                }

                .modal-close-btn {
                    cursor: pointer;
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
                }
            </style>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Show SweetAlert if submission was successful
                    <?php if (isset($_SESSION['show_success_alert']) && $_SESSION['show_success_alert']): ?>
                    Swal.fire({
                        title: 'Success!',
                        text: 'Your submission was successful.',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#ef4444'
                    });
                    <?php unset($_SESSION['show_success_alert']); ?>
                    <?php endif; ?>

                    // Show submission confirmation modal if set
                    <?php if (isset($_SESSION['show_submission_modal']) && $_SESSION['show_submission_modal']): ?>
                    document.getElementById('submissionConfirmationModal').classList.remove('hidden');
                    <?php unset($_SESSION['show_submission_modal']); ?>
                    <?php endif; ?>

                    // Handle document type "Others" selection
                    const documentTypeSelect = document.getElementById('document_type');
                    const otherTypeContainer = document.getElementById('other_type_container');
                    const otherTypeInput = document.getElementById('other_type');

                    documentTypeSelect.addEventListener('change', function() {
                        if (this.value === 'Others') {
                            otherTypeContainer.classList.remove('hidden');
                            otherTypeInput.setAttribute('required', 'required');
                        } else {
                            otherTypeContainer.classList.add('hidden');
                            otherTypeInput.removeAttribute('required');
                            otherTypeInput.value = '';
                        }
                    });

                    // File upload preview
                    const fileInput = document.getElementById('uploaded_file');
                    const fileInfo = document.getElementById('file_info');
                    const fileName = document.getElementById('file_name');
                    const previewButton = document.getElementById('preview_button');

                    fileInput.addEventListener('change', function() {
                        if (this.files.length > 0) {
                            const file = this.files[0];
                            const fileSize = (file.size / (1024 * 1024)).toFixed(2); // Convert to MB

                            fileName.textContent = `${file.name} (${fileSize} MB)`;
                            fileInfo.classList.remove('hidden');
                            previewButton.classList.remove('hidden');

                            // Validate file size (5MB max)
                            if (file.size > 5 * 1024 * 1024) {
                                alert('File size exceeds 5MB limit. Please choose a smaller file.');
                                this.value = ''; // Clear the input
                                fileInfo.classList.add('hidden');
                                previewButton.classList.add('hidden');
                            }
                        } else {
                            fileInfo.classList.add('hidden');
                            previewButton.classList.add('hidden');
                        }
                    });

                    // Drag and drop functionality
                    const uploadArea = document.querySelector('.file-upload-area');

                    uploadArea.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        uploadArea.classList.add('drag-over');
                    });

                    uploadArea.addEventListener('dragleave', () => {
                        uploadArea.classList.remove('drag-over');
                    });

                    uploadArea.addEventListener('drop', (e) => {
                        e.preventDefault();
                        uploadArea.classList.remove('drag-over');

                        if (e.dataTransfer.files.length) {
                            fileInput.files = e.dataTransfer.files;
                            const event = new Event('change');
                            fileInput.dispatchEvent(event);
                        }
                    });

                    // Description validation
                    const description = document.getElementById("description");
                    const warning = document.getElementById("desc-warning");

                    description.addEventListener("input", () => {
                        if (description.value.trim().length < 10) {
                            warning.style.display = "block";
                        } else {
                            warning.style.display = "none";
                        }
                    });
                });

                // File preview function
                function previewFile() {
                    const fileInput = document.getElementById('uploaded_file');
                    const modal = document.getElementById('filePreviewModal');
                    const preview = document.getElementById('filePreviewContent');
                    const downloadLink = document.getElementById('fileDownloadLink');

                    if (!fileInput.files.length) {
                        alert('Please select a file first.');
                        return;
                    }

                    const file = fileInput.files[0];
                    const url = URL.createObjectURL(file);

                    // Show loading state
                    preview.innerHTML = `
                    <div class="flex items-center justify-center min-h-full py-6">
                        <div class="text-center">
                            <svg class="animate-spin h-6 w-6 text-blue-500 mx-auto" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="mt-2 text-gray-500 text-sm">Loading file...</p>
                        </div>
                    </div>
                `;
                    modal.classList.remove('hidden');

                    // Simulate fetch with File API
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const blob = new Blob([e.target.result], { type: file.type || 'application/octet-stream' });
                        const contentType = file.type || 'application/octet-stream';

                        downloadLink.href = url;
                        downloadLink.classList.remove('hidden');

                        if (contentType === 'application/pdf') {
                            preview.innerHTML = `<iframe src="${url}#zoom=auto" style="width:100%; height:100%; max-height:100%;" frameborder="0" title="File Preview" aria-label="PDF file preview"></iframe>`;
                        } else if (contentType.startsWith('image/')) {
                            preview.innerHTML = `<img src="${url}" alt="Uploaded File" class="w-full h-full object-contain" aria-label="Image preview" />`;
                        } else {
                            preview.innerHTML = `
                            <div class="text-center py-6 text-red-500 text-sm">
                                Preview not available for this file type. Please download to view.
                            </div>
                        `;
                            downloadLink.classList.remove('hidden');
                        }
                    };
                    reader.onerror = function() {
                        preview.innerHTML = `
                        <div class="text-center py-6 text-red-500 text-sm">
                            Error loading file.
                        </div>
                    `;
                        downloadLink.classList.remove('hidden');
                    };
                    reader.readAsArrayBuffer(file);

                    // Add click event to close modal when clicking outside
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) {
                            closeModal('filePreviewModal');
                        }
                    }, { once: true });
                }

                // Close modal function
                function closeModal(modalId) {
                    const modal = document.getElementById(modalId);
                    const preview = document.getElementById('filePreviewContent');
                    modal.classList.add('hidden');
                    if (preview) {
                        const iframes = preview.getElementsByTagName('iframe');
                        const images = preview.getElementsByTagName('img');
                        for (let iframe of iframes) {
                            URL.revokeObjectURL(iframe.src);
                        }
                        for (let img of images) {
                            URL.revokeObjectURL(img.src);
                        }
                        preview.innerHTML = '';
                    }
                }

                // Submit another document (reset form)
                function submitAnother() {
                    document.getElementById('submissionConfirmationModal').classList.add('hidden');
                    document.getElementById('submissionForm').reset();
                    document.getElementById('file_info').classList.add('hidden');
                    document.getElementById('preview_button').classList.add('hidden');
                    document.getElementById('other_type_container').classList.add('hidden');
                    document.getElementById('other_type').removeAttribute('required');
                    document.getElementById('other_type').value = '';
                    document.getElementById('desc-warning').style.display = 'none';
                }

                // Go to dashboard
                function goToDashboard() {
                    window.location.href = 'http://localhost/SportsOfficeAIM/view/userView.php?page=Dashboard';
                }
            </script>














        <?php elseif ($currentPage === 'Track'): ?>
            <!-- Success Message Display -->
            <div id="successMessage" class="fixed top-4 right-4 z-50 transition-all duration-300 transform translate-x-full">
                <?php if (isset($_SESSION['submission_success'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg shadow-lg max-w-sm">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <p class="font-medium"><?php echo htmlspecialchars($_SESSION['submission_success']); ?></p>
                            <button onclick="document.getElementById('successMessage').classList.add('translate-x-full')" class="ml-auto text-green-700 hover:text-green-900">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <?php unset($_SESSION['submission_success']); ?>
                <?php endif; ?>
            </div>

            <script>
                // Show success message if it exists
                document.addEventListener('DOMContentLoaded', function() {
                    const successMessage = document.getElementById('successMessage');
                    if (successMessage.innerHTML.trim() !== '') {
                        setTimeout(() => {
                            successMessage.classList.remove('translate-x-full');
                            setTimeout(() => {
                                successMessage.classList.add('translate-x-full');
                            }, 5000);
                        }, 300);
                    }
                });
            </script>

        <div class="p-4 sm:p-6 lg:p-8 bg-gray-100 min-h-screen">
            <div class="space-y-8 max-w-7xl mx-auto">
                <?php
                // Database connection using environment variables
                $host = getenv('DB_HOST') ?: 'localhost';
                $username = getenv('DB_USER') ?: 'root';
                $password = getenv('DB_PASS') ?: '';
                $dbname = getenv('DB_NAME') ?: 'SportOfficeDB';

                try {
                    $conn = new mysqli($host, $username, $password, $dbname);
                    if ($conn->connect_error) {
                        throw new Exception("Connection failed: " . mysqli_escape_string($conn, $conn->connect_error));
                    }
                    $conn->set_charset("utf8mb4");
                } catch (Exception $e) {
                    echo '<p class="text-red-600 text-center py-8 text-lg font-semibold bg-white rounded-xl shadow-md">Database error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    exit;
                }

                // Secure user ID handling
                $user_id = isset($_SESSION['user']['id']) ? filter_var($_SESSION['user']['id'], FILTER_VALIDATE_INT) : 0;
                if ($user_id === false || $user_id === 0) {
                    echo '<p class="text-red-600 text-center py-8 text-lg font-semibold bg-white rounded-xl shadow-md">Invalid user session.</p>';
                    exit;
                }

                // Fetch submissions
                try {
                    $stmt = $conn->prepare("
                    SELECT id, document_type, submission_date, status, description, file_name, other_type 
                    FROM submissions 
                    WHERE user_id = ? 
                    ORDER BY submission_date DESC
                ");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } catch (Exception $e) {
                    echo '<p class="text-red-600 text-center py-8 text-lg font-semibold bg-white rounded-xl shadow-md">Query error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    exit;
                }

                // Fetch user profile image
                $profile_image_data = null;
                $profile_image_type = null;
                try {
                    $profile_stmt = $conn->prepare("SELECT image, image_type FROM user_images WHERE user_id = ? ORDER BY uploaded_at DESC LIMIT 1");
                    $profile_stmt->bind_param("i", $user_id);
                    $profile_stmt->execute();
                    $profile_result = $profile_stmt->get_result();
                    if ($profile_result->num_rows > 0) {
                        $profile_row = $profile_result->fetch_assoc();
                        $profile_image_data = $profile_row['image'];
                        $profile_image_type = $profile_row['image_type'];
                    }
                    $profile_stmt->close();
                } catch (Exception $e) {
                    error_log("Profile image query error: " . $e->getMessage());
                }

                // Status display mapping
                function getStatusDisplay($status) {
                    $map = [
                        'pending' => ['display' => 'Pending Review', 'class' => 'bg-yellow-100 text-yellow-800'],
                        'approved' => ['display' => 'Approved', 'class' => 'bg-green-100 text-green-800'],
                        'rejected' => ['display' => 'Rejected', 'class' => 'bg-red-100 text-red-800'],
                    ];
                    return $map[$status] ?? ['display' => $status, 'class' => 'bg-gray-100 text-gray-800'];
                }

                if ($result->num_rows > 0) { ?>
                    <div class="bg-white rounded-xl shadow-md p-4 sm:p-6">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                            <h3 class="text-xl sm:text-2xl font-bold text-gray-900">Your Submissions</h3>
                            <div class="relative w-full sm:w-auto">
                                <select id="statusFilter" class="appearance-none bg-white border border-gray-300 rounded-lg py-2.5 px-4 pr-10 text-sm text-gray-800 focus:ring-2 focus:ring-blue-600 focus:border-blue-600 w-full sm:w-48 transition-all duration-200">
                                    <option value="all">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                                <svg class="w-5 h-5 absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-600 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <!-- Add aria-live region for accessibility -->
                            <div id="filterStatus" class="sr-only" aria-live="polite"></div>
                            <table class="min-w-full divide-y divide-gray-200" role="grid" aria-label="Submissions table">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 sm:px-6 py-3.5 text-left text-xs sm:text-sm font-semibold text-gray-800 uppercase tracking-wider">Document Type</th>
                                    <th scope="col" class="px-4 sm:px-6 py-3.5 text-left text-xs sm:text-sm font-semibold text-gray-800 uppercase tracking-wider">Submission Date</th>
                                    <th scope="col" class="px-4 sm:px-6 py-3.5 text-left text-xs sm:text-sm font-semibold text-gray-800 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-4 sm:px-6 py-3.5 text-left text-xs sm:text-sm font-semibold text-gray-800 uppercase tracking-wider">Actions</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                <?php while ($doc = $result->fetch_assoc()) {
                                    $submission_date = $doc['submission_date'] ? date("m-d-Y", strtotime($doc['submission_date'])) : 'N/A';
                                    $status_info = getStatusDisplay($doc['status']);
                                    ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200" role="row">
                                        <td class="px-4 sm:px-6 py-4 text-sm font-medium text-gray-900 sm:truncate" data-label="Document Type"><?php echo htmlspecialchars($doc['document_type']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-sm text-gray-600" data-label="Submission Date"><?php echo htmlspecialchars($submission_date); ?></td>
                                        <td class="px-4 sm:px-6 py-4" data-label="Status">
                                        <span class="inline-flex px-3 py-1.5 text-xs sm:text-sm font-semibold rounded-full <?php echo htmlspecialchars($status_info['class']); ?>" data-status="<?php echo htmlspecialchars($doc['status']); ?>" aria-label="Status: <?php echo htmlspecialchars($status_info['display']); ?>">
                                            <?php echo htmlspecialchars($status_info['display']); ?>
                                        </span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4 text-sm font-medium" data-label="Actions">
                                            <div class="flex space-x-4">
                                                <button onclick="openDocumentModal('<?php echo htmlspecialchars(json_encode($doc['document_type'])); ?>', '<?php echo htmlspecialchars(json_encode($submission_date)); ?>', '<?php echo htmlspecialchars(json_encode($status_info['display'])); ?>', '<?php echo htmlspecialchars(json_encode($doc['description'])); ?>', '<?php echo htmlspecialchars(json_encode($doc['file_name'])); ?>', '<?php echo htmlspecialchars(json_encode($doc['id'])); ?>', '<?php echo htmlspecialchars(json_encode($doc['status'])); ?>')"
                                                        class="text-gray-600 hover:text-blue-600 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" aria-label="View document <?php echo htmlspecialchars($doc['document_type']); ?>">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </button>
                                                <?php if ($doc['status'] !== 'approved'): ?>
                                                    <button onclick="openEditModal('<?php echo htmlspecialchars(json_encode($doc['id'])); ?>', '<?php echo htmlspecialchars(json_encode($doc['document_type'])); ?>', '<?php echo htmlspecialchars(json_encode($doc['other_type'])); ?>', '<?php echo htmlspecialchars(json_encode($doc['description'])); ?>', '<?php echo htmlspecialchars(json_encode($doc['file_name'])); ?>', '<?php echo htmlspecialchars(json_encode($doc['status'])); ?>', '<?php echo htmlspecialchars(json_encode($submission_date)); ?>')"
                                                            class="text-gray-600 hover:text-blue-600 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" aria-label="Edit document <?php echo htmlspecialchars($doc['document_type']); ?>">
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php } else {
                    echo '<p class="text-gray-600 text-center py-8 text-lg font-semibold bg-white rounded-xl shadow-md">No submissions found.</p>';
                }

                // Clean up
                $stmt->close();
                $conn->close();

                // Prepare user data for JavaScript
                $user_data = [
                    'full_name' => htmlspecialchars($_SESSION['user']['full_name'] ?? 'Unknown'),
                    'student_id' => htmlspecialchars($_SESSION['user']['student_id'] ?? 'N/A')
                ];
                ?>
                <script>
                    const userData = <?php echo json_encode($user_data); ?>;
                </script>
            </div>

            <!-- Edit Submission Modal -->
            <div id="editSubmissionModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 transition-opacity duration-300 hidden" role="dialog" aria-labelledby="edit-modal-title">
                <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-5xl mx-4 sm:p-8 transform transition-all duration-300 scale-100" aria-modal="true">
                    <div class="relative mb-6">
                        <h2 id="edit-modal-title" class="text-2xl font-bold text-gray-900">Edit Submission</h2>
                        <p class="text-sm text-gray-600 mt-1">Update your document details below</p>
                        <button onclick="closeModal('editSubmissionModal')" class="absolute top-0 right-0 text-gray-500 hover:text-gray-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500" aria-label="Close modal">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form id="editSubmissionForm" action="../controller/update_submission.php" method="POST" enctype="multipart/form-data" onsubmit="return validateEditForm()">
                        <input type="hidden" name="submission_id" id="edit_submission_id">
                        <input type="hidden" name="is_resubmission" id="is_resubmission" value="0">
                        <div class="flex flex-col xl:flex-row gap-10">
                            <div class="left-side w-full xl:w-[55%]">
                                <!-- Profile Info -->
                                <div class="flex items-center gap-4 mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="w-14 h-14 rounded-full border-2 border-gray-300 overflow-hidden">
                                        <?php if ($profile_image_data && $profile_image_type): ?>
                                            <img src="data:<?php echo htmlspecialchars($profile_image_type); ?>;base64,<?php echo base64_encode($profile_image_data); ?>"
                                                 alt="Profile" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <svg class="w-9 h-9 text-gray-400 mx-auto my-2" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-lg font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? 'Unknown'); ?></p>
                                        <p class="text-sm text-gray-600 truncate">ID: <?php echo htmlspecialchars($_SESSION['user']['student_id'] ?? 'N/A'); ?></p>
                                    </div>
                                </div>

                                <!-- Document Type Section - Only shown for rejected documents -->
                                <div id="documentTypeSection" class="mb-6 hidden">
                                    <label class="text-base font-medium text-gray-700 block mb-2">Document Type</label>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <input type="radio" id="document_type_standard" name="document_type" value="standard" class="peer hidden" checked>
                                            <label for="document_type_standard" class="block p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all">
                                                <span class="block text-sm font-medium text-gray-900">Standard Document</span>
                                                <span class="block text-xs text-gray-500 mt-1">(Medical, Academic, etc.)</span>
                                            </label>
                                        </div>
                                        <div>
                                            <input type="radio" id="document_type_other" name="document_type" value="other" class="peer hidden">
                                            <label for="document_type_other" class="block p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition-all">
                                                <span class="block text-sm font-medium text-gray-900">Other Document</span>
                                                <span class="block text-xs text-gray-500 mt-1">(Specify your own type)</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div id="otherTypeContainer" class="mt-4 hidden">
                                        <label for="other_type" class="text-sm font-medium text-gray-700 block mb-1.5">Specify Document Type</label>
                                        <input type="text" id="other_type" name="other_type" class="w-full p-3 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-all bg-white shadow-sm">
                                    </div>
                                </div>

                                <!-- Document Type Display - Shown for non-rejected documents -->
                                <div id="documentTypeDisplay" class="flex justify-between items-center mb-6 gap-4">
                                    <div class="flex items-center gap-3">
                                        <span class="text-base font-medium text-gray-700">Document Type:</span>
                                        <p id="edit_document_type_display" class="text-base font-semibold text-gray-900 truncate"></p>
                                    </div>
                                    <div id="edit_status_display" class="text-sm px-3 py-1.5 rounded-full bg-gray-100 text-gray-800 font-semibold"></div>
                                </div>

                                <!-- Description -->
                                <div class="mb-6">
                                    <label for="edit_description" class="text-base font-medium text-gray-700 block mb-2">Description</label>
                                    <span id="edit_desc_warning" class="text-sm text-red-600 hidden mb-2 block">Minimum 10 characters</span>
                                    <textarea id="edit_description" name="description" rows="6"
                                              class="w-full p-4 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-all resize-none bg-white shadow-sm"
                                              style="min-height: 180px;" required></textarea>
                                </div>
                            </div>
                            <div class="right-side w-full xl:w-[45%]">
                                <!-- File Upload -->
                                <div class="mb-8">
                                    <label for="uploaded_file" class="text-base font-medium text-gray-700 block mb-2">Upload New File (Optional)</label>
                                    <div class="file-upload-area border-2 border-gray-300 rounded-xl p-8 bg-white hover:bg-gray-50 transition-colors duration-200 shadow-sm">
                                        <input type="file" id="uploaded_file" name="uploaded_file" class="hidden" accept=".pdf,.doc,.docx,.jpg,.png">
                                        <label for="uploaded_file" class="file-upload-label cursor-pointer block text-center">
                                            <div class="upload-icon-container mx-auto w-14 h-14 text-gray-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" class="w-full h-full">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                                </svg>
                                            </div>
                                            <span class="upload-instruction block text-base font-medium text-gray-700 mt-3">Click to upload or drag and drop</span>
                                            <span class="upload-requirements block text-sm text-gray-500 mt-2">PDF, DOC, DOCX, JPG, PNG (Max 5MB)</span>
                                        </label>
                                        <div id="file_info" class="file-info hidden mt-4 text-center">
                                            <span class="file-info-label text-base text-gray-600">Selected file:</span>
                                            <span id="file_name" class="text-base font-medium text-gray-900"></span>
                                        </div>
                                        <span id="edit_file_warning" class="text-sm text-red-600 hidden block mt-3">Please select a valid file</span>
                                    </div>
                                </div>
                                <!-- Submission Date -->
                                <div class="border-t-2 border-gray-200 pt-6">
                                    <p class="text-base text-gray-600 text-center sm:text-left">
                                        <span>Submitted: </span>
                                        <span id="edit_submission_date" class="font-medium text-gray-800"></span>
                                    </p>
                                </div>
                                <!-- Action Buttons -->
                                <div class="flex flex-col gap-4 mt-10">
                                    <div class="flex justify-center gap-4">
                                        <button type="submit" id="edit_submit_button"
                                                class="py-3 px-10 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg text-base font-semibold hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:bg-gray-400 disabled:cursor-not-allowed">
                                            Save Changes
                                        </button>
                                        <button type="button" id="resubmit_button"
                                                class="py-3 px-10 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg text-base font-semibold hover:from-green-700 hover:to-green-800 transition-all duration-200 shadow-lg focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 disabled:bg-gray-400 disabled:cursor-not-allowed hidden"
                                                onclick="resubmitSubmission()">
                                            Resubmit
                                        </button>
                                        <button type="button" id="delete_submission_button"
                                                class="py-3 px-10 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg text-base font-semibold hover:from-red-700 hover:to-red-800 transition-all duration-200 shadow-lg focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2"
                                                onclick="deleteSubmission()">
                                            Delete Submission
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Error Message -->
                        <div id="form_error_message" class="text-center text-base text-red-600 mb-6 hidden"></div>
                    </form>
                </div>
            </div>

            <!-- Success Modal -->
            <div id="successModal" class="fixed inset-0 z-60 flex items-center justify-center bg-black bg-opacity-70 transition-opacity duration-300 hidden">
                <div class="bg-white rounded-lg p-6 max-w-sm mx-4 shadow-xl transform transition-all duration-300 scale-100">
                    <div class="flex items-center gap-3 mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <h3 class="text-lg font-bold text-gray-900">Success</h3>
                    </div>
                    <p id="successModalMessage" class="text-base text-gray-600">Your submission was deleted successfully.</p>
                </div>
            </div>

            <script>
                // Document type radio button toggle
                document.querySelectorAll('input[name="document_type"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        const otherTypeContainer = document.getElementById('otherTypeContainer');
                        otherTypeContainer.classList.toggle('hidden', this.value !== 'other');
                        if (this.value !== 'other') {
                            document.getElementById('other_type').value = ''; // Clear other type input when not selected
                        }
                    });
                });

                // Client-side form validation for edit
                function validateEditForm() {
                    const description = document.getElementById('edit_description').value.trim();
                    const documentTypeSection = document.getElementById('documentTypeSection');
                    const otherTypeInput = document.getElementById('other_type');
                    const fileInput = document.getElementById('uploaded_file');
                    const errorMessage = document.getElementById('form_error_message');

                    // Reset error states
                    errorMessage.classList.add('hidden');
                    document.getElementById('edit_desc_warning').classList.add('hidden');
                    document.getElementById('edit_file_warning').classList.add('hidden');

                    // Validate description
                    if (description.length < 10) {
                        document.getElementById('edit_desc_warning').classList.remove('hidden');
                        errorMessage.textContent = 'Description must be at least 10 characters long.';
                        errorMessage.classList.remove('hidden');
                        return false;
                    }

                    // Validate document type for rejected documents
                    if (!documentTypeSection.classList.contains('hidden')) {
                        const isOtherType = document.getElementById('document_type_other').checked;
                        if (isOtherType && !otherTypeInput.value.trim()) {
                            errorMessage.textContent = 'Please specify a document type.';
                            errorMessage.classList.remove('hidden');
                            return false;
                        }
                    }

                    // Validate file (if uploaded)
                    if (fileInput.files.length > 0) {
                        const file = fileInput.files[0];
                        const validTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
                        const maxSize = 5 * 1024 * 1024; // 5MB
                        if (!validTypes.includes(file.type)) {
                            document.getElementById('edit_file_warning').classList.remove('hidden');
                            errorMessage.textContent = 'Invalid file type. Please upload PDF, DOC, DOCX, JPG, or PNG.';
                            errorMessage.classList.remove('hidden');
                            return false;
                        }
                        if (file.size > maxSize) {
                            document.getElementById('edit_file_warning').classList.remove('hidden');
                            errorMessage.textContent = 'File size exceeds 5MB limit.';
                            errorMessage.classList.remove('hidden');
                            return false;
                        }
                    }

                    return true;
                }

                // Show success modal
                function showSuccessModal(message) {
                    const successModal = document.getElementById('successModal');
                    const successMessage = document.getElementById('successModalMessage');
                    successMessage.textContent = message;
                    successModal.classList.remove('hidden');
                    setTimeout(() => {
                        successModal.classList.add('hidden');
                        window.location.reload(); // Reload the page after modal closes
                    }, 2000); // Display for 2 seconds
                }

                // Resubmit submission function with AJAX
                async function resubmitSubmission() {
                    const submissionId = document.getElementById('edit_submission_id').value;
                    const errorMessage = document.getElementById('form_error_message');

                    if (!submissionId) {
                        errorMessage.textContent = 'No submission ID found.';
                        errorMessage.classList.remove('hidden');
                        return;
                    }

                    if (confirm('Are you sure you want to resubmit this submission?')) {
                        try {
                            const response = await fetch('../controller/resubmit_submission.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `id=${encodeURIComponent(submissionId)}`
                            });
                            const result = await response.json();

                            if (result.success) {
                                closeModal('editSubmissionModal');
                                showSuccessModal('Submission resubmitted successfully');
                            } else {
                                errorMessage.textContent = result.message;
                                errorMessage.classList.remove('hidden');
                            }
                        } catch (e) {
                            console.error('Error resubmitting submission:', e);
                            errorMessage.textContent = 'An error occurred while resubmitting. Please try again.';
                            errorMessage.classList.remove('hidden');
                        }
                    }
                }

                // Delete submission function with AJAX
                async function deleteSubmission() {
                    const submissionId = document.getElementById('edit_submission_id').value;
                    const errorMessage = document.getElementById('form_error_message');

                    if (!submissionId) {
                        errorMessage.textContent = 'No submission ID found.';
                        errorMessage.classList.remove('hidden');
                        return;
                    }

                    if (confirm('Are you sure you want to delete this submission? This action cannot be undone.')) {
                        try {
                            const response = await fetch('../controller/delete_submission.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `id=${encodeURIComponent(submissionId)}`
                            });
                            const result = await response.json();

                            if (result.success) {
                                closeModal('editSubmissionModal');
                                showSuccessModal('Your submission was deleted successfully');
                            } else {
                                errorMessage.textContent = result.message;
                                errorMessage.classList.remove('hidden');
                            }
                        } catch (e) {
                            console.error('Error deleting submission:', e);
                            errorMessage.textContent = 'An error occurred while deleting. Please try again.';
                            errorMessage.classList.remove('hidden');
                        }
                    }
                }

                // Update the openEditModal function to handle button visibility
                function openEditModal(id, documentType, otherType, description, fileName, status, submissionDate) {
                    const modal = document.getElementById('editSubmissionModal');
                    const errorMessage = document.getElementById('form_error_message');
                    const submitButton = document.getElementById('edit_submit_button');
                    const resubmitButton = document.getElementById('resubmit_button');

                    // Reset modal state
                    errorMessage.classList.add('hidden');
                    document.getElementById('edit_desc_warning').classList.add('hidden');
                    document.getElementById('edit_file_warning').classList.add('hidden');
                    document.getElementById('uploaded_file').value = ''; // Clear file input
                    document.getElementById('file_info').classList.add('hidden');

                    try {
                        // Parse inputs safely
                        const submissionId = typeof id === 'string' ? id : String(id);
                        const docType = typeof documentType === 'string' ? documentType : String(documentType);
                        const otherTypeValue = typeof otherType === 'string' ? otherType : (otherType ? String(otherType) : '');
                        const desc = typeof description === 'string' ? description : String(description);
                        const subDate = typeof submissionDate === 'string' ? submissionDate : String(submissionDate);
                        const statusValue = typeof status === 'string' ? status : String(status);

                        // Set form values
                        document.getElementById('edit_submission_id').value = submissionId;
                        document.getElementById('edit_description').value = desc;
                        document.getElementById('edit_submission_date').textContent = subDate;

                        // Determine if document is rejected
                        const isRejected = statusValue.toLowerCase() === 'rejected';

                        // Show/hide resubmit button based on status
                        resubmitButton.classList.toggle('hidden', !isRejected);

                        // Handle document type display
                        if (isRejected) {
                            document.getElementById('documentTypeSection').classList.remove('hidden');
                            document.getElementById('documentTypeDisplay').classList.add('hidden');

                            // Set document type radio buttons
                            if (otherTypeValue && otherTypeValue.trim() !== '') {
                                document.getElementById('document_type_other').checked = true;
                                document.getElementById('otherTypeContainer').classList.remove('hidden');
                                document.getElementById('other_type').value = otherTypeValue;
                            } else {
                                document.getElementById('document_type_standard').checked = true;
                                document.getElementById('otherTypeContainer').classList.add('hidden');
                            }
                        } else {
                            document.getElementById('documentTypeSection').classList.add('hidden');
                            document.getElementById('documentTypeDisplay').classList.remove('hidden');
                            document.getElementById('edit_document_type_display').textContent = docType;
                        }

                        // Set status display
                        const statusClasses = {
                            'pending': 'bg-yellow-100 text-yellow-800',
                            'approved': 'bg-green-100 text-green-800',
                            'rejected': 'bg-red-100 text-red-800'
                        };
                        const statusDisplay = document.getElementById('edit_status_display');
                        statusDisplay.textContent = statusValue.charAt(0).toUpperCase() + statusValue.slice(1).toLowerCase();
                        statusDisplay.className = `text-sm px-3 py-1.5 rounded-full font-semibold ${statusClasses[statusValue.toLowerCase()] || 'bg-gray-100 text-gray-800'}`;

                        // Show modal
                        modal.classList.remove('hidden');
                    } catch (e) {
                        console.error('Error processing modal data:', e);
                        errorMessage.textContent = 'An error occurred while loading the form. Please try again.';
                        errorMessage.classList.remove('hidden');
                    }
                }

                // Close modal function
                function closeModal(modalId) {
                    const modal = document.getElementById(modalId);
                    modal.classList.add('hidden');
                    // Reset form
                    document.getElementById('editSubmissionForm').reset();
                    document.getElementById('documentTypeSection').classList.add('hidden');
                    document.getElementById('documentTypeDisplay').classList.remove('hidden');
                    document.getElementById('form_error_message').classList.add('hidden');
                    document.getElementById('edit_desc_warning').classList.add('hidden');
                    document.getElementById('edit_file_warning').classList.add('hidden');
                    // Reset buttons
                    const submitButton = document.getElementById('edit_submit_button');
                    submitButton.textContent = 'Save Changes';
                    submitButton.className = 'py-3 px-10 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg text-base font-semibold hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 disabled:bg-gray-400 disabled:cursor-not-allowed';
                    document.getElementById('resubmit_button').classList.add('hidden');
                    document.getElementById('is_resubmission').value = '0';
                }

                // File upload feedback
                document.getElementById('uploaded_file').addEventListener('change', function() {
                    const fileInfo = document.getElementById('file_info');
                    const fileName = document.getElementById('file_name');
                    if (this.files.length > 0) {
                        fileName.textContent = this.files[0].name;
                        fileInfo.classList.remove('hidden');
                    } else {
                        fileInfo.classList.add('hidden');
                    }
                });
            </script>




            <!-- View Submission Modal -->
            <div id="fileViewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 transition-opacity duration-300 hidden" role="dialog" aria-labelledby="file-view-title">
                <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-4xl mx-4 flex flex-col sm:p-8 max-h-[90vh] min-h-[60vh] overflow-y-auto transform transition-all duration-300 scale-100" aria-modal="true">
                    <div class="modal-header relative mb-4">
                        <h2 id="file-view-title" class="text-2xl font-bold text-gray-900">View Submission</h2>
                        <button onclick="closeModal('fileViewModal')" class="modal-close-btn absolute top-0 right-0 text-gray-500 hover:text-gray-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500" aria-label="Close modal">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-6 flex-1">
                        <div class="w-full sm:w-1/2 flex flex-col">
                            <div class="p-4 flex-1">
                                <h3 id="modal_document_type" class="text-lg font-semibold text-gray-900 mb-2"></h3>
                                <p class="text-sm text-gray-600 mb-2"><span class="font-medium">Status:</span> <span id="modal_status"></span></p>
                                <p class="text-sm text-gray-600 mb-2"><span class="font-medium">Submitted:</span> <span id="modal_submission_date"></span></p>
                                <p class="text-sm text-gray-600 mb-4"><span class="font-medium">Description:</span> <span id="modal_description" class="block mt-1 max-h-[200px] overflow-y-auto"></span></p>
                            </div>
                        </div>
                        <div class="w-full sm:w-1/2 flex flex-col">
                            <div id="fileViewPreview" class="w-full flex-1 min-h-[300px] bg-gray-50 rounded-lg p-4 overflow-auto" aria-label="File preview area">
                                <div id="previewContent" class="w-full h-full flex items-center justify-center">
                                    <!-- Preview will be injected here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer mt-4 flex justify-center gap-4 bg-gray-100 p-4 rounded-b-lg">
                        <a id="fileDownloadLink" href="#" download class="action-btn bg-gradient-to-r from-red-600 to-red-700 text-white py-2.5 px-5 rounded-lg flex items-center gap-2 transition-colors duration-200 shadow-md focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download
                        </a>
                        <button onclick="closeModal('fileViewModal')" class="action-btn bg-gradient-to-r from-blue-600 to-blue-700 text-white py-2.5 px-5 rounded-lg flex items-center gap-2 transition-colors duration-200 shadow-md focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- Inline CSS for responsive table -->
            <style>
                @media (max-width: 640px) {
                    table {
                        display: block;
                    }
                    thead {
                        display: none;
                    }
                    tr {
                        display: block;
                        margin-bottom: 1rem;
                        border: 1px solid #e5e7eb;
                        border-radius: 0.5rem;
                        padding: 1rem;
                    }
                    td {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 0.5rem 1rem;
                        border-bottom: 1px solid #e5e7eb;
                    }
                    td:last-child {
                        border-bottom: none;
                    }
                    td:before {
                        content: attr(data-label);
                        font-weight: 600;
                        color: #374151;
                        width: 40%;
                        flex-shrink: 0;
                    }
                    .file-upload-area {
                        position: relative;
                    }
                    .file-upload-label {
                        display: block;
                    }
                }
            </style>

            <!-- JavaScript -->
            <script>
                function closeModal(modalId) {
                    document.getElementById(modalId).classList.add('hidden');
                    // Clear preview content to prevent memory issues
                    const previewContent = document.getElementById('previewContent');
                    if (previewContent) {
                        previewContent.innerHTML = '';
                    }
                    // Clear error message
                    const errorMessage = document.getElementById('form_error_message');
                    if (errorMessage) {
                        errorMessage.classList.add('hidden');
                        errorMessage.textContent = '';
                    }
                }

                function openEditModal(id, documentType, otherType, description, fileName, status, submissionDate) {
                    const modal = document.getElementById('editSubmissionModal');
                    try {
                        document.getElementById('edit_submission_id').value = JSON.parse(id);
                        document.getElementById('edit_document_type_display').textContent = JSON.parse(documentType);
                        document.getElementById('edit_description').value = JSON.parse(description);
                        document.getElementById('edit_status_display').textContent = JSON.parse(status);
                        document.getElementById('edit_submission_date').textContent = JSON.parse(submissionDate);

                        const statusClasses = {
                            'pending': 'bg-yellow-100 text-yellow-800',
                            'approved': 'bg-green-100 text-green-800',
                            'rejected': 'bg-red-100 text-red-800'
                        };
                        document.getElementById('edit_status_display').className = `text-xs px-3 py-1.5 rounded-full font-semibold ${statusClasses[JSON.parse(status).toLowerCase()] || 'bg-gray-100 text-gray-800'}`;
                    } catch (e) {
                        console.error('Error parsing JSON data for edit modal:', e);
                    }
                    modal.classList.remove('hidden');
                }

                function openDocumentModal(documentType, submissionDate, status, description, fileName, id, statusRaw) {
                    const modal = document.getElementById('fileViewModal');
                    const previewContent = document.getElementById('previewContent');
                    const downloadLink = document.getElementById('fileDownloadLink');

                    // Parse JSON-encoded strings
                    try {
                        documentType = JSON.parse(documentType);
                        submissionDate = JSON.parse(submissionDate);
                        status = JSON.parse(status);
                        description = JSON.parse(description);
                        fileName = JSON.parse(fileName);
                        id = JSON.parse(id);
                        statusRaw = JSON.parse(statusRaw);
                    } catch (e) {
                        console.error('Error parsing JSON data:', e);
                        previewContent.innerHTML = '<p class="text-red-600">Error loading document data</p>';
                        return;
                    }

                    // Update modal metadata
                    document.getElementById('modal_document_type').textContent = documentType;
                    document.getElementById('modal_status').textContent = status;
                    document.getElementById('modal_submission_date').textContent = submissionDate;
                    document.getElementById('modal_description').textContent = description;

                    // Determine file extension
                    const extension = fileName.split('.').pop().toLowerCase();
                    const fileUrl = `../controller/download_submission.php?id=${encodeURIComponent(id)}`;
                    const downloadUrl = `../controller/download_submission.php?id=${encodeURIComponent(id)}&download=true`;

                    // Generate preview based on file type
                    if (['jpg', 'jpeg', 'png'].includes(extension)) {
                        previewContent.innerHTML = `
                        <img src="${fileUrl}" alt="File preview" class="max-w-full max-h-[500px] object-contain" onerror="this.src='';this.parentElement.innerHTML='<p class=\\'text-gray-600\\'>Unable to load image preview</p>'">
                    `;
                    } else if (extension === 'pdf') {
                        previewContent.innerHTML = `
                        <object data="${fileUrl}" type="application/pdf" class="w-full h-[500px]" onerror="this.parentElement.innerHTML='<p class=\\'text-gray-600\\'>Unable to load PDF preview. <a href=\\'${downloadUrl}\\' class=\\'text-blue-600 hover:underline\\'>Download the PDF</a> to view it.</p>'">
                            <p class="text-gray-600">PDF preview not supported. <a href="${downloadUrl}" class="text-blue-600 hover:underline">Download the PDF</a> to view it.</p>
                        </object>
                    `;
                    } else if (['doc', 'docx'].includes(extension)) {
                        previewContent.innerHTML = `
                        <p class="text-gray-600">Preview not available for Word documents. <a href="${downloadUrl}" class="text-blue-600 hover:underline">Download the file</a> to view it.</p>
                    `;
                    } else {
                        previewContent.innerHTML = `
                        <p class="text-gray-600">Preview not available for this file type. Use the download button to access the file.</p>
                    `;
                    }

                    // Set download link
                    downloadLink.href = downloadUrl;
                    downloadLink.setAttribute('download', fileName);
                    downloadLink.classList.remove('hidden');

                    modal.classList.remove('hidden');
                }

                // File upload validation
                document.getElementById('uploaded_file').addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    const fileInfo = document.getElementById('file_info');
                    const fileName = document.getElementById('file_name');
                    const warning = document.getElementById('edit_file_warning');

                    if (file) {
                        const validTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
                        if (!validTypes.includes(file.type)) {
                            warning.textContent = 'Invalid file type. Only PDF, DOC, DOCX, JPG, PNG are allowed.';
                            warning.classList.remove('hidden');
                            this.value = '';
                            return;
                        }
                        if (file.size > 5 * 1024 * 1024) {
                            warning.textContent = 'File size exceeds 5MB';
                            warning.classList.remove('hidden');
                            this.value = '';
                            return;
                        }
                        warning.classList.add('hidden');
                        fileInfo.classList.remove('hidden');
                        fileName.textContent = file.name;
                    } else {
                        fileInfo.classList.add('hidden');
                    }
                });

                // Status filter
                document.getElementById('statusFilter').addEventListener('change', function(e) {
                    const rows = document.querySelectorAll('tbody tr');
                    const filter = e.target.value.toLowerCase();
                    const filterStatus = document.getElementById('filterStatus');

                    // Update aria-live region for accessibility
                    const statusText = filter === 'all' ? 'Showing all submissions' : `Showing ${filter} submissions`;
                    filterStatus.textContent = statusText;

                    rows.forEach(row => {
                        const status = row.querySelector('td[data-label="Status"] span').getAttribute('data-status').toLowerCase();
                        row.style.display = (filter === 'all' || status === filter) ? '' : 'none';
                    });
                });

                // Form submission with AJAX
                document.getElementById('editSubmissionForm').addEventListener('submit', function(e) {
                    e.preventDefault();

                    const description = document.getElementById('edit_description').value;
                    const warning = document.getElementById('edit_desc_warning');
                    const errorMessage = document.getElementById('form_error_message');
                    const submitButton = document.getElementById('edit_submit_button');

                    // Client-side description validation
                    if (description.length < 10) {
                        warning.classList.remove('hidden');
                        errorMessage.classList.add('hidden');
                        return;
                    }
                    warning.classList.add('hidden');

                    // Disable submit button to prevent multiple submissions
                    submitButton.disabled = true;
                    submitButton.textContent = 'Saving...';

                    const formData = new FormData(this);

                    fetch('../controller/update_submission.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.href = '../view/userView.php?page=Track';
                            } else {
                                errorMessage.textContent = data.message || 'An error occurred';
                                errorMessage.classList.remove('hidden');
                                submitButton.disabled = false;
                                submitButton.textContent = 'Save Changes';
                            }
                        })
                        .catch(error => {
                            errorMessage.textContent = 'Network error: Unable to update submission';
                            errorMessage.classList.remove('hidden');
                            submitButton.disabled = false;
                            submitButton.textContent = 'Save Changes';
                            console.error('Fetch error:', error);
                        });
                });
            </script>
        </div>

    
    
    
    
    

































                <?php else: ?>
                    <p class="text-center">This is the <?php echo htmlspecialchars($currentPage); ?> content area.</p>
                <?php endif; ?>


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













</body>
</html>