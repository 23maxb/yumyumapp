<?php
declare(strict_types=1);
if (current_user()) {
    header('Location: /script/index.php?page=home');
    exit;
}
render_template('login.html', [
    'page_title' => 'Login',
    'page_css' => asset_url('styles/auth.css'),
    'page_js' => asset_url('script/auth.js')
]);
