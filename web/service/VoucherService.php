<?php

require_once __DIR__ . "/../repository/VoucherRepository.php";

class VoucherService
{
    private $voucherRepository;

    public function __construct(VoucherRepository $voucherRepository)
    {
        $this->voucherRepository = $voucherRepository;
    }

    public function registerVoucher(VoucherRegistrationDTO $voucherDTO): bool
    {
        // Validate existing voucher code
        $existingVoucher = $this->voucherRepository->checkExistingVoucher($voucherDTO->getCode());

        if ($existingVoucher['exists'] === true) {
            throw new Exception($existingVoucher['message']);
        }

        // Validate dates
        $startDate = new DateTime($voucherDTO->getStartDate());
        $endDate = new DateTime($voucherDTO->getEndDate());

        if ($endDate < $startDate) {
            throw new Exception("End date must be after start date");
        }

        // Validate discount value based on type
        if ($voucherDTO->getType() === 'percent') {
            if ($voucherDTO->getDiscountValue() < 0 || $voucherDTO->getDiscountValue() > 100) {
                throw new Exception("Percentage discount must be between 0 and 100");
            }
        } elseif ($voucherDTO->getType() === 'fixed') {
            if ($voucherDTO->getDiscountValue() < 0) {
                throw new Exception("Fixed discount must be greater than 0");
            }
        } elseif ($voucherDTO->getType() === 'freeshipping') {
            // Free shipping doesn't need discount value validation
        }

        return $this->voucherRepository->createVoucher($voucherDTO);
    }

    public function getAllVouchers($page = 1, $limit = 10, $searchTerm = '', $sortBy = 'voucher_id', $sortOrder = 'DESC'): array
    {
        // Service responsibility: Calculate pagination offset
        $offset = ($page - 1) * $limit;

        // Service responsibility: Sanitize search term
        $searchTerm = trim($searchTerm);

        // Get vouchers from repository
        $vouchers = $this->voucherRepository->getAllVouchers($limit, $offset, $searchTerm, $sortBy, $sortOrder);
        $totalVouchers = $this->voucherRepository->getTotalVouchersCount($searchTerm);

        // Service responsibility: Calculate pagination data
        $totalPages = ceil($totalVouchers / $limit);

        return [
            'vouchers' => $vouchers,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_vouchers' => $totalVouchers,
                'per_page' => $limit,
                'showing_from' => $offset + 1,
                'showing_to' => min($offset + $limit, $totalVouchers)
            ]
        ];
    }

    /**
     * Update voucher data
     */
    public function updateVoucher(VoucherUpdateDTO $voucherDTO): bool
    {
        $existingVoucher = $this->voucherRepository->checkExistingVoucherForUpdate(
            $voucherDTO->getVoucherId(),
            $voucherDTO->getCode()
        );

        if ($existingVoucher['exists'] === true) {
            throw new Exception($existingVoucher['message']);
        }

        // Validate dates
        $startDate = new DateTime($voucherDTO->getStartDate());
        $endDate = new DateTime($voucherDTO->getEndDate());

        if ($endDate < $startDate) {
            throw new Exception("End date must be after start date");
        }

        // Validate discount value based on type
        if ($voucherDTO->getType() === 'percent') {
            if ($voucherDTO->getDiscountValue() < 0 || $voucherDTO->getDiscountValue() > 100) {
                throw new Exception("Percentage discount must be between 0 and 100");
            }
        } elseif ($voucherDTO->getType() === 'fixed') {
            if ($voucherDTO->getDiscountValue() < 0) {
                throw new Exception("Fixed discount must be greater than 0");
            }
        }

        return $this->voucherRepository->updateVoucher($voucherDTO);
    }

    /**
     * Update voucher status
     */
    public function updateVoucherStatus($voucherId, $status): bool
    {
        return $this->voucherRepository->updateVoucherStatus($voucherId, $status);
    }

    /**
     * Delete voucher
     */
    public function deleteVoucher($voucherId): bool
    {
        return $this->voucherRepository->deleteVoucher($voucherId);
    }

    /**
     * Get all active members for voucher assignment
     * Excludes members who already have the voucher assigned
     */
    public function getAllActiveMembers($voucherId = null): array
    {
        return $this->voucherRepository->getAllActiveMembers($voucherId);
    }

    /**
     * Assign voucher to all active members
     */
    public function assignVoucherToAllMembers($voucherId, $assignedBy = null): array
    {
        return $this->voucherRepository->assignVoucherToAllMembers($voucherId, $assignedBy);
    }

    /**
     * Assign voucher to specific members
     */
    public function assignVoucherToSpecificMembers($voucherId, array $memberIds, $assignedBy = null): array
    {
        if (empty($memberIds)) {
            throw new Exception("No members selected for assignment");
        }

        // Validate that all member IDs are valid integers
        $validMemberIds = [];
        foreach ($memberIds as $memberId) {
            $memberId = (int)$memberId;
            if ($memberId > 0) {
                $validMemberIds[] = $memberId;
            }
        }

        if (empty($validMemberIds)) {
            throw new Exception("No valid members selected");
        }

        return $this->voucherRepository->assignVoucherToSpecificMembers($voucherId, $validMemberIds, $assignedBy);
    }
}

