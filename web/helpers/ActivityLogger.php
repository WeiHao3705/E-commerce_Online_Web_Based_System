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
     */
    private static function getAdminId(): ?int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user']) && isset($_SESSION['user']->user_id)) {
            return (int)$_SESSION['user']->user_id;
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
}

