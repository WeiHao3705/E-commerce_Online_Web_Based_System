<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Include required files for database access
require_once __DIR__ . '/../../database/connection.php';
require_once __DIR__ . '/../../repository/VoucherRepository.php';
require_once __DIR__ . '/../../service/VoucherService.php';
require_once __DIR__ . '/../../repository/MemberRepository.php';
require_once __DIR__ . '/../../service/MemberService.php';

header('Content-Type: application/json');

try {
    $database = new Database();

    // Voucher stats
    $voucherRepository = new VoucherRepository($database);
    $voucherService = new VoucherService($voucherRepository);

    // Automatically expire vouchers that have passed their end date
    $voucherService->autoExpireVouchers();

    // Get active vouchers count
    $activeVouchersCount = $voucherService->getActiveVouchersCount();

    // Get recent active vouchers count (last 7 days)
    $recentActiveVouchersCount = $voucherService->getRecentActiveVouchersCount(7);

    // Format the count with thousand separators
    $activeVouchersFormatted = number_format($activeVouchersCount);

    // Format the change indicator
    if ($recentActiveVouchersCount > 0) {
        $activeVouchersChange = '+' . number_format($recentActiveVouchersCount) . ' (7d)';
    } else {
        $activeVouchersChange = '0 (7d)';
    }

    // Member stats
    $memberRepository = new MembershipRepository($database);
    $memberService = new MembershipServices($memberRepository);

    // Get active members count
    $activeMembersCount = $memberService->getActiveMembersCount();

    // Get recent active members count (last 7 days)
    $recentActiveMembersCount = $memberService->getRecentActiveMembersCount(7);

    // Format the count with thousand separators
    $activeMembersFormatted = number_format($activeMembersCount);

    // Format the change indicator
    if ($recentActiveMembersCount > 0) {
        $activeMembersChange = '+' . number_format($recentActiveMembersCount) . ' (7d)';
    } else {
        $activeMembersChange = '0 (7d)';
    }

    // Return statistics as JSON
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_sales' => ['value' => '$1,234,567', 'change' => '+5.2%'],
            'active_members' => ['value' => $activeMembersFormatted, 'change' => $activeMembersChange],
            'total_products' => ['value' => '1,450', 'change' => '+12'],
            'active_vouchers' => ['value' => $activeVouchersFormatted, 'change' => $activeVouchersChange]
        ]
    ]);
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching statistics'
    ]);
}
?>
