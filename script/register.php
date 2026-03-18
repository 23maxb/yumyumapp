<?php
declare(strict_types=1);

// Avoid showing auth forms to already-authenticated users.
if (current_user()) {
    header('Location: /script/index.php?page=home');
    exit;
}

// Registration form posts via auth.js to api-register.php.
render_template('register.html', [
    'page_title' => 'Register',
    'page_css' => asset_url('styles/auth.css'),
    'page_js' => asset_url('script/auth.js')
]);
