





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
function showUserDocuments(studentId, fullName) {
    document.getElementById('modalStudentId').textContent = studentId;
    document.getElementById('modalStudentName').textContent = fullName;
    openModal('documentsModal');
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







