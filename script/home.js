document.addEventListener('DOMContentLoaded', () => {
  const stamp = document.querySelector('[data-now]');
  if (stamp) {
    // Render client-local date/time so the dashboard feels current.
    stamp.textContent = new Date().toLocaleString();
  }
});
