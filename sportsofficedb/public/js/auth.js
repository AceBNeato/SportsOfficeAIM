// Toggle Password Visibility
  function togglePassword() {
    let pass = document.getElementById('password');
    pass.type = (pass.type === 'password') ? 'text' : 'password';
}
// Light/Dark Mode Toggle
function toggleMode() {
    let body = document.body;
    let themeText = document.getElementById("osas-unit-text"); // Select only the specific <p>
    let btn = document.getElementById('theme-toggle');
    let loginform = document.querySelectorAll('.login-form, .login-title, .login-subtitle, .show-password, .forgot-password');


    // Toggle light mode classes
    body.classList.toggle('light-mode');
    themeText.classList.toggle('light-mode');
    loginform.forEach(el => el.classList.toggle('light-mode'));

    let mode = body.classList.contains('light-mode') ? 'light' : 'dark';

    // Save theme preference
    localStorage.setItem('theme', mode);

    // Change button text
    btn.textContent = mode === 'light' ? '☀️ Light Mode' : '🌙 Dark Mode';
}

// Apply saved theme on page load
window.onload = function () {
    let savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'light') {
        document.body.classList.add('light-mode');
        document.getElementById('theme-toggle').textContent = '☀️ Light Mode';
        document.getElementById("osas-unit-text").classList.add('light-mode');
        document.querySelectorAll(".login-form, .login-title, .login-subtitle, .id_number, .password, .show-password, .forgot-password").forEach(el => el.classList.add('light-mode'));
    }
};

// Add event listener to the button
document.getElementById('theme-toggle').addEventListener('click', toggleMode);
