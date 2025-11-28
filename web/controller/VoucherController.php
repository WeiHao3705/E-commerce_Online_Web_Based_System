<?php
session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../repository/VoucherRepository.php';
require_once __DIR__ . '/../service/VoucherService.php';
require_once __DIR__ . '/../DTO/VoucherDTO.php';

class VoucherController
{
    private $voucherService;

    public function __construct()
    {
        $database = new Database();
        $voucherRepository = new VoucherRepository($database);
        $this->voucherService = new VoucherService($voucherRepository);
    }

    public function showAllVouchers()
    {
        try {
            // Check if this is an AJAX request
            if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
                $this->getVouchersAjax();
                return;
            }

            // Get pagination, search, and sort parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
            $sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'voucher_id';
            $sortOrder = isset($_GET['sortOrder']) ? strtoupper($_GET['sortOrder']) : 'DESC';

            // Validate page number
            if ($page < 1) $page = 1;
            if ($limit < 1) $limit = 10;

            // Validate sort order
            if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') {
                $sortOrder = 'DESC';
            }

            // Get vouchers data from service
            $data = $this->voucherService->getAllVouchers($page, $limit, $searchTerm, $sortBy, $sortOrder);

            // Store data in variable to be used in view
            $vouchers = $data['vouchers'];
            $pagination = $data['pagination'];
            $currentSort = ['sortBy' => $sortBy, 'sortOrder' => $sortOrder];

