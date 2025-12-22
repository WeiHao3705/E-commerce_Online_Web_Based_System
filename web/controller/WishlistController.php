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
                    variant_id INT NULL,
                    size VARCHAR(20) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    
                    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE,
                    FOREIGN KEY (variant_id) REFERENCES product_variant(variant_id) ON DELETE CASCADE,
                    
                    UNIQUE KEY unique_user_product_variant_size (user_id, product_id, variant_id, size),
                    
                    INDEX idx_user_id (user_id),
                    INDEX idx_product_id (product_id),
                    INDEX idx_variant_id (variant_id),
                    INDEX idx_size (size),
                    INDEX idx_created_at (created_at)
                )
            ";
            $conn->exec($createTable);
            
            // Add variant_id and size columns if they don't exist (for existing tables)
            try {
                // Check and add variant_id column
                $checkVariantColumn = $conn->query("SHOW COLUMNS FROM wishlist LIKE 'variant_id'");
                if ($checkVariantColumn->rowCount() === 0) {
                    // Check if unique_user_product index exists before dropping
                    $checkIndex = $conn->query("SHOW INDEX FROM wishlist WHERE Key_name = 'unique_user_product'");
                    if ($checkIndex->rowCount() > 0) {
                        $conn->exec("ALTER TABLE wishlist DROP INDEX unique_user_product");
                    }
                    
                    // Check if unique_user_product_variant index exists before dropping
                    $checkIndex2 = $conn->query("SHOW INDEX FROM wishlist WHERE Key_name = 'unique_user_product_variant'");
                    if ($checkIndex2->rowCount() > 0) {
                        $conn->exec("ALTER TABLE wishlist DROP INDEX unique_user_product_variant");
                    }
                    
                    $conn->exec("ALTER TABLE wishlist ADD COLUMN variant_id INT NULL AFTER product_id");
                    $conn->exec("ALTER TABLE wishlist ADD FOREIGN KEY (variant_id) REFERENCES product_variant(variant_id) ON DELETE CASCADE");
                    $conn->exec("ALTER TABLE wishlist ADD INDEX idx_variant_id (variant_id)");
                }
                
                // Check and add size column
                $checkSizeColumn = $conn->query("SHOW COLUMNS FROM wishlist LIKE 'size'");
                if ($checkSizeColumn->rowCount() === 0) {
                    // Drop old unique constraint if exists
                    $checkIndex3 = $conn->query("SHOW INDEX FROM wishlist WHERE Key_name = 'unique_user_product_variant'");
                    if ($checkIndex3->rowCount() > 0) {
                        $conn->exec("ALTER TABLE wishlist DROP INDEX unique_user_product_variant");
                    }
                    
                    $conn->exec("ALTER TABLE wishlist ADD COLUMN size VARCHAR(20) NULL AFTER variant_id");
                    $conn->exec("ALTER TABLE wishlist ADD INDEX idx_size (size)");
                    $conn->exec("ALTER TABLE wishlist ADD UNIQUE KEY unique_user_product_variant_size (user_id, product_id, variant_id, size)");
                } else {
                    // If size exists but unique constraint doesn't include it, update it
                    $checkUniqueIndex = $conn->query("SHOW INDEX FROM wishlist WHERE Key_name = 'unique_user_product_variant_size'");
                    if ($checkUniqueIndex->rowCount() === 0) {
                        // Drop old unique constraint
                        $checkOldIndex = $conn->query("SHOW INDEX FROM wishlist WHERE Key_name = 'unique_user_product_variant'");
                        if ($checkOldIndex->rowCount() > 0) {
                            $conn->exec("ALTER TABLE wishlist DROP INDEX unique_user_product_variant");
                        }
                        $conn->exec("ALTER TABLE wishlist ADD UNIQUE KEY unique_user_product_variant_size (user_id, product_id, variant_id, size)");
                    }
                }
            } catch (Exception $e) {
                error_log("Error adding variant_id/size columns: " . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        error_log("Error checking/creating wishlist table: " . $e->getMessage());
    }
    
    switch ($action) {
        // Adds product to user wishlist
        case 'add':
            $productId = $_POST['product_id'] ?? null;
            $variantId = isset($_POST['variant_id']) && $_POST['variant_id'] !== '' ? (int)$_POST['variant_id'] : null;
            $size = isset($_POST['size']) && $_POST['size'] !== '' ? trim($_POST['size']) : null;
            
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
            
            // Validate variant_id if provided
            if ($variantId) {
                $checkVariant = $conn->prepare("SELECT variant_id FROM product_variant WHERE variant_id = :variant_id AND product_id = :product_id");
                $checkVariant->execute([':variant_id' => $variantId, ':product_id' => $productId]);
                if (!$checkVariant->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Invalid variant for this product']);
                    exit;
                }
            }
            
            // Add to wishlist (ignore if already exists)
            // Allow adding any product to wishlist, regardless of stock status or variant/size
            $insertQuery = "INSERT IGNORE INTO wishlist (user_id, product_id, variant_id, size) VALUES (:user_id, :product_id, :variant_id, :size)";
            $insertStmt = $conn->prepare($insertQuery);
            $insertStmt->execute([
                ':user_id' => $userId,
                ':product_id' => $productId,
                ':variant_id' => $variantId,
                ':size' => $size
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
            
        // Removes product from user wishlist
        case 'remove':
            $productId = $_POST['product_id'] ?? null;
            $wishlistId = $_POST['wishlist_id'] ?? null;
            
            if (!$productId && !$wishlistId) {
                echo json_encode(['success' => false, 'message' => 'Product ID or Wishlist ID is required']);
                exit;
            }
            
            if ($wishlistId) {
                // Remove by wishlist_id (more specific)
                $deleteQuery = "DELETE FROM wishlist WHERE user_id = :user_id AND wishlist_id = :wishlist_id";
                $deleteStmt = $conn->prepare($deleteQuery);
                $deleteStmt->execute([
                    ':user_id' => $userId,
                    ':wishlist_id' => $wishlistId
                ]);
            } else {
                // Remove by product_id (for backward compatibility)
                $deleteQuery = "DELETE FROM wishlist WHERE user_id = :user_id AND product_id = :product_id";
                $deleteStmt = $conn->prepare($deleteQuery);
                $deleteStmt->execute([
                    ':user_id' => $userId,
                    ':product_id' => $productId
                ]);
            }
            
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
            
        // Checks if product is in user wishlist
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
            
        // Lists all products in user wishlist
        case 'list':
            $listQuery = "
                SELECT 
                    w.wishlist_id, 
                    w.product_id,
                    w.variant_id,
                    w.size,
                    w.created_at,
                    p.product_name, 
                    p.description,
                    pp.original_price, 
                    pp.selling_price,
                    pv.color as variant_color,
                    COALESCE((
                        SELECT pi.image_path
                        FROM product_image pi
                        WHERE pi.product_id = p.product_id 
                          AND (pi.variant_id = w.variant_id OR (w.variant_id IS NULL AND pi.variant_id IS NULL))
                          AND pi.type = 'main'
                        ORDER BY CASE WHEN pi.variant_id = w.variant_id THEN 0 ELSE 1 END, pi.id ASC
                        LIMIT 1
                    ), (
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
                    CASE 
                        WHEN w.variant_id IS NOT NULL AND w.size IS NOT NULL THEN
                            -- Product WITH variant AND specific size
                            COALESCE((
                                SELECT SUM(i.stock_quantity)
                                FROM inventory i
                                WHERE i.variant_id = w.variant_id AND i.size = w.size
                            ), 0)
                        WHEN w.variant_id IS NOT NULL THEN
                            -- Product WITH variant, NO specific size
                            COALESCE((
                                SELECT SUM(i.stock_quantity)
                                FROM inventory i
                                WHERE i.variant_id = w.variant_id
                            ), 0)
                        WHEN w.size IS NOT NULL THEN
                            -- Product WITHOUT variant, WITH specific size
                            COALESCE((
                                SELECT SUM(i.stock_quantity)
                                FROM inventory i
                                WHERE i.product_id = p.product_id 
                                  AND i.variant_id IS NULL 
                                  AND i.size = w.size
                            ), 0)
                        ELSE
                            -- Product WITHOUT variant, NO specific size (check all product stock)
                            COALESCE((
                                SELECT SUM(i.stock_quantity)
                                FROM inventory i
                                WHERE i.product_id = p.product_id 
                                   OR i.variant_id IN (
                                       SELECT variant_id FROM product_variant WHERE product_id = p.product_id
                                   )
                            ), 0)
                    END as stock_quantity
                FROM wishlist w
                JOIN product p ON w.product_id = p.product_id
                LEFT JOIN product_price pp ON p.product_id = pp.product_id
                LEFT JOIN product_variant pv ON w.variant_id = pv.variant_id
                WHERE w.user_id = :user_id
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
            
        // Returns total count of items in user wishlist
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
