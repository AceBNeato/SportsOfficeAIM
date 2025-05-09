<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - USeP OSAS-Sports Unit</title>
    <link rel="stylesheet" href="../public/CSS/signup.css">
    <link rel="icon" href="../public/image/Usep.png" sizes="any">
    <script src="../public/JAVASCRIPT/validateStudentAthlete.js" defer></script>
    <link href="https://cdn.jsdelivr.net/npm/boxicons/css/boxicons.min.css" rel="stylesheet" />
    <style>
        .drop-zone {
            border: 2px dashed #007bff;
            border-radius: 5px;
            padding: 20px;
            text-align: center;
            background-color: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .drop-zone.dragover {
            border-color: #28a745;
            background-color: #e9ecef;
        }
        .drop-zone p {
            margin: 0;
            color: #6c757d;
            font-size: 16px;
        }
        .drop-zone .file-name {
            margin-top: 10px;
            color: #007bff;
            font-weight: bold;
        }
        .drop-zone input[type="file"] {
            display: none;
        }
        .drop-zone:hover {
            border-color: #0056b3;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        .modal-content p {
            margin: 0 0 20px 0;
            font-size: 16px;
        }
        .close-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .close-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<div class="container">
    <nav class="top-bar">
        <div class="top-bar-content">
            <div class="logo-container">
                <img src="../public/image/SportOffice.png" alt="Sports Office Logo" class="logo">
                <img src="../public/image/Usep.png" alt="USeP Logo" class="logo">
            </div>
            <div class="title-container">
                <h2><span class="highlight">Join</span> <span class="highlight">USeP Sports</span></h2>
                <h1>USeP OSAS-Sports Unit</h1>
            </div>
        </div>
    </nav>

    <div class="center-panel">
        <div class="login-box">
            <h1>CREATE ACCOUNT</h1>
            <form method="POST" action="../controller/approveUsers.php" enctype="multipart/form-data" onsubmit="return validateStudentAthleteForm(event)">
                <input type="text" name="student_id" placeholder="Student ID" required autocomplete="off">
                <input type="text" name="full_name" placeholder="Full Name" required autocomplete="name">
                <input type="email" name="email" placeholder="Email" required autocomplete="email">
                <select name="status" required>
                    <option value="" disabled selected>Select Status</option>
                    <option value="undergraduate">Undergraduate</option>
                    <option value="alumni">Alumni</option>
                </select>
                <h2><span class="highlight">Upload Verification Documents</span></h2>

                <div class="drop-zone" id="dropZone">
                    <p>Drag and drop your file here or click to select</p>
                    <span class="file-name" id="fileName"></span>
                    <input type="file" name="document" id="document" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>

                <input type="hidden" name="page" value="signup">
                <button type="submit">SIGN UP</button>
            </form>
            <p class="signup-link">Already have an account? <a href="loginView.php">Log In</a></p>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="modal">
    <div class="modal-content">
        <p>Your account has been submitted successfully! Please wait for admin approval.</p>
        <button class="close-btn" onclick="closeModal('successModal')">OK</button>
    </div>
</div>

<!-- Error Modal -->
<div id="errorModal" class="modal">
    <div class="modal-content">
        <p id="errorMessage"></p>
        <button class="close-btn" onclick="closeModal('errorModal')">OK</button>
    </div>
</div>

<script>
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('document');
    const fileNameDisplay = document.getElementById('fileName');

    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            fileNameDisplay.textContent = files[0].name;
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            fileNameDisplay.textContent = fileInput.files[0].name;
        } else {
            fileNameDisplay.textContent = '';
        }
    });

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        // Clear query parameters from URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Handle modal display based on query parameters
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const message = urlParams.get('message');

        if (status === 'success') {
            document.getElementById('successModal').style.display = 'flex';
        } else if (status === 'error' && message) {
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('errorModal').style.display = 'flex';
        }
    };
</script>

</body>
</html>