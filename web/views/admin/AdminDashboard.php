<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../LoginForm.php');
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
$adminName = isset($_SESSION['user']['full_name']) ? $_SESSION['user']['full_name'] : 'Admin';
$adminAvatar = isset($_SESSION['user']['profile_photo']) ? $_SESSION['user']['profile_photo'] : $webBasePath . 'images/defaultUserImage.jpg';

// Get actual stats from database
try {
    $database = new Database();
    
    // Voucher stats
    $voucherRepository = new VoucherRepository($database);
    $voucherService = new VoucherService($voucherRepository);
    
    // Get active vouchers count
    $activeVouchersCount = $voucherService->getActiveVouchersCount();
    
    // Get recent active vouchers count (last 7 days)
    $recentActiveVouchersCount = $voucherService->getRecentActiveVouchersCount(7);
    
    // Format the count with thousand separators
    $activeVouchersFormatted = number_format($activeVouchersCount);
    
    // Format the change indicator
    $activeVouchersChange = $recentActiveVouchersCount > 0 ? '+' . $recentActiveVouchersCount : '0';
    
    // Member stats
    $memberRepository = new MembershipRepository($database);
    $memberService = new MembershipServices($memberRepository);
    
    // Get active members count
    $activeMembersCount = $memberService->getActiveMembersCount();
    
    // Get recent active members count (last 7 days)
    $recentActiveMembersCount = $memberService->getRecentActiveMembersCount(7);
    
    // Format the count with thousand separators
    $activeMembersFormatted = number_format($activeMembersCount);
    
    // Calculate percentage change (approximate - comparing recent to total)
    // This is a simplified calculation showing recent growth
    $activeMembersChange = $recentActiveMembersCount > 0 
        ? '+' . number_format(($recentActiveMembersCount / max($activeMembersCount, 1)) * 100, 1) . '%'
        : '0%';
    
    // TODO: Get other stats from database (sales, products)
    $stats = [
        'total_sales' => ['value' => '$1,234,567', 'change' => '+5.2%'],
        'active_members' => ['value' => $activeMembersFormatted, 'change' => $activeMembersChange],
        'total_products' => ['value' => '1,450', 'change' => '+12'],
        'active_vouchers' => ['value' => $activeVouchersFormatted, 'change' => $activeVouchersChange]
    ];
} catch (Exception $e) {
    // Fallback to placeholder if database error occurs
    error_log("Error fetching stats: " . $e->getMessage());
    $stats = [
        'total_sales' => ['value' => '$1,234,567', 'change' => '+5.2%'],
        'active_members' => ['value' => '0', 'change' => '0%'],
        'total_products' => ['value' => '1,450', 'change' => '+12'],
        'active_vouchers' => ['value' => '0', 'change' => '+0']
    ];
}

