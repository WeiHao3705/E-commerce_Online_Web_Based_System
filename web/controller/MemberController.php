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

                // Check if email is verified
                $user = $this->membershipServices->getMemberByUsername($username);
                if ($user && !$user['email_verified']) {
                    $_SESSION['error_message'] = 'Please verify your email before logging in.';
                    $_SESSION['unverified_email'] = $user['email'];
                    header('Location: ../views/security/email_verification.php');
                    exit;
                }

                // Save minimal user info in session as stdClass object
                $_SESSION['user'] = new stdClass();
                $_SESSION['user']->user_id = $userDTO->getUserId();
                $_SESSION['user']->username = $userDTO->getUsername();
                $_SESSION['user']->full_name = $userDTO->getFullName();
                $_SESSION['user']->email = $userDTO->getEmail();
                $_SESSION['user']->role = $userDTO->getRole();
                $_SESSION['user']->profile_photo = null;

                // Redirect based on user role
                if ($userDTO->getRole() === 'admin') {
                    header('Location: ../views/admin/AdminDashboard.php');
                } else {
                    header('Location: /index.php');
                }
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../views/security/login.php');
            exit;
        }
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['success_message'] = 'You have been logged out';
        header('Location: /index.php');
        exit;
    }

    public function registerMember()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Get DateOfBirth - prioritize the hidden date input (YYYY-MM-DD format)
                // If not available, check the text input and convert from DD/MM/YYYY
                $dateOfBirth = null;
                if (isset($_POST['DateOfBirth']) && !empty($_POST['DateOfBirth'])) {
                    $dateOfBirth = trim($_POST['DateOfBirth']);
                } elseif (isset($_POST['DateOfBirthText']) && !empty($_POST['DateOfBirthText'])) {
                    // Convert from DD/MM/YYYY to YYYY-MM-DD
                    $dateText = trim($_POST['DateOfBirthText']);
                    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dateText, $matches)) {
                        $dateOfBirth = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
                    }
                }
                
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
                    null, // profile_photo will be set later
                    $dateOfBirth
                );

                // Get profile photo data
                $profilePhotoFile = $_FILES['profile_photo'] ?? null;
                $croppedPhotoData = isset($_POST['profile_photo_cropped']) ? trim($_POST['profile_photo_cropped']) : null;

                $result = $this->membershipServices->registerMember($memberDTO, $profilePhotoFile, $croppedPhotoData);

                if ($result) {
                    $_SESSION['success_message'] = "Registration successful! Please check your email to verify your account before logging in.";
                    $_SESSION['registered_email'] = $memberDTO->getEmail();
                    header('Location: ../views/security/email_verification.php');
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

            header('Location: ../views/member_management/MemberRegisterForm.php');
            exit;
        }
    }

    public function verifyEmail()
    {
        try {
            $token = isset($_GET['token']) ? trim($_GET['token']) : '';

            if (empty($token)) {
                $_SESSION['error_message'] = 'Invalid verification link';
                header('Location: ../views/security/email_verification.php');
                exit;
            }

            $result = $this->membershipServices->verifyEmail($token);

            if ($result['success']) {
                $_SESSION['success_message'] = 'Email verified successfully! You can now log in.';
                header('Location: ../views/security/login.php');
            } else {
                $_SESSION['error_message'] = $result['message'];
                header('Location: ../views/security/email_verification.php');
            }
            exit;
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../views/security/email_verification.php');
            exit;
        }
    }

    public function resendVerificationEmail()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $email = isset($_POST['email']) ? trim($_POST['email']) : '';

                if (empty($email)) {
                    $_SESSION['error_message'] = 'Email is required';
                    header('Location: ../views/security/email_verification.php');
                    exit;
                }

                $result = $this->membershipServices->resendVerificationEmail($email);

                if ($result['success']) {
                    $_SESSION['success_message'] = 'Verification email sent! Please check your inbox.';
                } else {
                    $_SESSION['error_message'] = $result['message'];
                }

                header('Location: ../views/security/email_verification.php');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: ../views/security/email_verification.php');
            exit;
        }
    }

    public function showAllMembers()
    {
        try {
            // Check if this is an AJAX request
            if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
                $this->getMembersAjax();
                return;
            }

            // Get pagination, search, and sort parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
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
            // Initialize empty variables for the view
            $members = [];
            $pagination = [];
            $currentSort = ['sortBy' => 'created_at', 'sortOrder' => 'DESC'];
            // Show empty state instead of error message
            require_once __DIR__ . '/../views/member_management/AllMembers.php';
        }
    }

    private function getMembersAjax()
    {
        try {
            // Get pagination, search, and sort parameters
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
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

            // Return JSON response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'members' => $data['members'],
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
                $contact_no = isset($_POST['contact_no']) ? trim($_POST['contact_no']) : '';
                $selected_address_id = isset($_POST['selected_address_id']) ? (int)$_POST['selected_address_id'] : 0;

                // Server-side constraints: no empty fields
                if ($full_name === '' || $email === '' || $contact_no === '') {
                    throw new Exception("All fields are required.");
                }

                // Email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid email format.");
                }

                // Normalize phone: keep only digits, support 10–11 digit numbers (e.g. MY or US including country code)
                $contact_digits = preg_replace('/\D+/', '', $contact_no);
                $len = strlen($contact_digits);
                if ($len < 10 || $len > 11) {
                    throw new Exception("Invalid phone number format. Please enter a valid 10–11 digit number.");
                }

                // Store digits-only (keep leading country code if present, e.g. 1XXXXXXXXXX for US)
                $contact_no = $contact_digits;

                // Get current user data to preserve gender
                $currentUser = $this->membershipServices->getMemberById((int)$_POST['user_id']);
                if (!$currentUser) {
                    throw new Exception("User not found.");
                }
                $gender = $currentUser['gender'];

                $memberDTO = new MemberUpdateDTO(
                    (int)$_POST['user_id'],
                    $username,
                    $full_name,
                    $email,
                    $gender,
                    $contact_no
                );

                $result = $this->membershipServices->updateMember($memberDTO);

                // Update default address if selected
                if ($result && $selected_address_id > 0) {
                    $db = new Database();
                    $conn = $db->getConnection();

                    // Remove default from all user addresses
                    $stmtReset = $conn->prepare("UPDATE address SET is_default = 0 WHERE user_id = ?");
                    $stmtReset->execute([(int)$_POST['user_id']]);

                    // Set new default
                    $stmtSet = $conn->prepare("UPDATE address SET is_default = 1 WHERE id = ? AND user_id = ?");
                    $stmtSet->execute([$selected_address_id, (int)$_POST['user_id']]);
                }

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
                header('Location: ../views/security/login.php');
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

    public function updateProfilePhoto()
    {
        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }

            if (empty($_SESSION['user'])) {
                throw new Exception("User not authenticated");
            }

            $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

            if ($userId !== (int)$_SESSION['user']->user_id) {
                throw new Exception("Unauthorized access");
            }

            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("No file uploaded or upload error");
            }

            $file = $_FILES['photo'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            // Validate file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception("Invalid file type. Only JPG, PNG, and GIF are allowed.");
            }

            // Validate file size
            if ($file['size'] > $maxSize) {
                throw new Exception("File size exceeds 5MB limit.");
            }

            // Get username for filename
            $username = $_SESSION['user']->username;
            $safeUsername = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);

            // Create upload directory if it doesn't exist
            $uploadDir = __DIR__ . '/../images/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Remove old profile photos
            $oldFiles = glob($uploadDir . $safeUsername . '.*');
            foreach ($oldFiles as $oldFile) {
                if (is_file($oldFile)) {
                    unlink($oldFile);
                }
            }

            // Generate new filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = 'jpg';
            }
            $newFilename = $safeUsername . '.' . $extension;
            $targetPath = $uploadDir . $newFilename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception("Failed to save uploaded file.");
            }

            // Update database with new photo path
            $photoPath = 'web/images/profiles/' . $newFilename;
            $db = new Database();
            $conn = $db->getConnection();

            $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE user_id = ?");
            $stmt->execute([$photoPath, $userId]);

            // Compute the web-accessible URL
            $docRoot = $_SERVER['DOCUMENT_ROOT'];
            $webRootDir = dirname(__DIR__); // /web directory
            $relativePath = str_replace($docRoot, '', $webRootDir);
            $webBasePath = str_replace('\\', '/', $relativePath) . '/';
            
            echo json_encode([
                'success' => true,
                'message' => 'Profile photo updated successfully',
                'photoUrl' => $webBasePath . 'images/profiles/' . $newFilename
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    public function addAddress()
    {
        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }

            if (empty($_SESSION['user'])) {
                throw new Exception("User not authenticated");
            }

            $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
            $addressId = isset($_POST['address_id']) ? (int)$_POST['address_id'] : 0;

            if ($userId !== (int)$_SESSION['user']->user_id) {
                throw new Exception("Unauthorized access");
            }

            // Validate required fields
            $address1 = isset($_POST['address1']) ? trim($_POST['address1']) : '';
            $address2 = isset($_POST['address2']) ? trim($_POST['address2']) : '';
            $city = isset($_POST['city']) ? trim($_POST['city']) : '';
            $state = isset($_POST['state']) ? trim($_POST['state']) : '';
            $postcode = isset($_POST['postcode']) ? trim($_POST['postcode']) : '';
            $label = isset($_POST['label']) ? trim($_POST['label']) : 'home';

            if (empty($address1) || empty($city) || empty($state) || empty($postcode)) {
                throw new Exception("All required fields must be filled");
            }

            // Validate postcode (5 digits)
            if (!preg_match('/^\d{5}$/', $postcode)) {
                throw new Exception("Invalid postcode format");
            }

            // Validate label
            if (!in_array($label, ['home', 'work', 'other'])) {
                $label = 'home';
            }

            $db = new Database();
            $conn = $db->getConnection();

            // Check if this is an update or insert
            if ($addressId > 0) {
                // Update existing address
                $stmtCheck = $conn->prepare("SELECT id FROM address WHERE id = ? AND user_id = ?");
                $stmtCheck->execute([$addressId, $userId]);
                if (!$stmtCheck->fetch()) {
                    throw new Exception("Address not found or unauthorized");
                }

                $stmt = $conn->prepare("
                    UPDATE address 
                    SET address1 = ?, address2 = ?, city = ?, postcode = ?, state = ?, label = ?
                    WHERE id = ? AND user_id = ?
                ");

                $stmt->execute([
                    $address1,
                    $address2,
                    $city,
                    $postcode,
                    $state,
                    $label,
                    $addressId,
                    $userId
                ]);

                $message = 'Address updated successfully';
            } else {
                // Insert new address
                $stmtCount = $conn->prepare("SELECT COUNT(*) FROM address WHERE user_id = ?");
                $stmtCount->execute([$userId]);
                $addressCount = $stmtCount->fetchColumn();

                // If first address, set as default
                $isDefault = ($addressCount == 0) ? 1 : 0;

                $stmt = $conn->prepare("
                    INSERT INTO address (user_id, address1, address2, city, postcode, state, label, is_default) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $userId,
                    $address1,
                    $address2,
                    $city,
                    $postcode,
                    $state,
                    $label,
                    $isDefault
                ]);

                $message = 'Address added successfully';
            }

            echo json_encode([
                'success' => true,
                'message' => $message
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    public function deleteAddress()
    {
        header('Content-Type: application/json');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Invalid request method");
            }

            if (empty($_SESSION['user'])) {
                throw new Exception("User not authenticated");
            }

            $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
            $addressId = isset($_POST['address_id']) ? (int)$_POST['address_id'] : 0;

            if ($userId !== (int)$_SESSION['user']->user_id) {
                throw new Exception("Unauthorized access");
            }

            if ($addressId <= 0) {
                throw new Exception("Invalid address ID");
            }

            $db = new Database();
            $conn = $db->getConnection();

            // Verify the address belongs to the user
            $stmtCheck = $conn->prepare("SELECT is_default FROM address WHERE id = ? AND user_id = ?");
            $stmtCheck->execute([$addressId, $userId]);
            $address = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if (!$address) {
                throw new Exception("Address not found or unauthorized");
            }

            $wasDefault = $address['is_default'];

            // Delete the address
            $stmt = $conn->prepare("DELETE FROM address WHERE id = ? AND user_id = ?");
            $stmt->execute([$addressId, $userId]);

            // If deleted address was default, set the first remaining address as default
            if ($wasDefault) {
                $stmtFirst = $conn->prepare("SELECT id FROM address WHERE user_id = ? LIMIT 1");
                $stmtFirst->execute([$userId]);
                $firstAddress = $stmtFirst->fetch(PDO::FETCH_ASSOC);
                
                if ($firstAddress) {
                    $stmtUpdate = $conn->prepare("UPDATE address SET is_default = 1 WHERE id = ?");
                    $stmtUpdate->execute([$firstAddress['id']]);
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Address deleted successfully'
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
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

    public function bulkDeleteMembers()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Validate required fields
                if (!isset($_POST['user_ids']) || empty($_POST['user_ids'])) {
                    throw new Exception("Please select at least one member to delete");
                }

                $userIds = $_POST['user_ids'];
                if (!is_array($userIds)) {
                    $userIds = [$userIds];
                }

                $result = $this->membershipServices->bulkDeleteMembers($userIds);

                if ($result['success']) {
                    $_SESSION['success_message'] = $result['message'];
                } else {
                    throw new Exception($result['message']);
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
    } elseif ($action === 'update_photo') {
        $controller->updateProfilePhoto();
    } elseif ($action === 'add_address') {
        $controller->addAddress();
    } elseif ($action === 'edit_address') {
        $controller->addAddress();
    } elseif ($action === 'delete_address') {
        $controller->deleteAddress();
    } elseif ($action === 'updateStatus') {
        $controller->updateMemberStatus();
    } elseif ($action === 'delete') {
        $controller->deleteMember();
    } elseif ($action === 'bulkDelete') {
        $controller->bulkDeleteMembers();
    } elseif ($action === 'login') {
        $controller->login();
        $action = $_POST['action'] ?? $_GET['action'] ?? 'register';
    } elseif ($action === 'send_reset') {
        $controller->sendReset();
    } elseif ($action === 'verify_reset') {
        $controller->verifyReset();
    } elseif ($action === 'complete_reset') {
        $controller->completeReset();
    } elseif ($action === 'resend_verification') {
        $controller->resendVerificationEmail();
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
    } elseif ($action === 'verifyEmail') {
        $controller->verifyEmail();
    }
}
