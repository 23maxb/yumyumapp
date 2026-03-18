<?php
// Validate login credentials and start an authenticated session.
declare(strict_types=1);
require_once __DIR__ . '/lib.php';
header('Content-Type: application/json');

// This endpoint only accepts JSON POST requests.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Read and normalize incoming credentials.
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$email = trim((string)($input['email'] ?? ''));
$password = (string)($input['password'] ?? '');

// Look up the user by email first, then verify the password hash.
$stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();
if (!$user || !password_verify($password, (string)$user['password_hash'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit;
}

// Persist login state in the session and return a redirect target.
$_SESSION['user_id'] = (int)$user['id'];
echo json_encode(['success' => true, 'redirect' => '/script/index.php?page=home']);
