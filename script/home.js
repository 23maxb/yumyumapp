document.addEventListener('DOMContentLoaded', () => {
  const stamp = document.querySelector('[data-now]');
  if (stamp) {
    stamp.textContent = new Date().toLocaleString();
  }
});
