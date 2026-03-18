<?php
declare(strict_types=1);
require_once __DIR__ . '/lib.php';

header('Content-Type: application/json');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            throw new Exception('Invalid JSON payload.');
        }
        $recipe = create_recipe($input, (int)$user['id']);
        $count = 0;
        foreach ($recipe['ingredients'] as $ingredient) {
            if (in_array(strtolower(trim((string)$ingredient)), array_map(fn($r) => strtolower(trim((string)$r['item_name'])), fridge_items_for_user((int)$user['id'])), true)) {
                $count++;
            }
        }
        $recipe['match_count'] = $count;
        $recipe['is_external'] = false;

        echo json_encode(['success' => true, 'recipe' => $recipe]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
$recipes = recipes_all_with_matches((int)$user['id']);
echo json_encode(['recipes' => $recipes]);