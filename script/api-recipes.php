<?php
declare(strict_types=1);

require_once __DIR__ . '/lib.php';
require_login();

header('Content-Type: application/json');

// Keep response fields consistent between list and create endpoints.
function recipe_api_payload(array $recipe): array {
    return [
        'id' => (int)$recipe['id'],
        'title' => $recipe['title'],
        'summary' => $recipe['summary'],
        'image_url' => $recipe['image_url'],
        'ready_minutes' => (int)$recipe['ready_minutes'],
        'servings' => (int)$recipe['servings'],
        'match_count' => (int)($recipe['match_count'] ?? 0),
        'category' => $recipe['category'] ?? 'Custom',
        'created_by_user_id' => isset($recipe['created_by_user_id']) ? (int)$recipe['created_by_user_id'] : null,
    ];
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    // Accept a JSON recipe draft from the create form.
    $payload = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid recipe payload.']);
        exit;
    }

    try {
        // Validation and persistence live in lib.php.
        $recipe = create_recipe($payload, (int)current_user()['id']);
        echo json_encode([
            'success' => true,
            'message' => 'Recipe added successfully.',
            'recipe' => recipe_api_payload($recipe),
        ]);
    } catch (InvalidArgumentException $exception) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
    } catch (Throwable $exception) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Recipe could not be saved.']);
    }
    exit;
}

// "all" returns every recipe with match counts, otherwise return matched-only.
$mode = $_GET['mode'] ?? 'matched';

if ($mode === 'all') {
    $recipes = recipes_all_with_matches((int)current_user()['id']);
} else {
    $recipes = matched_recipes_for_user((int)current_user()['id']);
}

$data = array_map('recipe_api_payload', $recipes);

// Always return the same envelope shape for client simplicity.
echo json_encode([
    'success' => true,
    'recipes' => $data
]);