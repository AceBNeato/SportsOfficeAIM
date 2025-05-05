<?php
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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons/css/boxicons.min.css" rel="stylesheet">
    <script src="https://unpkg.com/boxicons@2.1.4/dist/boxicons.js"></script>
    <link rel="stylesheet" href="../public/CSS/userStyle.css">
    <script src="../public/JAVASCRIPT/userScript.js" defer></script>
    <link rel="icon" href="../public/image/Usep.png" sizes="any">
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
            $menu = ['Dashboard', 'Submissions', 'Track', 'Log-out'];
            $icon = [
                'Dashboard' => "<box-icon type='solid' name='Dashboard' color='white'></box-icon>",
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

        <div class="border-b-4 border-red-500 px-5 pt-2 pb-1 flex justify-between items-center">
            <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 tracking-tight">
                <?php echo htmlspecialchars($currentPage); ?>
            </h1>

            <?php if ($currentPage === 'Dashboard'): ?>
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
                                <div class="text-5xl font-bold text-gray-800">
                                    <?php echo htmlspecialchars($_SESSION['user']['document_count'] ?? '0'); ?>
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
                        // Notification system elements
                        const notificationsContainer = document.getElementById('notificationsContainer');
                        const refreshBtn = document.getElementById('refreshNotifications');
                        const clearBtn = document.getElementById('clearNotifications');

                        // Load notifications from localStorage or initialize
                        let notifications = JSON.parse(localStorage.getItem('notifications')) || [];
                        let timestampUpdaters = {}; // Store interval IDs for timestamp updates

                        // Display notifications from PHP session
                        function displaySessionNotifications() {
                            <?php if (!empty($_SESSION['notifications'])): ?>
                            const sessionNotifications = <?php echo json_encode($_SESSION['notifications']); ?>;

                            sessionNotifications.forEach(notification => {
                                addNotification(notification.message, notification.timestamp, false); // Don't save to localStorage yet
                            });

                            // Save all new notifications to localStorage at once
                            localStorage.setItem('notifications', JSON.stringify(notifications));

                            // Clear notifications from session after displaying
                            <?php unset($_SESSION['notifications']); ?>
                            <?php endif; ?>
                        }

                        // Format time with real-time updates
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
                                    // Stop updating if more than 1 day old
                                    clearInterval(timestampUpdaters[timestamp]);
                                    delete timestampUpdaters[timestamp];
                                }
                            };

                            // Initial update
                            updateTime();

                            // Set up interval for updates (every minute for recent notifications)
                            if (!timestampUpdaters[timestamp]) {
                                timestampUpdaters[timestamp] = setInterval(updateTime, 60000);
                            }
                        }

                        // Display notifications in the UI
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

                                // Initialize timestamp with auto-updating
                                const timestampElement = notificationElement.querySelector('.timestamp');
                                formatTime(notification.timestamp, timestampElement);
                            });

                            // Add event listeners to delete buttons
                            document.querySelectorAll('.delete-notification').forEach(btn => {
                                btn.addEventListener('click', function() {
                                    const index = parseInt(this.getAttribute('data-index'));

                                    // Clear the timestamp updater for this notification
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

                        // Add a new notification
                        function addNotification(message, timestamp = null, saveToStorage = true) {
                            const newTimestamp = timestamp || new Date().toISOString();
                            notifications.unshift({
                                message: message,
                                timestamp: newTimestamp
                            });

                            // Keep only the last 50 notifications
                            if (notifications.length > 50) {
                                const removed = notifications.pop();
                                // Clean up timestamp updater for removed notification
                                if (timestampUpdaters[removed.timestamp]) {
                                    clearInterval(timestampUpdaters[removed.timestamp]);
                                    delete timestampUpdaters[removed.timestamp];
                                }
                            }

                            if (saveToStorage) {
                                localStorage.setItem('notifications', JSON.stringify(notifications));
                            }
                            displayNotifications();
                        }

                        // Clear all notifications
                        clearBtn.addEventListener('click', function() {
                            if (confirm('Are you sure you want to clear all notifications?')) {
                                // Clear all timestamp updaters
                                Object.values(timestampUpdaters).forEach(interval => clearInterval(interval));
                                timestampUpdaters = {};

                                notifications = [];
                                localStorage.setItem('notifications', JSON.stringify(notifications));
                                displayNotifications();
                            }
                        });

                        // Refresh notifications - now fetches from server if needed
                        refreshBtn.addEventListener('click', function() {
                            // Add loading indicator
                            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Refreshing';

                            // Simulate fetching new notifications from server
                            fetch('/api/notifications')
                                .then(response => response.json())
                                .then(newNotifications => {
                                    if (newNotifications && newNotifications.length > 0) {
                                        newNotifications.forEach(notification => {
                                            addNotification(notification.message, notification.timestamp);
                                        });
                                    }
                                })
                                .catch(error => {
                                    console.error('Error refreshing notifications:', error);
                                })
                                .finally(() => {
                                    // Restore refresh button
                                    refreshBtn.innerHTML = '<i class="fas fa-sync-alt mr-1"></i>Refresh';
                                });
                        });

                        // Initial setup
                        displaySessionNotifications();
                        displayNotifications();

                        // Listen for custom events (like profile updates)
                        document.addEventListener('profileUpdated', function(e) {
                            const changes = e.detail.changes;
                            let message = 'Profile updated: ';
                            const changesList = [];

                            if (changes.name) changesList.push('name');
                            if (changes.email) changesList.push('email');
                            if (changes.photo) changesList.push('profile photo');
                            if (changes.address) changesList.push('address');
                            if (changes.password) changesList.push('password');

                            if (changesList.length > 0) {
                                message += changesList.join(', ');
                                addNotification(message);
                            }
                        });

                        // Make the function available globally
                        window.addNotification = addNotification;

                        // Clean up intervals when page unloads
                        window.addEventListener('beforeunload', function() {
                            Object.values(timestampUpdaters).forEach(interval => clearInterval(interval));
                        });
                    });
                </script>












































































                <?php elseif ($currentPage === 'Submissions'): ?>
                    <!-- Enhanced Submissions Content -->
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
                            <form action="submit_form.php" method="POST" enctype="multipart/form-data" class="submissions-form">
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
                                            <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="year_section">Year & Section</label>
                                            <input type="text" id="year_section" name="year_section" placeholder="Ex: 4A - BSIT" required>
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
                                                <input type="file" id="uploaded_file" name="uploaded_file" required>
                                                <label for="uploaded_file" class="file-upload-label">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                    </svg>
                                                    <span class="upload-instruction">Click to upload or drag and drop</span>
                                                    <span class="upload-requirements">PDF, DOC, DOCX, JPG, PNG (Max 5MB)</span>
                                                </label>
                                                <div id="file_info" class="file-info hidden">
                                                    <span class="file-info-label">Selected file:</span> <span id="file_name"></span>
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



                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
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
                                }
                            });

                            // File upload preview
                            const fileInput = document.getElementById('uploaded_file');
                            const fileInfo = document.getElementById('file_info');
                            const fileName = document.getElementById('file_name');

                            fileInput.addEventListener('change', function() {
                                if (this.files.length > 0) {
                                    const file = this.files[0];
                                    const fileSize = (file.size / (1024 * 1024)).toFixed(2); // Convert to MB

                                    fileName.textContent = `${file.name} (${fileSize} MB)`;
                                    fileInfo.classList.remove('hidden');

                                    // Validate file size (5MB max)
                                    if (file.size > 5 * 1024 * 1024) {
                                        alert('File size exceeds 5MB limit. Please choose a smaller file.');
                                        this.value = ''; // Clear the input
                                        fileInfo.classList.add('hidden');
                                    }
                                } else {
                                    fileInfo.classList.add('hidden');
                                }
                            });
                        });
                    </script>




































































                <?php elseif ($currentPage === 'Track'): ?>
            <!-- Track content would go here -->
            <div class="p-4">
                <h2 class="text-xl font-semibold mb-4">Document Tracking</h2>
                <!-- Tracking content would be dynamically generated -->
            </div>

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

















