<?php
declare(strict_types=1);

// Calendar view is protected and rendered from a static template + page assets.
require_login();
render_template('calendar.html', [
    'page_title' => 'Calendar',
    'page_css' => asset_url('styles/calendar.css'),
    'page_js' => asset_url('script/calendar.js')
]);
