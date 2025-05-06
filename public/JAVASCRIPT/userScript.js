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
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
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

    // Close modals on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeModal('logoutModal');
        }
    });
});



document.getElementById('profile-upload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();

        reader.onload = function(event) {
            const profileImage = document.getElementById('profile-image');
            const profileInitials = document.getElementById('profile-initials');

            // Set the image source and show it
            profileImage.src = event.target.result;
            profileImage.classList.remove('hidden');

            // Hide the initials
            profileInitials.classList.add('hidden');

            // Here you would typically also upload the image to your server
            // uploadProfileImage(file);
        };

        reader.readAsDataURL(file);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Get elements
    const editButton = document.querySelector('.flex.justify-between.items-start button');
    const modal = document.getElementById('edit-profile-modal');
    const closeModal = document.getElementById('close-modal');
    const cancelEdit = document.getElementById('cancel-edit');
    const form = document.getElementById('profile-edit-form');

    // Profile image handling
    const profileUpload = document.getElementById('profile-upload');
    const modalProfileUpload = document.getElementById('modal-profile-upload');
    const profileImage = document.getElementById('profile-image');
    const modalProfileImage = document.getElementById('modal-profile-image');
    const profileInitials = document.getElementById('profile-initials');
    const modalProfileInitials = document.getElementById('modal-profile-initials');

    // Show modal when edit button is clicked
    editButton.addEventListener('click', function() {
        modal.classList.remove('hidden');
    });

    // Close modal when X is clicked
    closeModal.addEventListener('click', function() {
        modal.classList.add('hidden');
    });

    // Close modal when cancel is clicked
    cancelEdit.addEventListener('click', function() {
        modal.classList.add('hidden');
    });

    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });

    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();


        const fullName = document.getElementById('full-name').value;
        const email = document.getElementById('email').value;

        // Update the profile card with new values
        document.querySelector('.font-semibold:contains("0000-00000")').textContent = studentId;
        document.querySelector('.font-semibold:contains("User1234")').textContent = fullName;
        document.querySelector('.font-semibold:contains("user1234@example.com")').textContent = email;

        // Close the modal
        modal.classList.add('hidden');

        // Here you would typically send the data to your server
        // saveProfileChanges({ fullName, email });
    });

    // Handle profile image upload (main card)
    profileUpload.addEventListener('change', handleImageUpload(profileImage, profileInitials));

    // Handle profile image upload (modal)
    modalProfileUpload.addEventListener('change', handleImageUpload(modalProfileImage, modalProfileInitials));

    // Shared image upload handler
    function handleImageUpload(imageElement, initialsElement) {
        return function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();

                reader.onload = function(event) {
                    // Update the image element
                    imageElement.src = event.target.result;
                    imageElement.classList.remove('hidden');

                    // Hide the initials
                    initialsElement.classList.add('hidden');

                    // If this is the modal upload, also update the main profile image
                    if (imageElement === modalProfileImage) {
                        profileImage.src = event.target.result;
                        profileImage.classList.remove('hidden');
                        profileInitials.classList.add('hidden');
                    }
                };

                reader.readAsDataURL(file);
            }
        };
    }
});

function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const eyeIcon = passwordField.parentElement.querySelector('.eye-icon');
    const eyeSlashIcon = passwordField.parentElement.querySelector('.eye-slash-icon');

    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        eyeIcon.classList.add('hidden');
        eyeSlashIcon.classList.remove('hidden');
    } else {
        passwordField.type = 'password';
        eyeSlashIcon.classList.add('hidden');
        eyeIcon.classList.remove('hidden');
    }
}

// Make sure this is in your existing sidebar collapse/expand logic
document.getElementById('collapseBtn').addEventListener('click', function() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('collapsed');

    // Update the collapse icon
    const collapseIcon = document.getElementById('collapseBoxIcon');
    if (sidebar.classList.contains('collapsed')) {
        collapseIcon.setAttribute('name', 'expand-horizontal');
    } else {
        collapseIcon.setAttribute('name', 'collapse-horizontal');
    }
});

// This ensures the tooltips work even if JavaScript loads late
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const menuItems = document.querySelectorAll('.menu-item');

    // Initialize based on current state
    if (sidebar.classList.contains('collapsed')) {
        menuItems.forEach(item => {
            const text = item.querySelector('.menu-text');
            text.style.opacity = '0';
            text.style.transform = 'translateX(-20px)';
        });
    }
});

// Logout modal handling
document.getElementById('logoutBtn').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('logoutModal').classList.remove('hidden');
});

document.getElementById('cancelLogout').addEventListener('click', function() {
    document.getElementById('logoutModal').classList.add('hidden');
});

// Close edit profile modal
document.getElementById('close-modal').addEventListener('click', function() {
    document.getElementById('edit-profile-modal').classList.add('hidden');
});



    function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
    modal.classList.add('hidden');
}
}

// In your form submission handler
fetch('../controller/submit_form.php', {
    method: 'POST',
    body: formData
})
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Document submitted successfully! Reference ID: ' + data.submission_id);
            // Redirect or reset form
        } else {
            alert('Error: ' + data.errors.join('\n'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });




























