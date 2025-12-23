<?php
// Redirect search queries to ProductPage with search parameter
session_start();

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($searchQuery)) {
    // No search query, redirect to products page
    header('Location: /web/views/product/ProductPage.php');
    exit;
}

// Redirect to ProductPage with search parameter
header('Location: /web/views/product/ProductPage.php?search=' . urlencode($searchQuery));
exit;
