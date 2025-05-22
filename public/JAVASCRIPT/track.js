const VALID_FILE_TYPES = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/png'];
const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB

// Status mapping for consistency
const STATUS_MAP = {
    pending: { display: 'Pending Approval', class: 'text-yellow-800 bg-yellow-100' },
    approved: { display: 'Approved', class: 'text-green-800 bg-green-100' },
    rejected: { display: 'Rejected', class: 'text-red-800 bg-red-100' }
};

// Utility function to validate files
function validateFile(file) {
    if (!file) return { valid: false, message: 'No file selected.' };
    if (!VALID_FILE_TYPES.includes(file.type)) {
        return { valid: false, message: 'Invalid file type. Please select PDF, DOC, DOCX, JPG, or PNG.' };
    }
    if (file.size > MAX_FILE_SIZE) {
        return { valid: false, message: 'File too large. Maximum size is 5MB.' };
    }
    return { valid: true, message: '' };
}

// Utility function to close modals
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modal.classList.remove('opacity-100');
    modal.classList.add('opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
        const preview = modal.querySelector('#fileViewPreview') || modal.querySelector('#documentPreview');
        if (preview) {
            const iframes = preview.getElementsByTagName('iframe');
            const images = preview.getElementsByTagName('img');
            for (let iframe of iframes) URL.revokeObjectURL(iframe.src);
            for (let img of images) URL.revokeObjectURL(img.src);
            preview.innerHTML = '';
        }
    }, 300);
}

function openEditModal(submissionId, documentType, otherType, description, fileName, status, submissionDate, approvalDate = null) {
    const elements = {
        modal: document.getElementById('editSubmissionModal'),
        form: document.getElementById('editSubmissionForm'),
        submissionId: document.getElementById('edit_submission_id'),
        documentTypeDisplay: document.getElementById('edit_document_type_display'),
        description: document.getElementById('edit_description'),
        submissionDate: document.getElementById('edit_submission_date'),
        statusDisplay: document.getElementById('edit_status_display'),
        fileInput: document.getElementById('uploaded_file'),
        fileInfo: document.getElementById('file_info'),
        fileName: document.getElementById('file_name'),
        fileWarning: document.getElementById('edit_file_warning'),
        actionButtons: document.getElementById('edit_action_buttons'),
        submitButton: document.getElementById('edit_submit_button'),
        resubmitButton: document.getElementById('resubmitButton'),
        descWarning: document.getElementById('edit_desc_warning')
    };

    // Set form values
    elements.submissionId.value = submissionId;
    elements.documentTypeDisplay.textContent = documentType === 'Others' && otherType ? otherType : documentType;
    elements.description.value = description || '';
    elements.submissionDate.textContent = submissionDate;

    // Reset file input
    elements.fileInput.value = '';
    elements.fileInfo.classList.add('hidden');
    elements.fileName.textContent = fileName || 'No file uploaded';
    elements.fileWarning.classList.add('hidden');
    if (fileName) elements.fileInfo.classList.remove('hidden');

    // Set status display and action buttons
    const statusInfo = STATUS_MAP[status] || { display: status, class: 'text-gray-800 bg-gray-100' };
    const escapedFileName = fileName ? fileName.replace(/'/g, "\\'") : '';
    const viewFileButton = fileName ? `
        <button type="button" onclick="openFileViewModal('${submissionId}', '${escapedFileName}')"
                class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors shadow-sm"
                aria-label="View file">View File</button>
    ` : '';

    let actionButtonsHtml = `
        <button type="button" onclick="deleteSubmission(${submissionId})"
                class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded-lg text-sm hover:bg-gray-300 transition-colors shadow-sm"
                aria-label="Delete submission">Delete</button>
        ${viewFileButton}
    `;
    elements.resubmitButton.classList.add('hidden');

    if (status === 'approved') {
        actionButtonsHtml += `
            <a href="../controller/download_submission.php?id=${submissionId}"
               class="px-3 py-1.5 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition-colors shadow-sm"
               aria-label="Download file">Download</a>
        `;
    } else if (status === 'rejected') {
        elements.resubmitButton.classList.remove('hidden');
    }

    elements.statusDisplay.innerHTML = `<span class="px-4 py-1.5 rounded-full text-sm font-medium ${statusInfo.class}">${statusInfo.display}</span>`;
    elements.actionButtons.innerHTML = actionButtonsHtml;

    // Show modal with smooth transition
    elements.modal.classList.remove('hidden');
    setTimeout(() => elements.modal.classList.add('opacity-100'), 50);

    // Validation handlers
    const descriptionHandler = () => {
        elements.descWarning.classList.toggle('hidden', elements.description.value.trim().length >= 10);
    };
    elements.description.addEventListener('input', descriptionHandler);

    const fileChangeHandler = () => {
        const file = elements.fileInput.files[0];
        elements.fileInfo.classList.add('hidden');
        elements.fileName.textContent = fileName || 'No file uploaded';
        elements.fileWarning.classList.add('hidden');

        const validation = validateFile(file);
        if (file && !validation.valid) {
            elements.fileWarning.textContent = validation.message;
            elements.fileWarning.classList.remove('hidden');
            elements.fileInput.value = '';
        } else if (file) {
            elements.fileInfo.classList.remove('hidden');
            elements.fileName.textContent = file.name;
        }
    };
    elements.fileInput.addEventListener('change', fileChangeHandler);

    // Resubmit handler
    if (status === 'rejected') {
        const resubmitHandler = async () => {
            if (!confirm('Are you sure you want to resubmit this document?')) return;

            if (elements.description.value.trim().length < 10) {
                elements.descWarning.classList.remove('hidden');
                return;
            }

            elements.submitButton.disabled = true;
            elements.resubmitButton.disabled = true;
            elements.resubmitButton.textContent = 'Resubmitting...';

            try {
                const response = await fetch('../controller/return_submission.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${encodeURIComponent(submissionId)}`
                });
                const result = await response.json();
                if (result.success) {
                    alert('Submission resubmitted successfully!');
                    closeModal('editSubmissionModal');
                    location.reload();
                } else {
                    throw new Error(result.message || 'Resubmission failed');
                }
            } catch (error) {
                alert(`Error: ${error.message}`);
            } finally {
                elements.submitButton.disabled = false;
                elements.resubmitButton.disabled = false;
                elements.resubmitButton.textContent = 'Resubmit';
            }
        };
        elements.resubmitButton.addEventListener('click', resubmitHandler);

        // Cleanup event listeners when modal closes
        elements.modal.addEventListener('transitionend', () => {
            if (elements.modal.classList.contains('hidden')) {
                elements.description.removeEventListener('input', descriptionHandler);
                elements.fileInput.removeEventListener('change', fileChangeHandler);
                elements.resubmitButton.removeEventListener('click', resubmitHandler);
            }
        }, { once: true });
    }
}

