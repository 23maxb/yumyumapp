<?php
declare(strict_types=1);
require_login();

// Recipe id comes from query string and is validated as an integer.
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$recipe = $id > 0 ? recipe_find($id) : null;
if (!$recipe) {
    // Redirect back to the browser page with a flash message if id is invalid.
    set_flash('Recipe not found.', 'error');
    header('Location: /script/index.php?page=recipes');
    exit;
}
$user = current_user();
$fridgeItems = fridge_items_for_user((int)$user['id']);
$fridgeNames = array_map(static fn(array $item): string => strtolower(trim((string)$item['item_name'])), $fridgeItems);

$matchedIngredients = '';
$missingIngredients = '';

// Partition ingredients into "have" vs "need" based on the fridge list.
foreach ($recipe['ingredients'] as $ingredient) {
    $ingredientName = trim((string)$ingredient);
    $itemMarkup = '<li>' . h($ingredientName) . '</li>';

    if (in_array(strtolower($ingredientName), $fridgeNames, true)) {
        $matchedIngredients .= $itemMarkup;
    } else {
        $missingIngredients .= $itemMarkup;
    }
}

$matchedIngredients = $matchedIngredients !== ''
    ? $matchedIngredients
    : '<li class="empty-state">No matching ingredients in your fridge yet.</li>';

$missingIngredients = $missingIngredients !== ''
    ? $missingIngredients
    : '<li class="empty-state">You already have everything for this recipe.</li>';

$steps = '';
// Convert multi-line instructions into list items for template rendering.
foreach (preg_split('/\r\n|\r|\n/', trim((string)$recipe['instructions'])) as $step) {
    if (trim($step) !== '') {
        $steps .= '<li>' . h(trim($step)) . '</li>';
    }
}
render_template('recipe.html', [
    'page_title' => h((string)$recipe['title']),
    'page_css' => asset_url('styles/recipe.css'),
    'recipe_title' => h((string)$recipe['title']),
    'recipe_image' => h((string)$recipe['image_url']),
    'recipe_summary' => h((string)$recipe['summary']),
    'recipe_ready' => (string)$recipe['ready_minutes'],
    'recipe_servings' => (string)$recipe['servings'],
    'recipe_matched_ingredients' => $matchedIngredients,
    'recipe_missing_ingredients' => $missingIngredients,
    'recipe_steps' => $steps,
]);
