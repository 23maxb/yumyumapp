document.addEventListener('DOMContentLoaded', async () => {
  const wrap = document.querySelector('#calendar-grid');
  const status = document.querySelector('#calendar-status');
  if (!wrap) return;
  const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
  const mealTypes = ['breakfast', 'lunch', 'dinner'];
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
    return ['<option value="">No recipe selected</option>']
      .concat(recipes.map((recipe) => `<option value="${recipe.id}" ${Number(recipe.id) === Number(selectedId || 0) ? 'selected' : ''}>${recipe.title}</option>`))
      .join('');
  }

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
