document.addEventListener('DOMContentLoaded', () => {
  // Keep printing opt-in so users can review before sending to printer.
  const printButton = document.querySelector('#print-plan');
  printButton?.addEventListener('click', () => {
    window.print();
  });
});