<?php

require_once __DIR__ . '/../DTO/MemberDTO.php';

class MembershipRepository
{

    private $db;

    public function __construct(Database $databaseConnection)
    {
        $this->db = $databaseConnection->getConnection();
    }

    // Replace the createMember method (lines 15-35) with:

    public function createMember(MemberRegistrationDTO $memberDTO)
    {
        $sql = "INSERT INTO users (username, password, full_name, gender, contact_no, email, security_question, security_answer, profile_photo, DateOfBirth, email_verified, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, FALSE, 'inactive')";

        $stmt = $this->db->prepare($sql);

        $result = $stmt->execute([
            $memberDTO->getUsername(),
            $memberDTO->getPassword(),
            $memberDTO->getFullName(),
            $memberDTO->getGender(),
            $memberDTO->getContactNo(),
            $memberDTO->getEmail(),
            $memberDTO->getSecurityQuestion(),
            $memberDTO->getSecurityAnswer(),
            $memberDTO->getProfilePhoto(),
            $memberDTO->getDateOfBirth()
        ]);

        return $result;
    }

    public function setVerificationToken($userId, $token, $expiredAt)
    {
        $sql = "UPDATE users SET verification_token = ?, token_expires_at = ? WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$token, $expiredAt, $userId]);
    }

    public function verifyEmail($token)
    {
        try {
            $sql = "SELECT user_id, email, token_expires_at, email_verified 
                    FROM users 
                    WHERE verification_token = ? 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'Invalid verification token'];
            }

            // Check if already verified
            if ($user['email_verified']) {
                return ['success' => false, 'message' => 'Email already verified'];
            }

            // Check if token expired
            if ($user['token_expires_at'] && strtotime($user['token_expires_at']) < time()) {
                return ['success' => false, 'message' => 'Verification token has expired. Please request a new one.'];
            }

