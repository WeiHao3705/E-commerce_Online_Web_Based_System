<?php

require_once __DIR__ . '/../DTO/VoucherDTO.php';

class VoucherRepository
{
    private $db;

    public function __construct(Database $databaseConnection)
    {
        $this->db = $databaseConnection->getConnection();
    }

    public function createVoucher(VoucherRegistrationDTO $voucherDTO)
    {
        $sql = "INSERT INTO voucher (code, description, type, discount_value, min_spend, max_discount, start_date, end_date, is_redeemable) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute([
            $voucherDTO->getCode(),
            $voucherDTO->getDescription(),
            $voucherDTO->getType(),
            $voucherDTO->getDiscountValue(),
            $voucherDTO->getMinSpend(),
            $voucherDTO->getMaxDiscount(),
            $voucherDTO->getStartDate(),
            $voucherDTO->getEndDate(),
            $voucherDTO->getIsRedeemable() ? 1 : 0
        ]);

        return $result;
    }

    public function checkExistingVoucher($code): array
    {
        // Check code
        $sql = "SELECT COUNT(*) as count FROM voucher WHERE code = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return ['exists' => true, 'field' => 'code', 'message' => 'Voucher code already exists'];
        }

        return ['exists' => false];
    }

    public function checkExistingVoucherForUpdate($voucherId, $code): array
    {
        // Check code (excluding current voucher)
        $sql = "SELECT COUNT(*) as count FROM voucher WHERE code = ? AND voucher_id != ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$code, $voucherId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return ['exists' => true, 'field' => 'code', 'message' => 'Voucher code already exists'];
        }

        return ['exists' => false];
    }

    public function getAllVouchers($limit = 10, $offset = 0, $searchTerm = '', $sortBy = 'voucher_id', $sortOrder = 'DESC'): array
    {
        try {
            // Ensure limit and offset are integers
            $limit = (int)$limit;
            $offset = (int)$offset;

            // Validate sort column to prevent SQL injection
            $allowedSortColumns = ['voucher_id', 'code', 'description', 'type', 'discount_value', 'min_spend', 'start_date', 'end_date', 'status'];
            if (!in_array($sortBy, $allowedSortColumns)) {
                $sortBy = 'voucher_id';
            }

            // Validate sort order
            $sortOrder = strtoupper($sortOrder);
            if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') {
                $sortOrder = 'DESC';
            }

            // Base query - Note: using discount_value (correct spelling) instead of diacount_value
            $sql = "SELECT 
                    voucher_id,
                    code,
                    description,
                    type,
                    discount_value,
                    min_spend,
                    max_discount,
                    start_date,
                    end_date,
                    status,
                    is_redeemable
                FROM voucher
                WHERE 1=1";

            $params = [];

            // Add search filter if provided
            if (!empty($searchTerm)) {
                $sql .= " AND (
                        code LIKE :search OR
                        description LIKE :search
                    )";
                $params[':search'] = "%{$searchTerm}%";
            }

            // Add ordering and pagination (safe integers and validated columns, not bound as parameters)
            $sql .= " ORDER BY $sortBy $sortOrder LIMIT $limit OFFSET $offset";

            // Debug logging
            error_log("SQL Query: " . $sql);
            error_log("Params: " . print_r($params, true));

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Log results
            error_log("Query Results: " . print_r($results, true));

            return $results;
        } catch (PDOException $e) {
            error_log("Database error in getAllVouchers: " . $e->getMessage());
            throw new Exception("Error retrieving vouchers");
        }
    }

    public function getTotalVouchersCount($searchTerm = '')
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM voucher WHERE 1=1";
            $params = [];

            if (!empty($searchTerm)) {
                $sql .= " AND (
                    code LIKE ? OR 
                    description LIKE ?
                )";
                $searchParam = "%{$searchTerm}%";
                $params = [$searchParam, $searchParam];
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Database error in getTotalVouchersCount: " . $e->getMessage());
            throw new Exception("Error counting vouchers");
        }
    }

    /**
     * Get count of active vouchers
     * Active vouchers are those with status = 'active' and current date between start_date and end_date
     */
    public function getActiveVouchersCount(): int
    {
        try {
            $currentDate = date('Y-m-d');
            $sql = "SELECT COUNT(*) as total 
                    FROM voucher 
                    WHERE status = 'active' 
                    AND start_date <= ? 
                    AND end_date >= ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$currentDate, $currentDate]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Database error in getActiveVouchersCount: " . $e->getMessage());
            throw new Exception("Error counting active vouchers");
        }
    }

    /**
     * Get count of active vouchers that started recently (in the last 7 days)
     * This represents new active vouchers added recently
     */
    public function getRecentActiveVouchersCount($days = 7): int
    {
        try {
            $currentDate = date('Y-m-d');
            $pastDate = date('Y-m-d', strtotime("-{$days} days"));
            
            $sql = "SELECT COUNT(*) as total 
                    FROM voucher 
                    WHERE status = 'active' 
                    AND start_date <= ? 
                    AND end_date >= ? 
                    AND start_date >= ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$currentDate, $currentDate, $pastDate]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Database error in getRecentActiveVouchersCount: " . $e->getMessage());
            throw new Exception("Error counting recent active vouchers");
        }
    }

    public function getVoucherById($voucherId)
    {
        try {
            $sql = "SELECT * FROM voucher WHERE voucher_id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$voucherId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result : null;
        } catch (PDOException $e) {
            error_log("Database error in getVoucherById: " . $e->getMessage());
            throw new Exception("Error fetching voucher");
        }
    }

    public function updateVoucher(VoucherUpdateDTO $voucherDTO): bool
    {
        try {
            $sql = "UPDATE voucher 
                    SET code = ?, description = ?, type = ?, discount_value = ?, 
                        min_spend = ?, max_discount = ?, start_date = ?, end_date = ?, is_redeemable = ? 
                    WHERE voucher_id = ?";

            $stmt = $this->db->prepare($sql);

            $result = $stmt->execute([
                $voucherDTO->getCode(),
                $voucherDTO->getDescription(),
                $voucherDTO->getType(),
                $voucherDTO->getDiscountValue(),
                $voucherDTO->getMinSpend(),
                $voucherDTO->getMaxDiscount(),
                $voucherDTO->getStartDate(),
                $voucherDTO->getEndDate(),
                $voucherDTO->getIsRedeemable() ? 1 : 0,
                $voucherDTO->getVoucherId()
            ]);

            return $result;
        } catch (PDOException $e) {
            error_log("Database error in updateVoucher: " . $e->getMessage());
            throw new Exception("Error updating voucher");
        }
    }

    public function updateVoucherStatus($voucherId, $status): bool
    {
        try {
            // Validate status
            $allowedStatuses = ['active', 'inactive', 'expired'];
            if (!in_array($status, $allowedStatuses)) {
                throw new Exception("Invalid status value");
            }

            $sql = "UPDATE voucher SET status = ? WHERE voucher_id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$status, $voucherId]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Database error in updateVoucherStatus: " . $e->getMessage());
            throw new Exception("Error updating voucher status");
        }
    }

    public function deleteVoucher($voucherId): bool
    {
        try {
            $sql = "DELETE FROM voucher WHERE voucher_id = ?";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$voucherId]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Database error in deleteVoucher: " . $e->getMessage());
            throw new Exception("Error deleting voucher");
        }
    }

    /**
     * Automatically check and update expired vouchers to inactive status
     * Updates vouchers where end_date < current_date and status is 'active'
     * Returns the number of vouchers updated
     */
    public function autoExpireVouchers(): int
    {
        try {
            $currentDate = date('Y-m-d');
            
            $sql = "UPDATE voucher 
                    SET status = 'inactive' 
                    WHERE status = 'active' 
                    AND end_date < ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$currentDate]);
            
            $updatedCount = $stmt->rowCount();
            
            if ($updatedCount > 0) {
                error_log("Auto-expired {$updatedCount} voucher(s) on {$currentDate}");
            }
            
            return $updatedCount;
        } catch (PDOException $e) {
            error_log("Database error in autoExpireVouchers: " . $e->getMessage());
            throw new Exception("Error auto-expiring vouchers");
        }
    }

    /**
     * Get all active members (for voucher assignment)
     * Includes a flag indicating if the member already has the voucher assigned
     */
    public function getAllActiveMembers($voucherId = null): array
    {
        try {
            if ($voucherId !== null) {
                // Get all active members with assignment status
                $sql = "SELECT u.user_id, u.username, u.full_name, u.email,
                               CASE WHEN va.assignment_id IS NOT NULL THEN 1 ELSE 0 END as is_assigned
                        FROM users u
                        LEFT JOIN voucher_assignment va ON u.user_id = va.user_id AND va.voucher_id = ?
                        WHERE u.role = 'member' 
                        AND u.status = 'active'
                        ORDER BY u.full_name ASC";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$voucherId]);
            } else {
                // Get all active members (no assignment status)
                $sql = "SELECT user_id, username, full_name, email, 0 as is_assigned
                        FROM users 
                        WHERE role = 'member' AND status = 'active'
                        ORDER BY full_name ASC";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
            }
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert is_assigned to boolean for easier handling
            foreach ($results as &$result) {
                $result['is_assigned'] = (bool)$result['is_assigned'];
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("Database error in getAllActiveMembers: " . $e->getMessage());
            throw new Exception("Error retrieving members");
        }
    }

    /**
     * Assign voucher to all active members
     */
    public function assignVoucherToAllMembers($voucherId, $assignedBy = null): array
    {
        try {
            // Start transaction
            $this->db->beginTransaction();

            // Get all active members
            $members = $this->getAllActiveMembers();
            
            if (empty($members)) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'No active members found'];
            }

            $assignedCount = 0;
            $skippedCount = 0;
            $currentDate = date('Y-m-d H:i:s');

            // Insert assignments (avoid duplicates using voucher_assignment table)
            $sql = "INSERT IGNORE INTO voucher_assignment (voucher_id, user_id, assigned_at, assigned_by) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);

            foreach ($members as $member) {
                $result = $stmt->execute([$voucherId, $member['user_id'], $currentDate, $assignedBy]);
                
                // Check if row was inserted (not ignored due to duplicate)
                if ($stmt->rowCount() > 0) {
                    $assignedCount++;
                } else {
                    $skippedCount++;
                }
            }

            $this->db->commit();

            return [
                'success' => true,
                'assigned_count' => $assignedCount,
                'skipped_count' => $skippedCount,
                'total_members' => count($members),
                'message' => "Voucher assigned to $assignedCount member(s). " . 
                            ($skippedCount > 0 ? "$skippedCount member(s) already had this voucher." : "")
            ];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Database error in assignVoucherToAllMembers: " . $e->getMessage());
            throw new Exception("Error assigning voucher to members: " . $e->getMessage());
        }
    }

    /**
     * Assign voucher to specific members
     */
    public function assignVoucherToSpecificMembers($voucherId, array $memberIds, $assignedBy = null): array
    {
        try {
            if (empty($memberIds)) {
                return ['success' => false, 'message' => 'No members selected'];
            }

            // Start transaction
            $this->db->beginTransaction();

            $assignedCount = 0;
            $skippedCount = 0;
            $currentDate = date('Y-m-d H:i:s');

            // Insert assignments (avoid duplicates using voucher_assignment table)
            $sql = "INSERT IGNORE INTO voucher_assignment (voucher_id, user_id, assigned_at, assigned_by) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);

            foreach ($memberIds as $memberId) {
                $memberId = (int)$memberId;
                $result = $stmt->execute([$voucherId, $memberId, $currentDate, $assignedBy]);
                
                // Check if row was inserted
                if ($stmt->rowCount() > 0) {
                    $assignedCount++;
                } else {
                    $skippedCount++;
                }
            }

            $this->db->commit();

            return [
                'success' => true,
                'assigned_count' => $assignedCount,
                'skipped_count' => $skippedCount,
                'total_selected' => count($memberIds),
                'message' => "Voucher assigned to $assignedCount member(s). " . 
                            ($skippedCount > 0 ? "$skippedCount member(s) already had this voucher." : "")
            ];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Database error in assignVoucherToSpecificMembers: " . $e->getMessage());
            throw new Exception("Error assigning voucher to members: " . $e->getMessage());
        }
    }

    /**
     * Get all vouchers assigned to a specific member
     * Returns vouchers with their status (active, used, expired)
     */
    public function getMemberVouchers($userId, $filter = 'all', $sortBy = 'end_date', $sortOrder = 'ASC'): array
    {
        try {
            $currentDate = date('Y-m-d');
            
            $allowedSortColumns = ['end_date', 'start_date', 'assigned_at', 'discount_value', 'code'];
            if (!in_array($sortBy, $allowedSortColumns)){
                $sortBy = 'end_date';
            }
            
            // Validate sort order
            $sortOrder = strtoupper($sortOrder);
            if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') {
                $sortOrder = 'ASC';
            }

            $sql = "SELECT 
                        v.voucher_id,
                        v.code,
                        v.description,
                        v.type,
                        v.discount_value,
                        v.min_spend,
                        v.max_discount,
                        v.start_date,
                        v.end_date,
                        v.status,
                        va.assigned_at,
                        CASE 
                            WHEN vu.user_id IS NOT NULL THEN 'used'
                            WHEN v.end_date < :current_date THEN 'expired'
                            WHEN v.start_date > :current_date THEN 'pending'
                            WHEN v.status = 'inactive' THEN 'inactive'
                            ELSE 'active'
                        END as voucher_status,
                        vu.used_at
                    FROM voucher_assignment va
                    INNER JOIN voucher v ON va.voucher_id = v.voucher_id
                    LEFT JOIN voucher_usage vu ON va.voucher_id = vu.voucher_id AND va.user_id = vu.user_id
                    WHERE va.user_id = :user_id";
            
            $params = [':current_date' => $currentDate, ':user_id' => $userId];
            
            // Apply filter
            if ($filter === 'active') {
                $sql .= " AND v.end_date >= :current_date AND v.start_date <= :current_date2 AND v.status = 'active' AND vu.user_id IS NULL";
                $params[':current_date2'] = $currentDate;
            } elseif ($filter === 'used') {
                $sql .= " AND vu.user_id IS NOT NULL";
            } elseif ($filter === 'expired') {
                $sql .= " AND v.end_date < :expired_date AND vu.user_id IS NULL";
                $params[':expired_date'] = $currentDate;
            }
            
            if ($sortBy === 'end_date' || $sortBy === 'start_date'){
                $sql .= " ORDER BY v.$sortBy $sortOrder, va.assigned_at DESC";
            } elseif($sortBy === 'assigned_at'){
                $sql .= " ORDER BY va.$sortBy $sortOrder, v.end_date ASC";
            } elseif($sortBy === 'discount_value' || $sortBy === 'code'){
                $sql .= " ORDER BY v.$sortBy $sortOrder, v.end_date ASC";
            } else{
                $sql .= " ORDER BY v.end_date ASC, va.assigned_at DESC";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getMemberVouchers: " . $e->getMessage());
            throw new Exception("Error retrieving member vouchers");
        }
    }

    /**
     * Redeem a voucher by code for a specific member
     * Validates the voucher and assigns it to the member if valid
     */
    public function redeemVoucherByCode($code, $userId): array
    {
        try {
            $currentDate = date('Y-m-d');
            $currentDateTime = date('Y-m-d H:i:s');

            // First, check if voucher exists and is valid
            $sql = "SELECT voucher_id, code, description, type, discount_value, min_spend, max_discount, 
                           start_date, end_date, status, is_redeemable
                    FROM voucher 
                    WHERE code = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$code]);
            $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$voucher) {
                return [
                    'success' => false,
                    'message' => 'Invalid voucher code. Please check and try again.'
                ];
            }

            // Check if voucher is redeemable by members
            if (!$voucher['is_redeemable'] || $voucher['is_redeemable'] == 0) {
                return [
                    'success' => false,
                    'message' => 'This voucher cannot be redeemed directly. Please contact support for assistance.'
                ];
            }

            // Check if voucher is active
            if ($voucher['status'] !== 'active') {
                return [
                    'success' => false,
                    'message' => 'This voucher is not active.'
                ];
            }

            // Check if voucher is within valid date range
            if ($voucher['start_date'] > $currentDate) {
                return [
                    'success' => false,
                    'message' => 'This voucher is not yet valid. It starts on ' . date('d M Y', strtotime($voucher['start_date'])) . '.'
                ];
            }

            if ($voucher['end_date'] < $currentDate) {
                return [
                    'success' => false,
                    'message' => 'This voucher has expired on ' . date('d M Y', strtotime($voucher['end_date'])) . '.'
                ];
            }

            // Check if member already has this voucher assigned
            $checkSql = "SELECT assignment_id FROM voucher_assignment 
                           WHERE voucher_id = ? AND user_id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$voucher['voucher_id'], $userId]);
            $existingAssignment = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingAssignment) {
                return [
                    'success' => false,
                    'message' => 'You already have this voucher in your account.'
                ];
            }

            // Assign voucher to member
            $assignSql = "INSERT INTO voucher_assignment (voucher_id, user_id, assigned_at, assigned_by) 
                         VALUES (?, ?, ?, NULL)";
            $assignStmt = $this->db->prepare($assignSql);
            $result = $assignStmt->execute([$voucher['voucher_id'], $userId, $currentDateTime]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Voucher redeemed successfully! You can now use it for your purchases.',
                    'voucher' => $voucher
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to redeem voucher. Please try again.'
                ];
            }
        } catch (PDOException $e) {
            error_log("Database error in redeemVoucherByCode: " . $e->getMessage());
            throw new Exception("Error redeeming voucher: " . $e->getMessage());
        }
    }
}

