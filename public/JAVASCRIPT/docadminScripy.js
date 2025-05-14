


// File: public/JAVASCRIPT/docadminScripy.js
    // Helper function to escape HTML
    function htmlEscape(str) {
    return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

    // Show evaluation modal
    function showEvaluationModal(studentId, fullName, submissions) {
    try {
    const modal = document.getElementById('evaluationModal');
    const studentIdSpan = document.getElementById('modal_student_id');
    const fullNameSpan = document.getElementById('modal_full_name');
    const submissionsList = document.getElementById('submissions_list');

    if (!modal || !studentIdSpan || !fullNameSpan || !submissionsList) {
    console.error('Modal elements missing');
    alert('Error: Modal components are missing.');
    return;
}

    studentIdSpan.textContent = studentId;
    fullNameSpan.textContent = fullName;
    submissionsList.innerHTML = '';

    if (!submissions || submissions.length === 0) {
    submissionsList.innerHTML = '<p class="text-gray-500 text-center">No submissions found.</p>';
} else {
    submissions.forEach(sub => {
    const submissionDate = new Date(sub.submission_date).toLocaleDateString('en-US', {
    month: '2-digit',
    day: '2-digit',
    year: 'numeric'
});
    const docType = sub.document_type === 'Others' && sub.other_type ? sub.other_type : sub.document_type;
    let statusColor = sub.status === 'approved' ? 'text-green-600' : sub.status === 'rejected' ? 'text-red-600' : 'text-yellow-600';
    let actionsHtml = '';
    if (sub.status === 'pending') {
    actionsHtml = `
                                <div class="mt-3 flex justify-end space-x-2">
                                    <button onclick="viewSubmission('${sub.id}', '${htmlEscape(sub.file_name)}')" class="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600 transition-colors" aria-label="View submission">View</button>
                                    <button onclick="showApproveModal('${sub.id}')" class="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600 transition-colors" aria-label="Approve submission">Approve</button>
                                    <button onclick="showRejectModal('${sub.id}')" class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600 transition-colors" aria-label="Reject submission">Reject</button>
                                </div>
                            `;
}
    const commentsHtml = sub.comments ? `<p class="text-sm text-gray-700"><strong>Comments:</strong> ${htmlEscape(sub.comments)}</p>` : '';
    const submissionHtml = `
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <p class="text-sm text-gray-700"><strong>Type:</strong> ${htmlEscape(docType)}</p>
                                <p class="text-sm text-gray-700"><strong>Submission Date:</strong> ${htmlEscape(submissionDate)}</p>
                                <p class="text-sm text-gray-700"><strong>Status:</strong> <span class="${statusColor}">${htmlEscape(sub.status.charAt(0).toUpperCase() + sub.status.slice(1))}</span></p>
                                ${commentsHtml}
                                ${actionsHtml}
                            </div>
                        `;
    submissionsList.innerHTML += submissionHtml;
});
}

    modal.classList.remove('hidden');
} catch (err) {
    console.error('Error in showEvaluationModal:', err);
    alert('An error occurred while opening the modal.');
}
}

    // Event delegation for userFile clicks
    document.addEventListener('click', (e) => {
    const userFile = e.target.closest('.userFile');
    const viewBtn = e.target.closest('.view-details-btn');
    if (userFile || viewBtn) {
    const target = userFile || viewBtn.closest('.userFile');
    if (!target) {
    console.error('Target element not found');
    return;
}
    const studentId = target.dataset.studentId;
    const fullName = target.dataset.fullName;
    let submissions = [];
    try {
    if (target.dataset.submissions) {
    submissions = JSON.parse(target.dataset.submissions);
}
} catch (err) {
    console.error('JSON parse error for data-submissions:', err);
    alert('Error loading submissions. Please try again.');
    return;
}
    showEvaluationModal(studentId, fullName, submissions);
}
});

    // Close modal function
    function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
    modal.classList.add('hidden');
    const preview = modal.querySelector('#fileViewPreview');
    if (preview) {
    const iframes = preview.getElementsByTagName('iframe');
    const images = preview.getElementsByTagName('img');
    for (let iframe of iframes) {
    URL.revokeObjectURL(iframe.src);
}
    for (let img of images) {
    URL.revokeObjectURL(img.src);
}
    preview.innerHTML = '';
}
}
}

    // View submission
    function viewSubmission(submissionId, fileName) {
    if (!submissionId || isNaN(submissionId)) {
    console.error('Invalid submission ID:', submissionId);
    alert('Invalid submission ID. Please try again.');
    return;
}

    const modal = document.getElementById('fileViewModal');
    const preview = document.getElementById('fileViewPreview');
    const downloadLink = document.getElementById('fileDownloadLink');

    downloadLink.href = `../controller/download_submission.php?id=${encodeURIComponent(submissionId)}&download=true`;
    downloadLink.classList.remove('hidden');

    preview.innerHTML = `
                <div class="flex items-center justify-center min-h-full py-6">
                    <div class="text-center">
                        <svg class="animate-spin h-6 w-6 text-blue-500 mx-auto" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-2 text-gray-500 text-sm">Loading file...</p>
                    </div>
                </div>
            `;
    modal.classList.remove('hidden');

    fetch(`../controller/download_submission.php?id=${encodeURIComponent(submissionId)}`)
    .then(response => {
    if (!response.ok) throw new Error(`Failed to load file: ${response.statusText}`);
    return response.blob();
})
    .then(blob => {
    const url = URL.createObjectURL(blob);
    const contentType = blob.type;

    if (contentType === 'application/pdf') {
    preview.innerHTML = `<iframe src="${url}#zoom=auto" style="width:100%; height:100%; max-height:100%;" frameborder="0" title="File Preview" aria-label="PDF file preview"></iframe>`;
} else if (contentType.startsWith('image/')) {
    preview.innerHTML = `<img src="${url}" alt="Uploaded File" class="w-full h-full object-contain" aria-label="Image preview" />`;
} else {
    preview.innerHTML = `<div class="text-center py-6 text-red-500 text-sm">Preview not available for this file type. Please download the original file.</div>`;
    downloadLink.classList.remove('hidden');
}
})
    .catch(error => {
    console.error('Fetch error:', error);
    preview.innerHTML = `<div class="text-center py-6 text-red-500 text-sm">Error loading file: ${error.message}. Please download the original file.</div>`;
    downloadLink.classList.remove('hidden');
});
}

    // Approve submission
