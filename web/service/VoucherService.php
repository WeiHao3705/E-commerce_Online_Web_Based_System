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

    /**
     * Parse CSV file and validate voucher data
     */
    public function parseCSVFile($filePath): array
    {
        try {
            if (!file_exists($filePath)) {
                return ['success' => false, 'error' => 'File not found'];
            }

            $vouchers = [];
            $errors = [];
            $rowNumber = 0;

            if (($handle = fopen($filePath, 'r')) !== false) {
                // Read header row
                $headers = fgetcsv($handle);
                $rowNumber++;

                // Validate headers
                $expectedHeaders = ['code', 'description', 'type', 'discount_value', 'min_spend', 'max_discount', 'start_date', 'end_date', 'is_redeemable'];
                if ($headers !== $expectedHeaders) {
                    fclose($handle);
                    return [
                        'success' => false,
                        'error' => 'Invalid CSV format. Please download the template and use the correct format.'
                    ];
                }

                // Read data rows
                while (($data = fgetcsv($handle)) !== false) {
                    $rowNumber++;
                    
                    // Skip empty rows
                    if (empty(array_filter($data))) {
                        continue;
                    }

                    // Map CSV data to array
                    $type = trim($data[2] ?? '');
                    $discountValue = trim($data[3] ?? '');
                    
                    // For freeshipping, set discount_value to 0 if empty
                    if ($type === 'freeshipping' && empty($discountValue)) {
                        $discountValue = '0';
                    }
                    
                    // Parse is_redeemable (default to 1 if not provided or empty)
                    $isRedeemable = trim($data[8] ?? '1');
                    $isRedeemable = ($isRedeemable === '1' || $isRedeemable === 'true' || $isRedeemable === 'yes' || $isRedeemable === '') ? true : false;
                    
                    $voucherData = [
                        'code' => trim($data[0] ?? ''),
                        'description' => trim($data[1] ?? ''),
                        'type' => $type,
                        'discount_value' => $discountValue,
                        'min_spend' => trim($data[4] ?? '0'),
                        'max_discount' => trim($data[5] ?? ''),
                        'start_date' => trim($data[6] ?? ''),
                        'end_date' => trim($data[7] ?? ''),
                        'is_redeemable' => $isRedeemable,
                        'row_number' => $rowNumber
                    ];

                    // Validate row
                    $validation = $this->validateVoucherRow($voucherData);
                    
                    if ($validation['valid']) {
                        $vouchers[] = $voucherData;
                    } else {
                        $errors[] = [
                            'row' => $rowNumber,
                            'data' => $voucherData,
                            'errors' => $validation['errors']
                        ];
                    }
                }
                fclose($handle);
            }

            return [
                'success' => true,
                'vouchers' => $vouchers,
                'errors' => $errors,
                'total_rows' => $rowNumber - 1,
                'valid_rows' => count($vouchers),
                'invalid_rows' => count($errors)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error parsing CSV file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate a single voucher row from CSV
     */
    private function validateVoucherRow(array $row): array
    {
        $errors = [];

        // Validate code (required)
        if (empty($row['code'])) {
            $errors[] = 'Code is required';
        } else {
            // Check if code already exists
            $existing = $this->voucherRepository->checkExistingVoucher($row['code']);
            if ($existing['exists']) {
                $errors[] = 'Code already exists: ' . $row['code'];
            }
        }

        // Validate type (required)
        $allowedTypes = ['percent', 'fixed', 'freeshipping'];
        if (empty($row['type'])) {
            $errors[] = 'Type is required';
        } elseif (!in_array($row['type'], $allowedTypes)) {
            $errors[] = 'Invalid type. Must be: percent, fixed, or freeshipping';
        }

        // Validate discount_value (required, except for freeshipping)
        if ($row['type'] !== 'freeshipping') {
            if (empty($row['discount_value'])) {
                $errors[] = 'Discount value is required';
            } else {
                $discountValue = floatval($row['discount_value']);
                if ($row['type'] === 'percent') {
                    if ($discountValue < 0 || $discountValue > 100) {
                        $errors[] = 'Percentage discount must be between 0 and 100';
                    }
                } elseif ($row['type'] === 'fixed') {
                    if ($discountValue <= 0) {
                        $errors[] = 'Fixed discount must be greater than 0';
                    }
                }
            }
        } else {
            // For freeshipping, ensure discount_value is 0 or empty
            if (!empty($row['discount_value']) && floatval($row['discount_value']) != 0) {
                $errors[] = 'Free shipping vouchers should have discount value of 0 or empty';
            }
        }

        // Validate dates (required)
        if (empty($row['start_date'])) {
            $errors[] = 'Start date is required';
        } else {
            $startDate = DateTime::createFromFormat('Y-m-d', $row['start_date']);
            if (!$startDate) {
                $errors[] = 'Invalid start date format. Use YYYY-MM-DD';
            }
        }

        if (empty($row['end_date'])) {
            $errors[] = 'End date is required';
        } else {
            $endDate = DateTime::createFromFormat('Y-m-d', $row['end_date']);
            if (!$endDate) {
                $errors[] = 'Invalid end date format. Use YYYY-MM-DD';
            }
        }

        // Validate date range
        if (!empty($row['start_date']) && !empty($row['end_date'])) {
            $startDate = DateTime::createFromFormat('Y-m-d', $row['start_date']);
            $endDate = DateTime::createFromFormat('Y-m-d', $row['end_date']);
            if ($startDate && $endDate && $endDate < $startDate) {
                $errors[] = 'End date must be after start date';
            }
        }

        // Validate min_spend (optional, but must be numeric if provided)
        if (!empty($row['min_spend']) && !is_numeric($row['min_spend'])) {
            $errors[] = 'Minimum spend must be a number';
        }

        // Validate max_discount (optional, but must be numeric if provided)
        if (!empty($row['max_discount']) && !is_numeric($row['max_discount'])) {
            $errors[] = 'Maximum discount must be a number';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Bulk import vouchers
     */
    public function bulkImportVouchers(array $vouchers): array
    {
        try {
            if (empty($vouchers)) {
                return [
                    'success' => false,
                    'message' => 'No valid vouchers to import'
                ];
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];

            foreach ($vouchers as $voucherData) {
                try {
                    // Handle discount_value for freeshipping
                    $discountValue = $voucherData['discount_value'];
                    if ($voucherData['type'] === 'freeshipping' && (empty($discountValue) || $discountValue === '')) {
                        $discountValue = 0;
                    }
                    
                    // Handle is_redeemable
                    $isRedeemable = isset($voucherData['is_redeemable']) ? $voucherData['is_redeemable'] : true;
                    
                    // Create DTO
                    $voucherDTO = new VoucherRegistrationDTO(
                        $voucherData['code'],
                        $voucherData['description'] ?? '',
                        $voucherData['type'],
                        $discountValue,
                        $voucherData['min_spend'] ?? 0,
                        !empty($voucherData['max_discount']) ? $voucherData['max_discount'] : null,
                        $voucherData['start_date'],
                        $voucherData['end_date'],
                        false,
                        $isRedeemable
                    );

                    // Register voucher
                    $result = $this->registerVoucher($voucherDTO);
                    
                    if ($result) {
                        $successCount++;
                    } else {
                        $errorCount++;
                        $errors[] = 'Failed to import voucher: ' . $voucherData['code'];
                    }
                } catch (Exception $e) {
                    $errorCount++;
                    $errors[] = 'Error importing voucher ' . ($voucherData['code'] ?? 'unknown') . ': ' . $e->getMessage();
                }
            }

            $message = "Successfully imported $successCount voucher(s).";
            if ($errorCount > 0) {
                $message .= " $errorCount voucher(s) failed to import.";
            }

            return [
                'success' => $successCount > 0,
                'message' => $message,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error during bulk import: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get count of active vouchers
     */
    public function getActiveVouchersCount(): int
    {
        return $this->voucherRepository->getActiveVouchersCount();
    }

    /**
     * Get count of active vouchers that started recently
     */
    public function getRecentActiveVouchersCount($days = 7): int
    {
        return $this->voucherRepository->getRecentActiveVouchersCount($days);
    }

    /**
     * Get all vouchers assigned to a specific member
     */
    public function getMemberVouchers($userId, $filter = 'all', $sortBy = 'end_date', $sortOrder = 'ASC'): array
    {
        return $this->voucherRepository->getMemberVouchers($userId, $filter, $sortBy, $sortOrder);
    }

    /**
     * Redeem a voucher by code for a specific member
     */
    public function redeemVoucherByCode($code, $userId): array
    {
        // Validate code is not empty
        if (empty(trim($code))) {
            return [
                'success' => false,
                'message' => 'Please enter a voucher code.'
            ];
        }

        // Call repository method to redeem voucher
        return $this->voucherRepository->redeemVoucherByCode(trim($code), $userId);
    }
}

