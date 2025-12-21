<?php

require_once __DIR__ . '/../repository/InventoryRepository.php';
require_once __DIR__ . '/../database/connection.php';

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

    public function restock($productId, $variantId, $size, $quantity) {
        if (!is_numeric($quantity) || (int)$quantity <= 0) {
            throw new Exception('Quantity must be a positive integer.');
        }
        $productId = $productId !== null ? (int)$productId : null;
        $variantId = $variantId !== null && $variantId !== '' ? (int)$variantId : null;
        $size = trim($size);
        if ($size === '') {
            throw new Exception('Size is required.');
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
        
        // Notify if product OR variant was out of stock
        $shouldNotify = $wasOutOfStock || $variantWasOutOfStock;
        
        // Check wishlist count
        $wishlistCount = 0;
        if ($productId) {
            $wishlistCountSql = "SELECT COUNT(*) as count FROM wishlist WHERE product_id = :product_id";
            $wishlistStmt = $this->conn->prepare($wishlistCountSql);
            $wishlistStmt->execute([':product_id' => $productId]);
            $wishlistCount = $wishlistStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        }

        $this->conn->beginTransaction();
        try {
            $this->repo->upsertInventory($productId, $variantId, $size, (int)$quantity);
            $this->conn->commit();
            
            // If product or variant was out of stock before restocking, notify wishlist members
            // Do this AFTER commit to ensure data is saved
            $notificationResult = null;
            if ($shouldNotify && $productId) {
                // Use a separate try-catch so notification errors don't affect restock success
                try {
                    $notificationResult = $this->notifyWishlistMembers($productId);
                    $notificationResult['was_out_of_stock'] = $wasOutOfStock;
                    $notificationResult['variant_was_out_of_stock'] = $variantWasOutOfStock;
                    $notificationResult['stock_before'] = $stockBefore;
                    $notificationResult['variant_stock_before'] = $variantStockBefore;
                } catch (Exception $notifyError) {
                    $notificationResult = [
                        'success' => false,
                        'error' => $notifyError->getMessage(),
                        'notified' => 0,
                        'total' => $wishlistCount,
                        'was_out_of_stock' => $wasOutOfStock,
                        'variant_was_out_of_stock' => $variantWasOutOfStock,
                        'stock_before' => $stockBefore,
                        'variant_stock_before' => $variantStockBefore
                    ];
                }
            } else if ($productId && $wishlistCount > 0) {
                // Product and variant were not out of stock, but has wishlist members - store info for debugging
                $notificationResult = [
                    'success' => false,
                    'notified' => 0,
                    'total' => $wishlistCount,
                    'error' => 'Product was not out of stock (total stock: ' . $stockBefore . ($variantId ? ', variant stock: ' . $variantStockBefore : '') . '), so notifications were not sent. Notifications are only sent when restocking brings a product or variant back in stock.',
                    'was_out_of_stock' => false,
                    'variant_was_out_of_stock' => false,
                    'stock_before' => $stockBefore,
                    'variant_stock_before' => $variantStockBefore
                ];
            }
            
            // Store notification result in session (only if there's something to report)
            if ($notificationResult !== null) {
                if (!isset($_SESSION)) {
                    session_start();
                }
                $_SESSION['restock_notification'] = $notificationResult;
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
            
            if (empty($members)) {
                $result['success'] = true; // Success, just no one to notify
                return $result;
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
                    // Get or create chat room for system message (checks both open and closed chatrooms)
                    // This will assign the chatroom to the system user so it shows as assigned
                    $chatRoomId = $chatRepository->getOrCreateChatRoomForSystemMessage($member['user_id'], $systemUserId);
                    
                    if (!$chatRoomId) {
                        $errors[] = "Failed to create/get chat room for member {$member['user_id']} ({$member['full_name']})";
                        continue;
                    }
                    
                    // Verify chat room exists and is accessible
                    $chatRoom = $chatRepository->getChatRoomById($chatRoomId, $systemUserId);
                    if (!$chatRoom) {
                        $errors[] = "Chat room {$chatRoomId} not found for member {$member['user_id']} ({$member['full_name']})";
                        continue;
                    }
                    
                    // Ensure chatroom is assigned to system user (so it shows as assigned, not unassigned)
                    if ($chatRoom['admin_id'] != $systemUserId) {
                        $assignAdminSql = "UPDATE chat_room SET admin_id = ? WHERE chat_room_id = ?";
                        $assignAdminStmt = $this->conn->prepare($assignAdminSql);
                        $assignAdminStmt->execute([$systemUserId, $chatRoomId]);
                        error_log("InventoryService: Assigned chatroom {$chatRoomId} to system user {$systemUserId}");
                        // Refresh chatroom data
                        $chatRoom = $chatRepository->getChatRoomById($chatRoomId, $systemUserId);
                    }
                    
                    // Ensure chat room is open (getOrCreateChatRoomForSystemMessage should handle this, but double-check)
                    if ($chatRoom['status'] === 'closed') {
                        $reopened = $chatRepository->reopenChatRoom($chatRoomId);
                        if (!$reopened) {
                            $errors[] = "Failed to reopen closed chat room {$chatRoomId} for member {$member['user_id']}";
                            continue;
                        }
                    }
                    
                    // Create notification message
                    $message = "Good news! The item \"{$productName}\" from your wishlist is now back in stock. You can add it to your cart now!";
                    
                    // CRITICAL: Send message as system user ONLY (never use logged-in admin's ID)
                    // Directly to repository to bypass role checks and ensure system user is used
                    error_log("InventoryService: Sending restock notification to member {$member['user_id']} from system user {$systemUserId} in chatroom {$chatRoomId}");
                    $messageId = $chatRepository->addMessage($chatRoomId, $systemUserId, $message);
                    
                    // Verify the message was sent with the correct sender_id
                    if ($messageId) {
                        $verifyMsgSql = "SELECT sender_id FROM chat_message WHERE message_id = ? LIMIT 1";
                        $verifyMsgStmt = $this->conn->prepare($verifyMsgSql);
                        $verifyMsgStmt->execute([$messageId]);
                        $sentMessage = $verifyMsgStmt->fetch(PDO::FETCH_ASSOC);
                        if ($sentMessage && (int)$sentMessage['sender_id'] !== $systemUserId) {
                            error_log("InventoryService: ERROR - Message sent with wrong sender_id. Expected {$systemUserId}, got {$sentMessage['sender_id']}");
                        } else {
                            error_log("InventoryService: Message sent successfully with system user ID {$systemUserId}");
                        }
                    }
                    
                    if (!$messageId) {
                        $errors[] = "Failed to insert message for member {$member['user_id']} ({$member['full_name']})";
                        continue;
                    }
                    $notifiedCount++;
                } catch (Exception $e) {
                    $memberName = isset($member['full_name']) ? $member['full_name'] : 'Unknown';
                    $errors[] = "Member {$member['user_id']} ({$memberName}): " . $e->getMessage();
                }
            }
            
            $result['success'] = $notifiedCount > 0;
            $result['notified'] = $notifiedCount;
            
            // Build error message
            if (!empty($errors)) {
                $result['error'] = implode('; ', $errors);
            } else if ($notifiedCount == 0 && $result['total'] > 0) {
                $result['error'] = 'No members were notified, but no specific errors were captured. This might indicate a database or permission issue.';
            } else if ($notifiedCount == 0 && $result['total'] == 0) {
                // This shouldn't happen, but just in case
                $result['error'] = 'No members found in wishlist';
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
