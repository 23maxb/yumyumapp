document.addEventListener('DOMContentLoaded', async () => {
  const grid = document.querySelector('#recipe-grid');
  const search = document.querySelector('#recipe-search');
  const form = document.querySelector('#recipe-create-form');
  const message = document.querySelector('#recipe-form-message');
  if (!grid) return;

  // Keep a local cache so filtering and re-rendering are instant.
  let recipes = [];

  const setMessage = (text = '', type = '') => {
    if (!message) return;
    message.textContent = text;
    message.className = `form-message${type ? ` is-${type}` : ''}`;
  };

  const render = (term = '') => {
    const q = term.trim().toLowerCase();

    // Search by title, summary, or category.
    const filtered = recipes.filter((recipe) => {
      return (
        recipe.title.toLowerCase().includes(q) ||
        recipe.summary.toLowerCase().includes(q) ||
        (recipe.category || '').toLowerCase().includes(q)
      );
    });

    grid.innerHTML =
      filtered.map((recipe) => `
        <article class="recipe-card">
          <img src="${recipe.image_url}" alt="${recipe.title}">
          <div class="recipe-card-body">
            <div class="recipe-meta">
              <span>${recipe.ready_minutes} min</span>
              <span>${recipe.servings} serving(s)</span>
              <span>${recipe.category || 'Custom'}</span>
              <span>${recipe.match_count} ingredient match(es)</span>
            </div>
            <h3>${recipe.title}</h3>
            <p>${recipe.summary}</p>
            <a class="button-link" href="/script/index.php?page=recipe&id=${recipe.id}">Open recipe</a>
          </div>
        </article>
      `).join('') || '<p class="empty-state">No recipes matched your search.</p>';
  };

  // Load all recipes once on page load.
  const response = await fetch('/script/api-recipes.php?mode=all');
  const data = await response.json();

  recipes = data.recipes || [];
  render();

  // Live search by rerendering from local state.
  search?.addEventListener('input', () => render(search.value));

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const submitButton = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());

    if (submitButton) {
      submitButton.disabled = true;
    }
    setMessage('Saving recipe...');

    try {
      // Server performs validation and returns the created recipe payload.
      const response = await fetch('/script/api-recipes.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Recipe could not be saved.');
      }

      // Prepend new recipe so users see immediate confirmation.
      recipes = [data.recipe, ...recipes];
      form.reset();
      setMessage('Recipe added successfully.', 'success');
      render(search?.value || '');
    } catch (error) {
      setMessage(error.message || 'Recipe could not be saved.', 'error');
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
      }
    }
  });
});