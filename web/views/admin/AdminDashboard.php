<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user'])) {
    // Not logged in - redirect to login
    header('Location: ../security/login.php');
    exit;
}

// Check if user has admin role
if ($_SESSION['user']->role !== 'admin') {
    // User is logged in but not admin - redirect to member home
    $_SESSION['error_message'] = 'Access denied. Admin privileges required.';
    header('Location: ../../index.php');
    exit;
}

// Calculate base path (absolute from document root)
// Since this file is in web/views/admin/, go up two levels to get web root
$currentFileDir = dirname(__FILE__); // Gets web/views/admin/
$webRootDir = dirname(dirname($currentFileDir)); // Gets web/
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$webBasePath = str_replace('\\', '/', $relativePath) . '/'; // Normalize slashes
$cssBasePath = $webBasePath . 'css/';
$controllerBasePath = $webBasePath . 'controller/';
$viewsBasePath = $webBasePath . 'views/';

// For backward compatibility
$prefix = '../../';

// Include required files for database access
require_once __DIR__ . '/../../database/connection.php';
require_once __DIR__ . '/../../repository/VoucherRepository.php';
require_once __DIR__ . '/../../service/VoucherService.php';
require_once __DIR__ . '/../../repository/MemberRepository.php';
require_once __DIR__ . '/../../service/MemberService.php';

$pageTitle = 'Admin Dashboard';

// Get admin user info
$adminName = isset($_SESSION['user']->full_name) ? $_SESSION['user']->full_name : 'Admin';
$adminAvatar = isset($_SESSION['user']->profile_photo) ? $_SESSION['user']->profile_photo : $webBasePath . 'images/defaultUserImage.jpg';

// Get actual stats from database
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

    // Get total products count
    $totalProductsQuery = "SELECT COUNT(*) as total FROM product";
    $totalProductsStmt = $conn->query($totalProductsQuery);
    $totalProducts = $totalProductsStmt->fetch(PDO::FETCH_ASSOC)['total'];

    $stats = [
        'total_sales' => ['value' => 'RM ' . number_format($currentMonthSales, 2), 'change' => $monthSalesChangeFormatted],
        'active_members' => ['value' => $activeMembersFormatted, 'change' => $activeMembersChange],
        'total_products' => ['value' => number_format($totalProducts), 'change' => ''],
        'active_vouchers' => ['value' => $activeVouchersFormatted, 'change' => $activeVouchersChange]
    ];
} catch (Exception $e) {
    // Fallback to zeros if database error occurs
    error_log("Error fetching stats: " . $e->getMessage());
    $stats = [
        'total_sales' => ['value' => 'RM 0.00', 'change' => '0%'],
        'active_members' => ['value' => '0', 'change' => '0 (7d)'],
        'total_products' => ['value' => '0', 'change' => ''],
        'active_vouchers' => ['value' => '0', 'change' => '0 (7d)']
    ];
    $weeklySalesData = [0, 0, 0, 0];
    $currentWeekSales = 0;
    $weekSalesChangeFormatted = '0%';
}

