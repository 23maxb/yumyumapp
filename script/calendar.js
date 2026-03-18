document.addEventListener('DOMContentLoaded', async () => {
  const wrap = document.querySelector('#calendar-grid');
  const status = document.querySelector('#calendar-status');
  if (!wrap) return;

  // Canonical day and meal ordering for rendering and payloads.
  const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  const mealTypes = ['breakfast', 'lunch', 'dinner'];

  // Bootstrap both the saved plan and selectable recipe options.
  const response = await fetch('/script/api-calendar.php');
  const data = await response.json();
  const recipes = data.recipes || [];
  const plan = data.plan || {};

  function setStatus(message = '', type = '') {
    if (!status) return;
    status.textContent = message;
    status.className = `calendar-status${type ? ` is-${type}` : ''}`;
  }

  function options(selectedId) {
    // Build dropdown options once per slot with selected state preserved.
    return ['<option value="">No recipe selected</option>']
      .concat(recipes.map((recipe) => `<option value="${recipe.id}" ${Number(recipe.id) === Number(selectedId || 0) ? 'selected' : ''}>${recipe.title}</option>`))
      .join('');
  }

  // Render one card per day and one select per meal type.
  wrap.innerHTML = days.map((day) => `
    <article class="day-card">
      <h3>${day}</h3>
      ${mealTypes.map((mealType) => `
        <div class="meal-slot">
          <label for="${day}-${mealType}">${mealType}</label>
          <select id="${day}-${mealType}" data-day="${day}" data-meal-type="${mealType}">${options(plan[day]?.[mealType]?.recipe_id || '')}</select>
        </div>
      `).join('')}
    </article>
  `).join('');

  wrap.addEventListener('change', async (event) => {
    const select = event.target.closest('select[data-day]');
    if (!select) return;
    setStatus('Saving plan...');
    try {
      // Save only the changed slot; server upserts by day + meal type.
      const response = await fetch('/script/api-calendar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          day_name: select.dataset.day,
          meal_type: select.dataset.mealType,
          recipe_id: Number(select.value || 0),
        }),
      });
      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Could not save meal plan.');
      }
      setStatus('Meal plan saved.');
    } catch (error) {
      setStatus(error.message || 'Could not save meal plan.', 'error');
    }
  });
});
