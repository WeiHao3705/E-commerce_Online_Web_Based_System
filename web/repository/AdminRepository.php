<?php

require_once __DIR__ . '/../DTO/AdminDTO.php';

class AdminRepository
{
    private $db;

    public function __construct(Database $databaseConnection)
    {
        $this->db = $databaseConnection->getConnection();
    }

    public function createAdmin(AdminRegistrationDTO $adminDTO): ?int
    {
        $sql = "INSERT INTO users (username, password, full_name, email, contact_no, gender, role, status, email_verified, profile_photo) \n                VALUES (?, ?, ?, ?, ?, ?, 'admin', 'active', 1, ? )";

        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute([
            $adminDTO->getUsername(),
            $adminDTO->getPassword(),
            $adminDTO->getFullName(),
            $adminDTO->getEmail(),
            $adminDTO->getContactNo(),
            $adminDTO->getGender(),
            $adminDTO->getProfilePhoto()
        ]);

        if ($result) {
            return (int)$this->db->lastInsertId();
        }
        
        return null;
    }

    public function checkExistingAdmin($username, $email, $contactNo): array
    {
        $sql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['count'] > 0) {
            return ['exists' => true, 'field' => 'username', 'message' => 'Username already exists'];
        }

        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['count'] > 0) {
            return ['exists' => true, 'field' => 'email', 'message' => 'Email already exists'];
        }

        if (!empty($contactNo)) {
            $sql = "SELECT COUNT(*) as count FROM users WHERE contact_no = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$contactNo]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                return ['exists' => true, 'field' => 'contact_no', 'message' => 'Contact number already exists'];
            }
        }

        return ['exists' => false];
    }

    public function getAllAdmins($limit = 10, $offset = 0, $searchTerm = '', $sortBy = 'created_at', $sortOrder = 'DESC', $statusFilter = ''): array
    {
        $limit = (int)$limit;
        $offset = (int)$offset;
        $searchTerm = trim($searchTerm);
        $statusFilter = trim($statusFilter);

        $allowedSortColumns = ['username', 'full_name', 'email', 'contact_no', 'gender', 'created_at', 'status'];
        if (!in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = 'created_at';
        }

        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT user_id, username, full_name, email, contact_no, gender, profile_photo, status, created_at \n                FROM users WHERE role = 'admin'";
        $params = [];

        if (!empty($searchTerm)) {
            $sql .= " AND (username LIKE :search OR full_name LIKE :search OR email LIKE :search OR contact_no LIKE :search)";
            $params[':search'] = "%{$searchTerm}%";
        }

        if (!empty($statusFilter)) {
            $allowedStatuses = ['active', 'inactive', 'banned', 'blocked'];
            if (in_array($statusFilter, $allowedStatuses, true)) {
                $sql .= " AND status = :status";
                $params[':status'] = $statusFilter;
            }
        }

        $sql .= " ORDER BY $sortBy $sortOrder LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalAdminsCount($searchTerm = '', $statusFilter = ''): int
    {
        $searchTerm = trim($searchTerm);
        $statusFilter = trim($statusFilter);
        $sql = "SELECT COUNT(*) as total FROM users WHERE role = 'admin'";
        $params = [];

        if (!empty($searchTerm)) {
            $sql .= " AND (username LIKE ? OR full_name LIKE ? OR email LIKE ? OR contact_no LIKE ?)";
            $search = "%{$searchTerm}%";
            $params = [$search, $search, $search, $search];
        }

        if (!empty($statusFilter)) {
            $allowedStatuses = ['active', 'inactive', 'banned', 'blocked'];
            if (in_array($statusFilter, $allowedStatuses, true)) {
                $sql .= " AND status = ?";
                $params[] = $statusFilter;
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['total'];
    }

    public function getAdminById($userId)
    {
        $sql = "SELECT * FROM users WHERE user_id = ? AND role = 'admin' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getAdminByUsername($username)
    {
        $sql = "SELECT * FROM users WHERE username = ? AND role = 'admin' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function getAdminByEmail($email)
    {
        $sql = "SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function updatePasswordHash($userId, $newHashedPassword): bool
    {
        $sql = "UPDATE users SET password = ? WHERE user_id = ? AND role = 'admin'";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$newHashedPassword, $userId]);
    }

    public function updateAdmin(AdminUpdateDTO $adminDTO): bool
    {
        $sql = "UPDATE users SET full_name = ?, email = ?, gender = ?, contact_no = ? WHERE user_id = ? AND role = 'admin'";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $adminDTO->getFullName(),
            $adminDTO->getEmail(),
            $adminDTO->getGender(),
            $adminDTO->getContactNo(),
            $adminDTO->getUserId()
        ]);
    }

    public function checkExistingAdminForUpdate($userId, $username, $email, $contactNo): array
    {
        $sql = "SELECT COUNT(*) as count FROM users WHERE username = ? AND user_id != ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['count'] > 0) {
            return ['exists' => true, 'field' => 'username', 'message' => 'Username already exists'];
        }

        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ? AND user_id != ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['count'] > 0) {
            return ['exists' => true, 'field' => 'email', 'message' => 'Email already exists'];
        }

        if (!empty($contactNo)) {
            $sql = "SELECT COUNT(*) as count FROM users WHERE contact_no = ? AND user_id != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$contactNo, $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                return ['exists' => true, 'field' => 'contact_no', 'message' => 'Contact number already exists'];
            }
        }

        return ['exists' => false];
    }

    public function updateAdminStatus($userId, $status): bool
    {
        $allowedStatuses = ['active', 'inactive', 'banned', 'blocked'];
        if (!in_array($status, $allowedStatuses, true)) {
            throw new Exception('Invalid status value');
        }

        $sql = "UPDATE users SET status = ? WHERE user_id = ? AND role = 'admin'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $userId]);
        return $stmt->rowCount() > 0;
    }

    public function deleteAdmin($userId): bool
    {
        $sql = "DELETE FROM users WHERE user_id = ? AND role = 'admin'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->rowCount() > 0;
    }
}
