// Utility functions
function toggleClass(element, className, condition) {
    if (!element) return;
    element.classList.toggle(className, condition);
}

function setSidebarCollapsed(collapsed) {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const collapseBoxIcon = document.getElementById('collapseBoxIcon');
    if (!sidebar || !mainContent || !collapseBoxIcon) return;
    toggleClass(sidebar, 'collapsed', collapsed);
    toggleClass(mainContent, 'collapsed', collapsed);
    collapseBoxIcon.setAttribute('name', collapsed ? 'collapse-vertical' : 'collapse-horizontal');
    localStorage.setItem('sidebarCollapsed', JSON.stringify(collapsed));
}

function openModal(modalId) {
    console.log('Opening modal:', modalId);
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    } else {
        console.error('Modal not found:', modalId);
    }
}

function closeModal(modalId) {
    console.log('Closing modal:', modalId);
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
        // Clean up previews if applicable
        if (modalId === 'fileViewModal' || modalId === 'documentModal') {
            const preview = modal.querySelector('#fileViewPreview') || modal.querySelector('#documentPreview');
            if (preview) {
                const iframes = preview.getElementsByTagName('iframe');
                const images = preview.getElementsByTagName('img');
                for (let iframe of iframes) URL.revokeObjectURL(iframe.src);
                for (let img of images) URL.revokeObjectURL(img.src);
                preview.innerHTML = '';
            }
        }
    }
}

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

// Modal-specific functions
function showUserDocuments(studentId, fullName) {
    console.log('showUserDocuments called with:', { studentId, fullName });
    document.getElementById('modalStudentId').textContent = studentId;
    document.getElementById('modalStudentName').textContent = fullName;
    openModal('documentsModal');
}

function openEditModal(button) {
    console.log('openEditModal called');
    const studentId = button.getAttribute('data-student-id');
    const fullName = button.getAttribute('data-full-name');
    const address = button.getAttribute('data-address');
    const status = button.getAttribute('data-status');

    document.getElementById('edit-student-id').value = studentId;
    document.getElementById('edit-full-name').value = fullName;
    document.getElementById('edit-address').value = address;
    document.getElementById('edit-status').value = status;

    openModal('editUserModal');
}

function confirmDeleteUser(studentId, userId) {
    console.log('confirmDeleteUser called with:', { studentId, userId });
    document.getElementById('deleteUserId').value = userId;
    openModal('deleteUserModal');
}

// Unified DOMContentLoaded event
document.addEventListener('DOMContentLoaded', () => {
    // Initialize sidebar state
    let isCollapsed = JSON.parse(localStorage.getItem('sidebarCollapsed')) || false;
    setSidebarCollapsed(isCollapsed);

    // Collapse button
    document.getElementById('collapseBtn')?.addEventListener('click', () => {
        isCollapsed = !isCollapsed;
        setSidebarCollapsed(isCollapsed);
    });

    // Logout functionality
    document.getElementById('logoutBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        openModal('logoutModal');
    });

    document.getElementById('cancelLogout')?.addEventListener('click', () => closeModal('logoutModal'));

    // Handle URL parameters for message modal
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('message')) {
        openModal('messageModal');
    }

    // Close modals on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            ['logoutModal', 'messageModal', 'addUserModal', 'editUserModal', 'documentsModal', 'evaluationModal', 'fileViewModal', 'approveModal', 'rejectModal', 'deleteUserModal'].forEach(modalId => closeModal(modalId));
        }
    });

    // Close modals when clicking outside
    window.addEventListener('click', (event) => {
        ['documentsModal', 'evaluationModal', 'fileViewModal', 'approveModal', 'rejectModal', 'deleteUserModal'].forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal && event.target === modal) {
                closeModal(modalId);
            }
        });
    });
});