<?php
// Creates new account
declare(strict_types=1);
require_once __DIR__ . '/lib.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$name = trim((string)($input['name'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$password = (string)($input['password'] ?? '');
if ($name === '' || $email === '' || $password === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}
try {
    $stmt = db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    $_SESSION['user_id'] = (int)db()->lastInsertId();
    echo json_encode(['success' => true, 'redirect' => '/script/index.php?page=home']);
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
}
