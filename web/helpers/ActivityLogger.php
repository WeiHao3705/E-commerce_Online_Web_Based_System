<?php

require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../repository/ActivityLogRepository.php';
require_once __DIR__ . '/../service/ActivityLogService.php';

class ActivityLogger {
    private static $service = null;

    private static function getService(): ActivityLogService
    {
        if (self::$service === null) {
            $database = new Database();
            $repository = new ActivityLogRepository($database);
            self::$service = new ActivityLogService($repository);
        }
        return self::$service;
    }

    /**
     * Get current admin ID from session
     * Only returns ID if the user is actually an admin
     */
    private static function getAdminId(): ?int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user']) && isset($_SESSION['user']->user_id)) {
            // Only return ID if user is an admin
            $role = $_SESSION['user']->role ?? (is_object($_SESSION['user']) ? $_SESSION['user']->role : '');
            if ($role === 'admin') {
                return (int)$_SESSION['user']->user_id;
            }
        }
        
        return null;
    }

    /**
     * Get IP address from request
     */
    private static function getIpAddress(): ?string
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Get user agent from request
     */
    private static function getUserAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Main method to log admin actions
     * 
     * @param string $actionType Action type (e.g., 'admin_create', 'admin_update')
     * @param string $entityType Entity type (e.g., 'admin', 'product', 'order')
     * @param string $actionDescription Human-readable description
     * @param int|null $entityId ID of the affected entity
     * @param array|null $oldValues Previous state (for updates)
     * @param array|null $newValues New state (for creates/updates)
     * @param int|null $adminId Optional admin ID (uses session if not provided)
     * @return bool Success status
     */
    public static function logAction(
        string $actionType,
        string $entityType,
        string $actionDescription,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $adminId = null
    ): bool {
        try {
            $adminId = $adminId ?? self::getAdminId();
            
            if ($adminId === null) {
                // Can't log without admin ID
                error_log("ActivityLogger: No admin ID available for logging action: $actionType");
                return false;
            }

            // Remove sensitive data from values before logging
            if ($oldValues !== null) {
                $oldValues = self::sanitizeData($oldValues);
            }
            if ($newValues !== null) {
                $newValues = self::sanitizeData($newValues);
            }

            $service = self::getService();
            return $service->logAdminAction(
                $adminId,
                $actionType,
                $entityType,
                $actionDescription,
                $entityId,
                $oldValues,
                $newValues,
                self::getIpAddress(),
                self::getUserAgent()
            );
        } catch (Exception $e) {
            error_log("ActivityLogger Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove sensitive data from arrays before logging
     */
    private static function sanitizeData(array $data): array
    {
        $sensitiveFields = ['password', 'password_hash', 'security_answer', 'token', 'api_key', 'secret'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }
        
        return $data;
    }

    /**
     * Convenience methods for common actions
     */
    public static function logAdminCreate(int $adminId, string $username): bool
    {
        return self::logAction(
            'admin_create',
            'admin',
            "Created new admin: $username",
            $adminId
        );
    }

    public static function logAdminUpdate(int $adminId, string $username, ?array $oldValues, ?array $newValues): bool
    {
        return self::logAction(
            'admin_update',
            'admin',
            "Updated admin: $username",
            $adminId,
            $oldValues,
            $newValues
        );
    }

    public static function logAdminDelete(int $adminId, string $username): bool
    {
        return self::logAction(
            'admin_delete',
            'admin',
            "Deleted admin: $username",
            $adminId
        );
    }

    public static function logAdminStatusChange(int $adminId, string $username, string $oldStatus, string $newStatus): bool
    {
        return self::logAction(
            'admin_status_update',
            'admin',
            "Changed admin status: $username from $oldStatus to $newStatus",
            $adminId,
            ['status' => $oldStatus],
            ['status' => $newStatus]
        );
    }

    public static function logMemberStatusChange(int $memberId, string $username, string $oldStatus, string $newStatus): bool
    {
        return self::logAction(
            'member_status_update',
            'member',
            "Changed member status: $username from $oldStatus to $newStatus",
            $memberId,
            ['status' => $oldStatus],
            ['status' => $newStatus]
        );
    }

    public static function logMemberUpdate(int $memberId, string $username, ?array $oldValues, ?array $newValues): bool
    {
        return self::logAction(
            'member_update',
            'member',
            "Updated member: $username",
            $memberId,
            $oldValues,
            $newValues
        );
    }

    public static function logMemberDelete(int $memberId, string $username): bool
    {
        return self::logAction(
            'member_delete',
            'member',
            "Deleted member: $username",
            $memberId
        );
    }

    public static function logOrderStatusChange(int $orderId, string $oldStatus, string $newStatus): bool
    {
        return self::logAction(
            'order_status_update',
            'order',
            "Changed order status: Order #$orderId from $oldStatus to $newStatus",
            $orderId,
            ['order_status' => $oldStatus],
            ['order_status' => $newStatus]
        );
    }

    // Product management logging methods
    public static function logProductCreate(int $productId, string $productName): bool
    {
        return self::logAction(
            'product_create',
            'product',
            "Created new product: $productName",
            $productId
        );
    }

    public static function logProductUpdate(int $productId, string $productName, ?array $oldValues, ?array $newValues): bool
    {
        return self::logAction(
            'product_update',
            'product',
            "Updated product: $productName",
            $productId,
            $oldValues,
            $newValues
        );
    }

    public static function logProductDelete(int $productId, string $productName): bool
    {
        return self::logAction(
            'product_delete',
            'product',
            "Deleted product: $productName",
            $productId
        );
    }

    // Variant management logging methods
    public static function logVariantCreate(int $variantId, int $productId, string $color): bool
    {
        return self::logAction(
            'variant_create',
            'variant',
            "Created new variant: Color $color for product ID $productId",
            $variantId,
            null,
            ['product_id' => $productId, 'color' => $color]
        );
    }

    // Voucher management logging methods
    public static function logVoucherCreate(int $voucherId, string $code): bool
    {
        return self::logAction(
            'voucher_create',
            'voucher',
            "Created new voucher: $code",
            $voucherId
        );
    }

    public static function logVoucherUpdate(int $voucherId, string $code, ?array $oldValues, ?array $newValues): bool
    {
        return self::logAction(
            'voucher_update',
            'voucher',
            "Updated voucher: $code",
            $voucherId,
            $oldValues,
            $newValues
        );
    }

    public static function logVoucherDelete(int $voucherId, string $code): bool
    {
        return self::logAction(
            'voucher_delete',
            'voucher',
            "Deleted voucher: $code",
            $voucherId
        );
    }

    public static function logVoucherStatusChange(int $voucherId, string $code, string $oldStatus, string $newStatus): bool
    {
        return self::logAction(
            'voucher_status_update',
            'voucher',
            "Changed voucher status: $code from $oldStatus to $newStatus",
            $voucherId,
            ['status' => $oldStatus],
            ['status' => $newStatus]
        );
    }

    public static function logVoucherAssign(int $voucherId, string $code, string $assignmentType, int $memberCount = 0): bool
    {
        $description = $assignmentType === 'all' 
            ? "Assigned voucher $code to all active members"
            : "Assigned voucher $code to $memberCount member(s)";
        
        return self::logAction(
            'voucher_assign',
            'voucher',
            $description,
            $voucherId,
            null,
            ['assignment_type' => $assignmentType, 'member_count' => $memberCount]
        );
    }

    public static function logVoucherBulkImport(int $importedCount, int $totalCount): bool
    {
        return self::logAction(
            'voucher_bulk_import',
            'voucher',
            "Bulk imported $importedCount out of $totalCount vouchers",
            null,
            null,
            ['imported_count' => $importedCount, 'total_count' => $totalCount]
        );
    }

    // Order management logging methods
    public static function logOrderRefundApprove(int $orderId, string $reason = ''): bool
    {
        $description = "Approved refund for Order #$orderId";
        if ($reason) {
            $description .= ": $reason";
        }
        return self::logAction(
            'order_refund_approve',
            'order',
            $description,
            $orderId,
            ['order_status' => 'refund_requested'],
            ['order_status' => 'refunded', 'payment_status' => 'refunded']
        );
    }

    public static function logOrderRefundReject(int $orderId, string $reason = ''): bool
    {
        $description = "Rejected refund for Order #$orderId";
        if ($reason) {
            $description .= ": $reason";
        }
        return self::logAction(
            'order_refund_reject',
            'order',
            $description,
            $orderId,
            ['order_status' => 'refund_requested'],
            ['order_status' => 'paid']
        );
    }

    // Inventory management logging methods
    public static function logInventoryRestock(int $productId, ?int $variantId, string $size, int $quantity, int $stockBefore, int $stockAfter): bool
    {
        $variantInfo = $variantId ? " (Variant ID: $variantId)" : "";
        $description = "Restocked product ID $productId$variantInfo, Size: $size, Quantity: +$quantity (Stock: $stockBefore → $stockAfter)";
        
        return self::logAction(
            'inventory_restock',
            'inventory',
            $description,
            $productId,
            ['stock_before' => $stockBefore, 'variant_id' => $variantId, 'size' => $size],
            ['stock_after' => $stockAfter, 'quantity_added' => $quantity, 'variant_id' => $variantId, 'size' => $size]
        );
    }
}

