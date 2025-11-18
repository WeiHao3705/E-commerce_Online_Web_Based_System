<?php
session_start();
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../repository/MemberRepository.php';
require_once __DIR__ . '/../service/MemberService.php';
require_once __DIR__ . '/../DTO/MemberDTO.php';

class MemberController
{
    private $membershipServices;

    public function __construct()
    {
        $database = new Database();
        $membershipRepository = new MembershipRepository($database);
        $this->membershipServices = new MembershipServices($membershipRepository);
    }

    public function showLogin()
    {
        try {
            header('Location: ../account.php');
            exit;
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../index.php');
            exit;
        }
    }

    public function login()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $username = isset($_POST['username']) ? trim($_POST['username']) : '';
                $password = isset($_POST['password']) ? $_POST['password'] : '';

                $userDTO = $this->membershipServices->authenticate($username, $password);

                // Save minimal user info in session
                $_SESSION['user'] = [
                    'user_id' => $userDTO->getUserId(),
                    'username' => $userDTO->getUsername(),
                    'full_name' => $userDTO->getFullName(),
                    'email' => $userDTO->getEmail(),
                    'role' => $userDTO->getRole()
                ];

                $_SESSION['success_message'] = 'Login successful';
                header('Location: ../index.php');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../views/LoginForm.php');
            exit;
        }
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['success_message'] = 'You have been logged out';
        header('Location: ../index.php');
        exit;
    }

    public function registerMember()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $memberDTO = new MemberRegistrationDTO(
                    $_POST['username'],
                    $_POST['password'],
                    $_POST['repeat_password'],
                    $_POST['full_name'],
                    $_POST['gender'],
                    $_POST['contact_no'],
                    $_POST['email'],
                    $_POST['security_question'],
                    $_POST['security_answer'],
                    null
                );

                $profilePhotoFile = $_FILES['profile_photo'] ?? null;
                $croppedPhotoData = isset($_POST['profile_photo_cropped']) ? trim($_POST['profile_photo_cropped']) : null;

                if ($croppedPhotoData === '') {
                    $croppedPhotoData = null;
                }

                $result = $this->membershipServices->registerMember($memberDTO, $profilePhotoFile, $croppedPhotoData);

                if ($result) {
                    $_SESSION['success_message'] = "Member registered successfully!";
                    
                    // Check if registration is from admin panel
                    $returnTo = isset($_POST['return_to']) ? $_POST['return_to'] : (isset($_GET['return_to']) ? $_GET['return_to'] : '');
                    
                    if ($returnTo === 'admin') {
                        header('Location: ../controller/MemberController.php?action=showAll');
                    } else {
                        header('Location: ../views/MemberRegisterForm.php');
                    }
                    exit;
                }
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            
            // Check if registration is from admin panel
            $returnTo = isset($_POST['return_to']) ? $_POST['return_to'] : (isset($_GET['return_to']) ? $_GET['return_to'] : '');
            
            if ($returnTo === 'admin') {
                header('Location: ../views/MemberRegisterForm.php?return_to=admin');
            } else {
                header('Location: ../views/MemberRegisterForm.php');
            }
            exit;
        }
    }

    public function showAllMembers()
    {
        try {
            // Get pagination, search, and sort parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
            $sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'created_at';
            $sortOrder = isset($_GET['sortOrder']) ? strtoupper($_GET['sortOrder']) : 'DESC';

            // Validate page number
            if ($page < 1) $page = 1;
            if ($limit < 1) $limit = 10;

            // Validate sort order
            if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') {
                $sortOrder = 'DESC';
            }

            // Get members data from service
            $data = $this->membershipServices->getAllMembers($page, $limit, $searchTerm, $sortBy, $sortOrder);

            // Store data in variable to be used in view
            $members = $data['members'];
            $pagination = $data['pagination'];
            $currentSort = ['sortBy' => $sortBy, 'sortOrder' => $sortOrder];

            // Include the view
            require_once __DIR__ . '/../views/AllMembers.php';
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            // Redirect to error page or show error
            require_once __DIR__ . '/../views/AllMembers.php';
        }
    }

    public function updateMember()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validate required fields
                if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
                    throw new Exception("User ID is required");
                }

                $memberDTO = new MemberUpdateDTO(
                    (int)$_POST['user_id'],
                    $_POST['username'],
                    $_POST['full_name'],
                    $_POST['email'],
                    $_POST['gender'],
                    $_POST['contact_no'],
                    $_POST['current_profile_photo'] ?? null
                );

                $profilePhotoFile = $_FILES['profile_photo'] ?? null;

                $result = $this->membershipServices->updateMember($memberDTO, $profilePhotoFile);

                if ($result) {
                    $_SESSION['success_message'] = "Member updated successfully!";
                    header('Location: ../controller/MemberController.php?action=showAll');
                    exit;
                } else {
                    throw new Exception("Failed to update member");
                }
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../controller/MemberController.php?action=showAll');
            exit;
        }
    }

    public function deleteMember()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validate required fields
                if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
                    throw new Exception("User ID is required");
                }

                $userId = (int)$_POST['user_id'];

                $result = $this->membershipServices->deleteMember($userId);

                if ($result) {
                    $_SESSION['success_message'] = "Member deleted successfully!";
                } else {
                    throw new Exception("Failed to delete member. Member may not exist or may not be a regular member.");
                }

                header('Location: ../controller/MemberController.php?action=showAll');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../controller/MemberController.php?action=showAll');
            exit;
        }
    }

}

// Handle the request
$controller = new MemberController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'register';

    if ($action === 'register') {
        $controller->registerMember();
    } elseif ($action === 'update') {
        $controller->updateMember();
    } elseif ($action === 'delete') {
        $controller->deleteMember();
    }elseif ($action === 'login') {
        $controller->login();
        $action = $_POST['action'] ?? $_GET['action'] ?? 'register';
    }
} else {
    // Handle GET requests
    $action = $_GET['action'] ?? '';

    if ($action === 'showAll') {
        $controller->showAllMembers();
    } elseif ($action === 'login') {
        $controller->showLogin();
    } elseif ($action === 'logout') {
        $controller->logout();
    }
}
