<?php
declare(strict_types=1);
require_once __DIR__ . '/lib.php';
require_login();

// Build export payload once, then render it into print-friendly HTML chunks.
$user = current_user();
$export = meal_plan_export_for_user((int)$user['id']);

$plannedDays = '';
foreach ($export['days'] as $day) {
    $mealsMarkup = '';

    foreach (MEAL_TYPES as $mealType) {
        $recipe = $day['meals'][$mealType] ?? null;

        if (!$recipe) {
            // Keep empty slots visible so the weekly structure stays intact in exports.
            $mealsMarkup .= '<div class="export-meal"><h3>' . h(ucfirst($mealType)) . '</h3><p class="empty-state">No recipe planned.</p></div>';
            continue;
        }

        $ingredientsMarkup = '';
        foreach ($recipe['ingredients'] as $ingredient) {
            $ingredientsMarkup .= '<li>' . h((string)$ingredient) . '</li>';
        }

        $mealsMarkup .= '<div class="export-meal">'
            . '<h3>' . h(ucfirst($mealType)) . '</h3>'
            . '<p class="export-recipe-title">' . h((string)$recipe['title']) . '</p>'
            . '<p class="export-recipe-meta">' . (int)$recipe['ready_minutes'] . ' min | ' . (int)$recipe['servings'] . ' serving(s)</p>'
            . '<ul class="export-ingredients-list">' . $ingredientsMarkup . '</ul>'
            . '</div>';
    }

    $plannedDays .= '<article class="export-day-card">'
        . '<h2>' . h((string)$day['day_name']) . '</h2>'
        . $mealsMarkup
        . '</article>';
}

$ingredientList = '';
foreach ($export['ingredients'] as $ingredient) {
    $ingredientList .= '<li>' . h((string)$ingredient['name']) . '<span>Used in ' . (int)$ingredient['count'] . ' meal(s)</span></li>';
}

if ($ingredientList === '') {
    $ingredientList = '<li class="empty-state">No planned ingredients yet.</li>';
}

render_template('calendar-export.html', [
    'page_title' => 'Meal Plan Export',
    'page_css' => asset_url('styles/calendar-export.css'),
    'page_js' => asset_url('script/calendar-export.js'),
    'export_user_name' => h((string)$user['name']),
    'generated_at' => h(date('F j, Y g:i A')),
    'export_days' => $plannedDays,
    'export_ingredients' => $ingredientList,
]);