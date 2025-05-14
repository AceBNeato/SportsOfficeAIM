





// File: public/JAVASCRIPT/adminScript.js

// Utility functions
function toggleClass(element, className, condition) {
    if (!element) return;
    element.classList.toggle(className, condition);
}

function setSidebarCollapsed(collapsed) {
    if (!sidebar || !mainContent || !collapseBoxIcon) return;
    toggleClass(sidebar, 'collapsed', collapsed);
    toggleClass(mainContent, 'collapsed', collapsed);
    collapseBoxIcon.setAttribute('name', collapsed ? 'collapse-vertical' : 'collapse-horizontal');
    localStorage.setItem('sidebarCollapsed', JSON.stringify(collapsed));
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}



function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    const preview = document.getElementById('documentPreview');
    modal.style.display = 'none';
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
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

async function showDocument(id, fileName, fileType) {
    const modal = document.getElementById('documentModal');
    const preview = document.getElementById('documentPreview');
    const downloadLink = document.getElementById('downloadLink');

    downloadLink.href = `../controller/downloadDocument.php?id=${id}&download=true`;
    preview.innerHTML = '';

    try {
        const response = await fetch(`../controller/downloadDocument.php?id=${id}`);
        if (!response.ok) throw new Error('Failed to load document');

        const blob = await response.blob();
        const url = URL.createObjectURL(blob);

        if (fileType === 'application/pdf') {
            preview.innerHTML = `<iframe src="${url}" style="width:100%;height:100%;"></iframe>`;
        } else if (fileType.startsWith('image/')) {
            preview.innerHTML = `<img src="${url}" alt="${fileName}" />`;
        } else {
            preview.innerHTML = '<p class="error-message">Preview not available for this file type.</p>';
        }

        modal.style.display = 'block';
    } catch (error) {
        preview.innerHTML = `<p class="error-message">Error loading document: ${error.message}</p>`;
        modal.style.display = 'block';
    }
}

function closeModal() {
    const modal = document.getElementById('documentModal');
    const preview = document.getElementById('documentPreview');
    modal.style.display = 'none';

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

function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = input.nextElementSibling;
    if (!icon) return;

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove('bx-show');
        icon.classList.add('bx-hide');
    } else {
        input.type = "password";
        icon.classList.remove('bx-hide');
        icon.classList.add('bx-show');
    }
}

function populateEditUserModal(studentId, fullName, address, email, status) {
    document.getElementById('edit-student-id').value = studentId;
    document.getElementById('edit-full-name').value = fullName;
    document.getElementById('edit-address').value = address;
    document.getElementById('edit-email').value = email;
    document.getElementById('edit-status').value = status;
    openModal('editUserModal');
}

// DOM elements
let sidebar, mainContent, collapseBoxIcon;
let logoutBtn, logoutModal, confirmLogout, cancelLogout;
let messageModal;

