<?php 
session_start();
require __DIR__ . '/database/connection.php';

$db = new Database();
$conn = $db->getConnection();

$page = $_GET['page'] ?? 'home'; // default page

$pageTitle = ucfirst($page);

include 'general/_header.php';
include 'general/_navbar.php';

// ROUTING
switch ($page) {
    case 'product':
        require __DIR__ . '/views/ProductPage.php';
        break;

    case 'home':
    default:
        if (empty($_SESSION['user'])) {
            require __DIR__ . '/views/guest/GuestHome.php';
        } else {
            require __DIR__ . '/views/member/MemberHome.php';
        }
        break;
}

include 'general/_footer.php';
?>