// Approve submission
// Approve submission
function showApproveModal(submissionId) {
    if (!submissionId || isNaN(submissionId)) {
        console.error('Invalid submission ID:', submissionId);
        alert('Invalid submission ID. Please try again.');
        return;
    }

    const modal = document.getElementById('approveModal');
    const confirmButton = document.getElementById('confirmApprove');
    const commentsField = document.getElementById('approveComments');

    if (!modal || !confirmButton || !commentsField) {
        console.error('Approve modal elements missing');
        alert('Error: Approve modal components are missing.');
        return;
    }

    commentsField.value = '';

    const newConfirmButton = confirmButton.cloneNode(true);
    confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);

    newConfirmButton.addEventListener('click', () => {
        const comments = commentsField.value.trim();
        if (comments.length > 1000) {
            alert('Comments are too long. Please keep them under 1000 characters.');
            return;
        }

        // Get CSRF token from hidden input
        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';

        newConfirmButton.disabled = true;
        newConfirmButton.textContent = 'Processing...';

        fetch('../controller/approve_submission.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${encodeURIComponent(submissionId)}&comments=${encodeURIComponent(comments)}&csrf_token=${encodeURIComponent(csrfToken)}`,
            credentials: 'same-origin' // Ensure session cookies are sent
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Submission approved successfully!');
                    closeModal('approveModal');
                    location.reload();
                } else {
                    alert('Error approving submission: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error approving submission:', error);
                alert('An error occurred while approving the submission: ' + error.message);
            })
            .finally(() => {
                newConfirmButton.disabled = false;
                newConfirmButton.textContent = 'Confirm';
            });
    });

    modal.classList.remove('hidden');
}

    // Reject submission
    function showRejectModal(submissionId) {
    const modal = document.getElementById('rejectModal');
    const confirmButton = document.getElementById('confirmReject');
    const commentsField = document.getElementById('rejectComments');
    commentsField.value = '';

    const newConfirmButton = confirmButton.cloneNode(true);
    confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
    newConfirmButton.addEventListener('click', () => {
    const comments = commentsField.value.trim();
    fetch('../controller/reject_submission.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id=${encodeURIComponent(submissionId)}&comments=${encodeURIComponent(comments)}`
})
    .then(response => response.json())
    .then(data => {
    if (data.success) {
    alert('Submission rejected successfully!');
    location.reload();
} else {
    alert('Error rejecting submission: ' + (data.message || 'Unknown error'));
}
})
    .catch(error => {
    console.error('Error:', error);
    alert('An error occurred while rejecting the submission.');
});
    closeModal('rejectModal');
});
    modal.classList.remove('hidden');
}