// Unified DOMContentLoaded event
document.addEventListener('DOMContentLoaded', () => {
    // Initialize elements
    sidebar = document.getElementById('sidebar');
    mainContent = document.getElementById('mainContent');
    collapseBoxIcon = document.getElementById('collapseBoxIcon');
    logoutBtn = document.getElementById('logoutBtn');
    logoutModal = document.getElementById('logoutModal');
    confirmLogout = document.getElementById('confirmLogout');
    cancelLogout = document.getElementById('cancelLogout');
    messageModal = document.getElementById('messageModal');

    // Initialize sidebar state
    let isCollapsed = JSON.parse(localStorage.getItem('sidebarCollapsed')) || false;
    setSidebarCollapsed(isCollapsed);

    // Event listeners
    document.getElementById('collapseBtn')?.addEventListener('click', () => {
        isCollapsed = !isCollapsed;
        setSidebarCollapsed(isCollapsed);
    });

    // Logout functionality
    if (logoutBtn && logoutModal) {
        logoutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openModal('logoutModal');
        });

        cancelLogout?.addEventListener('click', () => closeModal('logoutModal'));
        confirmLogout?.addEventListener('click', () => {
            window.location.href = '../view/loginView.php';
        });
    }

    // Handle URL parameters for modals
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('message') && messageModal) {
        openModal('messageModal');
        const okButton = messageModal.querySelector('button');
        if (okButton) {
            okButton.addEventListener('click', () => {
                closeModal('messageModal');
                if (urlParams.get('reopenAddUser') === '1') {
                    openModal('addUserModal');
                }
            });
        }
    }

    if (urlParams.get('reopenAddUser') === '1') {
        openModal('addUserModal');
    }

    // Edit user buttons
    document.querySelectorAll('.edit-user-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const studentId = button.getAttribute('data-student-id');
            const fullName = button.getAttribute('data-full-name');
            const address = button.getAttribute('data-address');
            const email = button.getAttribute('data-email');
            const status = button.getAttribute('data-status');
            populateEditUserModal(studentId, fullName, address, email, status);
        });
    });

    // Delete user functionality
    const deleteForm = document.getElementById('deleteUserForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            const studentId = document.getElementById('deleteUserId').value;
            console.log("Submitting form to delete student ID:", studentId);
        });
    }

    // Close modals on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal('logoutModal');
            closeModal('messageModal');
            closeModal('addUserModal');
            closeModal('editUserModal');
            closeModal('documentsModal');
            closeModal('evaluationsModal');
            closeModal('deleteUserModal')
        }
    });

    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        ['documentsModal', 'evaluationsModal', 'deleteUserModal'].forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal && event.target === modal) {
                closeModal(modalId);
            }
        });
    });
});

// Document modal functions


document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.userFile').forEach(user => {
        user.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            const fullName = this.getAttribute('data-full-name');
            const submissions = JSON.parse(this.getAttribute('data-submissions') || '[]');
            showUserDocuments(studentId, fullName, submissions);
        });
    });
});
function showUserDocuments(studentId, fullName, submissions) {
    const modal = document.getElementById('documentModal');
    const modalStudentId = document.getElementById('modalStudentId');
    const modalStudentName = document.getElementById('modalStudentName');
    const documentsList = document.getElementById('documentsList');

    modalStudentId.textContent = studentId;
    modalStudentName.textContent = fullName;
    documentsList.innerHTML = '<div class="text-center p-4"><svg class="animate-spin h-5 w-5 mx-auto text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div>';

    modal.classList.remove('hidden');

    // Filter for approved documents
    const approvedDocuments = submissions.filter(sub => sub.status === 'approved');
    if (approvedDocuments.length === 0) {
        documentsList.innerHTML = '<div class="text-center p-4 text-gray-500">No documents found</div>';
        return;
    }

    documentsList.innerHTML = approvedDocuments.map(doc => {
        const fileUrl = `../controller/serveFile.php?submission_id=${encodeURIComponent(doc.id)}&file_name=${encodeURIComponent(doc.file_name)}`;
        const downloadUrl = `${fileUrl}&download=1`;
        const fileSizeMB = (doc.file_size / (1024 * 1024)).toFixed(2);
        return `
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <div class="bg-green-100 p-2 rounded-lg mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <span class="font-medium text-gray-800">${doc.document_type}</span>
                        <p class="text-sm text-gray-500">Uploaded: ${new Date(doc.submission_date).toLocaleDateString()} (${fileSizeMB} MB)</p>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <button onclick="viewDocument('${fileUrl}', '${encodeURIComponent(doc.file_name)}')" class="px-3 py-1 bg-blue-50 text-blue-600 rounded-md hover:bg-blue-100 text-sm font-medium">View</button>
                    <a href="${downloadUrl}" class="px-3 py-1 bg-gray-50 text-gray-600 rounded-md hover:bg-gray-100 text-sm font-medium">Download</a>
                </div>
            </div>
        `;
    }).join('');
}

