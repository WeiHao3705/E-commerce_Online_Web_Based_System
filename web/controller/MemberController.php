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
                    $_POST['security_answer']
                );

                $result = $this->membershipServices->registerMember($memberDTO);

                if ($result) {
                    $_SESSION['success_message'] = "Registration successful!";
                    header('Location: ../views/MemberRegisterForm.php');
                    exit;
                }
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../views/MemberRegisterForm.php');
            exit;
        }
    }

    public function showAllMembers()
    {
        try {
            // Get pagination and search parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

            // Validate page number
            if ($page < 1) $page = 1;
            if ($limit < 1) $limit = 10;

            // Get members data from service
            $data = $this->membershipServices->getAllMembers($page, $limit, $searchTerm);

            // Store data in variable to be used in view
            $members = $data['members'];
            $pagination = $data['pagination'];

            // Include the view
            require_once __DIR__ . '/../views/AllMembers.php';
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            // Redirect to error page or show error
            require_once __DIR__ . '/../views/AllMembers.php';
        }
    }
}

// Handle the request
$controller = new MemberController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'register';

    if ($action === 'register') {
        $controller->registerMember();
    } elseif ($action === 'login') {
        $controller->login();
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
