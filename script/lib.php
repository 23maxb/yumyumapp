<?php
// Shared utility library: database access, auth/session helpers, and app-level data utilities.
declare(strict_types=1);
session_start();

const ROOT_PATH = __DIR__ . '/..';
const TEMPLATE_PATH = ROOT_PATH . '/template';
const DATA_PATH = ROOT_PATH . '/data';
const DB_PATH = DATA_PATH . '/five_guys.sqlite';
const MEAL_TYPES = ['breakfast', 'lunch', 'dinner'];
const WEEK_DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
const SPOONACULAR_API_KEY = ''; // HERHERHEHRER actual key

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Lazily initialize a single PDO instance for this request lifecycle.
    $needsSetup = !file_exists(DB_PATH);
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    if ($needsSetup) {
        // First run: create schema and seed starter data.
        setup_database($pdo);
    } else {
        // Existing DB: apply lightweight migrations and ensure defaults exist.
        ensure_recipe_schema($pdo);
        ensure_meal_plan_schema($pdo);
        ensure_sample_recipes($pdo);
    }

    return $pdo;
}

function setup_database(PDO $pdo): void
{
    // Initialize the full schema from SQL, then run safety migrations.
    $schema = file_get_contents(DATA_PATH . '/schema.sql');
    $pdo->exec($schema ?: '');
    ensure_recipe_schema($pdo);
    ensure_meal_plan_schema($pdo);

    $check = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($check === 0) {
        // Seed one demo account plus basic fridge items for first-time use.
        $passwordHash = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute(['Demo User', 'demo@fiveguys.local', $passwordHash]);
        $userId = (int)$pdo->lastInsertId();

        $fridge = $pdo->prepare('INSERT INTO fridge_items (user_id, item_name, quantity) VALUES (?, ?, ?)');
        foreach ([['ground beef', '500g'], ['burger buns', '4'], ['onion', '2'], ['potatoes', '5']] as $item) {
            $fridge->execute([$userId, $item[0], $item[1]]);
        }
    }

    ensure_sample_recipes($pdo);
}

function ensure_recipe_schema(PDO $pdo): void
{
    $columns = $pdo->query('PRAGMA table_info(recipes)')->fetchAll();
    $columnNames = array_map(static fn(array $column): string => (string)$column['name'], $columns);

    if (!in_array('created_by_user_id', $columnNames, true)) {
        // Backfill newer ownership column for older databases.
        $pdo->exec('ALTER TABLE recipes ADD COLUMN created_by_user_id INTEGER');
    }
}