function viewDocument(fileUrl, fileName) {
    const modal = document.getElementById('fileViewModal');
    const preview = document.getElementById('fileViewPreview');
    const downloadLink = document.getElementById('fileDownloadLink');

    const extension = decodeURIComponent(fileName).split('.').pop().toLowerCase();
    let previewHtml = '';

    if (extension === 'pdf') {
        previewHtml = `<iframe src="${fileUrl}" class="w-full h-full" title="${decodeURIComponent(fileName)}"></iframe>`;
    } else if (['png', 'jpg', 'jpeg', 'gif'].includes(extension)) {
        previewHtml = `<img src="${fileUrl}" alt="${decodeURIComponent(fileName)}" class="w-full h-full object-contain" />`;
    } else {
        previewHtml = `<div class="flex items-center justify-center h-full text-gray-500">Preview not available for this file type</div>`;
    }

    preview.innerHTML = previewHtml;
    downloadLink.href = `${fileUrl}&download=1`;
    downloadLink.textContent = `Download ${decodeURIComponent(fileName)}`;
    downloadLink.classList.remove('hidden');

    modal.classList.remove('hidden');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        if (modalId === 'fileViewModal') {
            document.getElementById('fileViewPreview').innerHTML = '';
            document.getElementById('fileDownloadLink').classList.add('hidden');
        }
    }
}

// Evaluation modal functions
function showEvaluationModal(studentId, fullName, docType = "Medical Certificate") {
    document.getElementById('modalStudentIds').textContent = studentId;
    document.getElementById('modalStudentNames').textContent = fullName;
    const docTypeElement = document.querySelector('#evaluationsModal .text-blue-600');
    if (docTypeElement) docTypeElement.textContent = docType;
    openModal('evaluationsModal');
}

// Delete user functions
function confirmDeleteUser(studentId, userId) {
    document.getElementById('deleteUserId').value = userId;
    console.log("Setting user ID for deletion:", userId);
    console.log("Student ID (for reference):", studentId);
    openModal('deleteUserModal');
}













