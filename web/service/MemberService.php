<?php

require_once __DIR__ . "/../repository/MemberRepository.php";

class MembershipServices
{

    private $membershipRepository;
    private $defaultProfilePhoto = 'web/images/defaultUserImage.jpg';

    public function __construct(MembershipRepository $membershipRepository)
    {
        $this->membershipRepository = $membershipRepository;
    }

    // Replace the registerMember method (lines 16-58) with:

    public function registerMember(MemberRegistrationDTO $memberDTO, ?array $profilePhoto = null, ?string $croppedPhotoData = null): bool
    {
        //Validate existing member
        $existingMember = $this->membershipRepository->checkExistingMember(
            $memberDTO->getUsername(),
            $memberDTO->getEmail(),
            $memberDTO->getContactNo()
        );

        if ($existingMember['exists'] === true) {
            throw new Exception($existingMember['message']);
        }

        // Validate passwords match
        if ($memberDTO->getPassword() !== $memberDTO->getRepeatPassword()) {
            throw new Exception("Passwords do not match");
        }

        $hashedPassword = password_hash($memberDTO->getPassword(), PASSWORD_DEFAULT);
        $memberDTO->setPassword($hashedPassword);

        $memberDTO->setRepeatPassword(null);

        // Handle profile photo upload
        $photoPath = null;

        if ($croppedPhotoData) {
            $profilePhoto = $this->createFileArrayFromBase64($croppedPhotoData);
        }

        if ($profilePhoto && isset($profilePhoto['error']) && $profilePhoto['error'] !== UPLOAD_ERR_NO_FILE) {
            $photoPath = $this->handleProfilePhotoUpload($profilePhoto);
        }

        if (!$photoPath) {
            $photoPath = $this->defaultProfilePhoto;
        }

        $memberDTO->setProfilePhoto($photoPath);

        // Create member with email_verified = false
        $result = $this->membershipRepository->createMember($memberDTO);

        if ($result) {
            // Get the newly created user ID
            $newUser = $this->membershipRepository->getMemberByEmail($memberDTO->getEmail());

            if ($newUser) {
                // Generate verification token
                $verificationToken = bin2hex(random_bytes(32)); // 64 character token
                $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours')); // Token expires in 24 hours

                // Store verification token
                $this->membershipRepository->setVerificationToken($newUser['user_id'], $verificationToken, $expiresAt);

                // Send verification email
                require_once __DIR__ . '/EmailService.php';
                $emailService = new EmailService();
                try {
                    $emailService->sendVerificationEmail(
                        $memberDTO->getEmail(),
                        $memberDTO->getFullName(),
                        $verificationToken
                    );
                } catch (Exception $e) {
                    error_log("Failed to send verification email: " . $e->getMessage());
                    // Don't fail registration if email fails, but log it
                }
            }
        }

        return $result;
    }

    // Add new method for resending verification email
    public function resendVerificationEmail($email): array
    {
        $user = $this->membershipRepository->getMemberByEmail($email);

        if (!$user) {
            return ['success' => false, 'message' => 'Email not found'];
        }

        if ($user['email_verified']) {
            return ['success' => false, 'message' => 'Email already verified'];
        }

        // Generate new verification token
        $verificationToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Update token
        $updated = $this->membershipRepository->updateVerificationToken($email, $verificationToken, $expiresAt);

        if (!$updated) {
            return ['success' => false, 'message' => 'Failed to generate verification token'];
        }

        // Send verification email
        require_once __DIR__ . '/EmailService.php';
        $emailService = new EmailService();
        try {
            $emailService->sendVerificationEmail(
                $email,
                $user['full_name'],
                $verificationToken
            );
            return ['success' => true, 'message' => 'Verification email sent successfully'];
        } catch (Exception $e) {
            error_log("Failed to send verification email: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send verification email'];
        }
    }

    // Add method to verify email
    public function verifyEmail($token): array
    {
        return $this->membershipRepository->verifyEmail($token);
    }

    public function getAllMembers($page = 1, $limit = 10, $searchTerm = '', $sortBy = 'created_at', $sortOrder = 'DESC'): array
    {
        // Service responsibility: Calculate pagination offset
        $offset = ($page - 1) * $limit;

        // Service responsibility: Sanitize search term
        $searchTerm = trim($searchTerm);

        // Get members from repository
        $members = $this->membershipRepository->getAllMembers($limit, $offset, $searchTerm, $sortBy, $sortOrder);
        $totalMembers = $this->membershipRepository->getTotalMembersCount($searchTerm);

        // Service responsibility: Calculate pagination data
        $totalPages = ceil($totalMembers / $limit);

        return [
            'members' => $members,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_members' => $totalMembers,
                'per_page' => $limit,
                'showing_from' => $offset + 1,
                'showing_to' => min($offset + $limit, $totalMembers)
            ]
        ];
    }