function ensure_meal_plan_schema(PDO $pdo): void
{
    $columns = $pdo->query('PRAGMA table_info(meal_plan)')->fetchAll();
    $columnNames = array_map(static fn(array $column): string => (string)$column['name'], $columns);

    if (in_array('meal_type', $columnNames, true)) {
        return;
    }

    // Migration path for legacy meal_plan table that lacked meal_type.
    $pdo->beginTransaction();
    try {
        $pdo->exec('ALTER TABLE meal_plan RENAME TO meal_plan_legacy');
        $pdo->exec('CREATE TABLE meal_plan (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            day_name TEXT NOT NULL,
            meal_type TEXT NOT NULL DEFAULT "dinner",
            recipe_id INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, day_name, meal_type),
            FOREIGN KEY(user_id) REFERENCES users(id),
            FOREIGN KEY(recipe_id) REFERENCES recipes(id)
        )');
        $pdo->exec("INSERT INTO meal_plan (id, user_id, day_name, meal_type, recipe_id, created_at)
            SELECT id, user_id, day_name, 'dinner', recipe_id, created_at FROM meal_plan_legacy");
        $pdo->exec('DROP TABLE meal_plan_legacy');
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function default_recipe_image(): string
{
    return 'https://images.unsplash.com/photo-1515003197210-e0cd71810b5f?auto=format&fit=crop&w=1200&q=80';
}

function sample_recipes(): array
{
    return [
        [
            'title' => 'Classic Cheeseburger',
            'summary' => 'A simple Five Guys style burger with cheese, lettuce, tomato, onion, and pickles.',
            'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=1200&q=80',
            'ready_minutes' => 18,
            'servings' => 2,
            'ingredients' => ['ground beef', 'burger buns', 'american cheese', 'lettuce', 'tomato', 'onion', 'pickles'],
            'instructions' => "Season the beef and form patties.\nSear in a hot pan until browned.\nAdd cheese and let it melt.\nToast buns, stack toppings, and serve.",
            'category' => 'Burger',
        ],
        [
            'title' => 'Cajun Fries Bowl',
            'summary' => 'Crispy fries tossed in cajun seasoning with a creamy dipping sauce.',
            'image_url' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&w=1200&q=80',
            'ready_minutes' => 25,
            'servings' => 3,
            'ingredients' => ['potatoes', 'cajun seasoning', 'salt', 'pepper', 'mayonnaise'],
            'instructions' => "Cut potatoes into fries.\nBake or fry until crisp.\nToss with cajun seasoning.\nServe with seasoned mayo.",
            'category' => 'Sides',
        ],
        [
            'title' => 'Grilled Veggie Sandwich',
            'summary' => 'A fast grilled veggie sandwich with mushrooms, peppers, onions, and cheese.',
            'image_url' => 'https://images.unsplash.com/photo-1528735602780-2552fd46c7af?auto=format&fit=crop&w=1200&q=80',
            'ready_minutes' => 15,
            'servings' => 2,
            'ingredients' => ['sandwich rolls', 'mushrooms', 'bell peppers', 'onion', 'provolone cheese'],
            'instructions' => "Slice all vegetables.\nSaute until soft and lightly browned.\nMelt cheese over vegetables.\nServe inside toasted rolls.",
            'category' => 'Sandwich',
        ],
        [
            'title' => 'Bacon Burger Deluxe',
            'summary' => 'Juicy burger with bacon, grilled onions, and burger sauce.',
            'image_url' => 'https://images.unsplash.com/photo-1550317138-10000687a72b?auto=format&fit=crop&w=1200&q=80',
            'ready_minutes' => 22,
            'servings' => 2,
            'ingredients' => ['ground beef', 'burger buns', 'bacon', 'onion', 'cheddar cheese', 'mayonnaise', 'ketchup'],
            'instructions' => "Cook bacon until crispy.\nSear burger patties.\nGrill onions in bacon fat.\nAssemble with sauce and cheese.",
            'category' => 'Burger',
        ],
        [
            'title' => 'Mushroom Swiss Burger',
            'summary' => 'Savory burger topped with sauteed mushrooms, swiss cheese, and a toasted bun.',
            'image_url' => 'https://images.unsplash.com/photo-1432139555190-58524dae6a55?auto=format&fit=crop&w=1200&q=80',
            'ready_minutes' => 20,
            'servings' => 2,
            'ingredients' => ['ground beef', 'burger buns', 'mushrooms', 'swiss cheese', 'butter', 'onion'],
            'instructions' => "Saute mushrooms and onions in butter.\nCook burger patties until browned.\nTop with swiss cheese and let it melt.\nAssemble on toasted buns with the mushroom mixture.",
            'category' => 'Burger',
        ],
        [
            'title' => 'Patty Melt',
            'summary' => 'Crisp griddled bread with seasoned beef, caramelized onions, and melty cheese.',
            'image_url' => 'https://images.unsplash.com/photo-1520072959219-c595dc870360?auto=format&fit=crop&w=1200&q=80',
            'ready_minutes' => 24,
            'servings' => 2,
            'ingredients' => ['ground beef', 'sourdough bread', 'onion', 'swiss cheese', 'butter'],
            'instructions' => "Caramelize onions in a skillet.\nCook thin burger patties and season well.\nButter bread and griddle until golden with cheese, onions, and patties inside.\nSlice and serve hot.",
            'category' => 'Sandwich',
        ],
        [
            'title' => 'Loaded Cheese Fries',
            'summary' => 'Golden fries finished with melted cheddar, bacon, and green onions.',
            'image_url' => 'https://images.unsplash.com/photo-1518013431117-eb1465fa5752?auto=format&fit=crop&w=1200&q=80',
            'ready_minutes' => 28,
            'servings' => 4,
            'ingredients' => ['potatoes', 'cheddar cheese', 'bacon', 'green onions', 'salt'],
            'instructions' => "Bake or fry potatoes until crisp.\nCook bacon and crumble it.\nPile fries onto a tray with cheese and bacon.\nFinish with green onions and serve immediately.",
            'category' => 'Sides',
        ],
        [
            'title' => 'Veggie Burger Bowl',
            'summary' => 'A burger-inspired bowl with grilled vegetables, greens, pickles, and creamy sauce.',
            'image_url' => 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&fit=crop&w=1200&q=80',
            'ready_minutes' => 17,
            'servings' => 2,
            'ingredients' => ['lettuce', 'tomato', 'onion', 'mushrooms', 'pickles', 'mayonnaise'],
            'instructions' => "Grill onions and mushrooms until tender.\nLayer lettuce and tomatoes in bowls.\nTop with grilled vegetables and sliced pickles.\nDrizzle with mayo-based sauce before serving.",
            'category' => 'Salad',
        ],
    ];
}

function insert_recipe(PDO $pdo, array $recipe, ?int $createdByUserId = null): int
{
    $stmt = $pdo->prepare('INSERT INTO recipes (title, summary, image_url, ready_minutes, servings, ingredients_json, instructions, category, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $recipe['title'],
        $recipe['summary'],
        $recipe['image_url'] ?: '',
        (int)$recipe['ready_minutes'],
        (int)$recipe['servings'],
        json_encode($recipe['ingredients']),
        $recipe['instructions'],
        $recipe['category'],
        $createdByUserId,
    ]);

    return (int)$pdo->lastInsertId();
}

function ensure_sample_recipes(PDO $pdo): void
{
    $existingTitles = $pdo->query('SELECT title FROM recipes')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $existingMap = array_fill_keys(array_map('strtolower', array_map('strval', $existingTitles)), true);

    foreach (sample_recipes() as $recipe) {
        // Use title as a stable, human-readable dedupe key.
        if (isset($existingMap[strtolower($recipe['title'])])) {
            continue;
        }

        insert_recipe($pdo, $recipe);
    }
}

function asset_url(string $path): string
{
    return '/' . ltrim($path, '/');
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    // Re-query user details each request so session only stores the user id.
    $stmt = db()->prepare('SELECT id, name, email FROM users WHERE id = ?');
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function require_login(): void
{
    if (!current_user()) {
        // Centralized redirect keeps protected pages consistent.
        header('Location: /script/index.php?page=login');
        exit;
    }
}

function nav_links(): string
{
    $user = current_user();
    if (!$user) {
        return '<a href="/script/index.php?page=login">Login</a><a href="/script/index.php?page=register">Register</a>';
    }
    return '<a href="/script/index.php?page=home">Home</a><a href="/script/index.php?page=fridge">Fridge</a><a href="/script/index.php?page=recipes">Recipes</a><a href="/script/index.php?page=calendar">Calendar</a><a href="/script/logout.php">Logout</a>';
}

function render_template(string $template, array $data = []): void
{
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
        // Simple token replacement using {{token_name}} placeholders.
        $html = str_replace('{{' . $key . '}}', (string)$value, $html);
    }
    // Remove any unresolved tokens so raw placeholders never leak to UI.
    $html = preg_replace('/\{\{[a-zA-Z0-9_\-]+\}\}/', '', $html);
    echo $html;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function flash_message_html(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<div class="flash flash-' . h((string)$flash['type']) . '">' . h((string)$flash['message']) . '</div>';
}

function recipes_all(): array
{
    // Hydrate JSON-encoded recipe columns into PHP arrays.
    $rows = db()->query('SELECT * FROM recipes ORDER BY title')->fetchAll();
    foreach ($rows as &$row) {
        $row = hydrate_recipe_row($row);
    }
    return $rows;
}

function recipes_all_with_matches(int $userId): array
{
    // Compute per-recipe ingredient overlap against the user's fridge.
    $items = fridge_items_for_user($userId);
    $names = array_map(fn($row) => strtolower(trim((string)$row['item_name'])), $items);

    $recipes = recipes_all();
    foreach ($recipes as &$recipe) {
        $count = 0;
        foreach ($recipe['ingredients'] as $ingredient) {
            if (in_array(strtolower(trim((string)$ingredient)), $names, true)) {
                $count++;
            }
        }
        $recipe['match_count'] = $count;
        $recipe['is_external'] = false;
    }
    unset($recipe);

    if (defined('SPOONACULAR_API_KEY') && SPOONACULAR_API_KEY !== '') {
        $externalRecipes = recipes_search_external($userId);
        $recipes = array_merge($recipes, $externalRecipes);
    }

    usort($recipes, static function (array $a, array $b): int {
        if ($a['is_external'] !== $b['is_external']) {
            return $b['is_external'] ? 1 : -1;
        }
        return $b['match_count'] <=> $a['match_count'];
    });

    return $recipes;
}

function recipe_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM recipes WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) return null;
    return hydrate_recipe_row($row);
}

