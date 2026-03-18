<?php
declare(strict_types=1);

require_login();

// Pull dashboard metrics from fridge state and ingredient matching.
$user = current_user();
$items = fridge_items_for_user((int)$user['id']);
$matches = matched_recipes_for_user((int)$user['id']);
$top = $matches[0]['title'] ?? 'Nothing yet';

$cards = '';
$cards .= '<div class="stat-card"><span>Fridge Items</span><strong>' . count($items) . '</strong></div>';
$cards .= '<div class="stat-card"><span>Recipe Matches</span><strong>' . count($matches) . '</strong></div>';
$cards .= '<div class="stat-card"><span>Top Match</span><strong>' . h($top) . '</strong></div>';

$matchedRecipeCards = '';

if (empty($matches)) {
  // Friendly empty state when no recipes share ingredients with the fridge.
    $matchedRecipeCards = '<p class="empty-state">No recipe matches yet. Add more ingredients to your fridge.</p>';
} else {
  // Pre-render cards server-side so the home dashboard is instantly usable.
    foreach ($matches as $recipe) {
        $matchedRecipeCards .= '
            <article class="recipe-card">
              <img src="' . h($recipe['image_url']) . '" alt="' . h($recipe['title']) . '">
              <div class="recipe-card-body">
                <div class="recipe-meta">
                  <span>' . (int)$recipe['ready_minutes'] . ' min</span>
                  <span>' . (int)$recipe['match_count'] . ' ingredient match(es)</span>
                </div>
                <h3>' . h($recipe['title']) . '</h3>
                <p>' . h($recipe['summary']) . '</p>
                <a class="button-link" href="/script/index.php?page=recipe&id=' . (int)$recipe['id'] . '">Open recipe</a>
              </div>
            </article>
        ';
    }
}

render_template('home.html', [
    'page_title' => 'Home',
    'page_css' => asset_url('styles/home.css'),
    'page_js' => asset_url('script/home.js'),
    'stats_cards' => $cards,
    'matched_recipe_cards' => $matchedRecipeCards,
]);