    /**
     * Authenticate user by username and password.
     * Returns MemberDTO on success or throws Exception on failure.
     */
    public function authenticate(string $username, string $password): MemberDTO
    {
        $user = $this->membershipRepository->getMemberByUsername($username);

        if (!$user) {
            error_log("Auth debug: user not found for username='{$username}'");
            throw new Exception('Invalid username or password');
        }

        if (!isset($user['password'])) {
            error_log("Auth debug: user record found but no password column for username='{$username}'");
            throw new Exception('Invalid username or password');
        }

        // Check if this is an admin user
        $isAdmin = isset($user['role']) && strtolower($user['role']) === 'admin';

        // Check if account is blocked (only for non-admin users)
        if (!$isAdmin && isset($user['status']) && strtolower($user['status']) === 'blocked') {
            unset($_SESSION['login_username']);
            throw new Exception('Your account has been blocked due to multiple failed login attempts. Please use the "Forgot Password" option to reset your password.');
        }

        // Check if account is banned
        if (isset($user['status']) && strtolower($user['status']) === 'banned') {
            throw new Exception('Your account has been banned. Please contact support.');
        }

        // Verify password
        $verify = password_verify($password, $user['password']);

        if (!$verify) {
            error_log("Auth debug: password_verify failed for username='{$username}'");
            
            // Only track failed attempts for non-admin users
            if (!$isAdmin) {
                // Increment failed login attempts
                $this->membershipRepository->incrementFailedLoginAttempts($username);
                
                // Get updated user data to check failed attempts
                $user = $this->membershipRepository->getMemberByUsername($username);
                $failedAttempts = isset($user['failed_login_attempts']) ? (int)$user['failed_login_attempts'] : 0;
                
                // Block account after 3 failed attempts
                if ($failedAttempts >= 3) {
                    $this->membershipRepository->blockUser($username);
                    unset($_SESSION['login_username']);
                    throw new Exception('Your account has been blocked due to multiple failed login attempts. Please use the "Forgot Password" option to reset your password.');
                }
                
                $remainingAttempts = 3 - $failedAttempts;
                $_SESSION['login_username'] = $username;
                throw new Exception("Invalid username or password. You have {$remainingAttempts} attempt(s) remaining before your account is blocked.");
            } else {
                // Simple error message for admins without attempt tracking
                throw new Exception('Invalid username or password');
            }
        }

        // Block login if email not verified (only for non-admin users)
        if (!$isAdmin && isset($user['email_verified']) && !$user['email_verified']) {
            throw new Exception('Please verify your email before logging in.');
        }

        // Reset failed login attempts on successful login (for all users)
        if (!$isAdmin) {
            $this->membershipRepository->resetFailedLoginAttempts($username);
        }

        // Build DTO
        return new MemberDTO(
            $user['user_id'] ?? null,
            $user['username'] ?? null,
            $user['full_name'] ?? null,
            $user['email'] ?? null,
            $user['gender'] ?? null,
            $user['contact_no'] ?? null,
            $user['role'] ?? null,
            $user['status'] ?? null
        );
    }

    /**
     * Update member data
     */
    public function updateMember(MemberUpdateDTO $memberDTO): bool
    {
        $existingMember = $this->membershipRepository->checkExistingMemberForUpdate(
            $memberDTO->getUserId(),
            $memberDTO->getUsername(),   // still passed but not updated
            $memberDTO->getEmail(),
            $memberDTO->getContactNo()
        );

        if ($existingMember['exists'] === true && $existingMember['field'] !== 'username') {
            throw new Exception($existingMember['message']);
        }

        return $this->membershipRepository->updateMember($memberDTO);
    }

    /**
     * Update member status
     */
    public function updateMemberStatus($userId, $status): bool
    {
        return $this->membershipRepository->updateMemberStatus($userId, $status);
    }

    /**
     * Delete member
     */
    public function deleteMember($userId): bool
    {
        return $this->membershipRepository->deleteMember($userId);
    }

