<?php
declare(strict_types=1);

// Avoid showing auth forms to already-authenticated users.
if (current_user()) {
    header('Location: /script/index.php?page=home');
    exit;
}

// Login form posts via auth.js to api-login.php.
render_template('login.html', [
    'page_title' => 'Login',
    'page_css' => asset_url('styles/auth.css'),
    'page_js' => asset_url('script/auth.js')
]);
