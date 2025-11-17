<?php 
session_start();
require __DIR__ . '/database/connection.php';
$db = new Database();
$conn = $db->getConnection();
$pageTitle = "Home";
include 'general/_header.php'; 
include 'general/_navbar.php'; 
?>

<!-- Your page content goes here -->

<?php include 'general/_footer.php'; ?>