function openFileViewModal(submissionId, fileName) {
    const modal = document.getElementById('fileViewModal');
    const preview = document.getElementById('fileViewPreview');
    const downloadLink = document.getElementById('fileDownloadLink');

    // Remove other modals
    document.querySelectorAll('.fixed.inset-0').forEach(m => {
        if (m.id !== 'fileViewModal') m.remove();
    });

    downloadLink.href = `../controller/download_submission.php?id=${encodeURIComponent(submissionId)}&download=true`;
    downloadLink.classList.remove('hidden');

    preview.innerHTML = `
        <div class="flex items-center justify-center h-96">
            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="ml-3 text-gray-600 text-sm">Loading file...</p>
        </div>
    `;
    modal.classList.remove('hidden');
    setTimeout(() => modal.classList.add('opacity-100'), 50);

    fetch(`../controller/download_submission.php?id=${encodeURIComponent(submissionId)}`)
        .then(response => {
            if (!response.ok) throw new Error(`Failed to load file: ${response.statusText}`);
            return response.blob();
        })
        .then(blob => {
            const url = URL.createObjectURL(blob);
            const contentType = blob.type || 'application/octet-stream';
            const fileExtension = fileName.split('.').pop().toLowerCase();

            if (contentType === 'application/pdf' || fileExtension === 'pdf') {
                preview.innerHTML = `<iframe src="${url}#zoom=auto" class="w-full h-96 rounded-lg" title="PDF Preview" aria-label="PDF file preview"></iframe>`;
            } else if (contentType.startsWith('image/') || ['jpg', 'jpeg', 'png'].includes(fileExtension)) {
                preview.innerHTML = `<img src="${url}" alt="Uploaded File" class="w-full h-96 object-contain rounded-lg" aria-label="Image preview" />`;
            } else {
                preview.innerHTML = `
                    <div class="text-center py-6 text-red-600 text-sm">
                        Preview not available for this file type (.${fileExtension}). 
                        <a href="${downloadLink.href}" class="text-blue-600 underline">Download</a>
                    </div>
                `;
                downloadLink.classList.remove('hidden');
            }
        })
        .catch(error => {
            preview.innerHTML = `
                <div class="text-center py-6 text-red-600 text-sm">
                    Error loading file: ${error.message}. 
                    <a href="${downloadLink.href}" class="text-blue-600 underline">Download</a>
                </div>
            `;
            downloadLink.classList.remove('hidden');
        });

    const closeHandler = e => {
        if (e.target === modal) closeModal('fileViewModal');
    };
    modal.addEventListener('click', closeHandler, { once: true });
}

