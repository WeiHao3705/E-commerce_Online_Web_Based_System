<?php
session_start();

header('Content-Type: application/json');

require_once '../../database/connection.php';

// Check if user is logged in and get user_id from different session structures
$userId = null;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
} elseif (isset($_SESSION['user']) && isset($_SESSION['user']->user_id)) {
    $userId = $_SESSION['user']->user_id;
}

if (!$userId) {
    echo json_encode(['count' => 0]);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // query of getting the total amount of items in the cart
    $query = "SELECT COALESCE(SUM(ci.quantity), 0) as total_count FROM cart_item ci JOIN shopping_cart sc ON ci.cart_id = sc.cart_id WHERE sc.user_id = :user_id";

    $stm = $conn->prepare($query);
    $stm->execute([':user_id' => $userId]);
    $result = $stm->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['count' => (int)$result['total_count']]);
} catch (Exception $e) {
    echo json_encode(['count' => 0]);
}