function hydrate_recipe_row(array $row): array
{
    // Normalize raw DB rows into render-ready recipe objects.
    $row['ingredients'] = json_decode((string)$row['ingredients_json'], true) ?: [];
    $row['image_url'] = trim((string)($row['image_url'] ?? '')) !== '' ? (string)$row['image_url'] : default_recipe_image();
    return $row;
}

function create_recipe(array $input, int $userId): array
{
    // Normalize and validate before persistence.
    $title = trim((string)($input['title'] ?? ''));
    $summary = trim((string)($input['summary'] ?? ''));
    $imageUrl = trim((string)($input['image_url'] ?? ''));
    $instructions = trim((string)($input['instructions'] ?? ''));
    $category = trim((string)($input['category'] ?? '')) ?: 'Custom';
    $readyMinutes = max(1, (int)($input['ready_minutes'] ?? 20));
    $servings = max(1, (int)($input['servings'] ?? 2));
    $ingredients = preg_split('/[\r\n,]+/', (string)($input['ingredients'] ?? '')) ?: [];
    $ingredients = array_values(array_filter(array_map(static fn(string $item): string => trim($item), $ingredients), static fn(string $item): bool => $item !== ''));

    if ($title === '') {
        throw new InvalidArgumentException('Recipe title is required.');
    }
    if ($summary === '') {
        throw new InvalidArgumentException('Recipe summary is required.');
    }
    if (count($ingredients) === 0) {
        throw new InvalidArgumentException('Add at least one ingredient.');
    }
    if ($instructions === '') {
        throw new InvalidArgumentException('Recipe instructions are required.');
    }

    $id = insert_recipe(db(), [
        'title' => $title,
        'summary' => $summary,
        'image_url' => $imageUrl,
        'ready_minutes' => $readyMinutes,
        'servings' => $servings,
        'ingredients' => $ingredients,
        'instructions' => $instructions,
        'category' => $category,
    ], $userId);

    $recipe = recipe_find($id);
    if (!$recipe) {
        // Defensive guard to avoid returning a partial success state.
        throw new RuntimeException('Recipe could not be loaded after creation.');
    }

    return $recipe;
}