            // Include the view
            require_once __DIR__ . '/../views/AllVoucher.php';
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            // Redirect to error page or show error
            require_once __DIR__ . '/../views/AllVoucher.php';
        }
    }

    private function getVouchersAjax()
    {
        try {
            // Get pagination, search, and sort parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
            $sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'voucher_id';
            $sortOrder = isset($_GET['sortOrder']) ? strtoupper($_GET['sortOrder']) : 'DESC';

            // Validate page number
            if ($page < 1) $page = 1;
            if ($limit < 1) $limit = 10;

            // Validate sort order
            if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') {
                $sortOrder = 'DESC';
            }

            // Get vouchers data from service
            $data = $this->voucherService->getAllVouchers($page, $limit, $searchTerm, $sortBy, $sortOrder);

            // Return JSON response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'vouchers' => $data['vouchers'],
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

    public function registerVoucher()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $voucherDTO = new VoucherRegistrationDTO(
                    $_POST['code'],
                    $_POST['description'] ?? '',
                    $_POST['type'],
                    $_POST['discount_value'],
                    $_POST['min_spend'] ?? 0,
                    $_POST['max_discount'] ?? null,
                    $_POST['start_date'],
                    $_POST['end_date'],
                    false
                );

                $result = $this->voucherService->registerVoucher($voucherDTO);

                if ($result) {
                    $_SESSION['success_message'] = "Voucher registered successfully!";
                    
                    // Check if registration is from admin panel
                    $returnTo = isset($_POST['return_to']) ? $_POST['return_to'] : (isset($_GET['return_to']) ? $_GET['return_to'] : '');
                    
                    if ($returnTo === 'admin') {
                        header('Location: ../controller/VoucherController.php?action=showAll');
                    } else {
                        header('Location: ../views/VoucherRegisterForm.php');
                    }
                    exit;
                }
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            
            // Preserve POST data for form repopulation
            $_SESSION['form_data'] = $_POST;
            
            // Check if registration is from admin panel
            $returnTo = isset($_POST['return_to']) ? $_POST['return_to'] : (isset($_GET['return_to']) ? $_GET['return_to'] : '');
            
            if ($returnTo === 'admin') {
                header('Location: ../views/VoucherRegisterForm.php?return_to=admin');
            } else {
                header('Location: ../views/VoucherRegisterForm.php');
            }
            exit;
        }
    }

    public function updateVoucher()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validate required fields
                if (!isset($_POST['voucher_id']) || empty($_POST['voucher_id'])) {
                    throw new Exception("Voucher ID is required");
                }

                $voucherDTO = new VoucherUpdateDTO(
                    (int)$_POST['voucher_id'],
                    $_POST['code'],
                    $_POST['description'] ?? '',
                    $_POST['type'],
                    $_POST['discount_value'],
                    $_POST['min_spend'] ?? 0,
                    $_POST['max_discount'] ?? null,
                    $_POST['start_date'],
                    $_POST['end_date'],
                    false
                );

                $result = $this->voucherService->updateVoucher($voucherDTO);

                if ($result) {
                    $_SESSION['success_message'] = "Voucher updated successfully!";
                    header('Location: ../controller/VoucherController.php?action=showAll');
                    exit;
                } else {
                    throw new Exception("Failed to update voucher");
                }
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../controller/VoucherController.php?action=showAll');
            exit;
        }
    }

    public function updateVoucherStatus()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validate required fields
                if (!isset($_POST['voucher_id']) || empty($_POST['voucher_id'])) {
                    throw new Exception("Voucher ID is required");
                }

                if (!isset($_POST['status']) || empty($_POST['status'])) {
                    throw new Exception("Status is required");
                }

                $voucherId = (int)$_POST['voucher_id'];
                $status = $_POST['status'];

                // Validate status
                $allowedStatuses = ['active', 'inactive', 'expired'];
                if (!in_array($status, $allowedStatuses)) {
                    throw new Exception("Invalid status value");
                }

                $result = $this->voucherService->updateVoucherStatus($voucherId, $status);

                if ($result) {
                    $statusLabels = [
                        'active' => 'activated',
                        'inactive' => 'set to inactive',
                        'expired' => 'expired'
                    ];
                    $_SESSION['success_message'] = "Voucher " . $statusLabels[$status] . " successfully!";
                } else {
                    throw new Exception("Failed to update voucher status. Voucher may not exist.");
                }

                header('Location: ../controller/VoucherController.php?action=showAll');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../controller/VoucherController.php?action=showAll');
            exit;
        }
    }

    public function deleteVoucher()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validate required fields
                if (!isset($_POST['voucher_id']) || empty($_POST['voucher_id'])) {
                    throw new Exception("Voucher ID is required");
                }

                $voucherId = (int)$_POST['voucher_id'];

                $result = $this->voucherService->deleteVoucher($voucherId);

                if ($result) {
                    $_SESSION['success_message'] = "Voucher deleted successfully!";
                } else {
                    throw new Exception("Failed to delete voucher. Voucher may not exist.");
                }

                header('Location: ../controller/VoucherController.php?action=showAll');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../controller/VoucherController.php?action=showAll');
            exit;
        }
    }

    public function assignVoucher()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validate required fields
                if (!isset($_POST['voucher_id']) || empty($_POST['voucher_id'])) {
                    throw new Exception("Voucher ID is required");
                }

                if (!isset($_POST['assignment_type']) || empty($_POST['assignment_type'])) {
                    throw new Exception("Assignment type is required");
                }

                $voucherId = (int)$_POST['voucher_id'];
                $assignmentType = $_POST['assignment_type']; // 'all' or 'specific'
                $assignedBy = isset($_SESSION['user']['user_id']) ? (int)$_SESSION['user']['user_id'] : null;

                if ($assignmentType === 'all') {
                    // Assign to all active members
                    $result = $this->voucherService->assignVoucherToAllMembers($voucherId, $assignedBy);
                    
                    if ($result['success']) {
                        $_SESSION['success_message'] = $result['message'];
                    } else {
                        throw new Exception($result['message']);
                    }
                } elseif ($assignmentType === 'specific') {
                    // Assign to specific members
                    if (!isset($_POST['member_ids']) || empty($_POST['member_ids'])) {
                        throw new Exception("Please select at least one member");
                    }

                    $memberIds = $_POST['member_ids'];
                    if (!is_array($memberIds)) {
                        $memberIds = [$memberIds];
                    }

                    $result = $this->voucherService->assignVoucherToSpecificMembers($voucherId, $memberIds, $assignedBy);
                    
                    if ($result['success']) {
                        $_SESSION['success_message'] = $result['message'];
                    } else {
                        throw new Exception($result['message']);
                    }
                } else {
                    throw new Exception("Invalid assignment type");
                }

                header('Location: ../controller/VoucherController.php?action=showAll');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../controller/VoucherController.php?action=showAll');
            exit;
        }
    }

    public function getMembersForAssignment()
    {
        try {
            // Get voucher_id from request to exclude already assigned members
            $voucherId = isset($_GET['voucher_id']) ? (int)$_GET['voucher_id'] : null;
            
            // Return JSON list of members for assignment dropdown
            // Excludes members who already have this voucher assigned
            $members = $this->voucherService->getAllActiveMembers($voucherId);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'members' => $members
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

    public function downloadTemplate()
    {
        try {
            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="voucher_import_template.csv"');
            
            // Create output stream
            $output = fopen('php://output', 'w');
            
            // Write CSV headers
            fputcsv($output, [
                'code',
                'description',
                'type',
                'discount_value',
                'min_spend',
                'max_discount',
                'start_date',
                'end_date'
            ]);
            
            // Write example row
            fputcsv($output, [
                'SUMMER25',
                'Summer sale voucher',
                'percent',
                '25',
                '100',
                '50',
                '2024-06-01',
                '2024-08-31'
            ]);
            
            fclose($output);
            exit;
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../views/VoucherRegisterForm.php');
            exit;
        }
    }

    public function previewBulkImport()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
                $file = $_FILES['csv_file'];
                
                // Validate file
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("File upload error: " . $file['error']);
                }
                
                if ($file['type'] !== 'text/csv' && pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv') {
                    throw new Exception("Please upload a valid CSV file");
                }
                
                // Parse CSV
                $result = $this->voucherService->parseCSVFile($file['tmp_name']);
                
                if (!$result['success']) {
                    throw new Exception($result['error']);
                }
                
                // Store parsed vouchers in session for preview
                $_SESSION['bulk_import_vouchers'] = $result['vouchers'];
                $_SESSION['bulk_import_errors'] = $result['errors'];
                
                // Redirect to preview page
                $returnTo = isset($_POST['return_to']) ? $_POST['return_to'] : '';
                $redirectUrl = '../views/VoucherBulkImportPreview.php';
                if ($returnTo) {
                    $redirectUrl .= '?return_to=' . urlencode($returnTo);
                }
                header('Location: ' . $redirectUrl);
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            $returnTo = isset($_POST['return_to']) ? $_POST['return_to'] : '';
            $redirectUrl = '../views/VoucherRegisterForm.php';
            if ($returnTo) {
                $redirectUrl .= '?return_to=' . urlencode($returnTo);
            }
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    public function executeBulkImport()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!isset($_SESSION['bulk_import_vouchers'])) {
                    throw new Exception("No vouchers to import. Please upload a CSV file first.");
                }
                
                $vouchers = $_SESSION['bulk_import_vouchers'];
                $result = $this->voucherService->bulkImportVouchers($vouchers);
                
                // Clear session data
                unset($_SESSION['bulk_import_vouchers']);
                unset($_SESSION['bulk_import_errors']);
                
                if ($result['success']) {
                    $_SESSION['success_message'] = $result['message'];
                } else {
                    $_SESSION['error_message'] = $result['message'];
                }
                
                // Redirect based on return_to
                $returnTo = isset($_POST['return_to']) ? $_POST['return_to'] : '';
                if ($returnTo === 'admin') {
                    header('Location: ../controller/VoucherController.php?action=showAll');
                } else {
                    header('Location: ../views/VoucherRegisterForm.php');
                }
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            $returnTo = isset($_POST['return_to']) ? $_POST['return_to'] : '';
            if ($returnTo === 'admin') {
                header('Location: ../controller/VoucherController.php?action=showAll');
            } else {
                header('Location: ../views/VoucherRegisterForm.php');
            }
            exit;
        }
    }
}

// Handle the request
$controller = new VoucherController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? $_GET['action'] ?? 'register';

    if ($action === 'register') {
        $controller->registerVoucher();
    } elseif ($action === 'update') {
        $controller->updateVoucher();
    } elseif ($action === 'updateStatus') {
        $controller->updateVoucherStatus();
    } elseif ($action === 'delete') {
        $controller->deleteVoucher();
    } elseif ($action === 'assign') {
        $controller->assignVoucher();
    } elseif ($action === 'previewBulkImport') {
        $controller->previewBulkImport();
    } elseif ($action === 'executeBulkImport') {
        $controller->executeBulkImport();
    }
} else {
    // Handle GET requests
    $action = $_GET['action'] ?? '';

    if ($action === 'showAll') {
        $controller->showAllVouchers();
    } elseif ($action === 'getMembers') {
        $controller->getMembersForAssignment();
    } elseif ($action === 'downloadTemplate') {
        $controller->downloadTemplate();
    }
}

