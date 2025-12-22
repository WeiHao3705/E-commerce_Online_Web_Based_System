<?php

require_once __DIR__ . '/../DTO/ActivityLogDTO.php';

class ActivityLogRepository {
    private $db;

    public function __construct(Database $databaseConnection)
    {
        $this->db = $databaseConnection->getConnection();
    }

    // Records admin activity in the activity log
    public function logActivity(ActivityLogDTO $logDTO): bool
    {
        $sql = "INSERT INTO admin_activity_log 
                (admin_id, action_type, entity_type, entity_id, action_description, old_values, new_values, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $oldValuesJson = $logDTO->getOldValues() ? json_encode($logDTO->getOldValues()) : null;
        $newValuesJson = $logDTO->getNewValues() ? json_encode($logDTO->getNewValues()) : null;

        return $stmt->execute([
            $logDTO->getAdminId(),
            $logDTO->getActionType(),
            $logDTO->getEntityType(),
            $logDTO->getEntityId(),
            $logDTO->getActionDescription(),
            $oldValuesJson,
            $newValuesJson,
            $logDTO->getIpAddress(),
            $logDTO->getUserAgent()
        ]);
    }

    // Retrieves activity logs with filtering, pagination, and sorting
    public function getActivityLogs(
        $limit = 50,
        $offset = 0,
        $searchTerm = '',
        $adminId = null,
        $actionType = null,
        $entityType = null,
        $startDate = null,
        $endDate = null,
        $sortBy = 'created_at',
        $sortOrder = 'DESC'
    ): array {
        $limit = (int)$limit;
        $offset = (int)$offset;
        $searchTerm = trim($searchTerm);

        $allowedSortColumns = ['created_at', 'action_type', 'entity_type', 'admin_id'];
        if (!in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = 'created_at';
        }

        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT 
                    al.log_id,
                    al.admin_id,
                    al.action_type,
                    al.entity_type,
                    al.entity_id,
                    al.action_description,
                    al.old_values,
                    al.new_values,
                    al.ip_address,
                    al.user_agent,
                    al.created_at,
                    u.username as admin_username,
                    u.full_name as admin_full_name
                FROM admin_activity_log al
                LEFT JOIN users u ON al.admin_id = u.user_id
                WHERE 1=1";
        
        $params = [];

        if (!empty($searchTerm)) {
            $sql .= " AND (al.action_description LIKE :search 
                    OR u.username LIKE :search 
                    OR u.full_name LIKE :search)";
            $params[':search'] = "%{$searchTerm}%";
        }

        if ($adminId !== null) {
            $sql .= " AND (al.admin_id = :admin_id OR (al.entity_id = :admin_id_entity AND al.entity_type = 'admin'))";
            $params[':admin_id'] = (int)$adminId;
            $params[':admin_id_entity'] = (int)$adminId;
        }

        if (!empty($actionType)) {
            $sql .= " AND al.action_type = :action_type";
            $params[':action_type'] = $actionType;
        }

        if (!empty($entityType)) {
            $sql .= " AND al.entity_type = :entity_type";
            $params[':entity_type'] = $entityType;
        }

        if (!empty($startDate)) {
            $sql .= " AND DATE(al.created_at) >= :start_date";
            $params[':start_date'] = $startDate;
        }

        if (!empty($endDate)) {
            $sql .= " AND DATE(al.created_at) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $sql .= " ORDER BY al.$sortBy $sortOrder LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Returns total count of activity logs matching filters
    public function getTotalCount(
        $searchTerm = '',
        $adminId = null,
        $actionType = null,
        $entityType = null,
        $startDate = null,
        $endDate = null
    ): int {
        $searchTerm = trim($searchTerm);

        $sql = "SELECT COUNT(*) as total 
                FROM admin_activity_log al
                LEFT JOIN users u ON al.admin_id = u.user_id
                WHERE 1=1";
        
        $params = [];

        if (!empty($searchTerm)) {
            $sql .= " AND (al.action_description LIKE :search 
                    OR u.username LIKE :search 
                    OR u.full_name LIKE :search)";
            $params[':search'] = "%{$searchTerm}%";
        }

        if ($adminId !== null) {
            $sql .= " AND (al.admin_id = :admin_id OR (al.entity_id = :admin_id_entity AND al.entity_type = 'admin'))";
            $params[':admin_id'] = (int)$adminId;
            $params[':admin_id_entity'] = (int)$adminId;
        }

        if (!empty($actionType)) {
            $sql .= " AND al.action_type = :action_type";
            $params[':action_type'] = $actionType;
        }

        if (!empty($entityType)) {
            $sql .= " AND al.entity_type = :entity_type";
            $params[':entity_type'] = $entityType;
        }

        if (!empty($startDate)) {
            $sql .= " AND DATE(al.created_at) >= :start_date";
            $params[':start_date'] = $startDate;
        }

        if (!empty($endDate)) {
            $sql .= " AND DATE(al.created_at) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['total'];
    }

    // Retrieves a specific activity log by ID
    public function getActivityLogById($logId)
    {
        $sql = "SELECT 
                    al.*,
                    u.username as admin_username,
                    u.full_name as admin_full_name
                FROM admin_activity_log al
                LEFT JOIN users u ON al.admin_id = u.user_id
                WHERE al.log_id = ? 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$logId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: null;
    }

    // Retrieves activity logs for a specific admin
    public function getActivityLogsByAdmin($adminId, $limit = 50, $offset = 0): array
    {
        return $this->getActivityLogs($limit, $offset, '', $adminId);
    }

    // Retrieves activity logs for a specific entity
    public function getActivityLogsByEntity($entityType, $entityId, $limit = 50, $offset = 0): array
    {
        $sql = "SELECT 
                    al.*,
                    u.username as admin_username,
                    u.full_name as admin_full_name
                FROM admin_activity_log al
                LEFT JOIN users u ON al.admin_id = u.user_id
                WHERE al.entity_type = ? AND al.entity_id = ?
                ORDER BY al.created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$entityType, $entityId, $limit, $offset]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Archives activity logs older than specified months
    public function archiveOldLogs($monthsOld = 6): int
    {
        $sql = "INSERT INTO admin_activity_log_archive 
                (admin_id, action_type, entity_type, entity_id, action_description, old_values, new_values, ip_address, user_agent, created_at)
                SELECT 
                    admin_id, action_type, entity_type, entity_id, action_description, 
                    old_values, new_values, ip_address, user_agent, created_at
                FROM admin_activity_log
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? MONTH)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$monthsOld]);
        $archivedCount = $stmt->rowCount();

        $deleteSql = "DELETE FROM admin_activity_log 
                      WHERE created_at < DATE_SUB(NOW(), INTERVAL ? MONTH)";
        $deleteStmt = $this->db->prepare($deleteSql);
        $deleteStmt->execute([$monthsOld]);

        return $archivedCount;
    }

    // Returns distinct list of action types from activity logs
    public function getActionTypes(): array
    {
        $sql = "SELECT DISTINCT action_type 
                FROM admin_activity_log 
                ORDER BY action_type ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Returns distinct list of entity types from activity logs
    public function getEntityTypes(): array
    {
        $sql = "SELECT DISTINCT entity_type 
                FROM admin_activity_log 
                ORDER BY entity_type ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

