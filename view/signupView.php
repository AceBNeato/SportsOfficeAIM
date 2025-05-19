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
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <select name="sport">
                    <option value="" disabled selected>Select Sport (Optional)</option>
                    <option value="Athletics">Athletics</option>
                    <option value="Badminton">Badminton</option>
                    <option value="Basketball">Basketball</option>
                    <option value="Chess">Chess</option>
                    <option value="Football">Football</option>
                    <option value="Sepak Takraw">Sepak Takraw</option>
                    <option value="Swimming">Swimming</option>
                    <option value="Table Tennis">Table Tennis</option>
                    <option value="Taekwondo">Taekwondo</option>
                    <option value="Tennis">Tennis</option>
                    <option value="Volleyball">Volleyball</option>
                </select>
                <select name="campus">
                    <option value="" disabled selected>Select Campus (Optional)</option>
                    <option value="Tagum">Tagum</option>
                    <option value="Mabini">Mabini</option>
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

    // Handle SweetAlert2 display based on query parameters
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const message = urlParams.get('message');

        if (status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Your account has been submitted successfully! Please wait for admin approval.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#007bff'
            }).then(() => {
                // Clear query parameters from URL
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        } else if (status === 'error' && message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonText: 'OK',
                confirmButtonColor: '#007bff'
            }).then(() => {
                // Clear query parameters from URL
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }
    };
</script>

</body>
</html>