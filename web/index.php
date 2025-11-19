<?php 
session_start();
require __DIR__ . '/database/connection.php';
$db = new Database();
$conn = $db->getConnection();
$pageTitle = "Home";
include 'general/_header.php'; 
include 'general/_navbar.php'; 
?>

<?php
// Show guest/home content depending on session
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user'])) {
	// Guest view
	require_once __DIR__ . '/views/GuestHome.php';
} else {
	// Authenticated user — show member home with carousel
	require_once __DIR__ . '/views/MemberHome.php';
}

include 'general/_footer.php'; ?>