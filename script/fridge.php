<?php
declare(strict_types=1);

// Fridge page is interactive; data operations are handled by fridge.js + api-fridge.php.
require_login();
render_template('fridge.html', [
    'page_title' => 'Fridge',
    'page_css' => asset_url('styles/fridge.css'),
    'page_js' => asset_url('script/fridge.js')
]);
