<?php
// Route incoming page requests to the matching script entry point.
declare(strict_types=1);
require_once __DIR__ . '/lib.php';

// Default to home for logged-in users and login for guests.
$page = $_GET['page'] ?? (current_user() ? 'home' : 'login');
$allowed = ['home', 'recipes', 'recipe', 'fridge', 'calendar', 'calendar-export', 'login', 'register'];

// Only allow known page slugs to avoid loading arbitrary files.
if (!in_array($page, $allowed, true)) {
    http_response_code(404);
    echo 'Page not found.';
    exit;
}

// Hand off rendering to the page-specific script.
require __DIR__ . '/' . $page . '.php';
