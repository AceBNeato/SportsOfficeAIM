
<?php
// Database configurationkyawar
$host = "localhost";
$username = "root";
$password = "";
$dbname = "SportOfficeDB";

// Create database connection
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
die("Connection failed: " . $conn->connect_error);
}

// Search users function
function searchUsers($searchTerm, $conn) {
$searchTerm = '%' . $conn->real_escape_string($searchTerm) . '%';
$stmt = $conn->prepare("
        SELECT id, student_id, full_name
        FROM users
        WHERE student_id LIKE ? OR full_name LIKE ?
    ");
if (!$stmt) {
die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
return $result;
}

// Get search term from GET request
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$users = searchUsers($searchTerm, $conn);

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
if (!$stmt) {
die("Prepare failed: " . $conn->error);
}
$stmt->bind_param(str_repeat('i', count($userIds)), ...$userIds);
$stmt->execute();
$allSubmissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group submissions by user_id
foreach ($allSubmissions as $sub) {
$submissionsByUser[$sub['user_id']][] = $sub;
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Submission Evaluation</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .modal {
            transition: opacity 0.3s ease-in-out;
        }
        .modal.hidden {
            opacity: 0;
            pointer-events: none;
        }
        .modal:not(.hidden) {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gray-100">
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
        <div class="userFile cursor-pointer" data-student-id="<?php echo htmlspecialchars($user['student_id']); ?>" data-full-name="<?php echo htmlspecialchars($user['full_name']); ?>" data-submissions='<?php echo htmlspecialchars(json_encode($submissions, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>'>
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
                    <button class="view-details-btn" title="View Details">
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
            <button onclick="closeModal('evaluationModal')" class="absolute top-0 right-0 text-gray-400 hover:text-gray-600 transition-colors">
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
        <div id="fileViewPreview" class="w-full flex-1 overflow-auto bg-gray-50">
            <!-- Content will be injected here -->
        </div>
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

<!-- Approve Confirmation Modal -->
<div id="approveModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden" role="dialog" aria-labelledby="approveModalTitle" aria-modal="true">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
        <div class="relative mb-4">
            <h2 id="approveModalTitle" class="text-lg font-semibold text-gray-800">Confirm Approval</h2>
            <button onclick="closeModal('approveModal')" class="absolute top-0 right-0 text-gray-400 hover:text-gray-600 transition-colors">
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

<!-- Reject Confirmation Modal -->
<div id="rejectModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 hidden" role="dialog" aria-labelledby="rejectModalTitle" aria-modal="true">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
        <div class="relative mb-4">
            <h2 id="rejectModalTitle" class="text-lg font-semibold text-gray-800">Confirm Rejection</h2>
            <button onclick="closeModal('rejectModal')" class="absolute top-0 right-0 text-gray-400 hover:text-gray-600 transition-colors">
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

<!-- JavaScript for Modal Handling -->
<script>
    function htmlEscape(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function showEvaluationModal(studentId, fullName, submissions) {
        console.log('showEvaluationModal called:', studentId, fullName, submissions); // Debugging
        document.getElementById('modal_student_id').textContent = studentId;
        document.getElementById('modal_full_name').textContent = fullName;
        const submissionsList = document.getElementById('submissions_list');
        submissionsList.innerHTML = '';
        if (!submissions || submissions.length === 0) {
            submissionsList.innerHTML = '<p class="text-gray-500 text-center">No submissions found.</p>';
        } else {
            submissions.forEach(sub => {
                const submissionDate = new Date(sub.submission_date).toLocaleDateString('en-US', {
                    month: '2-digit',
                    day: '2-digit',
                    year: 'numeric'
                });
                const docType = sub.document_type === 'Others' && sub.other_type ? sub.other_type : sub.document_type;
                let statusColor = sub.status === 'approved' ? 'text-green-600' : sub.status === 'rejected' ? 'text-red-600' : 'text-yellow-600';
                let actionsHtml = '';
                if (sub.status === 'pending') {
                    actionsHtml = `
                        <div class="mt-3 flex justify-end space-x-2">
                            <button onclick="viewSubmission('${sub.id}', '${htmlEscape(sub.file_name)}')" class="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600 transition-colors">View</button>
                            <button onclick="showApproveModal('${sub.id}')" class="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600 transition-colors">Approve</button>
                            <button onclick="showRejectModal('${sub.id}')" class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600 transition-colors">Reject</button>
                        </div>
                    `;
                }
                const commentsHtml = sub.comments ? `<p class="text-sm text-gray-700"><strong>Comments:</strong> ${htmlEscape(sub.comments)}</p>` : '';
                const submissionHtml = `
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <p class="text-sm text-gray-700"><strong>Type:</strong> ${htmlEscape(docType)}</p>
                        <p class="text-sm text-gray-700"><strong>Submission Date:</strong> ${htmlEscape(submissionDate)}</p>
                        <p class="text-sm text-gray-700"><strong>Status:</strong> <span class="${statusColor}">${htmlEscape(sub.status.charAt(0).toUpperCase() + sub.status.slice(1))}</span></p>
                        ${commentsHtml}
                        ${actionsHtml}
                    </div>
                `;
                submissionsList.innerHTML += submissionHtml;
            });
        }
        const modal = document.getElementById('evaluationModal');
        modal.classList.remove('hidden');
    }

    function viewSubmission(submissionId, fileName) {
        if (!submissionId || isNaN(submissionId)) {
            console.error('Invalid submission ID:', submissionId);
            alert('Invalid submission ID. Please try again.');
            return;
        }

        const modal = document.getElementById('fileViewModal');
        const preview = document.getElementById('fileViewPreview');
        const downloadLink = document.getElementById('fileDownloadLink');

        downloadLink.href = `../controller/download_submission.php?id=${encodeURIComponent(submissionId)}&download=true`;
        downloadLink.classList.remove('hidden');

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

        fetch(`../controller/download_submission.php?id=${encodeURIComponent(submissionId)}&convert=pdf`)
            .then(response => {
                if (!response.ok) throw new Error(`Failed to load file: ${response.statusText}`);
                return response.blob();
            })
            .then(blob => {
                const url = URL.createObjectURL(blob);
                const contentType = blob.type;

                if (contentType === 'application/pdf') {
                    preview.innerHTML = `<iframe src="${url}#zoom=auto" style="width:100%; height:100%; max-height:100%;" frameborder="0" title="File Preview" aria-label="PDF file preview"></iframe>`;
                } else if (contentType.startsWith('image/')) {
                    preview.innerHTML = `<img src="${url}" alt="Uploaded File" class="w-full h-full object-contain" aria-label="Image preview" />`;
                } else if (contentType === 'text/plain') {
                    return fetch(url).then(response => response.text()).then(text => {
                        preview.innerHTML = `<pre class="w-full h-full overflow-auto">${htmlEscape(text)}</pre>`;
                    });
                } else {
                    preview.innerHTML = `<div class="text-center py-6 text-red-500 text-sm">Preview not available for this file type. Please download the original file.</div>`;
                    downloadLink.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                preview.innerHTML = `<div class="text-center py-6 text-red-500 text-sm">Error loading file: ${error.message}. Please download the original file.</div>`;
                downloadLink.classList.remove('hidden');
            });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal('fileViewModal');
            }
        }, { once: true });
    }

    function showApproveModal(submissionId) {
        const modal = document.getElementById('approveModal');
        const confirmButton = document.getElementById('confirmApprove');
        const commentsField = document.getElementById('approveComments');
        commentsField.value = '';
        const newConfirmButton = confirmButton.cloneNode(true);
        confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
        newConfirmButton.addEventListener('click', () => {
            const comments = commentsField.value.trim();
            fetch('../controller/approve_submission.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${encodeURIComponent(submissionId)}&comments=${encodeURIComponent(comments)}`
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Submission approved successfully!');
                        location.reload();
                    } else {
                        alert('Error approving submission: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while approving the submission.');
                });
            closeModal('approveModal');
        });
        modal.classList.remove('hidden');
    }

    function showRejectModal(submissionId) {
        const modal = document.getElementById('rejectModal');
        const confirmButton = document.getElementById('confirmReject');
        const commentsField = document.getElementById('rejectComments');
        commentsField.value = '';
        const newConfirmButton = confirmButton.cloneNode(true);
        confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
        newConfirmButton.addEventListener('click', () => {
            const comments = commentsField.value.trim();
            fetch('../controller/reject_submission.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${encodeURIComponent(submissionId)}&comments=${encodeURIComponent(comments)}`
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Submission rejected successfully!');
                        location.reload();
                    } else {
                        alert('Error rejecting submission: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while rejecting the submission.');
                });
            closeModal('rejectModal');
        });
        modal.classList.remove('hidden');
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        const preview = modal.querySelector('#fileViewPreview');
        modal.classList.add('hidden');
        if (preview) {
            const iframes = preview.getElementsByTagName('iframe');
            const images = preview.getElementsByTagName('img');
            const pres = preview.getElementsByTagName('pre');
            for (let iframe of iframes) {
                URL.revokeObjectURL(iframe.src);
            }
            for (let img of images) {
                URL.revokeObjectURL(img.src);
            }
            for (let pre of pres) {
                const url = preview.querySelector('a')?.href;
                if (url) URL.revokeObjectURL(url);
            }
            preview.innerHTML = '';
        }
    }

    // Event delegation for userFile clicks
    document.addEventListener('click', (e) => {
        const userFile = e.target.closest('.userFile');
        const viewBtn = e.target.closest('.view-details-btn');
        if (userFile || viewBtn) {
            const target = userFile || viewBtn.closest('.userFile');
            const studentId = target.dataset.studentId;
            const fullName = target.dataset.fullName;
            let submissions = [];
            try {
                submissions = JSON.parse(target.dataset.submissions);
            } catch (err) {
                console.error('JSON parse error:', err);
                alert('Error loading submissions. Please try again.');
                return;
            }
            showEvaluationModal(studentId, fullName, submissions);
        }
    });
</script>