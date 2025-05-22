let currentForm = null;

function showDocument(id, fileName, fileType) {
    const modal = document.getElementById('documentModal');
    const preview = document.getElementById('documentPreview');
    const downloadLink = document.getElementById('downloadLink');
    const modalTitle = document.getElementById('documentModalTitle');

    // Prevent page scrolling
    document.body.style.overflow = 'hidden';

    try {
        // Parse fileName safely
        const parsedFileName = JSON.parse(fileName);
        modalTitle.textContent = `Document: ${parsedFileName}`;

        // Set download link
        downloadLink.href = `../controller/downloadDocument.php?id=${encodeURIComponent(id)}&download=true`;

        // Show loading indicator
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

        // Show modal
        modal.classList.add('show');

        // Fetch document
        fetch(`../controller/downloadDocument.php?id=${encodeURIComponent(id)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.blob();
            })
            .then(blob => {
                const url = URL.createObjectURL(blob);
                // Normalize file type
                const normalizedFileType = fileType.toLowerCase();
                if (normalizedFileType === 'application/pdf' || normalizedFileType.includes('pdf')) {
                    preview.innerHTML = `<iframe src="${url}#zoom=auto" style="width:100%; height:100%; max-height:100%;" frameborder="0" title="Document Preview"></iframe>`;
                } else if (normalizedFileType.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif'].some(ext => normalizedFileType.includes(ext))) {
                    preview.innerHTML = `<img src="${url}" alt="Document" class="w-full h-full object-contain" />`;
                } else {
                    preview.innerHTML = `<div class="text-center py-6 text-red-500 text-sm">Preview not available for this file type. Please download to view.</div>`;
                }
            })
            .catch(error => {
                console.error('Error loading document:', error);
                preview.innerHTML = `<div class="text-center py-6 text-red-500 text-sm">Error loading document: ${error.message}. Please try downloading.</div>`;
            });
    } catch (error) {
        console.error('Error parsing fileName:', error);
        preview.innerHTML = `<div class="text-center py-6 text-red-500 text-sm">Error: Invalid document data. Please try again.</div>`;
        modal.classList.add('show');
    }
}

function closeModal() {
    const modal = document.getElementById('documentModal');
    const preview = document.getElementById('documentPreview');

    modal.classList.remove('show');
    document.body.style.overflow = '';

    if (preview) {
        const iframes = preview.getElementsByTagName('iframe');
        const images = preview.getElementsByTagName('img');
        Array.from(iframes).forEach(iframe => {
            if (iframe.src.startsWith('blob:')) {
                URL.revokeObjectURL(iframe.src);
            }
        });
        Array.from(images).forEach(img => {
            if (img.src.startsWith('blob:')) {
                URL.revokeObjectURL(img.src);
            }
        });
        preview.innerHTML = '';
    }
}

function showLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
    }
}

function hideLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
}

function showConfirmation(action, name, id, form) {
    currentForm = form;

    Swal.fire({
        title: `Are you sure you want to ${action} ${name}'s request?`,
        text: `This action will ${action} the account request${action === 'reject' ? ' and notify the user.' : '.'}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: action === 'approve' ? '#10B981' : '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: `Yes, ${action} it`,
        cancelButtonText: 'Cancel',
        customClass: {
            popup: 'swal2-custom'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            showLoading();
            form.submit();
        }
    });

    return false;
}

// Close modal on backdrop click
document.addEventListener('click', (event) => {
    const documentModal = document.getElementById('documentModal');
    if (event.target === documentModal && documentModal.classList.contains('show')) {
        closeModal();
    }
});

// Auto-dismiss alerts and show success modals
document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss existing alerts after 5 seconds
    const alert = document.getElementById('alertMessage');
    if (alert) {
        setTimeout(() => {
            alert.style.display = 'none';
        }, 5000);
    }

    // Check URL for success status and show SweetAlert2 modal
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const action = urlParams.get('action');

        if (status === 'success') {
            if (action === 'approve') {
                Swal.fire({
                    title: 'Success!',
                    text: 'The account has been approved successfully.',
                    icon: 'success',
                    confirmButtonColor: '#10B981',
                    confirmButtonText: 'OK',
                    customClass: {
                        popup: 'swal2-custom'
                    }
                });
            } else if (action === 'reject') {
                Swal.fire({
                    title: 'Success!',
                    text: 'The account has been rejected successfully.',
                    icon: 'success',
                    confirmButtonColor: '#EF4444',
                    confirmButtonText: 'OK',
                    customClass: {
                        popup: 'swal2-custom'
                    }
                });
            }
        }
    } catch (error) {
        console.error('Error checking URL parameters for success modal:', error);
    }
});