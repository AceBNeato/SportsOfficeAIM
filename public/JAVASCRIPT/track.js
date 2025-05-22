/**
 * track.js - Client-side JavaScript for submission tracking page
 * Manages modals, file uploads, and submission actions
 */

// Configuration constants
const CONFIG = {
    API_BASE: '/controller', // Adjust based on environment
    VALID_FILE_TYPES: [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png'
    ],
    MAX_FILE_SIZE: 5 * 1024 * 1024, // 5MB
    FILE_LOAD_TIMEOUT: 15000, // 15 seconds
    MIN_DESCRIPTION_LENGTH: 10
};

// Status mapping
const STATUS_MAP = {
    pending: { display: 'Pending Approval', class: 'text-yellow-800 bg-yellow-100' },
    approved: { display: 'Approved', class: 'text-green-800 bg-green-100' },
    rejected: { display: 'Rejected', class: 'text-red-800 bg-red-100' }
};

// Utility functions
const utils = {
    // Sanitize HTML to prevent XSS
    sanitizeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    // Validate file uploads
    validateFile(file) {
        if (!file) return { valid: false, message: 'No file selected.' };
        if (!CONFIG.VALID_FILE_TYPES.includes(file.type)) {
            return { valid: false, message: 'Invalid file type. Please select PDF, DOC, DOCX, JPG, or PNG.' };
        }
        if (file.size > CONFIG.MAX_FILE_SIZE) {
            return { valid: false, message: 'File too large. Maximum size is 5MB.' };
        }
        return { valid: true, message: '' };
    },

    // Fetch with timeout
    async fetchWithTimeout(url, options, timeout = CONFIG.FILE_LOAD_TIMEOUT) {
        const controller = new AbortController();
        const id = setTimeout(() => controller.abort(), timeout);
        try {
            const response = await fetch(url, { ...options, signal: controller.signal });
            clearTimeout(id);
            return response;
        } catch (error) {
            clearTimeout(id);
            throw error;
        }
    },

    // Trap focus within modal for accessibility
    trapFocus(modal) {
        const focusableElements = modal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        const trap = (e) => {
            if (e.key === 'Tab') {
                if (e.shiftKey && document.activeElement === firstElement) {
                    e.preventDefault();
                    lastElement.focus();
                } else if (!e.shiftKey && document.activeElement === lastElement) {
                    e.preventDefault();
                    firstElement.focus();
                }
            }
        };
        modal.addEventListener('keydown', trap);
        return trap;
    }
};

// Modal management
const modalManager = {
    activeModals: new Map(),
    objectURLs: new Set(),

    open(modalId, contentCallback) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        // Clear previous content
        const contentArea = modal.querySelector('.modal-content-area');
        if (contentArea) contentArea.innerHTML = '';

        // Set up modal
        modal.classList.remove('hidden');
        modal.classList.add('opacity-100');
        modal.setAttribute('aria-hidden', 'false');

        // Apply content
        if (contentCallback) contentCallback(modal);

        // Focus management
        const focusTrap = utils.trapFocus(modal);
        const firstFocusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (firstFocusable) firstFocusable.focus();

        this.activeModals.set(modalId, { modal, focusTrap });
    },

    close(modalId) {
        const modalInfo = this.activeModals.get(modalId);
        if (!modalInfo) return;

        const { modal, focusTrap } = modalInfo;
        modal.classList.remove('opacity-100');
        modal.classList.add('opacity-0');

        setTimeout(() => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');

            // Clean up object URLs
            this.objectURLs.forEach(url => URL.revokeObjectURL(url));
            this.objectURLs.clear();

            // Remove focus trap
            modal.removeEventListener('keydown', focusTrap);

            // Clear content
            const contentArea = modal.querySelector('.modal-content-area');
            if (contentArea) contentArea.innerHTML = '';

            this.activeModals.delete(modalId);
        }, 300);
    },

    closeAll() {
        this.activeModals.forEach((_, modalId) => this.close(modalId));
    }
};