function editUserModal(user) {
    document.getElementById('edit-student-id').value = user.student_id;
    document.getElementById('edit-full-name').value = user.full_name;
    document.getElementById('edit-address').value = user.address;

    const statusSelect = document.querySelector('#editUserModal select[name="status"]');
    statusSelect.value = user.status;

    document.getElementById('editUserModal').classList.remove('hidden');
}
function openEditModal(button) {
    // Fetch data from button attributes
    var studentId = button.getAttribute('data-student-id');
    var fullName = button.getAttribute('data-full-name');
    var address = button.getAttribute('data-address');
    var status = button.getAttribute('data-status');

    // Set values into the modal fields
    document.getElementById('edit-student-id').value = studentId;
    document.getElementById('edit-full-name').value = fullName;
    document.getElementById('edit-address').value = address;

    var statusSelect = document.getElementById('edit-status');
    if (statusSelect) {
        statusSelect.value = status; // Automatically set selected option
    }

    // Show the modal
    document.getElementById('editUserModal').classList.remove('hidden');
}


    // Ensure DOM is fully loaded before running the script
    document.addEventListener('DOMContentLoaded', () => {
    // Debug: Check if modals are present in the DOM
    console.log('Checking for documentsModal:', document.getElementById('documentsModal'));
    console.log('Checking for documentsFileViewModal:', document.getElementById('documentsFileViewModal'));

    // Close modal function
    function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
    modal.classList.add('hidden');
    modal.classList.remove('opacity-100');
} else {
    console.error(`Modal ${modalId} not found`);
}
}

    // Format file size for display
    function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

    // Open Documents Modal
    const viewDetailsButtons = document.querySelectorAll('.view-details-btn');
    if (viewDetailsButtons.length === 0) {
    console.error('No .view-details-btn elements found');
} else {
    viewDetailsButtons.forEach(button => {
    button.addEventListener('click', (e) => {
    const userDiv = e.target.closest('.userFile');
    if (!userDiv) {
    console.error('User div not found');
    return;
}

    const studentId = userDiv.dataset.studentId;
    const fullName = userDiv.dataset.fullName;
    let submissions;
    try {
    submissions = JSON.parse(userDiv.dataset.submissions);
} catch (error) {
    console.error('Failed to parse submissions:', error);
    submissions = [];
}

    // Debug: Log the submissions data
    console.log('Submissions:', submissions);

    // Populate modal
    const modalStudentName = document.getElementById('modalStudentName');
    const modalStudentId = document.getElementById('modalStudentId');
    const documentsList = document.getElementById('documentsList');
    const modal = document.getElementById('documentsModal');

    if (!modalStudentName || !modalStudentId || !documentsList || !modal) {
    console.error('Modal components not found:', {
    modalStudentName: !!modalStudentName,
    modalStudentId: !!modalStudentId,
    documentsList: !!documentsList,
    modal: !!modal
});
    return;
}

    modalStudentName.textContent = fullName;
    modalStudentId.textContent = studentId;

    documentsList.innerHTML = '';

    if (submissions.length === 0) {
    documentsList.innerHTML = '<p class="text-gray-500 text-center">No approved documents found.</p>';
} else {
    submissions.forEach(sub => {
    if (!sub.id || sub.id <= 0) {
    console.error('Invalid submission ID:', sub);
    return;
}

    const docType = sub.document_type === 'Others' && sub.other_type ? sub.other_type : sub.document_type;
    const fileSize = formatFileSize(sub.file_size);
    const submissionDate = new Date(sub.submission_date).toLocaleString();
    const escapedFileName = sub.file_name.replace(/'/g, "\\'");

    const docItem = document.createElement('div');
    docItem.className = 'border rounded-lg p-4 bg-gray-50';
    docItem.innerHTML = `
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <h4 class="text-md font-medium text-gray-800">${docType}</h4>
                                            <p class="text-sm text-gray-600">Submitted: ${submissionDate}</p>
                                            <p class="text-sm text-gray-600">File: ${sub.file_name} (${fileSize})</p>
                                            <p class="text-sm text-gray-600">Description: ${sub.description || 'N/A'}</p>
                                        </div>
                                        <div class="flex gap-2">
                                            <button onclick="openDocumentsFileViewModal('${sub.id}', '${escapedFileName}')"
                                                    class="px-2 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600">
                                                View
                                            </button>
                                            <a href="../controller/download_submission.php?id=${sub.id}"
                                               class="px-2 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600">
                                                Download
                                            </a>
                                        </div>
                                    </div>
                                `;
    documentsList.appendChild(docItem);
});
}

    modal.classList.remove('hidden');
    setTimeout(() => modal.classList.add('opacity-100'), 50);
});
});
}

    // Open File View Modal for Documents Section
    function openDocumentsFileViewModal(submissionId, fileName) {
    submissionId = parseInt(submissionId);
    if (isNaN(submissionId) || submissionId <= 0) {
    alert('Error: Invalid submission ID');
    console.error('Invalid submission ID passed to openDocumentsFileViewModal:', submissionId);
    return;
}

    console.log('Opening file view for submissionId:', submissionId); // Debug

    const previewDiv = document.getElementById('documentsFileViewPreview');
    const downloadLink = document.getElementById('documentsFileDownloadLink');
    const modal = document.getElementById('documentsFileViewModal');

    if (!previewDiv || !downloadLink || !modal) {
    console.error('File view modal components not found:', {
    previewDiv: !!previewDiv,
    downloadLink: !!downloadLink,
    modal: !!modal
});
    return;
}

    const extension = fileName.split('.').pop().toLowerCase();
    const viewableTypes = ['pdf', 'jpg', 'jpeg', 'png'];
    const isViewable = viewableTypes.includes(extension);

    downloadLink.href = `../controller/download_submission.php?id=${submissionId}`;
    downloadLink.classList.remove('hidden');

    if (isViewable) {
    if (extension === 'pdf') {
    previewDiv.innerHTML = `
                            <iframe src="../controller/view_submission.php?id=${submissionId}"
                                    class="w-full h-full border-0"></iframe>
                        `;
} else {
    previewDiv.innerHTML = `
                            <img src="../controller/view_submission.php?id=${submissionId}"
                                 class="w-full h-full object-contain" alt="File preview">
                        `;
}
} else {
    previewDiv.innerHTML = `
                        <div class="flex items-center justify-center h-full text-gray-500">
                            Preview not available for this file type. Please download to view.
                        </div>
                    `;
}

    modal.classList.remove('hidden');
    setTimeout(() => modal.classList.add('opacity-100'), 50);
}
});






