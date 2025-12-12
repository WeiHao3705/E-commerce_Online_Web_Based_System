<?php
session_start();
require __DIR__ . '/../../database/connection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$userId = $_SESSION['user_id'];
$db = new Database();
$conn = $db->getConnection();

// Get total quantity of items in cart
$query = "SELECT COALESCE(SUM(ci.quantity), 0) as total_count
          FROM cart_item ci
          JOIN shopping_cart sc ON ci.cart_id = sc.cart_id
          WHERE sc.user_id = :user_id";

$stmt = $conn->prepare($query);
$stmt->execute([':user_id' => $userId]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['count' => (int)$result['total_count']]);