// Submission actions
const submissionActions = {
    async deleteSubmission(submissionId) {
        if (!confirm('Are you sure you want to delete this submission?')) return;

        try {
            const response = await utils.fetchWithTimeout(`${CONFIG.API_BASE}/delete_submission.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${encodeURIComponent(submissionId)}`
            });
            const data = await response.json();
            if (data.success) {
                alert('Submission deleted successfully!');
                location.reload();
            } else {
                throw new Error(data.message || 'Deletion failed');
            }
        } catch (error) {
            alert(`Error deleting submission: ${error.message}`);
        }
    },

    async resubmitSubmission(submissionId, description) {
        if (description.trim().length < CONFIG.MIN_DESCRIPTION_LENGTH) {
            throw new Error('Description must be at least 10 characters');
        }

        try {
            const response = await utils.fetchWithTimeout(`${CONFIG.API_BASE}/return_submission.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${encodeURIComponent(submissionId)}`
            });
            const data = await response.json();
            if (data.success) {
                alert('Submission resubmitted successfully!');
                location.reload();
            } else {
                throw new Error(data.message || 'Resubmission failed');
            }
        } catch (error) {
            throw new Error(`Resubmission error: ${error.message}`);
        }
    },

    async updateFile(submissionId, file) {
        const validation = utils.validateFile(file);
        if (!validation.valid) {
            throw new Error(validation.message);
        }

        const formData = new FormData();
        formData.append('submission_id', submissionId);
        formData.append('uploaded_file', file);

        try {
            const response = await utils.fetchWithTimeout(`${CONFIG.API_BASE}/update_submission.php`, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                alert('File updated successfully!');
                location.reload();
            } else {
                throw new Error(data.message || 'Update failed');
            }
        } catch (error) {
            throw new Error(`Update error: ${error.message}`);
        }
    }
};

