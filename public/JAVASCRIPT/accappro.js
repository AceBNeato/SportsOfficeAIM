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

function showConfirmation(action, fullName, approvalId, form) {
    const modal = document.getElementById('confirmationModal');
    const title = document.getElementById('confirmationTitle');
    const message = document.getElementById('confirmationMessage');
    const confirmBtn = document.getElementById('confirmActionBtn');

    title.textContent = `${action.charAt(0).toUpperCase() + action.slice(1)} Request`;
    message.textContent = `Are you sure you want to ${action} the account request for ${fullName}?`;
    confirmBtn.className = `action-btn ${action === 'reject' ? 'reject-btn' : 'approve-btn'}`;
    if (action === 'reject') {
        message.textContent += ' This will notify the user that their request has been rejected.';
    }

    currentForm = form;
    modal.classList.add('show');
    return false;
}

function confirmAction() {
    if (currentForm) {
        currentForm.submit();
    }
}

function cancelConfirmation() {
    const modal = document.getElementById('confirmationModal');
    modal.classList.remove('show');
    currentForm = null;
}

// Unified event listener for backdrop clicks
document.addEventListener('click', (event) => {
    const documentModal = document.getElementById('documentModal');
    const confirmationModal = document.getElementById('confirmationModal');

    if (event.target === documentModal && documentModal.classList.contains('show')) {
        closeModal();
    }
    if (event.target === confirmationModal && confirmationModal.classList.contains('show')) {
        cancelConfirmation();
    }
});

// Auto-dismiss alerts after 5 seconds
const alertMessage = document.getElementById('alertMessage');
if (alertMessage) {
    setTimeout(() => {
        alertMessage.style.display = 'none';
    }, 5000);
}