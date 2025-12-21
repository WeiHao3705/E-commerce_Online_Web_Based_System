<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Please login to manage wishlist']);
    exit;
}

require_once __DIR__ . '/../database/connection.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? $_SESSION['user']->user_id;
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if wishlist table exists
    try {
        $checkTable = $conn->query("SHOW TABLES LIKE 'wishlist'");
        if ($checkTable->rowCount() === 0) {
            // Create wishlist table
            $createTable = "
                CREATE TABLE IF NOT EXISTS wishlist (
                    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    product_id INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    
                    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE,
                    
                    UNIQUE KEY unique_user_product (user_id, product_id),
                    
                    INDEX idx_user_id (user_id),
                    INDEX idx_product_id (product_id),
                    INDEX idx_created_at (created_at)
                )
            ";
            $conn->exec($createTable);
        }
    } catch (Exception $e) {
        error_log("Error checking/creating wishlist table: " . $e->getMessage());
    }
    
    switch ($action) {
        case 'add':
            $productId = $_POST['product_id'] ?? null;
            $variantId = isset($_POST['variant_id']) && $_POST['variant_id'] !== '' ? (int)$_POST['variant_id'] : null;
            
            if (!$productId) {
                echo json_encode(['success' => false, 'message' => 'Product ID is required']);
                exit;
            }
            
            // Check if product exists
            $checkProduct = $conn->prepare("SELECT product_id FROM product WHERE product_id = :product_id");
            $checkProduct->execute([':product_id' => $productId]);
            
            if (!$checkProduct->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit;
            }
            
            // Check stock quantity for the specific variant (if provided) or total product stock
            if ($variantId) {
                // Check stock for specific variant
                $stockQuery = "
                    SELECT COALESCE(SUM(i.stock_quantity), 0) as total_stock
                    FROM inventory i
                    WHERE i.variant_id = :variant_id
                ";
                $stockStmt = $conn->prepare($stockQuery);
                $stockStmt->execute([':variant_id' => $variantId]);
                $stockResult = $stockStmt->fetch(PDO::FETCH_ASSOC);
                $variantStock = (int)($stockResult['total_stock'] ?? 0);
                
                // Only allow adding to wishlist if this variant is out of stock
                if ($variantStock > 0) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'This variant is in stock. You can only add out-of-stock variants to your wishlist.'
                    ]);
                    exit;
                }
            } else {
                // If no variant specified, check total product stock
                $stockQuery = "
                    SELECT COALESCE(SUM(i.stock_quantity), 0) as total_stock
                    FROM inventory i
                    WHERE i.product_id = :product_id 
                       OR i.variant_id IN (
                           SELECT variant_id FROM product_variant WHERE product_id = :product_id
                       )
                ";
                $stockStmt = $conn->prepare($stockQuery);
                $stockStmt->execute([':product_id' => $productId]);
                $stockResult = $stockStmt->fetch(PDO::FETCH_ASSOC);
                $totalStock = (int)($stockResult['total_stock'] ?? 0);
                
                // Only allow adding to wishlist if stock is 0 (out of stock)
                if ($totalStock > 0) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'This item is in stock. You can only add out-of-stock items to your wishlist.'
                    ]);
                    exit;
                }
            }
            
            // Add to wishlist (ignore if already exists)
            $insertQuery = "INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (:user_id, :product_id)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->execute([
                ':user_id' => $userId,
                ':product_id' => $productId
            ]);
            
            // Get updated wishlist count
            $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = :user_id");
            $countStmt->execute([':user_id' => $userId]);
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Added to wishlist',
                'count' => $count
            ]);
            break;
            
        case 'remove':
            $productId = $_POST['product_id'] ?? null;
            
            if (!$productId) {
                echo json_encode(['success' => false, 'message' => 'Product ID is required']);
                exit;
            }
            
            $deleteQuery = "DELETE FROM wishlist WHERE user_id = :user_id AND product_id = :product_id";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->execute([
                ':user_id' => $userId,
                ':product_id' => $productId
            ]);
            
            // Get updated wishlist count
            $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = :user_id");
            $countStmt->execute([':user_id' => $userId]);
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Removed from wishlist',
                'count' => $count
            ]);
            break;
            
        case 'check':
            $productId = $_GET['product_id'] ?? null;
            
            if (!$productId) {
                echo json_encode(['success' => false, 'inWishlist' => false]);
                exit;
            }
            
            $checkQuery = "SELECT wishlist_id FROM wishlist WHERE user_id = :user_id AND product_id = :product_id";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->execute([
                ':user_id' => $userId,
                ':product_id' => $productId
            ]);
            
            $inWishlist = $checkStmt->fetch() !== false;
            
            echo json_encode([
                'success' => true,
                'inWishlist' => $inWishlist
            ]);
            break;
            
        case 'list':
            $listQuery = "
                SELECT 
                    w.wishlist_id, 
                    w.product_id, 
                    w.created_at,
                    p.product_name, 
                    p.description,
                    pp.original_price, 
                    pp.selling_price,
                    COALESCE((
                        SELECT pi.image_path
                        FROM product_image pi
                        WHERE pi.product_id = p.product_id 
                          AND pi.type = 'main'
                          AND pi.variant_id IS NULL
                        ORDER BY pi.id ASC
                        LIMIT 1
                    ), (
                        SELECT pi.image_path
                        FROM product_image pi
                        WHERE pi.product_id = p.product_id
                        ORDER BY CASE WHEN pi.type = 'main' THEN 0 ELSE 1 END, pi.id ASC
                        LIMIT 1
                    )) as image_path,
                    COALESCE((
                        SELECT SUM(i.stock_quantity)
                        FROM inventory i
                        WHERE i.product_id = p.product_id 
                           OR i.variant_id IN (
                               SELECT variant_id FROM product_variant WHERE product_id = p.product_id
                           )
                    ), 0) as stock_quantity
                FROM wishlist w
                JOIN product p ON w.product_id = p.product_id
                LEFT JOIN product_price pp ON p.product_id = pp.product_id
                WHERE w.user_id = :user_id
                GROUP BY w.wishlist_id, w.product_id, w.created_at, p.product_name, p.description, pp.original_price, pp.selling_price
                ORDER BY w.created_at DESC
            ";
            $listStmt = $conn->prepare($listQuery);
            $listStmt->execute([':user_id' => $userId]);
            $items = $listStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'items' => $items
            ]);
            break;
            
        case 'count':
            $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = :user_id");
            $countStmt->execute([':user_id' => $userId]);
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Wishlist Controller Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
