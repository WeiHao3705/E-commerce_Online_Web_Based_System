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
}
