<?php
session_start();

require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../repository/InventoryRepository.php';

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();
    $inventoryRepo = new InventoryRepository($conn);
    
    $action = $_GET['action'] ?? 'getStock';
    
    if ($action === 'getStock') {
        $variantId = isset($_GET['variant_id']) && $_GET['variant_id'] !== '' ? (int)$_GET['variant_id'] : null;
        $size = isset($_GET['size']) && $_GET['size'] !== '' ? trim($_GET['size']) : null;
        
        if (!$variantId || !$size) {
            echo json_encode(['success' => false, 'message' => 'Variant ID and size are required']);
            exit;
        }
        
        $stock = $inventoryRepo->getStockByVariantAndSize($variantId, $size);
        
        echo json_encode([
            'success' => true,
            'stock' => $stock
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Stock Controller Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

