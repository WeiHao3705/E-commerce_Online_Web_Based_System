<?php

require_once __DIR__ . '/../repository/InventoryRepository.php';
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../helpers/ActivityLogger.php';

class InventoryService {
    private $repo;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->repo = new InventoryRepository($conn);
    }

    public function getRestockData() {
        $products = $this->repo->getProductsBasic();
        $variantsMap = [];
        $sizesByProduct = [];
        $sizesByVariant = [];
        foreach ($products as $p) {
            $pid = (int)$p['product_id'];
            $variants = $this->repo->getVariantsByProduct($pid);
            $variantsMap[$pid] = $variants;
            $sizesByProduct[$pid] = $this->repo->getSizesForProduct($pid);
            foreach ($variants as $v) {
                $vid = (int)$v['variant_id'];
                $sizesByVariant[$vid] = $this->repo->getSizesForVariant($vid);
            }
        }
        return [
            'products' => $products,
            'variantsMap' => $variantsMap,
            'sizesByProduct' => $sizesByProduct,
            'sizesByVariant' => $sizesByVariant,
        ];
    }

    public function restock($productId, $variantId, $size, $quantity, $requiresSize = true) {
        if (!is_numeric($quantity) || (int)$quantity <= 0) {
            throw new Exception('Quantity must be a positive integer.');
        }
        $productId = $productId !== null ? (int)$productId : null;
        $variantId = $variantId !== null && $variantId !== '' ? (int)$variantId : null;
        $size = trim($size);

        if ($requiresSize) {
            if ($size === '') {
                throw new Exception('Size is required for this product.');
            }
        } else {
            // Products without sizes: use a default placeholder value to satisfy NOT NULL
            if ($size === '') {
                $size = 'default';
            }
        }

        // Check total stock before restocking
        $stockBefore = $this->getProductTotalStock($productId);
        $wasOutOfStock = $stockBefore <= 0;
        
        // Also check if the specific variant being restocked was out of stock
        $variantWasOutOfStock = false;
        $variantStockBefore = 0;
        if ($variantId) {
            $variantStockBefore = $this->getVariantStock($variantId);
            $variantWasOutOfStock = $variantStockBefore <= 0;
        }
        
        // Check if the SPECIFIC size being restocked was out of stock
        // This is the most important check - a specific size can be out of stock even if other sizes have stock
        $sizeWasOutOfStock = false;
        $sizeStockBefore = 0;
        $sizeStockBefore = $this->getSizeStock($productId, $variantId, $size);
        $sizeWasOutOfStock = $sizeStockBefore <= 0;
        
        // Notify if product OR variant OR specific size was out of stock
        $shouldNotify = $wasOutOfStock || $variantWasOutOfStock || $sizeWasOutOfStock;
        
        // Check wishlist count (total, including inactive members for reference)
        $wishlistCount = 0;
        $wishlistCountAll = 0;
        if ($productId) {
            // Count all wishlist entries
            $wishlistCountAllSql = "SELECT COUNT(*) as count FROM wishlist WHERE product_id = :product_id";
            $wishlistAllStmt = $this->conn->prepare($wishlistCountAllSql);
            $wishlistAllStmt->execute([':product_id' => $productId]);
            $wishlistCountAll = $wishlistAllStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // Count only active members (this is what will be notified)
            $wishlistCountSql = "SELECT COUNT(*) as count 
                                 FROM wishlist w
                                 JOIN users u ON w.user_id = u.user_id
                                 WHERE w.product_id = :product_id
                                 AND u.role = 'member'
                                 AND u.status = 'active'";
            $wishlistStmt = $this->conn->prepare($wishlistCountSql);
            $wishlistStmt->execute([':product_id' => $productId]);
            $wishlistCount = $wishlistStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            error_log("InventoryService: Restock - Product ID: {$productId}, Variant ID: " . ($variantId ?? 'NULL') . ", Size: {$size}, Total wishlist entries: {$wishlistCountAll}, Active members: {$wishlistCount}");
            error_log("InventoryService: Stock before - Total: {$stockBefore}, Variant: {$variantStockBefore}, Size '{$size}': {$sizeStockBefore}");
            error_log("InventoryService: Out of stock - Product: " . ($wasOutOfStock ? 'yes' : 'no') . ", Variant: " . ($variantWasOutOfStock ? 'yes' : 'no') . ", Size: " . ($sizeWasOutOfStock ? 'yes' : 'no') . ", Should notify: " . ($shouldNotify ? 'yes' : 'no'));
        }

        $this->conn->beginTransaction();
        try {
            $this->repo->upsertInventory($productId, $variantId, $size, (int)$quantity);
            $this->conn->commit();
            
            // Get stock after restocking for logging
            $stockAfter = $this->getProductTotalStock($productId);
            
            // Log restock action only if current user is admin
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (isset($_SESSION['user']) && $_SESSION['user']->role === 'admin') {
                ActivityLogger::logInventoryRestock($productId, $variantId, $size, (int)$quantity, $stockBefore, $stockAfter);
            }
            
            // If product or variant was out of stock before restocking, notify wishlist members
            // Do this AFTER commit to ensure data is saved
            $notificationResult = null;
            if ($shouldNotify && $productId) {
                // Use a separate try-catch so notification errors don't affect restock success
                try {
                    $notificationResult = $this->notifyWishlistMembers($productId);
                    $notificationResult['was_out_of_stock'] = $wasOutOfStock;
                    $notificationResult['variant_was_out_of_stock'] = $variantWasOutOfStock;
                    $notificationResult['size_was_out_of_stock'] = $sizeWasOutOfStock;
                    $notificationResult['stock_before'] = $stockBefore;
                    $notificationResult['variant_stock_before'] = $variantStockBefore;
                    $notificationResult['size_stock_before'] = $sizeStockBefore;
                } catch (Exception $notifyError) {
                    $notificationResult = [
                        'success' => false,
                        'error' => $notifyError->getMessage(),
                        'notified' => 0,
                        'total' => $wishlistCount,
                        'was_out_of_stock' => $wasOutOfStock,
                        'variant_was_out_of_stock' => $variantWasOutOfStock,
                        'size_was_out_of_stock' => $sizeWasOutOfStock,
                        'stock_before' => $stockBefore,
                        'variant_stock_before' => $variantStockBefore,
                        'size_stock_before' => $sizeStockBefore
                    ];
                    error_log("InventoryService: Exception during notifyWishlistMembers: " . $notifyError->getMessage());
                    error_log("InventoryService: Exception trace: " . $notifyError->getTraceAsString());
                }
            } else if ($productId && $wishlistCount > 0) {
                // Product and variant were not out of stock, but has wishlist members - store info for debugging
                    $stockInfo = "Total stock: {$stockBefore}";
                    if ($variantId) {
                        $stockInfo .= ", Variant stock: {$variantStockBefore}";
                    }
                    $stockInfo .= ", Size '{$size}' stock: {$sizeStockBefore}";
                    
                    $notificationResult = [
                        'success' => false,
                        'notified' => 0,
                        'total' => $wishlistCount,
                        'error' => "Product was not out of stock ({$stockInfo}), so notifications were not sent. Notifications are only sent when restocking brings a product, variant, or specific size back in stock.",
                        'was_out_of_stock' => false,
                        'variant_was_out_of_stock' => false,
                        'size_was_out_of_stock' => false,
                        'stock_before' => $stockBefore,
                        'variant_stock_before' => $variantStockBefore,
                        'size_stock_before' => $sizeStockBefore
                    ];
            }
            
            // Store notification result in session (always store if there's a result, even if product wasn't out of stock)
            // This ensures admins can see notification status
            if ($notificationResult !== null) {
                if (!isset($_SESSION)) {
                    session_start();
                }
                $_SESSION['restock_notification'] = $notificationResult;
                error_log("InventoryService: Stored notification result - total: {$notificationResult['total']}, notified: {$notificationResult['notified']}, was_out_of_stock: " . ($notificationResult['was_out_of_stock'] ?? 'N/A'));
            }
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get total stock quantity for a product
     */
    private function getProductTotalStock($productId) {
        $sql = "
            SELECT COALESCE(SUM(i.stock_quantity), 0) as total_stock
            FROM inventory i
            WHERE i.product_id = :product_id 
               OR i.variant_id IN (
                   SELECT variant_id FROM product_variant WHERE product_id = :product_id
               )
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':product_id' => $productId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total_stock'] ?? 0);
    }

    /**
     * Get total stock quantity for a specific variant
     */
    private function getVariantStock($variantId) {
        $sql = "
            SELECT COALESCE(SUM(i.stock_quantity), 0) as total_stock
            FROM inventory i
            WHERE i.variant_id = :variant_id
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':variant_id' => $variantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total_stock'] ?? 0);
    }

    /**
     * Get stock quantity for a specific product/variant/size combination
     * This is the most granular stock check - checks if a specific size is out of stock
     */
    private function getSizeStock($productId, $variantId, $size) {
        $sql = "SELECT COALESCE(SUM(i.stock_quantity), 0) as stock
                FROM inventory i
                WHERE i.size = :size";
        
        if ($variantId !== null) {
            $sql .= " AND i.variant_id = :variant_id";
        } else {
            $sql .= " AND i.variant_id IS NULL";
        }
        
        if ($productId !== null) {
            $sql .= " AND i.product_id = :product_id";
        } else {
            $sql .= " AND i.product_id IS NULL";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':size', $size);
        if ($variantId !== null) {
            $stmt->bindValue(':variant_id', (int)$variantId, PDO::PARAM_INT);
        }
        if ($productId !== null) {
            $stmt->bindValue(':product_id', (int)$productId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['stock'] ?? 0);
    }

    /**
     * Get or create system user for sending system notifications
     * Returns a dedicated system user_id that will be used to send system messages
     * The message will be displayed as 'System' based on sender_role logic
     * 
     * IMPORTANT: This method MUST always return the system user ID, never the logged-in admin's ID
     */
    private function getOrCreateSystemUser() {
        // First, try to find an existing system user (username = 'system')
        // Use exact match to ensure we get the system user, not any admin
        $sql = "SELECT user_id, username, full_name FROM users WHERE username = 'system' AND role = 'admin' LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $systemUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($systemUser && isset($systemUser['user_id'])) {
            $systemUserId = (int)$systemUser['user_id'];
            // Double-check it's actually the system user
            if ($systemUser['username'] === 'system') {
                error_log("InventoryService: Using existing system user (ID: {$systemUserId})");
                return $systemUserId;
            }
        }

        // If no system user exists, create one
        // This is a dedicated system user that will be used for all system notifications
        error_log("InventoryService: System user not found, creating new system user");
        $sql = "INSERT INTO users (username, full_name, email, password, role, status, email_verified, DateOfBirth, gender) 
                VALUES ('system', 'System', 'system@ngear.com', :password, 'admin', 'active', 1, '2000-01-01', 'Other')";
        $stmt = $this->conn->prepare($sql);
        // Use a random password that will never be used for login
        $hashedPassword = password_hash(uniqid('system_', true), PASSWORD_DEFAULT);
        $stmt->execute([':password' => $hashedPassword]);
        $systemUserId = (int)$this->conn->lastInsertId();
        error_log("InventoryService: Created new system user (ID: {$systemUserId})");
        return $systemUserId;
    }

    /**
     * Get all members who have a product in their wishlist
     */
    private function getWishlistMembers($productId) {
        $sql = "
            SELECT DISTINCT w.user_id, u.full_name, u.username
            FROM wishlist w
            JOIN users u ON w.user_id = u.user_id
            WHERE w.product_id = :product_id
            AND u.role = 'member'
            AND u.status = 'active'
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':product_id' => $productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Notify wishlist members when a product is restocked
     * @return array Notification result with success status and counts
     */
    private function notifyWishlistMembers($productId) {
        $result = [
            'success' => false,
            'notified' => 0,
            'total' => 0,
            'error' => null
        ];
        
        try {
            // Get product name
            $sql = "SELECT product_name FROM product WHERE product_id = :product_id LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':product_id' => $productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                $result['error'] = 'Product not found';
                return $result;
            }

            $productName = $product['product_name'];
            
            // Get all members who have this product in their wishlist
            $members = $this->getWishlistMembers($productId);
            $result['total'] = count($members);
            
            error_log("InventoryService: Found {$result['total']} wishlist member(s) for product {$productId}");
            
            if (empty($members)) {
                error_log("InventoryService: No wishlist members to notify for product {$productId}");
                $result['success'] = true; // Success, just no one to notify
                return $result;
            }
            
            // Log member details for debugging
            foreach ($members as $member) {
                error_log("InventoryService: Wishlist member - ID: {$member['user_id']}, Name: {$member['full_name']}, Username: {$member['username']}");
            }

            // Get or create system user
            // CRITICAL: Always use system user, never use logged-in admin's ID
            try {
                $systemUserId = $this->getOrCreateSystemUser();
                if (!$systemUserId || $systemUserId <= 0) {
                    error_log("InventoryService: Failed to get system user - invalid ID: {$systemUserId}");
                    $result['error'] = 'Failed to get or create system user for notifications';
                    return $result;
                }
                
                // Verify the system user exists and is correct
                $verifySql = "SELECT user_id, username, full_name FROM users WHERE user_id = ? AND username = 'system' AND role = 'admin' LIMIT 1";
                $verifyStmt = $this->conn->prepare($verifySql);
                $verifyStmt->execute([$systemUserId]);
                $verifiedUser = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$verifiedUser || $verifiedUser['username'] !== 'system') {
                    error_log("InventoryService: System user verification failed. Expected system user ID {$systemUserId}, but got: " . json_encode($verifiedUser));
                    $result['error'] = 'System user verification failed. Please ensure system user exists in database.';
                    return $result;
                }
                
                error_log("InventoryService: Verified system user (ID: {$systemUserId}, Username: {$verifiedUser['username']})");
            } catch (Exception $e) {
                error_log("InventoryService: Exception getting system user: " . $e->getMessage());
                $result['error'] = 'Failed to get system user: ' . $e->getMessage();
                return $result;
            }
            
            // Get chat repository and service
            require_once __DIR__ . '/../repository/ChatRepository.php';
            require_once __DIR__ . '/ChatService.php';
            require_once __DIR__ . '/../database/connection.php';
            try {
                $db = new Database();
                $chatRepository = new ChatRepository($db);
            } catch (Exception $e) {
                $result['error'] = 'Failed to initialize chat repository: ' . $e->getMessage();
                return $result;
            }
            
            // Send notification to each member
            $notifiedCount = 0;
            $errors = [];
            foreach ($members as $member) {
                try {
                    error_log("InventoryService: Processing notification for member {$member['user_id']} ({$member['full_name']})");
                    
                    // Get or create chat room for system message (checks both open and closed chatrooms)
                    // This will assign the chatroom to the system user so it shows as assigned
                    $chatRoomId = $chatRepository->getOrCreateChatRoomForSystemMessage($member['user_id'], $systemUserId);
                    
                    if (!$chatRoomId || $chatRoomId === 0 || $chatRoomId === '0') {
                        $errorMsg = "Failed to create/get chat room for member {$member['user_id']} ({$member['full_name']}). Got chatRoomId: " . var_export($chatRoomId, true);
                        error_log("InventoryService: ERROR - " . $errorMsg);
                        $errors[] = $errorMsg;
                        continue;
                    }
                    
                    // Ensure chatRoomId is an integer
                    $chatRoomId = (int)$chatRoomId;
                    error_log("InventoryService: Got chatroom ID {$chatRoomId} for member {$member['user_id']}");
                    
                    // Note: getOrCreateChatRoomForSystemMessage already verifies the chatroom was created
                    // and handles assignment to system user and reopening if closed.
                    // We trust its return value and proceed directly to sending the message.
                    // If there's an issue, addMessage will throw an exception which we'll catch below.
                    
                    // Create notification message
                    $message = "Good news! The item \"{$productName}\" from your wishlist is now back in stock. You can add it to your cart now!";
                    
                    // CRITICAL: Send message as system user ONLY (never use logged-in admin's ID)
                    // Directly to repository to bypass role checks and ensure system user is used
                    error_log("InventoryService: Attempting to send restock notification to member {$member['user_id']} from system user {$systemUserId} in chatroom {$chatRoomId}");
                    
                    try {
                        $messageId = $chatRepository->addMessage($chatRoomId, $systemUserId, $message);
                        
                        if (!$messageId || $messageId === 0 || $messageId === '0') {
                            $errorMsg = "addMessage returned invalid message ID for member {$member['user_id']} ({$member['full_name']}). Got: " . var_export($messageId, true);
                            error_log("InventoryService: ERROR - " . $errorMsg);
                            $errors[] = $errorMsg;
                            continue;
                        }
                        
                        error_log("InventoryService: Message inserted successfully with message_id {$messageId} for member {$member['user_id']}");
                        $notifiedCount++;
                    } catch (Exception $msgException) {
                        $errorMsg = "Failed to insert message for member {$member['user_id']} ({$member['full_name']}): " . $msgException->getMessage();
                        error_log("InventoryService: EXCEPTION - " . $errorMsg);
                        error_log("InventoryService: Exception trace: " . $msgException->getTraceAsString());
                        $errors[] = $errorMsg;
                        continue;
                    }
                } catch (Exception $e) {
                    $memberName = isset($member['full_name']) ? $member['full_name'] : 'Unknown';
                    $errors[] = "Member {$member['user_id']} ({$memberName}): " . $e->getMessage();
                }
            }
            
            $result['success'] = $notifiedCount > 0;
            $result['notified'] = $notifiedCount;
            
            error_log("InventoryService: Notification complete - Total members: {$result['total']}, Notified: {$notifiedCount}, Errors: " . count($errors));
            
            // Build error message
            if (!empty($errors)) {
                $result['error'] = implode('; ', $errors);
                error_log("InventoryService: Notification errors: " . $result['error']);
            } else if ($notifiedCount == 0 && $result['total'] > 0) {
                $result['error'] = 'No members were notified, but no specific errors were captured. This might indicate a database or permission issue. Please check server logs for details.';
                error_log("InventoryService: WARNING - No members notified despite {$result['total']} members in wishlist. No errors captured.");
            } else if ($notifiedCount == 0 && $result['total'] == 0) {
                // This shouldn't happen, but just in case
                $result['error'] = 'No members found in wishlist';
                error_log("InventoryService: No wishlist members found for product {$productId}");
            }
            
            return $result;
        } catch (Exception $e) {
            $result['error'] = $e->getMessage() ?: 'An exception occurred';
            return $result;
        } catch (Error $e) {
            $result['error'] = 'Fatal error: ' . $e->getMessage();
            return $result;
        }
    }
}
