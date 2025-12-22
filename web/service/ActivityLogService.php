<?php

require_once __DIR__ . '/../repository/ActivityLogRepository.php';
require_once __DIR__ . '/../DTO/ActivityLogDTO.php';

class ActivityLogService {
    private $activityLogRepository;

    public function __construct(ActivityLogRepository $activityLogRepository)
    {
        $this->activityLogRepository = $activityLogRepository;
    }

    public function logAdminAction(
        $adminId,
        $actionType,
        $entityType,
        $actionDescription,
        $entityId = null,
        $oldValues = null,
        $newValues = null,
        $ipAddress = null,
        $userAgent = null
    ): bool {
        $logDTO = new ActivityLogDTO(
            $adminId,
            $actionType,
            $entityType,
            $actionDescription,
            $entityId,
            $oldValues,
            $newValues,
            $ipAddress,
            $userAgent
        );

        return $this->activityLogRepository->logActivity($logDTO);
    }

    public function getActivityLogs(
        $page = 1,
        $limit = 50,
        $searchTerm = '',
        $adminId = null,
        $actionType = null,
        $entityType = null,
        $startDate = null,
        $endDate = null,
        $sortBy = 'created_at',
        $sortOrder = 'DESC'
    ): array {
        $page = (int)$page;
        $limit = (int)$limit;
        if ($page < 1) { $page = 1; }
        if ($limit < 1) { $limit = 50; }

        $offset = ($page - 1) * $limit;

        $logs = $this->activityLogRepository->getActivityLogs(
            $limit,
            $offset,
            $searchTerm,
            $adminId,
            $actionType,
            $entityType,
            $startDate,
            $endDate,
            $sortBy,
            $sortOrder
        );

        $total = $this->activityLogRepository->getTotalCount(
            $searchTerm,
            $adminId,
            $actionType,
            $entityType,
            $startDate,
            $endDate
        );

        $totalPages = $limit > 0 ? (int)ceil($total / $limit) : 1;

        return [
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_logs' => $total,
                'per_page' => $limit,
                'showing_from' => $offset + 1,
                'showing_to' => min($offset + $limit, $total)
            ]
        ];
    }

    public function getActivityLogById($logId)
    {
        return $this->activityLogRepository->getActivityLogById($logId);
    }

    public function getActivityLogsByAdmin($adminId, $page = 1, $limit = 50): array
    {
        return $this->getActivityLogs($page, $limit, '', $adminId);
    }

    public function getActivityLogsByEntity($entityType, $entityId, $page = 1, $limit = 50): array
    {
        $page = (int)$page;
        $limit = (int)$limit;
        if ($page < 1) { $page = 1; }
        if ($limit < 1) { $limit = 50; }

        $offset = ($page - 1) * $limit;
        $logs = $this->activityLogRepository->getActivityLogsByEntity($entityType, $entityId, $limit, $offset);
        
        // Note: getActivityLogsByEntity doesn't return total count, so we'll need to handle pagination differently
        // For now, return logs with basic pagination info
        return [
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit
            ]
        ];
    }

    public function exportLogs(
        $format = 'csv',
        $searchTerm = '',
        $adminId = null,
        $actionType = null,
        $entityType = null,
        $startDate = null,
        $endDate = null
    ): array {
        // Get all logs matching criteria (no pagination for export)
        $logs = $this->activityLogRepository->getActivityLogs(
            10000, // Large limit for export
            0,
            $searchTerm,
            $adminId,
            $actionType,
            $entityType,
            $startDate,
            $endDate
        );

        if ($format === 'json') {
            return [
                'format' => 'json',
                'data' => $logs,
                'filename' => 'activity_logs_' . date('Y-m-d_His') . '.json'
            ];
        } else {
            // CSV format
            $csvData = [];
            $csvData[] = ['Timestamp', 'Admin', 'Action Type', 'Entity Type', 'Entity ID', 'Description', 'IP Address'];
            
            foreach ($logs as $log) {
                $csvData[] = [
                    $log['created_at'],
                    $log['admin_full_name'] . ' (' . $log['admin_username'] . ')',
                    $log['action_type'],
                    $log['entity_type'],
                    $log['entity_id'] ?? '',
                    $log['action_description'],
                    $log['ip_address'] ?? ''
                ];
            }

            return [
                'format' => 'csv',
                'data' => $csvData,
                'filename' => 'activity_logs_' . date('Y-m-d_His') . '.csv'
            ];
        }
    }

    public function archiveOldLogs($monthsOld = 6): int
    {
        return $this->activityLogRepository->archiveOldLogs($monthsOld);
    }

    public function getActionTypes(): array
    {
        return $this->activityLogRepository->getActionTypes();
    }

    public function getEntityTypes(): array
    {
        return $this->activityLogRepository->getEntityTypes();
    }
}