            // Verify the email
            $sql = "UPDATE users 
                    SET email_verified = TRUE, 
                        verification_token = NULL, 
                        token_expires_at = NULL,
                        status = 'active'
                    WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$user['user_id']]);

            if ($result) {
                return ['success' => true, 'message' => 'Email verified successfully', 'user_id' => $user['user_id']];
            }

            return ['success' => false, 'message' => 'Failed to verify email'];
        } catch (PDOException $e) {
            error_log("Database error in verifyEmail: " . $e->getMessage());
            throw new Exception("Error verifying email");
        }
    }

    public function getMemberByVerificationToken($token)
    {
        try {
            $sql = "SELECT * FROM users WHERE verification_token = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$token]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result : null;
        } catch (PDOException $e) {
            error_log("Database error in getMemberByVerificationToken: " . $e->getMessage());
            return null;
        }
    }

    public function updateVerificationToken($email, $token, $expiresAt)
    {
        try {
            $sql = "UPDATE users 
                    SET verification_token = ?, 
                        token_expires_at = ? 
                    WHERE email = ? AND email_verified = FALSE";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$token, $expiresAt, $email]);
        } catch (PDOException $e) {
            error_log("Database error in updateVerificationToken: " . $e->getMessage());
            return false;
        }
    }

    public function checkExistingMember($username, $email, $contactNo): array
    {
        // Check username - only verified usernames block registration
        $sql = "SELECT COUNT(*) as count FROM users WHERE username = ? AND email_verified = TRUE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return ['exists' => true, 'field' => 'username', 'message' => 'Username already exists, Try others'];
        }

        // Check email - only verified emails block registration
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ? AND email_verified = TRUE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return ['exists' => true, 'field' => 'email', 'message' => 'Email already exists'];
        }
        
        // If email exists but is unverified, delete the old unverified record
        // This allows users to re-register if they didn't verify their email
        $sql = "SELECT user_id FROM users WHERE email = ? AND email_verified = FALSE";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $unverifiedUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($unverifiedUser) {
            // Delete the old unverified account to allow fresh registration
            try {
                $this->deleteMember($unverifiedUser['user_id']);
                error_log("Deleted unverified account for email: {$email} (user_id: {$unverifiedUser['user_id']})");
            } catch (Exception $e) {
                error_log("Failed to delete unverified account: " . $e->getMessage());
            }
        }

        // Check contact number - keep as is (contact numbers are unique regardless of verification status)
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

            // Trim and normalize search term
            $searchTerm = trim($searchTerm);

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
                    profile_photo,
                    status,
                    DateOfBirth,
                    created_at
                FROM users
                WHERE role = 'member'";

            $params = [];

            // Add search filter if provided (after trimming)
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
            // Trim and normalize search term
            $searchTerm = trim($searchTerm);

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
     * Get count of active members
     * Active members are those with role = 'member' and status = 'active'
     */
    public function getActiveMembersCount(): int
    {
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM users 
                    WHERE role = 'member' 
                    AND status = 'active'";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Database error in getActiveMembersCount: " . $e->getMessage());
            throw new Exception("Error counting active members");
        }
    }

    /**
     * Get count of active members that were created recently (in the last 7 days)
     * This represents new active members registered recently
     */
    public function getRecentActiveMembersCount($days = 7): int
    {
        try {
            $pastDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

            $sql = "SELECT COUNT(*) as total 
                    FROM users 
                    WHERE role = 'member' 
                    AND status = 'active' 
                    AND created_at >= ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$pastDate]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Database error in getRecentActiveMembersCount: " . $e->getMessage());
            throw new Exception("Error counting recent active members");
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
     * Fetch a single user record by email
     */
    public function getMemberByEmail($email)
    {
        try {
            $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result : null;
        } catch (PDOException $e) {
            error_log("Database error in getMemberByEmail: " . $e->getMessage());
            throw new Exception("Error fetching member by email");
        }
    }

    /**
     * Fetch a single user record by id
     */
    public function getMemberById($userId)
    {
        try {
            $sql = "SELECT * FROM users WHERE user_id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? $result : null;
        } catch (PDOException $e) {
            error_log("Database error in getMemberById: " . $e->getMessage());
            throw new Exception("Error fetching member by id");
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

    public function updateMemberStatus($userId, $status): bool
    {
        try {
            // Validate status
            $allowedStatuses = ['active', 'inactive', 'banned'];
            if (!in_array($status, $allowedStatuses)) {
                throw new Exception("Invalid status value");
            }

            $sql = "UPDATE users SET status = ? WHERE user_id = ? AND role = 'member'";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$status, $userId]);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Database error in updateMemberStatus: " . $e->getMessage());
            throw new Exception("Error updating member status");
        }
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

    /**
     * Bulk delete members by array of user IDs
     */
    public function bulkDeleteMembers(array $userIds): array
    {
        try {
            if (empty($userIds)) {
                return ['success' => false, 'message' => 'No members selected for deletion'];
            }

            // Validate and sanitize user IDs
            $validUserIds = [];
            foreach ($userIds as $userId) {
                $userId = (int)$userId;
                if ($userId > 0) {
                    $validUserIds[] = $userId;
                }
            }

            if (empty($validUserIds)) {
                return ['success' => false, 'message' => 'No valid member IDs provided'];
            }

            // Create placeholders for IN clause
            $placeholders = str_repeat('?,', count($validUserIds) - 1) . '?';

            $sql = "DELETE FROM users WHERE user_id IN ($placeholders) AND role = 'member'";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($validUserIds);

            $deletedCount = $stmt->rowCount();

            return [
                'success' => true,
                'deleted_count' => $deletedCount,
                'message' => "Successfully deleted $deletedCount member(s)."
            ];
        } catch (PDOException $e) {
            error_log("Database error in bulkDeleteMembers: " . $e->getMessage());
            throw new Exception("Error bulk deleting members: " . $e->getMessage());
        }
    }
}
