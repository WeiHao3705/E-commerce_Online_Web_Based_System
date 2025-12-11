<?php
session_start();
require __DIR__ . '/../../database/connection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if cart_item_id is provided
if (!isset($_POST['cart_item_id'])) {
    echo json_encode(['success' => false, 'message' => 'Cart item ID not provided']);
    exit;
}

$userId = $_SESSION['user_id'];
$cartItemId = (int) $_POST['cart_item_id'];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Delete the cart item (with security check to ensure it belongs to the user)
    $deleteQuery = "DELETE ci FROM cart_item ci 
                    JOIN shopping_cart sc ON ci.cart_id = sc.cart_id 
                    WHERE ci.cart_item_id = :cart_item_id 
                    AND sc.user_id = :user_id";
    
    $stmt = $conn->prepare($deleteQuery);
    $stmt->execute([
        ':cart_item_id' => $cartItemId,
        ':user_id' => $userId
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found or already deleted']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
