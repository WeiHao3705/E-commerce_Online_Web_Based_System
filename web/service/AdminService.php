<?php

require_once __DIR__ . '/../repository/AdminRepository.php';

class AdminService
{
    private $adminRepository;
    private $defaultProfilePhoto = 'web/images/defaultUserImage.jpg';

    public function __construct(AdminRepository $adminRepository)
    {
        $this->adminRepository = $adminRepository;
    }

    public function registerAdmin(AdminRegistrationDTO $adminDTO): bool
    {
        $existing = $this->adminRepository->checkExistingAdmin(
            $adminDTO->getUsername(),
            $adminDTO->getEmail(),
            $adminDTO->getContactNo()
        );

        if ($existing['exists'] === true) {
            throw new Exception($existing['message']);
        }

        if ($adminDTO->getPassword() !== $adminDTO->getRepeatPassword()) {
            throw new Exception('Passwords do not match');
        }

        $password = $adminDTO->getPassword();
        if (!$this->isPasswordStrong($password)) {
            throw new Exception('Password must be at least 8 characters and include uppercase, lowercase, number, and special character.');
        }

        $hashedPassword = password_hash($adminDTO->getPassword(), PASSWORD_DEFAULT);
        $adminDTO->setPassword($hashedPassword);
        $adminDTO->setRepeatPassword(null);

        $photo = $adminDTO->getProfilePhoto();
        if (!$photo || trim($photo) === '') {
            $adminDTO->setProfilePhoto($this->defaultProfilePhoto);
        }

        return $this->adminRepository->createAdmin($adminDTO);
    }

    private function isPasswordStrong(string $password): bool
    {
        $lengthOk = strlen($password) >= 8;
        $hasUpper = preg_match('/[A-Z]/', $password) === 1;
        $hasLower = preg_match('/[a-z]/', $password) === 1;
        $hasNumber = preg_match('/[0-9]/', $password) === 1;
        $hasSpecial = preg_match('/[!@#$%^&*]/', $password) === 1;

        return $lengthOk && $hasUpper && $hasLower && $hasNumber && $hasSpecial;
    }

    public function getAllAdmins($page = 1, $limit = 10, $searchTerm = '', $sortBy = 'created_at', $sortOrder = 'DESC', $statusFilter = ''): array
    {
        $page = (int)$page;
        $limit = (int)$limit;
        if ($page < 1) { $page = 1; }
        if ($limit < 1) { $limit = 10; }

        $offset = ($page - 1) * $limit;
        $searchTerm = trim($searchTerm);
        $statusFilter = trim($statusFilter);

        $admins = $this->adminRepository->getAllAdmins($limit, $offset, $searchTerm, $sortBy, $sortOrder, $statusFilter);
        $total = $this->adminRepository->getTotalAdminsCount($searchTerm, $statusFilter);
        $totalPages = $limit > 0 ? (int)ceil($total / $limit) : 1;

        return [
            'admins' => $admins,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_admins' => $total,
                'per_page' => $limit,
                'showing_from' => $offset + 1,
                'showing_to' => min($offset + $limit, $total)
            ]
        ];
    }

    public function updateAdmin(AdminUpdateDTO $adminDTO): bool
    {
        $existing = $this->adminRepository->checkExistingAdminForUpdate(
            $adminDTO->getUserId(),
            $adminDTO->getUsername(),
            $adminDTO->getEmail(),
            $adminDTO->getContactNo()
        );

        if ($existing['exists'] === true) {
            throw new Exception($existing['message']);
        }

        return $this->adminRepository->updateAdmin($adminDTO);
    }

    public function updateAdminStatus($userId, $status): bool
    {
        return $this->adminRepository->updateAdminStatus($userId, $status);
    }

    public function deleteAdmin($userId): bool
    {
        return $this->adminRepository->deleteAdmin($userId);
    }

    public function getAdminById($userId)
    {
        return $this->adminRepository->getAdminById($userId);
    }

    public function getAdminByUsername($username)
    {
        return $this->adminRepository->getAdminByUsername($username);
    }

    public function getAdminByEmail($email)
    {
        return $this->adminRepository->getAdminByEmail($email);
    }

    public function resetPassword($userId, $newPassword)
    {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->adminRepository->updatePasswordHash($userId, $hashed);
    }
}
