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

        return $this->membershipRepository->createMember($memberDTO);
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

        $verify = password_verify($password, $user['password']);

        if (!$verify) {
            $hashLength = is_string($user['password']) ? strlen($user['password']) : 0;

            // Legacy plaintext password migration
            if ($hashLength > 0 && $hashLength < 20 && $password === $user['password']) {
                error_log("Auth debug: plaintext match for username='{$username}'; migrating to hashed password");

                $newHash = password_hash($password, PASSWORD_DEFAULT);

                try {
                    $updated = $this->membershipRepository->updatePasswordHash($user['user_id'], $newHash);
                    if (!$updated) {
                        error_log("Auth debug: failed to update password hash for user_id={$user['user_id']}");
                    }
                } catch (Exception $e) {
                    error_log("Auth debug: exception while updating password hash: " . $e->getMessage());
                }

                $verify = true;
            } else {
                error_log("Auth debug: password_verify failed for username='{$username}'; stored_hash_length={$hashLength}");
                throw new Exception('Invalid username or password');
            }
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
}
