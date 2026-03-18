<?php
declare(strict_types=1);
require_once __DIR__ . '/lib.php';

// Clear session state and return to the login screen.
session_destroy();
header('Location: /script/index.php?page=login');
exit;