function fridge_items_for_user(int $userId): array
{
    $stmt = db()->prepare('SELECT * FROM fridge_items WHERE user_id = ? ORDER BY item_name');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function matched_recipes_for_user(int $userId): array
{
    // Return only recipes with at least one ingredient overlap.
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
        if ($count > 0) {
            $recipe['match_count'] = $count;
            $matches[] = $recipe;
        }
    }
    usort($matches, fn($a, $b) => $b['match_count'] <=> $a['match_count']);
    return $matches;
}

function meal_plan_for_user(int $userId): array
{
    // Build a complete day x meal matrix so the UI can render predictable slots.
    $map = [];
    foreach (WEEK_DAYS as $day) {
        foreach (MEAL_TYPES as $mealType) {
            $map[$day][$mealType] = null;
        }
    }

    $stmt = db()->prepare('SELECT meal_plan.day_name, meal_plan.meal_type, meal_plan.recipe_id, recipes.title FROM meal_plan LEFT JOIN recipes ON recipes.id = meal_plan.recipe_id WHERE meal_plan.user_id = ?');
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $dayName = (string)$row['day_name'];
        $mealType = (string)$row['meal_type'];
        if (!array_key_exists($dayName, $map) || !array_key_exists($mealType, $map[$dayName])) {
            continue;
        }
        $map[$dayName][$mealType] = [
            'recipe_id' => isset($row['recipe_id']) ? (int)$row['recipe_id'] : null,
            'title' => $row['title'] !== null ? (string)$row['title'] : '',
        ];
    }

    return $map;
}