// TODO: Get actual recent orders from database
$recentOrders = [
    ['name' => 'Pro Running Shoes', 'id' => '#ORD-12345', 'price' => '$129.99', 'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuD69vuaeWxUD9PqXPTCkm0BoeBxpwRmAqYzugBROzB1q9UsU1CzSdYEkxAtLL-UpOe1kC5g83q5BP9bolSM-E06U6llm7tmsnuN-t3cWyKKHxclSEPimI4pfbHmidSfr1vZT3lX9s-aboZRcnGN1XerhpHpbluplG4SjDg5W64CTxg8EzYjoZ8t9pk7_rxEWyMWKGp93YuifO32ET73UbJUeATqGtElhbQv5EmeFeWf9qo1hrEAXJ8oGhnPEtdMoiXG9dXuTQ1UkNo'],
    ['name' => 'Yoga Mat Deluxe', 'id' => '#ORD-12344', 'price' => '$45.00', 'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAmtj3k6sUbrnDDfvzFfZ4u4wtddmvmQzLJHWqJuGNejXxFZiODfjcqffHhwwS2TQLPRlmxsNZgdTlTY8bVrooIPe3MNwHClwWhiWgE4eKqY_WBdwZv_aFjuMsgB7Ldp5U7eeGofyjVwgg02zLFTD3IUEt1JpFdQOiXd_EOFzVdc0jdkssg1mXoKxxSUBI8dRc6z3m2v49RvuV_J6yI9CcK-Cp6x7AP6RW1YVlCfF-CGBtAZYn3WwmQEIhvExVGHicK09nHZlgtChg'],
    ['name' => 'Smart Fitness Watch', 'id' => '#ORD-12343', 'price' => '$199.50', 'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDgw6kpUvezfd6DT2Dky09lZe-vDJ4ywVpjuGrtzDRJVHCzz0VQ5zI8VpIpdTNUrn_IRvm0__iQTb4Pwkq-rsa1ETYakuO6qIiW0MtkPMxJExmAsUBpFsaR1hviH-3lJFnw4W8gqQUedPcU2JyCt1R7Uar9ZSE8zRvQwOy21sTQgx6c-NijYKVKgvsiFONzLhxp0pTheZCAKahDPsnNo7573Zi6RKIGggTcNOJZY4kymEJX5l18ourVr3vTOD27pHprKAw9lc9QaaE']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - REDSTORE</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>AdminDashboard.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <span class="material-symbols-outlined admin-sidebar-logo">sports_soccer</span>
                <h1 class="admin-sidebar-title">NGear</h1>
            </div>

            <div class="admin-user-profile">
                <div class="admin-user-avatar" style="background-image: url('<?php echo htmlspecialchars($adminAvatar); ?>');"></div>
                <div class="admin-user-info">
                    <h2 class="admin-user-name"><?php echo htmlspecialchars($adminName); ?></h2>
                    <p class="admin-user-role">Administrator</p>
                </div>
            </div>

            <nav class="admin-nav">
                <a href="<?php echo $viewsBasePath; ?>admin/AdminDashboard.php" class="admin-nav-item active">
                    <span class="material-symbols-outlined">dashboard</span>
                    <p>Dashboard</p>
                </a>
                <a href="<?php echo $viewsBasePath; ?>ProductPage.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">inventory_2</span>
                    <p>Products</p>
                </a>
                <a href="<?php echo $controllerBasePath; ?>MemberController.php?action=showAll" class="admin-nav-item">
                    <span class="material-symbols-outlined">group</span>
                    <p>Members</p>
                </a>
                <a href="<?php echo $controllerBasePath; ?>VoucherController.php?action=showAll" class="admin-nav-item">
                    <span class="material-symbols-outlined">sell</span>
                    <p>Vouchers</p>
                </a>
                <a href="#" class="admin-nav-item">
                    <span class="material-symbols-outlined">settings</span>
                    <p>Settings</p>
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
            <div class="admin-main-content">
                <!-- Header -->
                <header class="admin-header">
                    <h1 class="admin-page-title">Dashboard</h1>
                    <div class="admin-header-actions">
                        <!-- Search Bar -->
                        <div class="admin-search-container">
                            <div class="admin-search-wrapper">
                                <div class="admin-search-icon">
                                    <span class="material-symbols-outlined">search</span>
                                </div>
                                <input type="text" class="admin-search-input" id="admin-search" placeholder="Search for products, members, etc." value="">
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Stats -->
                <section class="admin-stats-grid">
                    <div class="admin-stat-card">
                        <p class="admin-stat-label">Total Sales</p>
                        <p class="admin-stat-value"><?php echo htmlspecialchars($stats['total_sales']['value']); ?></p>
                        <p class="admin-stat-change"><?php echo htmlspecialchars($stats['total_sales']['change']); ?></p>
                    </div>
                    <div class="admin-stat-card">
                        <p class="admin-stat-label">Active Members</p>
                        <p class="admin-stat-value"><?php echo htmlspecialchars($stats['active_members']['value']); ?></p>
                        <p class="admin-stat-change"><?php echo htmlspecialchars($stats['active_members']['change']); ?></p>
                    </div>
                    <div class="admin-stat-card">
                        <p class="admin-stat-label">Total Products</p>
                        <p class="admin-stat-value"><?php echo htmlspecialchars($stats['total_products']['value']); ?></p>
                        <p class="admin-stat-change"><?php echo htmlspecialchars($stats['total_products']['change']); ?></p>
                    </div>
                    <div class="admin-stat-card">
                        <p class="admin-stat-label">Active Vouchers</p>
                        <p class="admin-stat-value"><?php echo htmlspecialchars($stats['active_vouchers']['value']); ?></p>
                        <p class="admin-stat-change"><?php echo htmlspecialchars($stats['active_vouchers']['change']); ?></p>
                    </div>
                </section>

                <!-- Charts and Recent Activity -->
                <section class="admin-content-grid">
                    <!-- Chart -->
                    <div class="admin-chart-card">
                        <p class="admin-chart-title">Sales Overview</p>
                        <div class="admin-chart-header">
                            <p class="admin-chart-value">$152,340</p>
                            <p class="admin-chart-change">+12.5%</p>
                        </div>
                        <p class="admin-chart-period">Last 30 Days</p>
                        <div class="admin-chart-bars">
                            <div>
                                <div class="admin-chart-bar" style="height: 50%;"></div>
                                <p class="admin-chart-label">Week 1</p>
                            </div>
                            <div>
                                <div class="admin-chart-bar" style="height: 80%;"></div>
                                <p class="admin-chart-label">Week 2</p>
                            </div>
                            <div>
                                <div class="admin-chart-bar" style="height: 65%;"></div>
                                <p class="admin-chart-label">Week 3</p>
                            </div>
                            <div>
                                <div class="admin-chart-bar active" style="height: 95%;"></div>
                                <p class="admin-chart-label active">Week 4</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions / Recent Orders -->
                    <div class="admin-orders-card">
                        <div class="admin-orders-header">
                            <h3 class="admin-orders-title">Recent Orders</h3>
                            <a href="#" class="admin-orders-link">View All</a>
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
                            <a href="<?php echo $viewsBasePath; ?>ProductPage.php" class="admin-quick-action-btn">
                                <span class="material-symbols-outlined">add_shopping_cart</span>
                                <span>Add New Product</span>
                            </a>
                            <a href="<?php echo $viewsBasePath; ?>voucher_management/VoucherRegisterForm.php?return_to=admin" class="admin-quick-action-btn">
                                <span class="material-symbols-outlined">confirmation_number</span>
                                <span>Create Voucher</span>
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // jQuery event handlers - following conventions (no inline JavaScript)
        $(document).ready(function() {
            // Search functionality
            $('#admin-search').on('input', function() {
                var searchTerm = $(this).val().toLowerCase();
                // TODO: Implement search functionality
                console.log('Searching for:', searchTerm);
            });

            // Navigation active state
            var currentPage = window.location.pathname;
            $('.admin-nav-item').each(function() {
                var href = $(this).attr('href');
                if (href && currentPage.indexOf(href.split('/').pop()) !== -1) {
                    $(this).addClass('active');
                }
            });
        });
    </script>
</body>
</html>

