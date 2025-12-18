<?php
session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../repository/AdminRepository.php';
require_once __DIR__ . '/../service/AdminService.php';
require_once __DIR__ . '/../DTO/AdminDTO.php';

class AdminController
{
    private $adminService;

    public function __construct()
    {
        $database = new Database();
        $repository = new AdminRepository($database);
        $this->adminService = new AdminService($repository);
    }

    private function requireAdmin(): void
    {
        if (!isset($_SESSION['user']) || $_SESSION['user']->role !== 'admin') {
            $_SESSION['error_message'] = 'You must be logged in as admin to access this page.';
            header('Location: ../views/security/login.php');
            exit;
        }
    }

    public function showAllAdmins(): void
    {
        $this->requireAdmin();

        if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
            $this->getAdminsAjax();
            return;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        $sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'created_at';
        $sortOrder = isset($_GET['sortOrder']) ? strtoupper($_GET['sortOrder']) : 'DESC';
        $status = isset($_GET['status']) ? trim($_GET['status']) : '';

        if ($page < 1) { $page = 1; }
        if ($limit < 1) { $limit = 10; }
        if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') { $sortOrder = 'DESC'; }

        try {
            $data = $this->adminService->getAllAdmins($page, $limit, $searchTerm, $sortBy, $sortOrder, $status);
            $admins = $data['admins'];
            $pagination = $data['pagination'];
            $currentSort = ['sortBy' => $sortBy, 'sortOrder' => $sortOrder];
        } catch (Exception $e) {
            $admins = [];
            $pagination = [];
            $currentSort = ['sortBy' => 'created_at', 'sortOrder' => 'DESC'];
            $_SESSION['error_message'] = $e->getMessage();
        }

        require_once __DIR__ . '/../views/admin/AllAdmins.php';
    }

    private function getAdminsAjax(): void
    {
        $this->requireAdmin();

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        $sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'created_at';
        $sortOrder = isset($_GET['sortOrder']) ? strtoupper($_GET['sortOrder']) : 'DESC';
        $status = isset($_GET['status']) ? trim($_GET['status']) : '';

        if ($page < 1) { $page = 1; }
        if ($limit < 1) { $limit = 10; }
        if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') { $sortOrder = 'DESC'; }

        try {
            $data = $this->adminService->getAllAdmins($page, $limit, $searchTerm, $sortBy, $sortOrder, $status);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'admins' => $data['admins'],
                'pagination' => $data['pagination'],
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder,
                'status' => $status
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

    public function createAdmin(): void
    {
        $this->requireAdmin();

        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $username = isset($_POST['username']) ? trim($_POST['username']) : '';
                $password = isset($_POST['password']) ? $_POST['password'] : '';
                $repeatPassword = isset($_POST['repeat_password']) ? $_POST['repeat_password'] : '';
                $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
                $email = isset($_POST['email']) ? trim($_POST['email']) : '';
                $contactNo = isset($_POST['contact_no']) ? trim($_POST['contact_no']) : '';
                $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';

                if ($username === '' || $password === '' || $repeatPassword === '' || $fullName === '' || $email === '') {
                    throw new Exception('Username, password, full name, and email are required.');
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email format.');
                }

                // Contact number is already validated and formatted by JavaScript (format: "+60 11-5550 5761")
                // Just trim it, no need to extract digits or validate length since different countries have different formats
                $contactNo = $contactNo === '' ? '' : trim($contactNo);

                $adminDTO = new AdminRegistrationDTO(
                    $username,
                    $password,
                    $repeatPassword,
                    $fullName,
                    $gender,
                    $contactNo,
                    $email
                );

                $result = $this->adminService->registerAdmin($adminDTO);

                if ($result) {
                    $_SESSION['success_message'] = 'Admin created successfully.';
                }

                header('Location: ../controller/AdminController.php?action=showAll');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../controller/AdminController.php?action=showAll');
            exit;
        }
    }

    public function updateAdmin(): void
    {
        $this->requireAdmin();

        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
                    throw new Exception('User ID is required');
                }

                $username = isset($_POST['username']) ? trim($_POST['username']) : '';
                $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
                $email = isset($_POST['email']) ? trim($_POST['email']) : '';
                $contactNo = isset($_POST['contact_no']) ? trim($_POST['contact_no']) : '';
                $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';

                if ($fullName === '' || $email === '') {
                    throw new Exception('Full name and email are required.');
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email format.');
                }

                // Contact number is already validated and formatted by JavaScript (format: "+60 11-5550 5761")
                // Just trim it, no need to extract digits or validate length since different countries have different formats
                $contactNo = $contactNo === '' ? '' : trim($contactNo);

                $adminDTO = new AdminUpdateDTO(
                    (int)$_POST['user_id'],
                    $username,
                    $fullName,
                    $email,
                    $gender,
                    $contactNo
                );

                $result = $this->adminService->updateAdmin($adminDTO);

                if ($result) {
                    $_SESSION['success_message'] = 'Admin updated successfully.';
                }

                header('Location: ../controller/AdminController.php?action=showAll');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../controller/AdminController.php?action=showAll');
            exit;
        }
    }

    public function updateAdminStatus(): void
    {
        $this->requireAdmin();

        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
                $status = isset($_POST['status']) ? trim($_POST['status']) : '';

                if ($userId <= 0 || $status === '') {
                    throw new Exception('User ID and status are required');
                }

                $result = $this->adminService->updateAdminStatus($userId, $status);

                if ($result) {
                    $_SESSION['success_message'] = 'Status updated successfully.';
                } else {
                    $_SESSION['error_message'] = 'No changes were made.';
                }

                header('Location: ../controller/AdminController.php?action=showAll');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../controller/AdminController.php?action=showAll');
            exit;
        }
    }

    public function deleteAdmin(): void
    {
        $this->requireAdmin();

        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

                if ($userId <= 0) {
                    throw new Exception('Invalid user ID');
                }

                $result = $this->adminService->deleteAdmin($userId);

                if ($result) {
                    $_SESSION['success_message'] = 'Admin deleted successfully.';
                } else {
                    $_SESSION['error_message'] = 'Unable to delete admin.';
                }

                header('Location: ../controller/AdminController.php?action=showAll');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../controller/AdminController.php?action=showAll');
            exit;
        }
    }

    public function bulkDeleteAdmins(): void
    {
        $this->requireAdmin();

        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!isset($_POST['user_ids'])) {
                    throw new Exception('No admins selected for deletion');
                }

                $userIds = $_POST['user_ids'];
                if (!is_array($userIds)) {
                    $userIds = [$userIds];
                }

                $result = $this->adminService->bulkDeleteAdmins($userIds);

                if ($result['success']) {
                    $_SESSION['success_message'] = $result['message'];
                } else {
                    throw new Exception($result['message']);
                }

                header('Location: ../controller/AdminController.php?action=showAll');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../controller/AdminController.php?action=showAll');
            exit;
        }
    }
}

$controller = new AdminController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? $_GET['action'] ?? 'create';

    if ($action === 'create') {
        $controller->createAdmin();
    } elseif ($action === 'update') {
        $controller->updateAdmin();
    } elseif ($action === 'updateStatus') {
        $controller->updateAdminStatus();
    } elseif ($action === 'delete') {
        $controller->deleteAdmin();
    } elseif ($action === 'bulkDelete') {
        $controller->bulkDeleteAdmins();
    }
} else {
    $action = $_GET['action'] ?? 'showAll';

    if ($action === 'showAll') {
        $controller->showAllAdmins();
    }
}