// Get actual recent orders from database
try {
    $recentOrdersQuery = "
        SELECT o.order_id, 
               o.total_amount,
               o.create_at,
               oi.product_name_snapshot,
               pi.image_path
        FROM orders o
        LEFT JOIN order_item oi ON o.order_id = oi.order_id
        LEFT JOIN product p ON oi.product_id = p.product_id
        LEFT JOIN product_image pi ON p.product_id = pi.product_id AND pi.type = 'main'
        WHERE o.order_status != 'canceled'
        ORDER BY o.create_at DESC
        LIMIT 3
    ";
    $recentOrdersStmt = $conn->query($recentOrdersQuery);
    $recentOrdersResult = $recentOrdersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $recentOrders = [];
    foreach ($recentOrdersResult as $order) {
        $recentOrders[] = [
            'name' => $order['product_name_snapshot'] ?? 'Product',
            'id' => '#' . str_pad($order['order_id'], 6, '0', STR_PAD_LEFT),
            'price' => 'RM ' . number_format($order['total_amount'], 2),
            'image' => $webBasePath . 'images/' . ($order['image_path'] ?? 'products/default.jpg')
        ];
    }
    
    // If no orders found, show empty state
    if (empty($recentOrders)) {
        $recentOrders = [];
    }
} catch (Exception $e) {
    error_log("Error fetching recent orders: " . $e->getMessage());
    $recentOrders = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - NGEAR</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <!-- Font Awesome for chat icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>AdminDashboard.css">
</head>

<body data-user-role="admin" data-user-id="<?php echo htmlspecialchars($_SESSION['user']->user_id ?? ''); ?>">
    <?php
    // Display and clear success/error messages
    if (isset($_SESSION['success_message'])) {
        echo '<div class="success-popup" style="position: fixed; top: 80px; right: 20px; background: #4CAF50; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1000; max-width: 400px; animation: slideIn 0.3s ease;">';
        echo '<i class="fas fa-check-circle" style="margin-right: 8px;"></i>';
        echo htmlspecialchars($_SESSION['success_message']);
        echo '</div>';
        unset($_SESSION['success_message']);

        echo '<script>
            setTimeout(function() {
                var popup = document.querySelector(".success-popup");
                if (popup) {
                    popup.style.animation = "slideOut 0.3s ease";
                    setTimeout(function() { popup.remove(); }, 300);
                }
            }, 3000);
        </script>';
    }

    if (isset($_SESSION['error_message'])) {
        echo '<div class="error-popup" style="position: fixed; top: 80px; right: 20px; background: #f44336; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1000; max-width: 400px; animation: slideIn 0.3s ease;">';
        echo '<i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>';
        echo htmlspecialchars($_SESSION['error_message']);
        echo '</div>';
        unset($_SESSION['error_message']);

        echo '<script>
            setTimeout(function() {
                var popup = document.querySelector(".error-popup");
                if (popup) {
                    popup.style.animation = "slideOut 0.3s ease";
                    setTimeout(function() { popup.remove(); }, 300);
                }
            }, 5000);
        </script>';
    }
    ?>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="admin-sidebar">
            <div class="admin-sidebar-header">
                <img src="<?php echo $webBasePath; ?>images/logo/logo1.png" alt="NGEAR" class="admin-sidebar-logo">
                <h1 class="admin-sidebar-title">NGear</h1>
                <button class="admin-sidebar-toggle" id="sidebar-toggle" title="Toggle Sidebar">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>

            <a href="#" class="admin-user-profile" id="adminProfileLink" data-view="admin_profile" data-url="<?php echo $viewsBasePath; ?>admin/AdminProfile.php">
                <div class="admin-user-avatar" style="background-image: url('<?php echo htmlspecialchars($adminAvatar); ?>');"></div>
                <div class="admin-user-info">
                    <h2 class="admin-user-name"><?php echo htmlspecialchars($adminName); ?></h2>
                    <p class="admin-user-role">Administrator</p>
                </div>
            </a>

            <nav class="admin-nav">
                <a href="#" data-view="dashboard" class="admin-nav-item active">
                    <span class="material-symbols-outlined">dashboard</span>
                    <p>Dashboard</p>
                </a>
                <a href="#" data-view="products" data-url="<?php echo $viewsBasePath; ?>admin/AdminProduct.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">inventory_2</span>
                    <p>Products</p>
                </a>
                <a href="#" data-view="members" data-url="<?php echo $controllerBasePath; ?>MemberController.php?action=showAll" class="admin-nav-item">
                    <span class="material-symbols-outlined">group</span>
                    <p>Members</p>
                </a>
                <a href="#" data-view="admins" data-url="<?php echo $controllerBasePath; ?>AdminController.php?action=showAll" class="admin-nav-item">
                    <span class="material-symbols-outlined">admin_panel_settings</span>
                    <p>Admins</p>
                </a>
                <a href="#" data-view="vouchers" data-url="<?php echo $controllerBasePath; ?>VoucherController.php?action=showAll" class="admin-nav-item">
                    <span class="material-symbols-outlined">sell</span>
                    <p>Vouchers</p>
                </a>
                <a href="#" data-view="orders" data-url="<?php echo $viewsBasePath; ?>admin/AdminOrder.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">shopping_cart</span>
                    <p>Orders</p>
                </a>
                <a href="#" data-view="reviews" data-url="<?php echo $controllerBasePath; ?>ReviewController.php?action=viewAll" class="admin-nav-item">
                    <span class="material-symbols-outlined">star</span>
                    <p>Reviews</p>
                </a>
            </nav>

            <div class="admin-sidebar-footer">
                <a href="<?php echo $webBasePath; ?>logout.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">logout</span>
                    <p>Logout</p>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Dashboard View (Default) -->
            <div class="admin-main-content" id="dashboard-view">
                <!-- Header -->
                <header class="admin-header">
                    <h1 class="admin-page-title">Dashboard</h1>
                </header>

                <!-- Stats -->
                <section class="admin-stats-grid">
                    <div class="admin-stat-card">
                        <p class="admin-stat-label">Monthly Sales</p>
                        <p class="admin-stat-value" id="stat-total-sales-value"><?php echo htmlspecialchars($stats['total_sales']['value']); ?></p>
                        <p class="admin-stat-change" id="stat-total-sales-change"><?php echo htmlspecialchars($stats['total_sales']['change']); ?></p>
                    </div>
                    <div class="admin-stat-card">
                        <p class="admin-stat-label">All Members</p>
                        <p class="admin-stat-value" id="stat-active-members-value"><?php echo htmlspecialchars($stats['active_members']['value']); ?></p>
                        <p class="admin-stat-change" id="stat-active-members-change"><?php echo htmlspecialchars($stats['active_members']['change']); ?></p>
                    </div>
                    <div class="admin-stat-card">
                        <p class="admin-stat-label">Total Products</p>
                        <p class="admin-stat-value" id="stat-total-products-value"><?php echo htmlspecialchars($stats['total_products']['value']); ?></p>
                        <p class="admin-stat-change" id="stat-total-products-change"><?php echo htmlspecialchars($stats['total_products']['change']); ?></p>
                    </div>
                    <div class="admin-stat-card">
                        <p class="admin-stat-label">All Vouchers</p>
                        <p class="admin-stat-value" id="stat-active-vouchers-value"><?php echo htmlspecialchars($stats['active_vouchers']['value']); ?></p>
                        <p class="admin-stat-change" id="stat-active-vouchers-change"><?php echo htmlspecialchars($stats['active_vouchers']['change']); ?></p>
                    </div>
                </section>

                <!-- Charts and Recent Activity -->
                <section class="admin-content-grid">
                    <!-- Chart -->
                    <div class="admin-chart-card">
                        <p class="admin-chart-title">Sales Overview (Weekly)</p>
                        <div class="admin-chart-header">
                            <p class="admin-chart-value">RM <?php echo number_format($currentWeekSales, 2); ?></p>
                            <p class="admin-chart-change"><?php echo $weekSalesChangeFormatted; ?></p>
                        </div>
                        <p class="admin-chart-period" id="selected-week-label">This Week</p>
                        <div class="admin-chart-bars">
                            <?php 
                            $maxWeeklySales = max($weeklySalesData);
                            if ($maxWeeklySales == 0) $maxWeeklySales = 1; // Prevent division by zero
                            for ($i = 0; $i < 4; $i++): 
                                $height = ($weeklySalesData[$i] / $maxWeeklySales) * 100;
                                $isActive = ($i == 3) ? 'active' : '';
                                $weekLabel = ($i == 3) ? 'This Week' : (4 - $i) . ' weeks ago';
                                $weeksAgo = 3 - $i;
                            ?>
                            <div class="week-bar-container" data-week-index="<?php echo $i; ?>" data-weeks-ago="<?php echo $weeksAgo; ?>" data-sales="<?php echo $weeklySalesData[$i]; ?>" data-label="<?php echo $weekLabel; ?>" style="cursor: pointer;">
                                <div class="admin-chart-bar <?php echo $isActive; ?>" style="height: <?php echo max($height, 5); ?>%;"></div>
                                <p class="admin-chart-label <?php echo $isActive; ?>"><?php echo $weekLabel; ?></p>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Quick Actions / Recent Orders -->
                    <div class="admin-orders-card">
                        <div class="admin-orders-header">
                            <h3 class="admin-orders-title">Recent Orders</h3>
                            <a href="#" data-view="orders" data-url="<?php echo $viewsBasePath; ?>admin/AdminOrder.php" class="admin-orders-link">View All</a>
                        </div>
                        <div class="admin-orders-list">
                            <?php foreach ($recentOrders as $order): ?>
                                <div class="admin-order-item">
                                    <div class="admin-order-image" style="background-image: url('<?php echo htmlspecialchars($order['image']); ?>');"></div>
                                    <div class="admin-order-info">
                                        <p class="admin-order-name"><?php echo htmlspecialchars($order['name']); ?></p>
                                        <p class="admin-order-id"><?php echo htmlspecialchars($order['id']); ?></p>
                                    </div>
                                    <p class="admin-order-price"><?php echo htmlspecialchars($order['price']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="admin-divider"></div>
                        <h3 class="admin-quick-actions-title">Quick Actions</h3>
                        <div class="admin-quick-actions">
                            <a href="#" data-view="products" data-url="<?php echo $viewsBasePath; ?>admin/AdminProduct.php" class="admin-quick-action-btn">
                                <span class="material-symbols-outlined">add_shopping_cart</span>
                                <span>Add New Product</span>
                            </a>
                            <a href="#" data-view="vouchers" data-url="<?php echo $viewsBasePath; ?>voucher_management/VoucherRegisterForm.php?return_to=admin" class="admin-quick-action-btn">
                                <span class="material-symbols-outlined">confirmation_number</span>
                                <span>Create Voucher</span>
                            </a>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Content View (Members/Vouchers/Admin Profile) -->
            <div class="admin-content-view" id="content-view" style="display: none;">
                <div class="admin-content-header">
                    <h1 class="admin-content-title" id="content-title">Content</h1>
                </div>
                <div class="admin-content-iframe-wrapper">
                    <iframe id="content-iframe" class="admin-content-iframe" frameborder="0" allowfullscreen></iframe>
                </div>
            </div>

        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Pass PHP variables to JavaScript
        $(document).ready(function() {
            $('#dashboard-view').attr('data-views-base-path', '<?php echo $viewsBasePath; ?>');
            $('#dashboard-view').attr('data-web-base-path', '<?php echo $webBasePath; ?>');
            $('#dashboard-view').attr('data-stats-url', '<?php echo $viewsBasePath; ?>admin/getDashboardStats.php');
        });
    </script>
    <script src="<?php echo $webBasePath; ?>js/adminDashboard.js?v=<?php echo filemtime(__DIR__ . '/../../js/adminDashboard.js'); ?>"></script>

    <?php
    // Include chat component for admin to reply to messages
    if (isset($_SESSION['user']) && $_SESSION['user']->role === 'admin') {
        include __DIR__ . '/../chat/chat.php';
    }
    ?>
</body>

</html>