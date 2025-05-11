

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
            overflow: auto;
        }

        #documentModal.show {
            display: flex;
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
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="mt-2 space-y-1 text-gray-600 text-sm">
                                <p><span class="font-medium">Student ID:</span> <?php echo htmlspecialchars($row['student_id'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><span class="font-medium">Email:</span> <?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><span class="font-medium">Status:</span> <?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><span class="font-medium">Request Date:</span> <?php echo htmlspecialchars($row['request_date'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between md:justify-end space-x-2">
                            <button class="action-btn view-btn" onclick="showDocument(<?php echo (int)$row['id']; ?>, '<?php echo htmlspecialchars(json_encode($row['file_name']), ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($row['file_type'], ENT_QUOTES, 'UTF-8'); ?>')">
                                <i class="fas fa-eye"></i> View Document
                            </button>
                            <form method="POST" action="../controller/approveRequest.php" onsubmit="return showConfirmation('approve', '<?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo (int)$row['id']; ?>')">
                                <input type="hidden" name="approval_id" value="<?php echo (int)$row['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="action-btn approve-btn">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <form method="POST" action="../controller/approveRequest.php" onsubmit="return showConfirmation('reject', '<?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo (int)$row['id']; ?>')">
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
    <div class="modal-content flex flex-col bg-white rounded-lg w-full mx-auto my-2 overflow-hidden">
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
<div id="confirmationModal" class="confirmation-modal">
    <div class="bg-white rounded-lg shadow-lg p-4 w-full max-w-xs">
        <h3 class="text-base font-semibold text-gray-800 mb-3" id="confirmationTitle"></h3>
        <p class="text-gray-600 mb-4 text-sm" id="confirmationMessage"></p>
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

<script>
    let currentForm = null;

    function showDocument(id, fileName, fileType) {
        const modal = document.getElementById('documentModal');
        const preview = document.getElementById('documentPreview');
        const downloadLink = document.getElementById('downloadLink');
        const modalTitle = document.getElementById('documentModalTitle');

        document.body.style.overflow = 'hidden';
        modalTitle.textContent = `Document: ${JSON.parse(fileName)}`;
        downloadLink.href = `../controller/downloadDocument.php?id=${encodeURIComponent(id)}&download=true`;
        preview.innerHTML = `
            <div class="flex items-center justify-center min-h-full py-6">
                <div class="text-center">
                    <svg class="animate-spin h-6 w-6 text-red-500 mx-auto" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-2 text-gray-500 text-sm">Loading document...</p>
                </div>
            </div>`;
        modal.classList.add('show');

        fetch(`../controller/downloadDocument.php?id=${encodeURIComponent(id)}`)
            .then(response => {
                if (!response.ok) throw new Error(`Failed to load document: ${response.statusText}`);
                return response.blob();
            })
            .then(blob => {
                const url = URL.createObjectURL(blob);
                if (fileType === 'application/pdf') {
                    preview.innerHTML = `<iframe src="${url}#zoom=auto" style="width:100%; height:100%; max-height:100%;" frameborder="0" title="Document Preview"></iframe>`;
                } else if (fileType.startsWith('image/')) {
                    preview.innerHTML = `<img src="${url}" alt="Document" class="w-full h-full object-contain" />`;
                } else {
                    preview.innerHTML = `<div class="text-center py-6 text-red-500 text-sm">Preview not available for this file type. Please download to view.</div>`;
                }
            })
            .catch(error => {
                preview.innerHTML = `<div class="text-center py-6 text-red-500 text-sm">Error loading document: ${error.message}</div>`;
            });
    }

    function closeModal() {
        const modal = document.getElementById('documentModal');
        const preview = document.getElementById('documentPreview');

        document.body.style.overflow = '';
        modal.classList.remove('show');
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

    function showConfirmation(action, fullName, approvalId) {
        const modal = document.getElementById('confirmationModal');
        const title = document.getElementById('confirmationTitle');
        const message = document.getElementById('confirmationMessage');
        const confirmBtn = document.getElementById('confirmActionBtn');

        title.textContent = `${action.charAt(0).toUpperCase() + action.slice(1)} Request`;
        message.textContent = `Are you sure you want to ${action} the account request for ${fullName}?`;

        if (action === 'approve') {
            message.textContent += ' This will send an automated email to the user with their login credentials.';
            confirmBtn.className = 'action-btn approve-btn';
        } else if (action === 'reject') {
            message.textContent += ' This will notify the user that their request has been rejected.';
            confirmBtn.className = 'action-btn reject-btn';
        }

        currentForm = document.querySelector(`form input[name="approval_id"][value="${approvalId}"]`).closest('form');
        modal.classList.add('show');
        return false;
    }

    function confirmAction() {
        if (currentForm) {
            currentForm.submit();
        }
    }

    function cancelConfirmation() {
        document.getElementById('confirmationModal').classList.remove('show');
        currentForm = null;
    }

    document.getElementById('documentModal').addEventListener('click', function(event) {
        if (event.target === this) {
            closeModal();
        }
    });

    document.getElementById('confirmationModal').addEventListener('click', function(event) {
        if (event.target === this) {
            cancelConfirmation();
        }
    });

        // Auto-dismiss success/error alerts after 5 seconds
        const alertMessage = document.getElementById('alertMessage');
        if (alertMessage) {
            setTimeout(() => {
                alertMessage.style.display = 'none';
            }, 5000);
        }
    </script>
    </body>
    </html>

<?php
$conn->close();
?>