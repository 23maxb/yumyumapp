<?php
// Router of the website. It includes the requested page and displays it.   
declare(strict_types=1);
require_once __DIR__ . '/lib.php';

$page = $_GET['page'] ?? (current_user() ? 'home' : 'login');
$allowed = ['home', 'recipes', 'recipe', 'fridge', 'calendar', 'calendar-export', 'login', 'register'];
if (!in_array($page, $allowed, true)) {
    http_response_code(404);
    echo 'Page not found.';
    exit;
}
require __DIR__ . '/' . $page . '.php';
