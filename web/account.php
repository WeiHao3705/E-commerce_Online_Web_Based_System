<?php
// Simple entry for account/login page
session_start();

$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_views = (strpos($current_dir, '/views') !== false);
$prefix = $is_in_views ? '../' : '';

require_once __DIR__ . '/views/LoginForm.php';
