document.addEventListener('DOMContentLoaded', async () => {
  const grid = document.querySelector('#recipe-grid');
  const search = document.querySelector('#recipe-search');
  if (!grid) return;

  let recipes = [];

  const render = (term = '') => {
    const q = term.trim().toLowerCase();
    const filtered = recipes.filter((recipe) => {
      return recipe.title.toLowerCase().includes(q) || recipe.summary.toLowerCase().includes(q);
    });

    grid.innerHTML = filtered.map((recipe) => `
      <article class="recipe-card">
        <img src="${recipe.image_url}" alt="${recipe.title}">
        <div class="recipe-card-body">
          <div class="recipe-meta"><span>${recipe.ready_minutes} min</span><span>${recipe.match_count} ingredient match(es)</span></div>
          <h3>${recipe.title}</h3>
          <p>${recipe.summary}</p>
          <a class="button-link" href="/script/index.php?page=recipe&id=${recipe.id}">Open recipe</a>
        </div>
      </article>
    `).join('') || '<p class="empty-state">No recipes matched your search.</p>';
  };

  const response = await fetch('/script/api-recipes.php');
  const data = await response.json();
  recipes = data.recipes || [];
  render();

  search?.addEventListener('input', () => render(search.value));
});
