<?php
// Handles meal plan operations
declare(strict_types=1);
require_once __DIR__ . '/lib.php';
require_login();
header('Content-Type: application/json');
$userId = (int)current_user()['id'];
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['success' => true, 'plan' => meal_plan_for_user($userId), 'recipes' => recipes_all()]);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $day = trim((string)($input['day_name'] ?? ''));
    $recipeId = (int)($input['recipe_id'] ?? 0);
    if ($day === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Day is required']);
        exit;
    }
    $stmt = db()->prepare('INSERT INTO meal_plan (user_id, day_name, recipe_id) VALUES (?, ?, ?) ON CONFLICT(user_id, day_name) DO UPDATE SET recipe_id = excluded.recipe_id');
    $stmt->execute([$userId, $day, $recipeId ?: null]);
    echo json_encode(['success' => true]);
    exit;
}
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
