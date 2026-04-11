<?php
$pdo = require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/helpers.php';

$auth = new Auth($pdo);

// Logout user
$auth->logout();

// Redirect to home
redirect(APP_URL . '/public/index.php');
