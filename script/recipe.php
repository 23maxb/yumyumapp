<?php
declare(strict_types=1);
require_login();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$recipe = $id > 0 ? recipe_find($id) : null;
if (!$recipe) {
    set_flash('Recipe not found.', 'error');
    header('Location: /script/index.php?page=recipes');
    exit;
}
$ingredients = '';
foreach ($recipe['ingredients'] as $ingredient) {
    $ingredients .= '<li>' . h((string)$ingredient) . '</li>';
}
$steps = '';
foreach (preg_split('/
||
/', trim((string)$recipe['instructions'])) as $step) {
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
    'recipe_ingredients' => $ingredients,
    'recipe_steps' => $steps,
]);
