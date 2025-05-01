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

    // Set initial state - collapsed
    const isCollapsed = localStorage.getItem('sidebarCollapsed') !== 'false';
    setSidebarState(isCollapsed);

    // Toggle function
    collapseBtn.addEventListener('click', function() {
        const currentlyCollapsed = sidebar.classList.contains('collapsed');
        setSidebarState(!currentlyCollapsed);
    });

    function setSidebarState(collapsed) {
        if (collapsed) {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('collapsed');
            collapseBoxIcon.setAttribute('name', 'expand-horizontal');
            localStorage.setItem('sidebarCollapsed', 'true');
        } else {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('collapsed');
            collapseBoxIcon.setAttribute('name', 'collapse-horizontal');
            localStorage.setItem('sidebarCollapsed', 'false');
        }
    }

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


function uploadProfileImage(file) {
    const formData = new FormData();
    formData.append('profileImage', file);

    fetch('/api/upload-profile', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            console.log('Upload successful:', data);
        })
        .catch(error => {
            console.error('Upload error:', error);
        });
}





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

        // Get form values
        const fullName = document.getElementById('full-name').value;
        const email = document.getElementById('email').value;

        // Update the profile card with new values
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



<!-- JavaScript for Image Preview and Auto-upload -->

    // Preview image before upload
    function previewImage(input) {
    if (input.files && input.files[0]) {
    const reader = new FileReader();

    reader.onload = function(e) {
    // Show the image preview
    const imgElement = document.getElementById('modal-profile-image');
    imgElement.src = e.target.result;
    imgElement.classList.remove('hidden');

    // Hide the initials
    document.getElementById('modal-profile-initials').classList.add('hidden');

    // Auto-submit the form
    document.getElementById('profile-pic-form').submit();
}

    reader.readAsDataURL(input.files[0]);
}
}

    // Show loading state during upload
    document.getElementById('profile-pic-form').addEventListener('submit', function() {
    const uploadBtn = document.querySelector('#modal-profile-upload + label span');
    if (uploadBtn) {
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Uploading...';
}
});
