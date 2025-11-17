<?php

require_once __DIR__ . "/../repository/MemberRepository.php";

class MembershipServices
{

    private $membershipRepository;

    public function __construct(MembershipRepository $membershipRepository)
    {
        $this->membershipRepository = $membershipRepository;
    }

    public function registerMember(MemberRegistrationDTO $memberDTO): bool
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

        return $this->membershipRepository->createMember($memberDTO);
    }

    public function getAllMembers($page = 1, $limit = 10, $searchTerm = ''): array
    {
        // Service responsibility: Calculate pagination offset
        $offset = ($page - 1) * $limit;

        // Service responsibility: Sanitize search term
        $searchTerm = trim($searchTerm);

        // Get members from repository
        $members = $this->membershipRepository->getAllMembers($limit, $offset, $searchTerm);
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
            // Log details useful for debugging (do not log the actual password hash)
            $hashLength = is_string($user['password']) ? strlen($user['password']) : 0;

            // Migration fallback: if stored password looks like plaintext (short length), allow login
            // by comparing plaintext, then re-hash and update the DB. This converts legacy plaintext
            // passwords to secure hashes on first successful login.
            if ($hashLength > 0 && $hashLength < 20 && $password === $user['password']) {
                error_log("Auth debug: plaintext match for username='{$username}'; migrating to hashed password");

                $newHash = password_hash($password, PASSWORD_DEFAULT);
                // Attempt to update the stored hash; log but do not block login on failure
                try {
                    $updated = $this->membershipRepository->updatePasswordHash($user['user_id'], $newHash);
                    if (!$updated) {
                        error_log("Auth debug: failed to update password hash for user_id={$user['user_id']}");
                    }
                } catch (Exception $e) {
                    error_log("Auth debug: exception while updating password hash: " . $e->getMessage());
                }

                // treat as verified
                $verify = true;
            } else {
                error_log("Auth debug: password_verify failed for username='{$username}'; stored_hash_length={$hashLength}");
                throw new Exception('Invalid username or password');
            }
        }

        // Build DTO (match MemberDTO constructor)
        $dto = new MemberDTO(
            $user['user_id'] ?? null,
            $user['username'] ?? null,
            $user['full_name'] ?? null,
            $user['email'] ?? null,
            $user['gender'] ?? null,
            $user['contact_no'] ?? null,
            $user['role'] ?? null,
            $user['status'] ?? null
        );

        return $dto;
    }
}
