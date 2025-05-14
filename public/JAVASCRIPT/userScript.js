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



// Open and populate the edit submission modal
function editSubmissionModal(submission) {
    const modal = document.getElementById('editSubmissionModal');
    const form = document.getElementById('editSubmissionForm');
    const submissionIdInput = document.getElementById('edit_submission_id');
    const descriptionInput = document.getElementById('edit_description');
    const documentTypeDisplay = document.getElementById('edit_document_type_display');
    const statusDisplay = document.getElementById('edit_status_display');
    const submissionDateDisplay = document.getElementById('edit_submission_date');
    const fileInput = document.getElementById('uploaded_file');
    const fileInfo = document.getElementById('file_info');
    const fileNameDisplay = document.getElementById('file_name');
    const fileWarning = document.getElementById('edit_file_warning');
    const descWarning = document.getElementById('edit_desc_warning');
    const actionButtons = document.getElementById('edit_action_buttons');

    // Populate modal fields
    submissionIdInput.value = submission.id;
    descriptionInput.value = submission.description || '';
    documentTypeDisplay.textContent = submission.document_type || 'Unknown';
    submissionDateDisplay.textContent = submission.submission_date || 'N/A';

    // Set status display with appropriate styling
    statusDisplay.textContent = submission.status || 'Unknown';
    statusDisplay.className = `text-xs px-2 py-1 rounded-full ${
        submission.status === 'rejected' ? 'bg-red-100 text-red-600' :
            submission.status === 'pending' ? 'bg-yellow-100 text-yellow-600' :
                submission.status === 'approved' ? 'bg-green-100 text-green-600' :
                    'bg-gray-100 text-gray-600'
    }`;

    // Reset file input and info
    fileInput.value = '';
    fileInfo.classList.add('hidden');
    fileNameDisplay.textContent = '';
    fileWarning.classList.add('hidden');
    descWarning.classList.add('hidden');

    // If there's an existing file, show it
    if (submission.file_name) {
        fileInfo.classList.remove('hidden');
        fileNameDisplay.textContent = submission.file_name;
    }

    // Add Resubmit button if status is rejected
    actionButtons.innerHTML = '';
    if (submission.status === 'rejected') {
        const resubmitButton = document.createElement('button');
        resubmitButton.type = 'button';
        resubmitButton.className = 'py-2 px-4 bg-green-600 text-white rounded-md text-xs font-semibold hover:bg-green-700 transition-colors shadow-sm';
        resubmitButton.textContent = 'Resubmit';
        resubmitButton.onclick = () => resubmitSubmission(submission.id);
        actionButtons.appendChild(resubmitButton);
    }

    // Show modal
    modal.classList.remove('hidden');

    // Client-side file validation
    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        fileWarning.classList.add('hidden');
        fileInfo.classList.add('hidden');

        if (file) {
            const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
            const maxSize = 5 * 1024 * 1024; // 5MB

            if (!allowedTypes.includes(file.type)) {
                fileWarning.textContent = 'Invalid file type. Allowed: PDF, DOC, DOCX, JPG, PNG';
                fileWarning.classList.remove('hidden');
                fileInput.value = '';
                return;
            }

            if (file.size > maxSize) {
                fileWarning.textContent = 'File too large. Max size: 5MB';
                fileWarning.classList.remove('hidden');
                fileInput.value = '';
                return;
            }

            fileInfo.classList.remove('hidden');
            fileNameDisplay.textContent = file.name;
        }
    });

    // Client-side description validation
    descriptionInput.addEventListener('input', () => {
        descWarning.classList.add('hidden');
        if (descriptionInput.value.length < 10) {
            descWarning.classList.remove('hidden');
        }
    });
}

// Close the modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.add('hidden');
    document.getElementById('editSubmissionForm').reset();
    document.getElementById('edit_file_warning').classList.add('hidden');
    document.getElementById('edit_desc_warning').classList.add('hidden');
    document.getElementById('file_info').classList.add('hidden');
}

// Handle form submission
document.getElementById('editSubmissionForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const descWarning = document.getElementById('edit_desc_warning');

    // Client-side validation
    const description = formData.get('description');
    if (description.length < 10) {
        descWarning.classList.remove('hidden');
        return;
    }

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            alert('Submission updated successfully!');
            // Optionally, refresh the submission list or close modal
            closeModal('editSubmissionModal');
            // Trigger a function to refresh the submission list if needed
            // e.g., fetchSubmissions();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('An error occurred while updating the submission.');
    }
});

// Resubmit submission
async function resubmitSubmission(submissionId) {
    try {
        const response = await fetch('../controller/return_submission.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${submissionId}`
        });
        const result = await response.json();

        if (result.success) {
            alert('Submission resubmitted successfully!');
            closeModal('editSubmissionModal');
            // Trigger a function to refresh the submission list if needed
            // e.g., fetchSubmissions();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('An error occurred while resubmitting.');
    }
}
