<!-- Edit Profile Modal -->
<div id="edit-profile-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-y-auto max-h-screen">
        <!-- Flex direction changes based on screen size -->
        <div class="flex flex-col md:flex-row">
            <!-- Profile Picture Section - Full width on mobile, 1/3 on desktop -->
            <!-- Profile Picture Section -->
            <div class="bg-blue-600 text-white p-6 md:w-1/3 flex flex-col items-center justify-center">

                <div class="relative group mb-6">
                    <div class="w-40 h-40 rounded-full bg-gray-200 overflow-hidden border-[6px] border-blue-100 relative">
                        <?php if (isset($_SESSION['user']['has_profile_image'])): ?>
                            <img id="profile-image"
                                 src="../controller/get_profile_image.php?id=<?php echo $_SESSION['user']['id']; ?>&t=<?php echo time(); ?>"
                                 onerror="this.onerror=null; this.src='../public/image/user.png'"
                                 alt="______Image_____"
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

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const profileUpload = document.getElementById('modal-profile-upload');
                        const profileImage = document.getElementById('profile-image');
                        const profileInitials = document.getElementById('profile-initials');
                        const maxSizeMB = 2; // Maximum allowed size in MB
                        const maxSizeBytes = maxSizeMB * 1024 * 1024; // Convert to bytes

                        profileUpload.addEventListener('change', function(e) {
                            if (e.target.files && e.target.files[0]) {
                                const file = e.target.files[0];

                                // Validate file type
                                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                                if (!validTypes.includes(file.type)) {
                                    alert('Please select a valid image file (JPEG, PNG, GIF)');
                                    resetFileInput();
                                    return;
                                }

                                // Validate file size (max 2MB)
                                if (file.size > maxSizeBytes) {
                                    alert(`Image size should be less than ${maxSizeMB}MB. Your file is ${(file.size/(1024*1024)).toFixed(2)}MB`);
                                    resetFileInput();
                                    return;
                                }

                                const reader = new FileReader();

                                reader.onload = function(event) {
                                    // Show the image and hide initials
                                    profileImage.src = event.target.result;
                                    profileImage.classList.remove('hidden');

                                    if (profileInitials) {
                                        profileInitials.classList.add('hidden');
                                    }

                                    // Optional: Trigger a save to server here via AJAX
                                    // uploadProfileImage(file);
                                };

                                reader.readAsDataURL(file);
                            }
                        });

                        function resetFileInput() {
                            // Reset the file input to allow selecting a new file
                            profileUpload.value = '';
                        }

                        // Optional: Function to upload to server immediately
                        function uploadProfileImage(file) {
                            const formData = new FormData();
                            formData.append('profile_image', file);

                            fetch('../controller/update_profile_image.php', {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        // Update timestamp to prevent caching
                                        document.getElementById('profile-image').src =
                                            `../controller/get_profile_image.php?id=<?php echo $_SESSION['user']['id']; ?>&t=${new Date().getTime()}`;
                                    } else {
                                        alert('Error uploading image: ' + (data.message || 'Unknown error'));
                                        resetFileInput();
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('Error uploading image');
                                    resetFileInput();
                                });
                        }
                    });
                </script>


                <h3 class="text-xl font-semibold mb-1 text-center break-words"><?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? 'User'); ?></h3>
                <p class="text-blue-100 text-base text-center break-words"><?php echo htmlspecialchars($_SESSION['user']['student_id'] ?? ''); ?></p>
            </div>

            <!-- Form Section - Full width on mobile, 2/3 on desktop -->
            <div class="p-4 md:p-6 md:w-4/5 lg:w-3/4 xl:w-2/3">
                <div class="flex justify-between items-center mb-4 md:mb-6">
                    <h3 class="text-lg md:text-xl font-semibold text-gray-800">Edit Profile</h3>
                    <button onclick="closeModal('edit-profile-modal')" class="text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 md:h-6 md:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form id="profile-edit-form" class="space-y-3 md:space-y-4" method="post" action="../controller/update_profile.php" enctype="multipart/form-data">
                    <!-- Single column on mobile, two columns on desktop -->
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input type="text" id="full_name" name="full_name"
                               value="<?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? 'N/A'); ?>"
                               class="w-96 px-3 py-2 md:px-4 md:py-2 text-sm md:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email"
                               value="<?php echo htmlspecialchars($_SESSION['user']['email'] ?? 'N/A'); ?>"
                               class="w-96 px-3 py-2 md:px-4 md:py-2 text-sm md:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <input type="text" id="address" name="address"
                               value="<?php echo htmlspecialchars($_SESSION['user']['address'] ?? 'N/A'); ?>"
                               class="w-96 px-3 py-2 md:px-4 md:py-2 text-sm md:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>


                    <div class="pt-3 md:pt-4 flex flex-col-reverse sm:flex-row justify-end gap-3">
                        <div class="relative">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                            <div class="relative">
                                <input type="password" id="password" name="password"
                                       value=""
                                       class="w-full px-3 py-2 md:px-4 md:py-2 text-sm md:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-10"
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

                        <div class="relative">
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                            <div class="relative">
                                <input type="password" id="confirm_password" name="confirm_password"
                                       value=""
                                       class="w-full px-3 py-2 md:px-4 md:py-2 text-sm md:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-10"
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

                    <!-- Remove the hidden profile_pic_path field which isn't working correctly -->
                    <!-- Instead, use the file input directly in the form submission -->
                    <!-- Hidden file input will be added by JavaScript -->

                    <div class="pt-3 md:pt-4 flex flex-col-reverse sm:flex-row justify-end gap-3">
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
                        const profileImage = document.getElementById('modal-profile-image');
                        const profileInitials = document.getElementById('modal-profile-initials');

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






</body>
</html>