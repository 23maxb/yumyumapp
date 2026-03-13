async function sendAuthForm(form, endpoint) {
  const message = form.querySelector('[data-message]');
  const payload = Object.fromEntries(new FormData(form).entries());
  message.textContent = 'Working...';
  try {
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await response.json();
    if (!response.ok || !data.success) {
      message.textContent = data.message || 'Something went wrong.';
      return;
    }
    window.location.href = data.redirect;
  } catch (error) {
    message.textContent = 'Network error.';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const loginForm = document.querySelector('#login-form');
  const registerForm = document.querySelector('#register-form');
  if (loginForm) {
    loginForm.addEventListener('submit', (event) => {
      event.preventDefault();
      sendAuthForm(loginForm, '/script/api-login.php');
    });
  }
  if (registerForm) {
    registerForm.addEventListener('submit', (event) => {
      event.preventDefault();
      sendAuthForm(registerForm, '/script/api-register.php');
    });
  }
});
