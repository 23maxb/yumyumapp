<?php
// Shared utility file - Contains database connections and helper functions used across multiple scripts.
declare(strict_types=1);
session_start();

const ROOT_PATH = __DIR__ . '/..';
const TEMPLATE_PATH = ROOT_PATH . '/template';
const DATA_PATH = ROOT_PATH . '/data';
const DB_PATH = DATA_PATH . '/five_guys.sqlite';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $needsSetup = !file_exists(DB_PATH);
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if ($needsSetup) {
        setup_database($pdo);
    }

    return $pdo;
}

function setup_database(PDO $pdo): void {
    $schema = file_get_contents(DATA_PATH . '/schema.sql');
    $pdo->exec($schema ?: '');

    $check = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($check > 0) {
        return;
    }

    $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute(['Demo User', 'demo@fiveguys.local', $passwordHash]);
    $userId = (int)$pdo->lastInsertId();

    $recipes = [
        [
            'Classic Cheeseburger',
            'A simple Five Guys style burger with cheese, lettuce, tomato, onion, and pickles.',
            'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=1200&q=80',
            18,
            2,
            json_encode(['ground beef', 'burger buns', 'american cheese', 'lettuce', 'tomato', 'onion', 'pickles']),
            "Season the beef and form patties.
Sear in a hot pan until browned.
Add cheese and let it melt.
Toast buns, stack toppings, and serve.",
            'Burger'
        ],
        [
            'Cajun Fries Bowl',
            'Crispy fries tossed in cajun seasoning with a creamy dipping sauce.',
            'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=1200&q=80',
            25,
            3,
            json_encode(['potatoes', 'cajun seasoning', 'salt', 'pepper', 'mayonnaise']),
            "Cut potatoes into fries.
Bake or fry until crisp.
Toss with cajun seasoning.
Serve with seasoned mayo.",
            'Sides'
        ],
        [
            'Grilled Veggie Sandwich',
            'A fast grilled veggie sandwich with mushrooms, peppers, onions, and cheese.',
            'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=1200&q=80',
            15,
            2,
            json_encode(['sandwich rolls', 'mushrooms', 'bell peppers', 'onion', 'provolone cheese']),
            "Slice all vegetables.
Saute until soft and lightly browned.
Melt cheese over vegetables.
Serve inside toasted rolls.",
            'Sandwich'
        ],
        [
            'Bacon Burger Deluxe',
            'Juicy burger with bacon, grilled onions, and burger sauce.',
            'https://images.unsplash.com/photo-1550317138-10000687a72b?auto=format&fit=crop&w=1200&q=80',
            22,
            2,
            json_encode(['ground beef', 'burger buns', 'bacon', 'onion', 'cheddar cheese', 'mayonnaise', 'ketchup']),
            "Cook bacon until crispy.
Sear burger patties.
Grill onions in bacon fat.
Assemble with sauce and cheese.",
            'Burger'
        ]
    ];

    $stmt = $pdo->prepare('INSERT INTO recipes (title, summary, image_url, ready_minutes, servings, ingredients_json, instructions, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($recipes as $recipe) {
        $stmt->execute($recipe);
    }

    $fridge = $pdo->prepare('INSERT INTO fridge_items (user_id, item_name, quantity) VALUES (?, ?, ?)');
    foreach ([['ground beef', '500g'], ['burger buns', '4'], ['onion', '2'], ['potatoes', '5']] as $item) {
        $fridge->execute([$userId, $item[0], $item[1]]);
    }
}

function asset_url(string $path): string {
    return '/' . ltrim($path, '/');
}

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, name, email FROM users WHERE id = ?');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function require_login(): void {
    if (!current_user()) {
        header('Location: /script/index.php?page=login');
        exit;
    }
}

function nav_links(): string {
    $user = current_user();
    if (!$user) {
        return '<a href="/script/index.php?page=login">Login</a><a href="/script/index.php?page=register">Register</a>';
    }
    return '<a href="/script/index.php?page=home">Home</a><a href="/script/index.php?page=fridge">Fridge</a><a href="/script/index.php?page=recipes">Recipes</a><a href="/script/index.php?page=calendar">Calendar</a><a href="/script/logout.php">Logout</a>';
}

function render_template(string $template, array $data = []): void {
    $path = TEMPLATE_PATH . '/' . $template;
    if (!file_exists($path)) {
        http_response_code(500);
        echo 'Template not found: ' . htmlspecialchars($template);
        exit;
    }
    $html = file_get_contents($path);
    $defaults = [
        'base_css' => asset_url('styles/base.css'),
        'page_css' => '',
        'page_js' => '',
        'nav_links' => nav_links(),
        'user_name' => htmlspecialchars(current_user()['name'] ?? 'Guest'),
        'flash_message' => flash_message_html(),
    ];
    $data = array_merge($defaults, $data);
    foreach ($data as $key => $value) {
        $html = str_replace('{{' . $key . '}}', (string)$value, $html);
    }
    $html = preg_replace('/\{\{[a-zA-Z0-9_\-]+\}\}/', '', $html);
    echo $html;
}

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function set_flash(string $message, string $type = 'success'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function flash_message_html(): string {
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<div class="flash flash-' . h((string)$flash['type']) . '">' . h((string)$flash['message']) . '</div>';
}

function recipes_all(): array {
    $rows = db()->query('SELECT * FROM recipes ORDER BY title')->fetchAll();
    foreach ($rows as &$row) {
        $row['ingredients'] = json_decode((string)$row['ingredients_json'], true) ?: [];
    }
    return $rows;
}

function recipe_find(int $id): ?array {
    $stmt = db()->prepare('SELECT * FROM recipes WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) return null;
    $row['ingredients'] = json_decode((string)$row['ingredients_json'], true) ?: [];
    return $row;
}

function fridge_items_for_user(int $userId): array {
    $stmt = db()->prepare('SELECT * FROM fridge_items WHERE user_id = ? ORDER BY item_name');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function matched_recipes_for_user(int $userId): array {
    $items = fridge_items_for_user($userId);
    $names = array_map(fn($row) => strtolower(trim((string)$row['item_name'])), $items);
    $matches = [];
    foreach (recipes_all() as $recipe) {
        $count = 0;
        foreach ($recipe['ingredients'] as $ingredient) {
            if (in_array(strtolower(trim((string)$ingredient)), $names, true)) {
                $count++;
            }
        }
        $recipe['match_count'] = $count;
        $matches[] = $recipe;
    }
    usort($matches, fn($a, $b) => $b['match_count'] <=> $a['match_count']);
    return $matches;
}

function meal_plan_for_user(int $userId): array {
    $stmt = db()->prepare('SELECT meal_plan.day_name, recipes.title FROM meal_plan LEFT JOIN recipes ON recipes.id = meal_plan.recipe_id WHERE meal_plan.user_id = ?');
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $row) {
        $map[$row['day_name']] = $row['title'];
    }
    return $map;
}
