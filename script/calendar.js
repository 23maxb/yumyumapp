document.addEventListener('DOMContentLoaded', async () => {
  const wrap = document.querySelector('#calendar-grid');
  if (!wrap) return;
  const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  const response = await fetch('/script/api-calendar.php');
  const data = await response.json();
  const recipes = data.recipes || [];
  const plan = data.plan || {};

  function options(selected) {
    return ['<option value="">No recipe selected</option>']
      .concat(recipes.map((recipe) => `<option value="${recipe.id}" ${String(recipe.title) === String(selected) ? 'selected' : ''}>${recipe.title}</option>`))
      .join('');
  }

  wrap.innerHTML = days.map((day) => `
    <article class="day-card">
      <h3>${day}</h3>
      <select data-day="${day}">${options(plan[day] || '')}</select>
    </article>
  `).join('');

  wrap.addEventListener('change', async (event) => {
    const select = event.target.closest('select[data-day]');
    if (!select) return;
    await fetch('/script/api-calendar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ day_name: select.dataset.day, recipe_id: Number(select.value || 0) }),
    });
  });
});
