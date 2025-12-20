<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please log in.']);
    exit;
}

// Check if user has admin role
if ($_SESSION['user']->role !== 'admin') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden. Admin privileges required.']);
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

    // Get all vouchers count
    $activeVouchersCount = $voucherService->getActiveVouchersCount();

    // Get recent new vouchers count (last 30 days / 1 month)
    $recentActiveVouchersCount = $voucherService->getRecentActiveVouchersCount(30);

    // Format the count with thousand separators
    $activeVouchersFormatted = number_format($activeVouchersCount);

    // Format the change indicator
    if ($recentActiveVouchersCount > 0) {
        $activeVouchersChange = '+' . number_format($recentActiveVouchersCount) . ' (30d)';
    } else {
        $activeVouchersChange = '0 (30d)';
    }

    // Member stats
    $memberRepository = new MembershipRepository($database);
    $memberService = new MembershipServices($memberRepository);

    // Get all members count
    $activeMembersCount = $memberService->getActiveMembersCount();

    // Get recent new members count (last 30 days / 1 month)
    $recentActiveMembersCount = $memberService->getRecentActiveMembersCount(30);

    // Format the count with thousand separators
    $activeMembersFormatted = number_format($activeMembersCount);

    // Format the change indicator
    if ($recentActiveMembersCount > 0) {
        $activeMembersChange = '+' . number_format($recentActiveMembersCount) . ' (30d)';
    } else {
        $activeMembersChange = '0 (30d)';
    }

    // Get sales data from orders table
    $conn = $database->getConnection();

    // Get current month sales (Monthly Sales)
    $currentMonthSalesQuery = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE MONTH(create_at) = MONTH(CURRENT_DATE()) AND YEAR(create_at) = YEAR(CURRENT_DATE()) AND order_status != 'canceled'";
    $currentMonthSalesStmt = $conn->query($currentMonthSalesQuery);
    $currentMonthSales = $currentMonthSalesStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get previous month sales for comparison
    $previousMonthSalesQuery = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE MONTH(create_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(create_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH) AND order_status != 'canceled'";
    $previousMonthSalesStmt = $conn->query($previousMonthSalesQuery);
    $previousMonthSales = $previousMonthSalesStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate percentage change
    if ($previousMonthSales > 0) {
        $monthSalesChangePercent = (($currentMonthSales - $previousMonthSales) / $previousMonthSales) * 100;
        $monthSalesChangeFormatted = ($monthSalesChangePercent >= 0 ? '+' : '') . number_format($monthSalesChangePercent, 1) . '%';
    } else {
        $monthSalesChangeFormatted = $currentMonthSales > 0 ? '+100%' : '0%';
    }

    // Get total products count
    $totalProductsQuery = "SELECT COUNT(*) as total FROM product";
    $totalProductsStmt = $conn->query($totalProductsQuery);
    $totalProducts = $totalProductsStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get current week sales (for Sales Overview chart)
    $currentWeekSalesQuery = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE YEARWEEK(create_at, 1) = YEARWEEK(CURRENT_DATE(), 1) AND order_status != 'canceled'";
    $currentWeekSalesStmt = $conn->query($currentWeekSalesQuery);
    $currentWeekSales = $currentWeekSalesStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get previous week sales for comparison
    $previousWeekSalesQuery = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE YEARWEEK(create_at, 1) = YEARWEEK(CURRENT_DATE() - INTERVAL 1 WEEK, 1) AND order_status != 'canceled'";
    $previousWeekSalesStmt = $conn->query($previousWeekSalesQuery);
    $previousWeekSales = $previousWeekSalesStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate weekly percentage change
    if ($previousWeekSales > 0) {
        $weekSalesChangePercent = (($currentWeekSales - $previousWeekSales) / $previousWeekSales) * 100;
        $weekSalesChangeFormatted = ($weekSalesChangePercent >= 0 ? '+' : '') . number_format($weekSalesChangePercent, 1) . '%';
    } else {
        $weekSalesChangeFormatted = $currentWeekSales > 0 ? '+100%' : '0%';
    }

    // Get last 4 weeks sales data for chart
    $weeklySalesData = [];
    for ($i = 3; $i >= 0; $i--) {
        $weekQuery = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE YEARWEEK(create_at, 1) = YEARWEEK(CURRENT_DATE() - INTERVAL $i WEEK, 1) AND order_status != 'canceled'";
        $weekStmt = $conn->query($weekQuery);
        $weeklySalesData[] = $weekStmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // Get recent orders
    $recentOrdersQuery = "
        SELECT o.order_id, 
               o.total_amount,
               o.create_at,
               oi.product_name_snapshot
        FROM orders o
        LEFT JOIN order_item oi ON o.order_id = oi.order_id
        WHERE o.order_status != 'canceled'
        ORDER BY o.create_at DESC
        LIMIT 3
    ";
    $recentOrdersStmt = $conn->query($recentOrdersQuery);
    $recentOrdersResult = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $recentOrders = [];
    foreach ($recentOrdersResult as $order) {
        $recentOrders[] = [
            'id' => str_pad($order['order_id'], 6, '0', STR_PAD_LEFT),
            'name' => $order['product_name_snapshot'] ?? 'Product',
            'price' => number_format($order['total_amount'], 2)
        ];
    }

    // Return statistics as JSON
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_sales' => ['value' => 'RM ' . number_format($currentMonthSales, 2), 'change' => $monthSalesChangeFormatted],
            'active_members' => ['value' => $activeMembersFormatted, 'change' => $activeMembersChange],
            'total_products' => ['value' => number_format($totalProducts), 'change' => ''],
            'active_vouchers' => ['value' => $activeVouchersFormatted, 'change' => $activeVouchersChange]
        ],
        'weekly_sales' => [
            'current' => $currentWeekSales,
            'change' => $weekSalesChangeFormatted,
            'data' => $weeklySalesData
        ],
        'recent_orders' => $recentOrders
    ]);
} catch (Exception $e) {
    error_log("Error fetching stats: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching statistics'
    ]);
}
?>
