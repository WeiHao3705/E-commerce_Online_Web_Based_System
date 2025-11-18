<?php

require_once __DIR__ . '/../DTO/MemberDTO.php';

class MembershipRepository
{

    private $db;

    public function __construct(Database $databaseConnection)
    {
        $this->db = $databaseConnection->getConnection();
    }

    public function createMember(MemberRegistrationDTO $memberDTO)
    {
        $sql = "INSERT INTO users (username, password, full_name, gender, contact_no, email, security_question, security_answer) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute([
            $memberDTO->getUsername(),
            $memberDTO->getPassword(),
            $memberDTO->getFullName(),
            $memberDTO->getGender(),
            $memberDTO->getContactNo(),
            $memberDTO->getEmail(),
            $memberDTO->getSecurityQuestion(),
            $memberDTO->getSecurityAnswer()
        ]);

        return $result;
    }

    public function checkExistingMember($username, $email, $contactNo): array
    {
        // Check username
        $sql = "SELECT COUNT(*) as count FROM users WHERE username = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return ['exists' => true, 'field' => 'username', 'message' => 'Username already exists, Try others'];
        }

        // Check email
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return ['exists' => true, 'field' => 'email', 'message' => 'Email already exists'];
        }

        // Check contact number
        $sql = "SELECT COUNT(*) as count FROM users WHERE contact_no = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$contactNo]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return ['exists' => true, 'field' => 'contact_no', 'message' => 'Contact number already exists'];
        }

        return ['exists' => false];
    }

    public function getAllMembers($limit = 10, $offset = 0, $searchTerm = '', $sortBy = 'created_at', $sortOrder = 'DESC'): array
    {
        try {
            // Ensure limit and offset are integers
            $limit = (int)$limit;
            $offset = (int)$offset;

            // Validate sort column to prevent SQL injection
            $allowedSortColumns = ['username', 'full_name', 'email', 'contact_no', 'gender', 'created_at'];
            if (!in_array($sortBy, $allowedSortColumns)) {
                $sortBy = 'created_at';
            }

            // Validate sort order
            $sortOrder = strtoupper($sortOrder);
            if ($sortOrder !== 'ASC' && $sortOrder !== 'DESC') {
                $sortOrder = 'DESC';
            }

            // Base query
            $sql = "SELECT 
                    user_id,
                    username,
                    full_name,
                    email,
                    contact_no,
                    gender,
                    created_at
                FROM users
                WHERE role = 'member'";

            $params = [];

            // Add search filter if provided
            if (!empty($searchTerm)) {
                $sql .= " AND (
                        username LIKE :search OR
                        full_name LIKE :search OR
                        email LIKE :search OR
                        contact_no LIKE :search
                    )";
                $params[':search'] = "%{$searchTerm}%";
            }

            // Add ordering and pagination (safe integers and validated columns, not bound as parameters)
            $sql .= " ORDER BY $sortBy $sortOrder LIMIT $limit OFFSET $offset";

            // Debug logging
            error_log("SQL Query: " . $sql);
            error_log("Params: " . print_r($params, true));

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Log results
            error_log("Query Results: " . print_r($results, true));

            return $results;
        } catch (PDOException $e) {
            error_log("Database error in getAllMembers: " . $e->getMessage());
            throw new Exception("Error retrieving members");
        }
    }


    public function getTotalMembersCount($searchTerm = '')
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM users WHERE role ='member'";
            $params = [];

            if (!empty($searchTerm)) {
                $sql .= " AND (
                    username LIKE ? OR 
                    full_name LIKE ? OR 
                    email LIKE ? OR 
                    contact_no LIKE ?
                )";
                $searchParam = "%{$searchTerm}%";
                $params = array_fill(0, 4, $searchParam);
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Database error in getTotalMembersCount: " . $e->getMessage());
            throw new Exception("Error counting members");
        }
    }

    /**
     * Fetch a single user record by username
     * Returns associative array or null when not found
     */
    public function getMemberByUsername($username)
    {
        try {
            $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result : null;
        } catch (PDOException $e) {
            error_log("Database error in getMemberByUsername: " . $e->getMessage());
            throw new Exception("Error fetching member");
        }
    }

    /**
     * Update the stored password hash for a user by id
     */
    public function updatePasswordHash($userId, $newHashedPassword)
    {
        try {
            $sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$newHashedPassword, $userId]);
        } catch (PDOException $e) {
            error_log("Database error in updatePasswordHash: " . $e->getMessage());
            return false;
        }
    }

    public function updateMember(MemberUpdateDTO $memberDTO): bool
    {
        try {
            $sql = "UPDATE users 
                    SET full_name = ?, email = ?, gender = ?, contact_no = ? 
                    WHERE user_id = ?";

            $stmt = $this->db->prepare($sql);

            $result = $stmt->execute([
                $memberDTO->getFullName(),
                $memberDTO->getEmail(),
                $memberDTO->getGender(),
                $memberDTO->getContactNo(),
                $memberDTO->getUserId()
            ]);

            return $result;
        } catch (PDOException $e) {
            error_log("Database error in updateMember: " . $e->getMessage());
            throw new Exception("Error updating member");
        }
    }

    public function checkExistingMemberForUpdate($userId, $username, $email, $contactNo): array
    {
        // Check username (excluding current user)
        $sql = "SELECT COUNT(*) as count FROM users WHERE username = ? AND user_id != ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return ['exists' => true, 'field' => 'username', 'message' => 'Username already exists, Try others'];
        }

        // Check email (excluding current user)
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ? AND user_id != ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return ['exists' => true, 'field' => 'email', 'message' => 'Email already exists'];
        }

        // Check contact number (excluding current user)
        $sql = "SELECT COUNT(*) as count FROM users WHERE contact_no = ? AND user_id != ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$contactNo, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return ['exists' => true, 'field' => 'contact_no', 'message' => 'Contact number already exists'];
        }

        return ['exists' => false];
    }

    public function deleteMember($userId): bool
    {
        try {
            $sql = "DELETE FROM users WHERE user_id = ? AND role = 'member'";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$userId]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Database error in deleteMember: " . $e->getMessage());
            throw new Exception("Error deleting member");
        }
    }
}
