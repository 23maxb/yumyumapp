<?php
declare(strict_types=1);
require_login();
$user = current_user();
$items = fridge_items_for_user((int)$user['id']);
$matches = matched_recipes_for_user((int)$user['id']);
$top = $matches[0]['title'] ?? 'Nothing yet';
$cards = '';
$cards .= '<div class="stat-card"><span>Fridge Items</span><strong>' . count($items) . '</strong></div>';
$cards .= '<div class="stat-card"><span>Recipe Matches</span><strong>' . count($matches) . '</strong></div>';
$cards .= '<div class="stat-card"><span>Top Match</span><strong>' . h($top) . '</strong></div>';

render_template('home.html', [
    'page_title' => 'Home',
    'page_css' => asset_url('styles/home.css'),
    'page_js' => asset_url('script/home.js'),
    'stats_cards' => $cards,
]);
