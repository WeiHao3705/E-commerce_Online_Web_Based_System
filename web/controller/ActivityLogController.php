<?php
session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../repository/ActivityLogRepository.php';
require_once __DIR__ . '/../service/ActivityLogService.php';

// Load FPDF library
if (!class_exists('FPDF')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    // FPDF should be available via Composer autoload
    if (!class_exists('FPDF')) {
        // Fallback: try direct path
        if (file_exists(__DIR__ . '/../../vendor/setasign/fpdf/fpdf.php')) {
            require_once __DIR__ . '/../../vendor/setasign/fpdf/fpdf.php';
        }
    }
}

class ActivityLogController
{
    private $activityLogService;

    public function __construct()
    {
        $database = new Database();
        $repository = new ActivityLogRepository($database);
        $this->activityLogService = new ActivityLogService($repository);
    }

    private function requireAdmin(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']->role !== 'admin') {
            $_SESSION['error_message'] = 'You must be logged in as admin to access this page.';
            header('Location: ../views/security/login.php');
            exit;
        }
    }

    public function showActivityLogs(): void
    {
        $this->requireAdmin();
        $this->generateActivityLogPDF();
    }

    private function getActivityLogsAjax(): void
    {
        $this->requireAdmin();

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        $adminId = isset($_GET['admin_id']) ? (int)$_GET['admin_id'] : null;
        $actionType = isset($_GET['action_type']) ? trim($_GET['action_type']) : null;
        $entityType = isset($_GET['entity_type']) ? trim($_GET['entity_type']) : null;
        $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : null;
        $endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : null;
        $sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'created_at';
        $sortOrder = isset($_GET['sortOrder']) ? strtoupper($_GET['sortOrder']) : 'DESC';

        if ($page < 1) { $page = 1; }
        if ($limit < 1) { $limit = 50; }
        if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') { $sortOrder = 'DESC'; }

        try {
            $data = $this->activityLogService->getActivityLogs(
                $page,
                $limit,
                $searchTerm,
                $adminId,
                $actionType,
                $entityType,
                $startDate,
                $endDate,
                $sortBy,
                $sortOrder
            );
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'logs' => $data['logs'],
                'pagination' => $data['pagination'],
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder
            ]);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }

    public function getActivityLogDetails(): void
    {
        $this->requireAdmin();

        header('Content-Type: application/json');

        try {
            $logId = isset($_GET['log_id']) ? (int)$_GET['log_id'] : 0;

            if ($logId <= 0) {
                throw new Exception('Log ID is required');
            }

            $log = $this->activityLogService->getActivityLogById($logId);

            if (!$log) {
                throw new Exception('Activity log not found');
            }

            // Parse JSON values
            if ($log['old_values']) {
                $log['old_values'] = json_decode($log['old_values'], true);
            }
            if ($log['new_values']) {
                $log['new_values'] = json_decode($log['new_values'], true);
            }

            echo json_encode([
                'success' => true,
                'log' => $log
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }

    public function generateActivityLogPDF(): void
    {
        $this->requireAdmin();

        try {
            // Check if FPDF is available
            if (!class_exists('FPDF')) {
                throw new Exception('FPDF library is not available. Please install it via Composer.');
            }

            $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
            $adminId = isset($_GET['admin_id']) ? (int)$_GET['admin_id'] : null;
            $actionType = isset($_GET['action_type']) ? trim($_GET['action_type']) : null;
            $entityType = isset($_GET['entity_type']) ? trim($_GET['entity_type']) : null;
            $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : null;
            $endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : null;

            // Get all logs (no pagination for PDF)
            // Service expects: page, limit, searchTerm, adminId, ...
            $data = $this->activityLogService->getActivityLogs(
                1,      // page = 1
                10000,  // Large limit to get all logs
                $searchTerm,
                $adminId,
                $actionType,
                $entityType,
                $startDate,
                $endDate,
                'created_at',
                'DESC'
            );
            $logs = $data['logs'];

            // Get admin name if filtering by admin
            $adminName = 'All Admins';
            if ($adminId) {
                require_once __DIR__ . '/../database/connection.php';
                $database = new Database();
                $conn = $database->getConnection();
                $stmt = $conn->prepare("SELECT full_name, username FROM users WHERE user_id = ?");
                $stmt->execute([$adminId]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($admin) {
                    $adminName = $admin['full_name'] . ' (' . $admin['username'] . ')';
                }
            }

            // Generate filename
            $filename = 'activity_logs_' . date('Y-m-d_His');
            if ($adminId) {
                $filename .= '_admin_' . $adminId;
            }
            $filename .= '.pdf';

            // Create PDF
            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->SetAutoPageBreak(true, 15);
            $pdf->AddPage();

            // Header
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->SetFillColor(255, 82, 59);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->Cell(0, 10, 'Admin Activity Logs Report', 0, 1, 'C', true);
            $pdf->Ln(5);

            // Report Info
            $pdf->SetFont('Arial', '', 10);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 6, 'Generated: ' . date('F d, Y h:i A'), 0, 1);
            $pdf->Cell(0, 6, 'Admin: ' . $adminName, 0, 1);
            if ($startDate || $endDate) {
                $dateRange = 'Date Range: ';
                $dateRange .= $startDate ? date('M d, Y', strtotime($startDate)) : 'Start';
                $dateRange .= ' - ';
                $dateRange .= $endDate ? date('M d, Y', strtotime($endDate)) : 'End';
                $pdf->Cell(0, 6, $dateRange, 0, 1);
            }
            if ($actionType) {
                $pdf->Cell(0, 6, 'Action Type: ' . $actionType, 0, 1);
            }
            if ($entityType) {
                $pdf->Cell(0, 6, 'Entity Type: ' . $entityType, 0, 1);
            }
            $pdf->Cell(0, 6, 'Total Records: ' . count($logs), 0, 1);
            $pdf->Ln(5);

            // Table Header
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(249, 250, 251);
            $pdf->Cell(25, 8, 'Date/Time', 1, 0, 'L', true);
            $pdf->Cell(40, 8, 'Admin', 1, 0, 'L', true);
            $pdf->Cell(30, 8, 'Action', 1, 0, 'L', true);
            $pdf->Cell(25, 8, 'Entity', 1, 0, 'L', true);
            $pdf->Cell(70, 8, 'Description', 1, 1, 'L', true);

            // Table Data
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetFillColor(255, 255, 255);
            $fill = false;

            if (empty($logs)) {
                $pdf->Cell(190, 8, 'No activity logs found.', 1, 1, 'C', $fill);
            } else {
                foreach ($logs as $log) {
                    $fill = !$fill;
                    $dateTime = date('M d, Y', strtotime($log['created_at'])) . "\n" . date('H:i', strtotime($log['created_at']));
                    $admin = ($log['admin_full_name'] ?? 'Unknown') . "\n" . ($log['admin_username'] ?? '');
                    $action = $log['action_type'];
                    $entity = $log['entity_type'];
                    if ($log['entity_id']) {
                        $entity .= "\nID: " . $log['entity_id'];
                    }
                    $description = substr($log['action_description'], 0, 60);
                    if (strlen($log['action_description']) > 60) {
                        $description .= '...';
                    }

                    // Calculate row height
                    $height = max(8, ceil(strlen($dateTime) / 20) * 4, ceil(strlen($admin) / 20) * 4);
                    
                    $pdf->Cell(25, $height, $dateTime, 1, 0, 'L', $fill);
                    $pdf->Cell(40, $height, $admin, 1, 0, 'L', $fill);
                    $pdf->Cell(30, $height, $action, 1, 0, 'L', $fill);
                    $pdf->Cell(25, $height, $entity, 1, 0, 'L', $fill);
                    $pdf->Cell(70, $height, $description, 1, 1, 'L', $fill);
                }
            }

            // Footer
            $pdf->SetY(-15);
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->Cell(0, 10, 'Page ' . $pdf->PageNo(), 0, 0, 'C');

            // Output PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $pdf->Output('D', $filename);
            exit;

        } catch (Exception $e) {
            $_SESSION['error_message'] = 'PDF generation failed: ' . $e->getMessage();
            header('Location: ../controller/AdminController.php?action=showAll');
            exit;
        }
    }

    public function archiveOldLogs(): void
    {
        $this->requireAdmin();

        header('Content-Type: application/json');

        try {
            $monthsOld = isset($_POST['months']) ? (int)$_POST['months'] : 6;

            if ($monthsOld < 1) {
                throw new Exception('Invalid months value');
            }

            $archivedCount = $this->activityLogService->archiveOldLogs($monthsOld);

            echo json_encode([
                'success' => true,
                'message' => "Archived $archivedCount log entries older than $monthsOld months",
                'archived_count' => $archivedCount
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }
}

$controller = new ActivityLogController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    if ($action === 'archive') {
        $controller->archiveOldLogs();
    }
    } else {
        $action = $_GET['action'] ?? 'showAll';

        if ($action === 'showAll') {
            $controller->showActivityLogs();
        } elseif ($action === 'getDetails') {
            $controller->getActivityLogDetails();
        }
    }

