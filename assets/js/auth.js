/**
 * Auth JavaScript Helpers & Demo Autofill
 */

function fillDemoLogin(role) {
  const usernameInput = document.getElementById('username');
  const passwordInput = document.getElementById('password');
  
  if (!usernameInput || !passwordInput) return;

  if (role === 'admin') {
    usernameInput.value = 'admin';
    passwordInput.value = 'password123';
  } else if (role === 'librarian') {
    usernameInput.value = 'librarian';
    passwordInput.value = 'password123';
  } else if (role === 'student') {
    usernameInput.value = 'student';
    passwordInput.value = 'password123';
  }

  // Trigger input events so floating labels work nicely
  usernameInput.dispatchEvent(new Event('input', { bubbles: true }));
  passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
}

function togglePasswordVisibility(inputId, iconId) {
  const input = document.getElementById(inputId);
  const icon = document.getElementById(iconId);
  if (!input || !icon) return;

  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}
