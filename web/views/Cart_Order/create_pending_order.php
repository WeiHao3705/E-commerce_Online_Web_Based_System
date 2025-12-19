<?php
session_start();
header('Content-Type: application/json');

require __DIR__ . '/../../database/connection.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $selectedItemIds = $input['selectedItems'] ?? [];
    
    if (empty($selectedItemIds)) {
        throw new Exception('No items selected');
    }
    
    $userId = $_SESSION['user_id'];
    
    // Get database connection
    $db = new Database();
    $conn = $db->getConnection();
    $conn->beginTransaction();
    
    // Fetch selected cart items with product details
    $placeholders = implode(',', array_fill(0, count($selectedItemIds), '?'));
    $query = "
        SELECT 
            ci.cart_item_id as id,
            ci.product_id,
            ci.quantity,
            p.product_name as name,
            COALESCE(pp.original_price, 0) as price,
            COALESCE(pi.image_path, '../../images/no-image.png') as image
        FROM cart_item ci
        JOIN shopping_cart sc ON ci.cart_id = sc.cart_id
        JOIN product p ON ci.product_id = p.product_id
        LEFT JOIN product_price pp ON p.product_id = pp.product_id
        LEFT JOIN product_image pi ON p.product_id = pi.product_id
        WHERE ci.cart_item_id IN ($placeholders) 
        AND sc.user_id = ?
        GROUP BY ci.cart_item_id
    ";
    
    $params = array_merge($selectedItemIds, [$userId]);
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cartItems)) {
        throw new Exception('No valid items found. Selected IDs: ' . implode(',', $selectedItemIds));
    }
    
    // Calculate totals
    $subtotal = 0;
    foreach ($cartItems as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    $shippingFee = 10.00;
    $taxRate = 0.06;
    $tax = $subtotal * $taxRate;
    $totalAmount = $subtotal + $shippingFee + $tax;
    
    // Create pending order
    $orderStmt = $conn->prepare("
        INSERT INTO orders (user_id, total_amount, order_status, create_at)
        VALUES (:user_id, :total_amount, 'pending', NOW())
    ");
    
    $orderStmt->execute([
        ':user_id' => $userId,
        ':total_amount' => $totalAmount
    ]);
    
    $orderId = $conn->lastInsertId();
    
    // Insert order items
    $itemStmt = $conn->prepare("
        INSERT INTO order_item (order_id, product_id, product_name_snapshot, product_price_snapshot, quantity, subtotal)
        VALUES (:order_id, :product_id, :product_name, :product_price, :quantity, :subtotal)
    ");
    
    foreach ($cartItems as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $itemStmt->execute([
            ':order_id' => $orderId,
            ':product_id' => $item['product_id'],
            ':product_name' => $item['name'],
            ':product_price' => $item['price'],
            ':quantity' => $item['quantity'],
            ':subtotal' => $subtotal
        ]);
    }
    
    $conn->commit();
    
    // Store order data in session for checkout
    $_SESSION['pending_order_id'] = $orderId;
    $_SESSION['pending_order_data'] = [
        'orderId' => $orderId,
        'items' => $cartItems,
        'total_amount' => $totalAmount,
        'subtotal' => $subtotal,
        'shipping' => $shippingFee,
        'tax' => $tax
    ];
    
    echo json_encode([
        'success' => true,
        'orderId' => $orderId,
        'message' => 'Order created successfully'
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