function meal_plan_export_for_user(int $userId): array
{
    // Export includes both the day plan and a shopping-list-style ingredient rollup.
    $plan = meal_plan_for_user($userId);
    $days = [];
    $ingredientIndex = [];

    foreach (WEEK_DAYS as $dayName) {
        $meals = [];

        foreach (MEAL_TYPES as $mealType) {
            $meal = $plan[$dayName][$mealType] ?? null;
            $recipeId = isset($meal['recipe_id']) ? (int)$meal['recipe_id'] : 0;
            $recipe = $recipeId > 0 ? recipe_find($recipeId) : null;

            $meals[$mealType] = $recipe;

            if (!$recipe) {
                continue;
            }

            foreach ($recipe['ingredients'] as $ingredient) {
                $key = strtolower(trim((string)$ingredient));
                if ($key === '') {
                    continue;
                }
                if (!isset($ingredientIndex[$key])) {
                    $ingredientIndex[$key] = [
                        'name' => trim((string)$ingredient),
                        'count' => 0,
                    ];
                }
                $ingredientIndex[$key]['count']++;
            }
        }

        $days[] = [
            'day_name' => $dayName,
            'meals' => $meals,
        ];
    }

    uasort($ingredientIndex, static fn(array $left, array $right): int => strcasecmp($left['name'], $right['name']));

    return [
        'days' => $days,
        'ingredients' => array_values($ingredientIndex),
    ];
}

function recipes_search_external(int $userId): array
{
    $items = fridge_items_for_user($userId);
    if (empty($items)) {
        return [];
    }
    $ingredient_string = urlencode(implode(',', array_map(fn($row) => $row['item_name'], $items)));
    $url = "https://api.spoonacular.com/recipes/findByIngredients?ingredients={$ingredient_string}&number=10&ranking=1&ignorePantry=true&apiKey=" . SPOONACULAR_API_KEY;
    $response = @file_get_contents($url);
    if ($response === false) {
        return [];
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return [];
    }

    return array_map(function ($r) {
        return [
            'id' => $r['id'],
            'title' => $r['title'],
            'image_url' => $r['image'] ?? default_recipe_image(),
            'ready_minutes' => null,
            'servings' => null,
            'category' => 'External',
            'match_count' => $r['usedIngredientCount'] ?? 0,
            'summary' => "Uses " . ($r['usedIngredientCount'] ?? 0) . " of your items. Missing " . ($r['missedIngredientCount'] ?? 0) . ".",
            'is_external' => true,
            'external_url' => "https://spoonacular.com/recipes/" . urlencode($r['title']) . "-{$r['id']}",
        ];
    }, $data);
}
