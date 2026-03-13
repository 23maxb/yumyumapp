<?php
declare(strict_types=1);
require_login();
render_template('recipes.html', [
    'page_title' => 'Recipes',
    'page_css' => asset_url('styles/recipes.css'),
    'page_js' => asset_url('script/recipes.js')
]);
