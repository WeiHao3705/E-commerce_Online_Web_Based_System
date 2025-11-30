<?php
// Simple entry for account/login page
session_start();

// If user is already logged in, redirect to appropriate page
if (!empty($_SESSION['user'])) {
    // Check user role and redirect accordingly
    if ($_SESSION['user']['role'] === 'admin') {
        header('Location: views/admin/AdminDashboard.php');
    } else {
        // Redirect members to home page (shows MemberHome when logged in)
        header('Location: index.php');
    }
    exit;
}

$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_views = (strpos($current_dir, '/views') !== false);
$prefix = $is_in_views ? '../' : '';

require_once __DIR__ . '/views/LoginForm.php';
