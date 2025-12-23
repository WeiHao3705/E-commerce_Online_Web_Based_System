<?php
// AdminOrderController.php: Handles AJAX order deletion for admin
require_once __DIR__ . '/../database/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    if ($action === 'delete' && !empty($input['order_ids']) && is_array($input['order_ids'])) {
        session_start();
        if (!isset($_SESSION['user']) || $_SESSION['user']->role !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        $orderIds = array_map('intval', $input['order_ids']);
        $db = new Database();
        $conn = $db->getConnection();
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        // Delete order items, notes, and the order itself (cascading)
        try {
            $conn->beginTransaction();
            // Delete order notes
            $conn->prepare("DELETE FROM order_notes WHERE order_id IN ($placeholders)")->execute($orderIds);
            // Delete order items
            $conn->prepare("DELETE FROM order_item WHERE order_id IN ($placeholders)")->execute($orderIds);
            // Delete the order
            $conn->prepare("DELETE FROM orders WHERE order_id IN ($placeholders)")->execute($orderIds);
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
echo json_encode(['success' => false, 'message' => 'Invalid request']);
