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

    public function getAllMembers($limit = 10, $offset = 0, $searchTerm = ''): array
    {
        try {
            // Ensure limit and offset are integers
            $limit = (int)$limit;
            $offset = (int)$offset;

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

            // Add ordering and pagination (safe integers, not bound as parameters)
            $sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

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
}
