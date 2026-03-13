<?php
// Returns recipe data
declare(strict_types=1);
require_once __DIR__ . '/lib.php';
require_login();
header('Content-Type: application/json');
$recipes = matched_recipes_for_user((int)current_user()['id']);
$data = array_map(function(array $recipe): array {
    return [
        'id' => (int)$recipe['id'],
        'title' => $recipe['title'],
        'summary' => $recipe['summary'],
        'image_url' => $recipe['image_url'],
        'ready_minutes' => (int)$recipe['ready_minutes'],
        'servings' => (int)$recipe['servings'],
        'match_count' => (int)($recipe['match_count'] ?? 0),
    ];
}, $recipes);
echo json_encode(['success' => true, 'recipes' => $data]);