function openDocumentModal(type, date, status, description, fileName, submissionId, rawStatus) {

    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 flex items-center justify-center bg-black bg-opacity-70 z-50 px-4 transition-opacity duration-300';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-labelledby', 'modal-title');

    const escapedDescription = description.replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const filePreviewSection = fileName ? `
        <div id="documentPreview" class="w-full h-96 mt-4 rounded-lg overflow-hidden bg-gray-50 relative">
            <div class="absolute inset-0 flex items-center justify-center">
                <svg class="animate-spin h-8 w-8 text-blue-600" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="ml-3 text-sm text-gray-600">Loading...</p>
            </div>
        </div>
    ` : '<p class="text-sm text-gray-600 mt-4">No file available.</p>';

    const fileUploadSection = rawStatus === 'pending' ? `
        <div class="w-full max-w-md mx-auto mt-4">
            <label for="modal_uploaded_file" class="text-sm font-medium text-gray-700 block text-center">Upload New File (Optional)</label>
            <div class="file-upload-area border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition-colors">
                <input type="file" id="modal_uploaded_file" name="uploaded_file" class="hidden" accept=".pdf,.doc,.docx,.jpg,.png">
                <label for="modal_uploaded_file" class="file-upload-label cursor-pointer">
                    <div class="upload-icon-container mx-auto w-12 h-12 text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke="currentColor" class="w-full h-full">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                    </div>
                    <span class="upload-instruction block text-sm font-medium text-gray-600">Click to upload or drag and drop</span>
                    <span class="upload-requirements block text-xs text-gray-500">PDF, DOC, DOCX, JPG, PNG (Max 5MB)</span>
                </label>
                <div id="modal_file_info" class="file-info hidden mt-3">
                    <span class="file-info-label text-sm text-gray-600">Selected file:</span> 
                    <span id="modal_file_name" class="text-sm text-gray-800">${fileName || 'No file uploaded'}</span>
                </div>
                <span id="modal_file_warning" class="text-xs text-red-600 hidden block mt-3">Please select a valid file</span>
            </div>
        </div>
    ` : '';

    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-2xl transform transition-all duration-300">
            <div class="flex items-center justify-between mb-4 border-b pb-3">
                <div class="flex items-center space-x-4">
                     <div class="w-10 h-10 rounded-full border-2 border-gray-300 overflow-hidden">
                            <?php if ($profile_image_data && $profile_image_type): ?>
                                <img src="data:<?php echo htmlspecialchars($profile_image_type); ?>;base64,<?php echo base64_encode($profile_image_data); ?>"
                                     alt="Profile" class="w-full h-full object-cover">
                            <?php else: ?>
                                <svg class="w-6 h-6 text-gray-400 mx-auto my-2" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                    <div>
                        <h2 id="modal-title" class="text-lg font-semibold text-gray-900"> value="<?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? ''); ?>"></h2>
                        <p class="text-sm text-gray-500">ID:   value="<?php echo isset($_SESSION['user']['full_name']) ? htmlspecialchars($_SESSION['user']['student_id']) : ''; ?>"></p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="px-3 py-1.5 rounded-full text-sm font-medium ${STATUS_MAP[rawStatus]?.class || 'text-gray-800 bg-gray-100'}">${STATUS_MAP[rawStatus]?.display || rawStatus}</span>
                    <button onclick="this.closest('.fixed.inset-0').remove()" class="text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded p-1" aria-label="Close modal">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div>
                    <p class="text-sm font-medium text-gray-700">Document Type</p>
                    <p class="text-sm text-gray-600">${type}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-700">Submission Date</p>
                    <p class="text-sm text-gray-600">${date}</p>
                </div>
                <div class="col-span-1 sm:col-span-2">
                    <p class="text-sm font-medium text-gray-700">Description</p>
                    <p class="text-sm text-gray-600 break-words">${escapedDescription}</p>
                </div>
            </div>
            <div class="mt-6">
                <h3 class="text-sm font-medium text-gray-700 mb-3">File Preview</h3>
                ${filePreviewSection}
            </div>
            ${fileUploadSection}
            <div class="mt-6 flex justify-end gap-3">
                ${rawStatus === 'pending' ? `
                    <button onclick="updateFileFromModal(${submissionId})" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">Update File</button>
                ` : ''}
                <button onclick="this.closest('.fixed.inset-0').remove()" class="px-4 py-2 bg-gray-500 text-white rounded-lg text-sm hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">Close</button>
            </div>
        </div>
    `;

    if (fileName) {
        const fileUrl = `../controller/download_submission.php?id=${encodeURIComponent(submissionId)}`;
        const timeoutId = setTimeout(() => {
            const preview = modal.querySelector('#documentPreview');
            preview.innerHTML = `<div class="text-center py-4 text-red-600 text-sm">Timeout loading file. <a href="${fileUrl}&download=true" class="text-blue-600 underline">Download</a></div>`;
        }, 10000);

        fetch(fileUrl)
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                return response.blob();
            })
            .then(blob => {
                clearTimeout(timeoutId);
                const url = URL.createObjectURL(blob);
                const ext = fileName.split('.').pop().toLowerCase();
                const preview = modal.querySelector('#documentPreview');
                preview.innerHTML = (blob.type === 'application/pdf' || ext === 'pdf')
                    ? `<iframe src="${url}#zoom=auto" class="w-full h-full rounded-lg" title="PDF Preview"></iframe>`
                    : (blob.type.startsWith('image/') || ['jpg', 'jpeg', 'png'].includes(ext))
                        ? `<img src="${url}" alt="File" class="w-full h-full object-contain rounded-lg">`
                        : `<div class="text-center py-4 text-red-600 text-sm">Unsupported type (${ext}). <a href="${fileUrl}&download=true" class="text-blue-600 underline">Download</a></div>`;
            })
            .catch(error => {
                clearTimeout(timeoutId);
                modal.querySelector('#documentPreview').innerHTML = `<div class="text-center py-4 text-red-600 text-sm">${error.message}. <a href="${fileUrl}&download=true" class="text-blue-600 underline">Download</a></div>`;
            });
    }

    if (rawStatus === 'pending') {
        const fileInput = modal.querySelector('#modal_uploaded_file');
        const fileInfo = modal.querySelector('#modal_file_info');
        const fileNameSpan = modal.querySelector('#modal_file_name');
        const fileWarning = modal.querySelector('#modal_file_warning');

        const fileChangeHandler = () => {
            const file = fileInput.files[0];
            fileInfo.classList.add('hidden');
            fileNameSpan.textContent = fileName || 'No file uploaded';
            fileWarning.classList.add('hidden');

            const validation = validateFile(file);
            if (file && !validation.valid) {
                fileWarning.textContent = validation.message;
                fileWarning.classList.remove('hidden');
                fileInput.value = '';
            } else if (file) {
                fileInfo.classList.remove('hidden');
                fileNameSpan.textContent = file.name;
            }
        };
        fileInput.addEventListener('change', fileChangeHandler);

        modal.addEventListener('transitionend', () => {
            if (modal.classList.contains('hidden')) {
                fileInput.removeEventListener('change', fileChangeHandler);
            }
        }, { once: true });
    }

    modal.addEventListener('click', e => e.target === modal && modal.remove());
    document.body.appendChild(modal);
}

function updateFileFromModal(submissionId) {
    const fileInput = document.getElementById('modal_uploaded_file');
    const fileWarning = document.getElementById('modal_file_warning');
    const updateButton = document.querySelector('.bg-blue-600');

    const validation = validateFile(fileInput.files[0]);
    if (!validation.valid) {
        fileWarning.textContent = validation.message;
        fileWarning.classList.remove('hidden');
        return;
    }

    const formData = new FormData();
    formData.append('submission_id', submissionId);
    formData.append('uploaded_file', fileInput.files[0]);

    updateButton.disabled = true;
    updateButton.textContent = 'Updating...';

    fetch('../controller/update_submission.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('File updated successfully!');
                location.reload();
            } else {
                alert(`Error: ${data.message || 'Update failed'}`);
            }
        })
        .catch(error => {
            alert(`Error updating file: ${error.message}`);
        })
        .finally(() => {
            updateButton.disabled = false;
            updateButton.textContent = 'Update File';
        });
}

function deleteSubmission(submissionId) {
    if (!confirm('Are you sure you want to delete this submission?')) return;

    fetch('../controller/delete_submission.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${encodeURIComponent(submissionId)}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Submission deleted successfully!');
                location.reload();
            } else {
                alert(`Error deleting submission: ${data.message || 'Unknown error'}`);
            }
        })
        .catch(error => {
            alert(`Error deleting submission: ${error.message}`);
        });
}

document.getElementById('editSubmissionForm').addEventListener('submit', async e => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const descWarning = document.getElementById('edit_desc_warning');
    const submitButton = document.getElementById('edit_submit_button');
    const resubmitButton = document.getElementById('resubmitButton');

    if (formData.get('description').length < 10) {
        descWarning.classList.remove('hidden');
        return;
    }

    submitButton.disabled = true;
    resubmitButton.disabled = true;
    submitButton.textContent = 'Saving...';

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            alert('Submission updated successfully!');
            closeModal('editSubmissionModal');
            location.reload();
        } else {
            throw new Error(result.message || 'Update failed');
        }
    } catch (error) {
        alert(`Error updating submission: ${error.message}`);
    } finally {
        submitButton.disabled = false;
        resubmitButton.disabled = false;
        submitButton.textContent = 'Save Changes';
    }
});