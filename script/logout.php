<?php
declare(strict_types=1);
require_once __DIR__ . '/lib.php';
session_destroy();
header('Location: /script/index.php?page=login');
exit;
