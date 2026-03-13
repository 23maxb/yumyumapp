document.addEventListener('DOMContentLoaded', () => {
  const list = document.querySelector('#fridge-list');
  const form = document.querySelector('#fridge-form');
  const message = document.querySelector('#fridge-message');

  async function loadItems() {
    const response = await fetch('/script/api-fridge.php');
    const data = await response.json();
    const items = data.items || [];
    list.innerHTML = items.map((item) => `
      <li class="fridge-item">
        <div>
          <strong>${item.item_name}</strong>
          <span>${item.quantity || 'No quantity'}</span>
        </div>
        <button data-id="${item.id}" class="danger-button">Remove</button>
      </li>
    `).join('') || '<li class="empty-state">No ingredients yet.</li>';
  }

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const payload = Object.fromEntries(new FormData(form).entries());
    const response = await fetch('/script/api-fridge.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await response.json();
    message.textContent = data.success ? 'Ingredient added.' : (data.message || 'Failed to add item.');
    if (data.success) {
      form.reset();
      loadItems();
    }
  });

  list?.addEventListener('click', async (event) => {
    const button = event.target.closest('button[data-id]');
    if (!button) return;
    await fetch('/script/api-fridge.php', {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: Number(button.dataset.id) }),
    });
    loadItems();
  });

  loadItems();
});
