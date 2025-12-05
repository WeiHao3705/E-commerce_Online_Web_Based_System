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
                
                // Redirect based on user role
                if ($userDTO->getRole() === 'admin') {
                    header('Location: ../views/admin/AdminDashboard.php');
                } else {
                    header('Location: ../index.php');
                }
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../views/security/LoginForm.php');
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

                // Get profile photo data
                $profilePhotoFile = $_FILES['profile_photo'] ?? null;
                $croppedPhotoData = isset($_POST['profile_photo_cropped']) ? trim($_POST['profile_photo_cropped']) : null;

                $result = $this->membershipServices->registerMember($memberDTO, $profilePhotoFile, $croppedPhotoData);

                if ($result) {
                    $_SESSION['success_message'] = "Member registered successfully!";
                    
                    // Check if registration is from admin panel
                    $returnTo = isset($_POST['return_to']) ? $_POST['return_to'] : (isset($_GET['return_to']) ? $_GET['return_to'] : '');
                    
                    if ($returnTo === 'admin') {
                        header('Location: ../controller/MemberController.php?action=showAll');
                    } else {
                        header('Location: ../views/member_management/MemberRegisterForm.php');
                    }
                    exit;
                }
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            
            // Preserve POST data for form repopulation
            $_SESSION['form_data'] = $_POST;
            
            // Detect which field caused the error from error message
            $errorField = null;
            $errorMessage = strtolower($e->getMessage());
            
            // Check error message for field indicators
            if (stripos($errorMessage, 'username') !== false && stripos($errorMessage, 'already exists') !== false) {
                $errorField = 'username';
            } elseif (stripos($errorMessage, 'email') !== false && stripos($errorMessage, 'already exists') !== false) {
                $errorField = 'email';
            } elseif ((stripos($errorMessage, 'contact') !== false || stripos($errorMessage, 'phone') !== false) && stripos($errorMessage, 'already exists') !== false) {
                $errorField = 'contact_no';
            }
            
            if ($errorField) {
                $_SESSION['error_field'] = $errorField;
            }
            
            // Check if registration is from admin panel
            $returnTo = isset($_POST['return_to']) ? $_POST['return_to'] : (isset($_GET['return_to']) ? $_GET['return_to'] : '');
            
            if ($returnTo === 'admin') {
                header('Location: ../views/member_management/MemberRegisterForm.php?return_to=admin');
            } else {
                header('Location: ../views/member_management/MemberRegisterForm.php');
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
            require_once __DIR__ . '/../views/member_management/AllMembers.php';
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            // Redirect to error page or show error
            require_once __DIR__ . '/../views/member_management/AllMembers.php';
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

                // Basic input sanitization
                $username = isset($_POST['username']) ? trim($_POST['username']) : '';
                $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
                $email = isset($_POST['email']) ? trim($_POST['email']) : '';
                $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';
                $contact_no = isset($_POST['contact_no']) ? trim($_POST['contact_no']) : '';

                // Server-side constraints: no empty fields
                if ($full_name === '' || $email === '' || $gender === '' || $contact_no === '') {
                    throw new Exception("All fields are required.");
                }

                // Email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid email format.");
                }

                // Normalize phone, accept display format 000-000 0000, store digits-only
                $contact_digits = preg_replace('/\D+/', '', $contact_no);
                if (strlen($contact_digits) !== 10) {
                    throw new Exception("Invalid phone number format. Use 000-000 0000.");
                }
                $contact_no = $contact_digits;

                $memberDTO = new MemberUpdateDTO(
                    (int)$_POST['user_id'],
                    $username,
                    $full_name,
                    $email,
                    $gender,
                    $contact_no
                );

                $result = $this->membershipServices->updateMember($memberDTO);

                if ($result) {
                    $_SESSION['success_message'] = "Member updated successfully!";
                    // Allow redirection back to profile when updating from user profile page
                    $returnTo = isset($_POST['return_to']) ? $_POST['return_to'] : (isset($_GET['return_to']) ? $_GET['return_to'] : '');
                    if ($returnTo === 'profile') {
                        header('Location: ../views/member/profile.php');
                    } else {
                        header('Location: ../controller/MemberController.php?action=showAll');
                    }
                    exit;
                } else {
                    throw new Exception("Failed to update member");
                }
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            $returnTo = isset($_POST['return_to']) ? $_POST['return_to'] : (isset($_GET['return_to']) ? $_GET['return_to'] : '');
            if ($returnTo === 'profile') {
                header('Location: ../views/member/profile.php');
            } else {
                header('Location: ../controller/MemberController.php?action=showAll');
            }
            exit;
        }
    }

    /**
     * Handle password reset via security question flow.
     * POST with 'email' -> show question
     * POST with 'security_answer' and 'new_password' -> verify and update
     */
    public function sendReset()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ../views/security/forgot_password.php');
                exit;
            }

            // Step 1: user submitted username only -> show security question
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            if ($username) {
                $user = $this->membershipServices->getMemberByUsername($username);
                if (!$user) {
                    $_SESSION['fp_message'] = 'Username not found';
                    header('Location: ../views/security/forgot_password.php');
                    exit;
                }

                // store user in session for next step (only minimal data)
                $_SESSION['reset_user'] = [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'security_question' => $user['security_question'] ?? '',
                    'created_at' => time()
                ];

                // ensure previous verification flag is cleared
                unset($_SESSION['reset_verified']);

                header('Location: ../views/security/forgot_password.php');
                exit;
            }

            // Fallback: redirect back
            header('Location: ../views/security/forgot_password.php');
            exit;
        } catch (Exception $e) {
            $_SESSION['fp_message'] = $e->getMessage();
            header('Location: ../views/security/forgot_password.php');
            exit;
        }
    }

    /**
     * Verify the security answer. If correct, mark verified and redirect to new-password page.
     */
    public function verifyReset()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ../views/security/forgot_password.php');
                exit;
            }

            $securityAnswer = isset($_POST['security_answer']) ? trim($_POST['security_answer']) : '';

            if (empty($_SESSION['reset_user'])) {
                $_SESSION['fp_message'] = 'Session expired. Please start again.';
                header('Location: ../views/security/forgot_password.php');
                exit;
            }

            $userId = (int)$_SESSION['reset_user']['user_id'];
            $user = $this->membershipServices->getMemberById($userId);
            if (!$user) {
                $_SESSION['fp_message'] = 'User not found';
                unset($_SESSION['reset_user']);
                header('Location: ../views/security/forgot_password.php');
                exit;
            }

            $stored = isset($user['security_answer']) ? trim($user['security_answer']) : '';
            if (strcasecmp($stored, $securityAnswer) !== 0) {
                $_SESSION['fp_message'] = 'Security answer did not match';
                header('Location: ../views/security/forgot_password.php');
                exit;
            }

            // mark as verified and go to new-password step on the single page
            $_SESSION['reset_verified'] = true;
            header('Location: ../views/security/forgot_password.php');
            exit;
        } catch (Exception $e) {
            $_SESSION['fp_message'] = $e->getMessage();
            header('Location: ../views/security/forgot_password.php');
            exit;
        }
    }

    /**
     * Complete the reset by setting the new password. Requires prior verification.
     */
    public function completeReset()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ../views/security/forgot_password.php');
                exit;
            }

            if (empty($_SESSION['reset_user']) || empty($_SESSION['reset_verified'])) {
                $_SESSION['fp_message'] = 'Unauthorized action. Please verify your security answer first.';
                header('Location: ../views/security/forgot_password.php');
                exit;
            }

            $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : null;
            $newPasswordConfirm = isset($_POST['new_password_confirm']) ? $_POST['new_password_confirm'] : null;

            if ($newPassword === null || $newPasswordConfirm === null) {
                $_SESSION['fp_message'] = 'Please provide the new password and confirmation.';
                header('Location: ../views/security/forgot_password.php');
                exit;
            }

            if ($newPassword !== $newPasswordConfirm) {
                $_SESSION['fp_message'] = 'Passwords do not match';
                header('Location: ../views/security/forgot_password.php');
                exit;
            }

            if (strlen($newPassword) < 6) {
                $_SESSION['fp_message'] = 'Password must be at least 6 characters';
                header('Location: ../views/security/forgot_password.php');
                exit;
            }

            $userId = (int)$_SESSION['reset_user']['user_id'];
            $updated = $this->membershipServices->resetPassword($userId, $newPassword);
            if ($updated) {
                unset($_SESSION['reset_user']);
                unset($_SESSION['reset_verified']);
                // Use the general success message so it shows on the login page,
                // and does not persist as a forgot-password specific message.
                $_SESSION['success_message'] = 'Password updated successfully. You may now log in.';
                header('Location: ../views/security/LoginForm.php');
                exit;
            } else {
                $_SESSION['fp_message'] = 'Failed to update password. Please try again.';
                header('Location: ../views/security/forgot_password.php');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['fp_message'] = $e->getMessage();
            header('Location: ../views/security/forgot_password.php');
            exit;
        }
    }

    public function updateMemberStatus()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validate required fields
                if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
                    throw new Exception("User ID is required");
                }

                if (!isset($_POST['status']) || empty($_POST['status'])) {
                    throw new Exception("Status is required");
                }

                $userId = (int)$_POST['user_id'];
                $status = $_POST['status'];

                // Validate status
                $allowedStatuses = ['active', 'inactive', 'banned'];
                if (!in_array($status, $allowedStatuses)) {
                    throw new Exception("Invalid status value");
                }

                $result = $this->membershipServices->updateMemberStatus($userId, $status);

                if ($result) {
                    $statusLabels = [
                        'active' => 'activated',
                        'inactive' => 'set to inactive',
                        'banned' => 'banned'
                    ];
                    $_SESSION['success_message'] = "Member " . $statusLabels[$status] . " successfully!";
                } else {
                    throw new Exception("Failed to update member status. Member may not exist or may not be a regular member.");
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
    $action = $_POST['action'] ?? $_GET['action'] ?? 'register';

    if ($action === 'register') {
        $controller->registerMember();
    } elseif ($action === 'update') {
        $controller->updateMember();
    } elseif ($action === 'updateStatus') {
        $controller->updateMemberStatus();
    } elseif ($action === 'delete') {
        $controller->deleteMember();
    }elseif ($action === 'login') {
        $controller->login();
        $action = $_POST['action'] ?? $_GET['action'] ?? 'register';
    } elseif ($action === 'send_reset') {
        $controller->sendReset();
    } elseif ($action === 'verify_reset') {
        $controller->verifyReset();
    } elseif ($action === 'complete_reset') {
        $controller->completeReset();
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
