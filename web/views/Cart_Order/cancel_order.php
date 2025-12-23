<?php
// Cancel order endpoint for AJAX
require_once '../../database/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = intval($_POST['order_id']);
    session_start();
    $userId = $_SESSION['user_id'] ?? 0;
    $db = new Database();
    $conn = $db->getConnection();

    // Only restock if the order is pending or processing and belongs to user
    $stmt = $conn->prepare("UPDATE orders SET order_status = 'canceled' WHERE order_id = :order_id AND user_id = :user_id AND order_status IN ('pending', 'processing')");
    $stmt->execute([':order_id' => $orderId, ':user_id' => $userId]);
    if ($stmt->rowCount() > 0) {
        // Restock inventory for each item in the order
        require_once '../../service/InventoryService.php';
        $inventoryService = new InventoryService($conn);
        $itemStmt = $conn->prepare("SELECT product_id, variant_id, size, quantity FROM order_item WHERE order_id = :order_id");
        $itemStmt->execute([':order_id' => $orderId]);
        $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as $item) {
            $inventoryService->restock(
                $item['product_id'],
                $item['variant_id'] !== null ? $item['variant_id'] : null,
                $item['size'] ?? 'default',
                $item['quantity'],
                true
            );
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Unable to cancel order.']);
    }
    exit;
}
echo json_encode(['success' => false, 'error' => 'Invalid request.']);
