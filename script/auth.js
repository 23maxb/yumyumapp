async function sendAuthForm(form, endpoint) {
  // FormData -> plain object keeps payload construction concise.
  const message = form.querySelector('[data-message]');
  const payload = Object.fromEntries(new FormData(form).entries());
  message.textContent = 'Working...';
  try {
    // Both login and register use the same JSON contract.
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
    // Network errors do not always include a JSON response body.
    message.textContent = 'Network error.';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const loginForm = document.querySelector('#login-form');
  const registerForm = document.querySelector('#register-form');
  if (loginForm) {
    loginForm.addEventListener('submit', (event) => {
      event.preventDefault();
      // Reuse the same helper with the login endpoint.
      sendAuthForm(loginForm, '/script/api-login.php');
    });
  }
  if (registerForm) {
    registerForm.addEventListener('submit', (event) => {
      event.preventDefault();
      // Reuse the same helper with the registration endpoint.
      sendAuthForm(registerForm, '/script/api-register.php');
    });
  }
});