// Modal content generators
const modalContent = {
    editSubmission(submissionId, documentType, otherType, description, fileName, status, submissionDate) {
        return (modal) => {
            const elements = {
                form: modal.querySelector('#editSubmissionForm'),
                submissionId: modal.querySelector('#edit_submission_id'),
                documentTypeDisplay: modal.querySelector('#edit_document_type_display'),
                description: modal.querySelector('#edit_description'),
                submissionDate: modal.querySelector('#edit_submission_date'),
                statusDisplay: modal.querySelector('#edit_status_display'),
                fileInput: modal.querySelector('#uploaded_file'),
                fileInfo: modal.querySelector('#file_info'),
                fileName: modal.querySelector('#file_name'),
                fileWarning: modal.querySelector('#edit_file_warning'),
                actionButtons: modal.querySelector('#edit_action_buttons'),
                submitButton: modal.querySelector('#edit_submit_button'),
                resubmitButton: modal.querySelector('#resubmitButton'),
                descWarning: modal.querySelector('#edit_desc_warning')
            };

            // Set initial values
            elements.submissionId.value = submissionId;
            elements.documentTypeDisplay.textContent = documentType === 'Others' && otherType ? otherType : documentType;
            elements.description.value = description || '';
            elements.submissionDate.textContent = submissionDate;
            elements.fileInput.value = '';
            elements.fileInfo.classList.toggle('hidden', !fileName);
            elements.fileName.textContent = fileName || 'No file uploaded';
            elements.fileWarning.classList.add('hidden');

            // Status and action buttons
            const statusInfo = STATUS_MAP[status] || { display: status, class: 'text-gray-800 bg-gray-100' };
            elements.statusDisplay.innerHTML = `<span class="px-4 py-1.5 rounded-full text-sm font-medium ${statusInfo.class}">${statusInfo.display}</span>`;

            const actionButtons = [
                `<button type="button" data-action="delete" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded-lg text-sm hover:bg-gray-300 transition-colors shadow-sm" aria-label="Delete submission">Delete</button>`
            ];
            if (fileName) {
                actionButtons.push(
                    `<button type="button" data-action="view" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors shadow-sm" aria-label="View file">View File</button>`
                );
            }
            if (status === 'approved') {
                actionButtons.push(
                    `<a href="${CONFIG.API_BASE}/download_submission.php?id=${encodeURIComponent(submissionId)}" class="px-3 py-1.5 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition-colors shadow-sm" aria-label="Download file">Download</a>`
                );
            }
            elements.actionButtons.innerHTML = actionButtons.join('');
            elements.resubmitButton.classList.toggle('hidden', status !== 'rejected');

            // Event listeners
            const listeners = [];
            const descriptionHandler = () => {
                const isValid = elements.description.value.trim().length >= CONFIG.MIN_DESCRIPTION_LENGTH;
                elements.descWarning.classList.toggle('hidden', isValid);
                return isValid;
            };
            elements.description.addEventListener('input', descriptionHandler);
            listeners.push({ element: elements.description, event: 'input', handler: descriptionHandler });

            const fileChangeHandler = () => {
                const file = elements.fileInput.files[0];
                elements.fileInfo.classList.toggle('hidden', !file && !fileName);
                elements.fileName.textContent = file ? file.name : (fileName || 'No file uploaded');
                elements.fileWarning.classList.add('hidden');

                const validation = utils.validateFile(file);
                if (file && !validation.valid) {
                    elements.fileWarning.textContent = validation.message;
                    elements.fileWarning.classList.remove('hidden');
                    elements.fileInput.value = '';
                }
            };
            elements.fileInput.addEventListener('change', fileChangeHandler);
            listeners.push({ element: elements.fileInput, event: 'change', handler: fileChangeHandler });

            const resubmitHandler = async () => {
                if (!confirm('Are you sure you want to resubmit this document?')) return;
                if (!descriptionHandler()) return;

                elements.submitButton.disabled = true;
                elements.resubmitButton.disabled = true;
                elements.resubmitButton.textContent = 'Resubmitting...';

                try {
                    await submissionActions.resubmitSubmission(submissionId, elements.description.value);
                } catch (error) {
                    alert(error.message);
                } finally {
                    elements.submitButton.disabled = false;
                    elements.resubmitButton.disabled = false;
                    elements.resubmitButton.textContent = 'Resubmit';
                }
            };
            if (status === 'rejected') {
                elements.resubmitButton.addEventListener('click', resubmitHandler);
                listeners.push({ element: elements.resubmitButton, event: 'click', handler: resubmitHandler });
            }

            const actionButtonHandler = async (e) => {
                const action = e.target.dataset.action;
                if (action === 'delete') {
                    await submissionActions.deleteSubmission(submissionId);
                } else if (action === 'view') {
                    modalManager.open('fileViewModal', modalContent.viewFile(submissionId, fileName));
                }
            };
            elements.actionButtons.addEventListener('click', actionButtonHandler);
            listeners.push({ element: elements.actionButtons, event: 'click', handler: actionButtonHandler });

            // Form submission
            const submitHandler = async (e) => {
                e.preventDefault();
                if (!descriptionHandler()) return;

                const formData = new FormData(elements.form);
                elements.submitButton.disabled = true;
                elements.resubmitButton.disabled = true;
                elements.submitButton.textContent = 'Saving...';

                try {
                    const response = await utils.fetchWithTimeout(elements.form.action, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        alert('Submission updated successfully!');
                        modalManager.close('editSubmissionModal');
                        location.reload();
                    } else {
                        throw new Error(data.message || 'Update failed');
                    }
                } catch (error) {
                    alert(`Error updating submission: ${error.message}`);
                } finally {
                    elements.submitButton.disabled = false;
                    elements.resubmitButton.disabled = false;
                    elements.submitButton.textContent = 'Save Changes';
                }
            };
            elements.form.addEventListener('submit', submitHandler);
            listeners.push({ element: elements.form, event: 'submit', handler: submitHandler });

            // Cleanup
            modal.addEventListener('transitionend', () => {
                if (modal.classList.contains('hidden')) {
                    listeners.forEach(({ element, event, handler }) => {
                        element.removeEventListener(event, handler);
                    });
                }
            }, { once: true });
        };
    },

    viewFile(submissionId, fileName) {
        return async (modal) => {
            const preview = modal.querySelector('#fileViewPreview');
            const downloadLink = modal.querySelector('#fileDownloadLink');

            downloadLink.href = `${CONFIG.API_BASE}/download_submission.php?id=${encodeURIComponent(submissionId)}&download=true`;
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

            try {
                const response = await utils.fetchWithTimeout(`${CONFIG.API_BASE}/download_submission.php?id=${encodeURIComponent(submissionId)}`);
                if (!response.ok) throw new Error(`Failed to load file: ${response.statusText}`);
                const blob = await response.blob();
                const url = URL.createObjectURL(blob);
                modalManager.objectURLs.add(url);

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
                }
            } catch (error) {
                preview.innerHTML = `
                    <div class="text-center py-6 text-red-600 text-sm">
                        Error loading file: ${error.message}. 
                        <a href="${downloadLink.href}" class="text-blue-600 underline">Download</a>
                    </div>
                `;
            }
        };
    },

    viewDocument(type, date, status, description, fileName, submissionId, rawStatus, userData) {
        return async (modal) => {
            const contentArea = modal.querySelector('.modal-content-area');
            const escapedDescription = utils.sanitizeHTML(description);
            const statusInfo = STATUS_MAP[rawStatus] || { display: rawStatus, class: 'text-gray-800 bg-gray-100' };

            let filePreviewSection = fileName ? `
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

            let fileUploadSection = rawStatus === 'pending' ? `
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
                            <span id="modal_file_name" class="text-sm text-gray-800">${utils.sanitizeHTML(fileName || 'No file uploaded')}</span>
                        </div>
                        <span id="modal_file_warning" class="text-xs text-red-600 hidden block mt-3">Please select a valid file</span>
                    </div>
                </div>
            ` : '';

            contentArea.innerHTML = `
                <div class="flex items-center justify-between mb-4 border-b pb-3">
                    <div class="flex items-center space-x-4">
                        <div class="w-10 h-10 rounded-full border-2 border-gray-300 overflow-hidden">
                            ${userData.profileImage ? `
                                <img src="${userData.profileImage}" alt="Profile" class="w-full h-full object-cover">
                            ` : `
                                <svg class="w-6 h-6 text-gray-400 mx-auto my-2" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            `}
                        </div>
                        <div>
                            <h2 id="modal-title" class="text-lg font-semibold text-gray-900">${utils.sanitizeHTML(userData.fullName || 'Unknown')}</h2>
                            <p class="text-sm text-gray-500">ID: ${utils.sanitizeHTML(userData.studentId || 'N/A')}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1.5 rounded-full text-sm font-medium ${statusInfo.class}">${statusInfo.display}</span>
                        <button data-action="close" class="text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded p-1" aria-label="Close modal">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Document Type</p>
                        <p class="text-sm text-gray-600">${utils.sanitizeHTML(type)}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Submission Date</p>
                        <p class="text-sm text-gray-600">${utils.sanitizeHTML(date)}</p>
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
                        <button data-action="update" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">Update File</button>
                    ` : ''}
                    <button data-action="close" class="px-4 py-2 bg-gray-500 text-white rounded-lg text-sm hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">Close</button>
                </div>
            `;

            // File preview
            if (fileName) {
                const fileUrl = `${CONFIG.API_BASE}/download_submission.php?id=${encodeURIComponent(submissionId)}`;
                try {
                    const response = await utils.fetchWithTimeout(fileUrl);
                    if (!response.ok) throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    const blob = await response.blob();
                    const url = URL.createObjectURL(blob);
                    modalManager.objectURLs.add(url);

                    const ext = fileName.split('.').pop().toLowerCase();
                    const preview = contentArea.querySelector('#documentPreview');
                    preview.innerHTML = (blob.type === 'application/pdf' || ext === 'pdf')
                        ? `<iframe src="${url}#zoom=auto" class="w-full h-full rounded-lg" title="PDF Preview"></iframe>`
                        : (blob.type.startsWith('image/') || ['jpg', 'jpeg', 'png'].includes(ext))
                            ? `<img src="${url}" alt="File" class="w-full h-full object-contain rounded-lg">`
                            : `<div class="text-center py-4 text-red-600 text-sm">Unsupported type (.${ext}). <a href="${fileUrl}&download=true" class="text-blue-600 underline">Download</a></div>`;
                } catch (error) {
                    contentArea.querySelector('#documentPreview').innerHTML = `<div class="text-center py-4 text-red-600 text-sm">${utils.sanitizeHTML(error.message)}. <a href="${fileUrl}&download=true" class="text-blue-600 underline">Download</a></div>`;
                }
            }

            // File upload for pending submissions
            if (rawStatus === 'pending') {
                const fileInput = contentArea.querySelector('#modal_uploaded_file');
                const fileInfo = contentArea.querySelector('#modal_file_info');
                const fileNameSpan = contentArea.querySelector('#modal_file_name');
                const fileWarning = contentArea.querySelector('#modal_file_warning');

                const fileChangeHandler = () => {
                    const file = fileInput.files[0];
                    fileInfo.classList.toggle('hidden', !file && !fileName);
                    fileNameSpan.textContent = file ? file.name : (fileName || 'No file uploaded');
                    fileWarning.classList.add('hidden');

                    const validation = utils.validateFile(file);
                    if (file && !validation.valid) {
                        fileWarning.textContent = validation.message;
                        fileWarning.classList.remove('hidden');
                        fileInput.value = '';
                    }
                };
                fileInput.addEventListener('change', fileChangeHandler);

                const updateHandler = async () => {
                    const file = fileInput.files[0];
                    const updateButton = contentArea.querySelector('[data-action="update"]');
                    updateButton.disabled = true;
                    updateButton.textContent = 'Updating...';

                    try {
                        await submissionActions.updateFile(submissionId, file);
                    } catch (error) {
                        fileWarning.textContent = error.message;
                        fileWarning.classList.remove('hidden');
                    } finally {
                        updateButton.disabled = false;
                        updateButton.textContent = 'Update File';
                    }
                };

                const actionHandler = (e) => {
                    const action = e.target.dataset.action;
                    if (action === 'close') {
                        modalManager.close('documentModal');
                    } else if (action === 'update') {
                        updateHandler();
                    }
                };
                contentArea.addEventListener('click', actionHandler);

                modal.addEventListener('transitionend', () => {
                    if (modal.classList.contains('hidden')) {
                        fileInput.removeEventListener('change', fileChangeHandler);
                        contentArea.removeEventListener('click', actionHandler);
                    }
                }, { once: true });
            } else {
                const actionHandler = (e) => {
                    if (e.target.dataset.action === 'close') {
                        modalManager.close('documentModal');
                    }
                };
                contentArea.addEventListener('click', actionHandler);
                modal.addEventListener('transitionend', () => {
                    if (modal.classList.contains('hidden')) {
                        contentArea.removeEventListener('click', actionHandler);
                    }
                }, { once: true });
            }
        };
    }
};

