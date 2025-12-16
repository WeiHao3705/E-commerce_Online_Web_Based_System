<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();

// Redirect admins to AdminDashboard - they should not access member pages
if (isset($_SESSION['user']) && isset($_SESSION['user']->role) && $_SESSION['user']->role === 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Admins cannot access member payment pages']);
    exit;
}

header('Content-Type: application/json');

require __DIR__ . '/../../../vendor/autoload.php';
require __DIR__ . '/../../config/stripe_config.php';
require __DIR__ . '/../../database/connection.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $paymentIntentId = $input['paymentIntentId'] ?? null;
    
    if (!$paymentIntentId) {
        throw new Exception('Payment Intent ID missing');
    }
    
    // Verify payment with Stripe
    $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
    
    if ($paymentIntent->status !== 'succeeded') {
        throw new Exception('Payment not completed');
    }
    
    // Get order data from session
    $orderData = $_SESSION['pending_order_data'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$orderData || !$userId) {
        throw new Exception('Order data not found');
    }
    
    // Start database transaction
    $db = new Database();
    $conn = $db->getConnection();
    $conn->beginTransaction();
    
    // Insert order
    $orderStmt = $conn->prepare("
        INSERT INTO orders (user_id, total_amount, order_status, create_at)
        VALUES (:user_id, :total_amount, 'paid', NOW())
    ");
    
    $orderStmt->execute([
        ':user_id' => $userId,
        ':total_amount' => $orderData['total_amount']
    ]);
    
    $orderId = $conn->lastInsertId();
    
    // Insert payment record
    $paymentStmt = $conn->prepare("
        INSERT INTO payment (order_id, payment_method, payment_status, transaction_id, paid_amount, payment_date)
        VALUES (:order_id, 'credit_card', 'completed', :transaction_id, :paid_amount, NOW())
    ");
    
    $paymentStmt->execute([
        ':order_id' => $orderId,
        ':transaction_id' => $paymentIntentId,
        ':paid_amount' => $paymentIntent->amount / 100 // Convert from cents
    ]);
    
    // Insert order items
    $itemStmt = $conn->prepare("
        INSERT INTO order_item (order_id, product_id, product_name_snapshot, product_price_snapshot, quantity, subtotal)
        VALUES (:order_id, :product_id, :product_name, :product_price, :quantity, :subtotal)
    ");
    
    foreach ($orderData['items'] as $item) {
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
    
    // Delete items from cart
    $cartItemIds = array_column($orderData['items'], 'id');
    if (!empty($cartItemIds)) {
        $placeholders = implode(',', array_fill(0, count($cartItemIds), '?'));
        $deleteStmt = $conn->prepare("DELETE FROM cart_item WHERE cart_item_id IN ($placeholders)");
        $deleteStmt->execute($cartItemIds);
    }
    
    $conn->commit();
    
    // Clear session data
    unset($_SESSION['payment_intent_id']);
    unset($_SESSION['pending_order_data']);
    unset($_SESSION['checkout_items']);
    
    echo json_encode([
        'success' => true,
        'orderId' => $orderId,
        'message' => 'Payment successful!'
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
