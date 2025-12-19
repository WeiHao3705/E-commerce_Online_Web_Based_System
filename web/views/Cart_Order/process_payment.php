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
    
    // Get order ID from session
    $orderId = $_SESSION['pending_order_id'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$orderId || !$userId) {
        throw new Exception('Order ID not found in session');
    }
    
    // Start database transaction
    $db = new Database();
    $conn = $db->getConnection();
    $conn->beginTransaction();
    
    // Verify order exists and is pending
    $verifyStmt = $conn->prepare("
        SELECT order_id, total_amount FROM orders 
        WHERE order_id = :order_id AND user_id = :user_id AND order_status = 'pending'
    ");
    $verifyStmt->execute([':order_id' => $orderId, ':user_id' => $userId]);
    $order = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Order not found or already processed');
    }
    
    // Update order status to paid
    $orderStmt = $conn->prepare("
        UPDATE orders SET order_status = 'paid', update_at = NOW()
        WHERE order_id = :order_id
    ");
    
    $orderStmt->execute([':order_id' => $orderId]);
    
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
    
    // Get cart items from order_items to delete from cart
    $itemsStmt = $conn->prepare("
        SELECT oi.product_id, oi.quantity 
        FROM order_item oi 
        WHERE oi.order_id = :order_id
    ");
    $itemsStmt->execute([':order_id' => $orderId]);
    $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Delete corresponding items from cart
    foreach ($orderItems as $item) {
        $deleteStmt = $conn->prepare("
            DELETE FROM cart_item 
            WHERE user_id = :user_id 
            AND product_id = :product_id 
            LIMIT :quantity
        ");
        $deleteStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $deleteStmt->bindValue(':product_id', $item['product_id'], PDO::PARAM_INT);
        $deleteStmt->bindValue(':quantity', $item['quantity'], PDO::PARAM_INT);
        $deleteStmt->execute();
    }
    
    $conn->commit();
    
    // Clear session data
    unset($_SESSION['payment_intent_id']);
    unset($_SESSION['pending_order_data']);
    unset($_SESSION['pending_order_id']);
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