// Initialize modals and event listeners
document.addEventListener('DOMContentLoaded', () => {
    // Close modals on backdrop click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modalManager.close(modal.id);
            }
        });
    });

    // Status filter
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', () => {
            const value = statusFilter.value;
            document.querySelectorAll('tbody tr').forEach(row => {
                const status = row.querySelector('td[data-label="Status"] span').textContent.toLowerCase().replace(' review', '');
                row.style.display = (value === 'all' || status === value) ? '' : 'none';
            });
        });
    }

    // Global modal close buttons
    document.querySelectorAll('[data-action="close"]').forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('.modal');
            if (modal) modalManager.close(modal.id);
        });
    });
});

// Exposed API for PHP template
window.submissionManager = {
    openEditModal(submissionId, documentType, otherType, description, fileName, status, submissionDate, approvalDate = null) {
        modalManager.open('editSubmissionModal', modalContent.editSubmission(
            submissionId, documentType, otherType, description, fileName, status, submissionDate
        ));
    },

    openFileViewModal(submissionId, fileName) {
        modalManager.open('fileViewModal', modalContent.viewFile(submissionId, fileName));
    },

    openDocumentModal(type, date, status, description, fileName, submissionId, rawStatus) {
        modalManager.open('documentModal', modalContent.viewDocument(
            type, date, status, description, fileName, submissionId, rawStatus, window.userData || {}
        ));
    },

    deleteSubmission(submissionId) {
        submissionActions.deleteSubmission(submissionId);
    }
};