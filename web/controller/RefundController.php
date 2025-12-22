<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once __DIR__ . '/../database/connection.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $userId = $_SESSION['user_id'] ?? $_SESSION['user']->user_id;
    $orderId = $_POST['order_id'] ?? null;
    $reason = $_POST['reason'] ?? null;
    $details = $_POST['details'] ?? '';
    
    // Validate input
    if (!$orderId || !$reason) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Verify the order belongs to the user and is eligible for refund
    $checkQuery = "SELECT order_id, order_status, total_amount FROM orders WHERE order_id = :order_id AND user_id = :user_id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->execute([':order_id' => $orderId, ':user_id' => $userId]);
    $order = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or does not belong to you']);
        exit;
    }
    
    // Check if order is eligible for refund (only delivered orders)
    $refundableStatus = ['delivered', 'paid'];
    if (!in_array($order['order_status'], $refundableStatus)) {
        echo json_encode(['success' => false, 'message' => 'Only delivered or paid orders can be refunded']);
        exit;
    }
    
    // Check if refund was already requested
    if ($order['order_status'] === 'refunded') {
        echo json_encode(['success' => false, 'message' => 'This order has already been refunded']);
        exit;
    }
    
    $conn->beginTransaction();
    
    // Update order status to 'refund_requested' (pending admin approval)
    $updateQuery = "UPDATE orders SET order_status = 'refund_requested', updated_at = CURRENT_TIMESTAMP WHERE order_id = :order_id";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->execute([':order_id' => $orderId]);
    
    // Note: Payment status remains 'completed' until admin approves refund
    
    // Insert refund note into order_notes (if table exists)
    try {
        $reasonText = str_replace('_', ' ', ucwords($reason));
        $noteText = "Refund requested by customer. Reason: {$reasonText}";
        if (!empty($details)) {
            $noteText .= ". Details: {$details}";
        }
        
        $noteQuery = "INSERT INTO order_notes (order_id, admin_id, note_text, created_at) 
                      VALUES (:order_id, :user_id, :note_text, CURRENT_TIMESTAMP)";
        $noteStmt = $conn->prepare($noteQuery);
        $noteStmt->execute([
            ':order_id' => $orderId,
            ':user_id' => $userId,
            ':note_text' => $noteText
        ]);
    } catch (Exception $e) {
        // Table might not exist, continue anyway
        error_log("Could not insert order note: " . $e->getMessage());
    }
    
    $conn->commit();
    
    // TODO: Send email notification to admin and customer
    // TODO: Trigger payment gateway refund API
    
    echo json_encode([
        'success' => true,
        'message' => 'Refund request submitted successfully. Awaiting admin approval.',
        'order_id' => $orderId
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Refund request error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your refund request'
    ]);
}
