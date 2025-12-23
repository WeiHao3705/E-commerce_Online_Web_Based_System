<?php
// Cancel order endpoint for AJAX
require_once '../../database/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = intval($_POST['order_id']);
    session_start();
    $userId = $_SESSION['user_id'] ?? 0;
    $db = new Database();
    $conn = $db->getConnection();

    // Allow cancel if order is pending or processing and belongs to user
    $stmt = $conn->prepare("UPDATE orders SET order_status = 'canceled' WHERE order_id = :order_id AND user_id = :user_id AND order_status IN ('pending', 'processing')");
    $stmt->execute([':order_id' => $orderId, ':user_id' => $userId]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Unable to cancel order.']);
    }
    exit;
}
echo json_encode(['success' => false, 'error' => 'Invalid request.']);
