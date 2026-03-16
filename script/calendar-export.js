document.addEventListener('DOMContentLoaded', () => {
  const printButton = document.querySelector('#print-plan');
  printButton?.addEventListener('click', () => {
    window.print();
  });
});