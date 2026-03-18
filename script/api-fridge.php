<?php
// Handle CRUD-style fridge operations for the logged-in user.
declare(strict_types=1);
require_once __DIR__ . '/lib.php';
require_login();
header('Content-Type: application/json');
$userId = (int)current_user()['id'];
$method = $_SERVER['REQUEST_METHOD'];

// Return all fridge items sorted by item name.
if ($method === 'GET') {
    echo json_encode(['success' => true, 'items' => fridge_items_for_user($userId)]);
    exit;
}

// For write operations, read JSON payload once.
$input = json_decode(file_get_contents('php://input'), true) ?: [];
if ($method === 'POST') {
    $name = trim((string)($input['item_name'] ?? ''));
    $quantity = trim((string)($input['quantity'] ?? ''));
    if ($name === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Ingredient name is required']);
        exit;
    }

    // Quantity is optional and can be free-form (for example: "2 cups").
    $stmt = db()->prepare('INSERT INTO fridge_items (user_id, item_name, quantity) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $name, $quantity]);
    echo json_encode(['success' => true]);
    exit;
}

// Delete only within the current user's scope to avoid cross-user deletes.
if ($method === 'DELETE') {
    $id = (int)($input['id'] ?? 0);
    $stmt = db()->prepare('DELETE FROM fridge_items WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    echo json_encode(['success' => true]);
    exit;
}
http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