    /**
     * Bulk delete members
     */
    public function bulkDeleteMembers(array $userIds): array
    {
        return $this->membershipRepository->bulkDeleteMembers($userIds);
    }

    /**
     * Fetch member by email (pass-through to repository)
     */
    public function getMemberByEmail($email)
    {
        return $this->membershipRepository->getMemberByEmail($email);
    }

    /**
     * Fetch member by id
     */
    public function getMemberById($userId)
    {
        return $this->membershipRepository->getMemberById($userId);
    }

    /**
     * Fetch member by username
     */
    public function getMemberByUsername($username)
    {
        return $this->membershipRepository->getMemberByUsername($username);
    }

    /**
     * Reset a member password by id (handles hashing)
     */
    public function resetPassword($userId, $newPassword)
    {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->membershipRepository->updatePasswordHash($userId, $hashed);
    }

    /**
     * Get count of active members
     */
    public function getActiveMembersCount(): int
    {
        return $this->membershipRepository->getActiveMembersCount();
    }

    /**
     * Get count of active members that were created recently
     */
    public function getRecentActiveMembersCount($days = 7): int
    {
        return $this->membershipRepository->getRecentActiveMembersCount($days);
    }

    /**
     * Handle profile photo upload logic
     */
    private function handleProfilePhotoUpload(array $file): ?string
    {
        $isBase64 = isset($file['is_base64']) && $file['is_base64'] === true;

        if (!isset($file['tmp_name']) || (!$isBase64 && !is_uploaded_file($file['tmp_name']))) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Failed to upload profile photo. Error code: " . $file['error']);
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            throw new Exception("Invalid profile photo format. Allowed: JPG, PNG, GIF, WEBP.");
        }

        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) {
            throw new Exception("Profile photo must be 2MB or smaller.");
        }

        $uploadDir = realpath(__DIR__ . '/../images');
        if ($uploadDir === false) {
            $uploadDir = __DIR__ . '/../images';
        }
        $profileDir = $uploadDir . '/profiles';

        if (!is_dir($profileDir) && !mkdir($profileDir, 0775, true) && !is_dir($profileDir)) {
            throw new Exception("Unable to create directory for profile photos.");
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeExtension = strtolower($extension);
        if ($safeExtension === '') {
            $safeExtension = $this->mapMimeToExtension($mimeType);
        }

        $fileName = uniqid('profile_', false) . '.' . $safeExtension;
        $destination = $profileDir . '/' . $fileName;

        $saveSucceeded = $isBase64
            ? rename($file['tmp_name'], $destination)
            : move_uploaded_file($file['tmp_name'], $destination);

        if (!$saveSucceeded) {
            throw new Exception("Failed to save profile photo.");
        }

        // Return web-accessible relative path
        return 'web/images/profiles/' . $fileName;
    }

    private function mapMimeToExtension(string $mimeType): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];

        return $map[$mimeType] ?? 'jpg';
    }

    private function createFileArrayFromBase64(string $dataUrl): array
    {
        if (!preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/', $dataUrl, $matches)) {
            throw new Exception("Invalid cropped photo data.");
        }

        $mimeType = $matches[1];
        $data = base64_decode($matches[2]);

        if ($data === false) {
            throw new Exception("Failed to decode cropped photo data.");
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'profile_');
        if ($tmpFile === false) {
            throw new Exception("Unable to create temporary file for cropped photo.");
        }

        if (file_put_contents($tmpFile, $data) === false) {
            throw new Exception("Unable to write cropped photo data.");
        }

        return [
            'name' => 'cropped_profile.' . $this->mapMimeToExtension($mimeType),
            'type' => $mimeType,
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => strlen($data),
            'is_base64' => true
        ];
    }

    /**
     * Save remember me token for persistent login
     */
    public function saveRememberToken(int $userId, string $hashedToken, string $expiry): bool
    {
        return $this->membershipRepository->saveRememberToken($userId, $hashedToken, $expiry);
    }

    /**
     * Get remember me token data for a user
     */
    public function getRememberToken(int $userId): ?array
    {
        return $this->membershipRepository->getRememberToken($userId);
    }

    /**
     * Clear remember me token for a user
     */
    public function clearRememberToken(int $userId): bool
    {
        return $this->membershipRepository->clearRememberToken($userId);
    }
}
