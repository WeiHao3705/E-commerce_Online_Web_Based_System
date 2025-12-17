<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']->role !== 'admin') {
    header('Location: ../security/login.php');
    exit;
}

require __DIR__ . '/../../database/connection.php';
$db = new Database();
$conn = $db->getConnection();

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$query = "
    SELECT o.*, 
           u.username, 
           u.email,
           COUNT(oi.order_item_id) as total_items
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    LEFT JOIN order_item oi ON o.order_id = oi.order_id
    WHERE 1=1
";

$params = [];

if ($status_filter !== 'all') {
    $query .= " AND o.order_status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $query .= " AND (u.username LIKE :search OR u.email LIKE :search OR o.order_id LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($date_from)) {
    $query .= " AND DATE(o.create_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(o.create_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

$query .= " GROUP BY o.order_id ORDER BY o.create_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN order_status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
        SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
        SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN order_status = 'canceled' THEN 1 ELSE 0 END) as canceled_orders,
        SUM(total_amount) as total_revenue
    FROM orders
";
$statsStmt = $conn->query($statsQuery);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Calculate base path
$currentFileDir = dirname(__FILE__);
$webRootDir = dirname(dirname($currentFileDir));
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$webBasePath = str_replace('\\', '/', $relativePath) . '/';
$cssBasePath = $webBasePath . 'css/';
$controllerBasePath = $webBasePath . 'controller/';
$viewsBasePath = $webBasePath . 'views/';

// Get admin user info
$adminName = isset($_SESSION['user']->full_name) ? $_SESSION['user']->full_name : 'Admin';
$adminAvatar = isset($_SESSION['user']->profile_photo) ? $_SESSION['user']->profile_photo : $webBasePath . 'images/defaultUserImage.jpg';

$pageTitle = "Manage Orders - Admin";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>AdminDashboard.css">
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>AdminOrder.css">
</head>
<body>
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

            <a href="AdminProfile.php" class="admin-user-profile">
                <div class="admin-user-avatar" style="background-image: url('<?php echo htmlspecialchars($adminAvatar); ?>');"></div>
                <div class="admin-user-info">
                    <h2 class="admin-user-name"><?php echo htmlspecialchars($adminName); ?></h2>
                    <p class="admin-user-role">Administrator</p>
                </div>
            </a>

            <nav class="admin-nav">
                <a href="AdminDashboard.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">dashboard</span>
                    <p>Dashboard</p>
                </a>
                <a href="AdminProduct.php" class="admin-nav-item">
                    <span class="material-symbols-outlined">inventory_2</span>
                    <p>Products</p>
                </a>
                <a href="<?php echo $controllerBasePath; ?>MemberController.php?action=showAll" class="admin-nav-item">
                    <span class="material-symbols-outlined">group</span>
                    <p>Members</p>
                </a>
                <a href="<?php echo $controllerBasePath; ?>AdminController.php?action=showAll" class="admin-nav-item">
                    <span class="material-symbols-outlined">admin_panel_settings</span>
                    <p>Admins</p>
                </a>
                <a href="<?php echo $controllerBasePath; ?>VoucherController.php?action=showAll" class="admin-nav-item">
                    <span class="material-symbols-outlined">sell</span>
                    <p>Vouchers</p>
                </a>
                <a href="AdminOrder.php" class="admin-nav-item active">
                    <span class="material-symbols-outlined">shopping_cart</span>
                    <p>Orders</p>
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
                    <h1 class="admin-page-title">Orders Management</h1>
                </header>

                <!-- Statistics Cards -->
                <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($stats['total_orders']) ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($stats['pending_orders']) ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($stats['delivered_orders']) ?></h3>
                    <p>Delivered</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-dollar-sign"></i></div>
                <div class="stat-info">
                    <h3>RM <?= number_format($stats['total_revenue'], 2) ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
                </section>

                <!-- Filters -->
                <section class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label><i class="fas fa-filter"></i> Status</label>
                    <select name="status" onchange="this.form.submit()">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Orders</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="paid" <?= $status_filter === 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="shipped" <?= $status_filter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="canceled" <?= $status_filter === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                        <option value="refunded" <?= $status_filter === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-search"></i> Search</label>
                    <input type="text" name="search" placeholder="Order ID, Username, Email..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> From Date</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> To Date</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="orders.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </form>
                </section>

                <!-- Orders Table -->
                <section class="table-container">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Order Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>No orders found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                                <td>
                                    <div class="customer-info">
                                        <strong><?= htmlspecialchars($order['username']) ?></strong>
                                        <small><?= htmlspecialchars($order['email']) ?></small>
                                    </div>
                                </td>
                                <td><?= $order['total_items'] ?> item(s)</td>
                                <td><strong>RM <?= number_format($order['total_amount'], 2) ?></strong></td>
                                <td>
                                    <span class="payment-badge payment-<?= strtolower($order['payment_status']) ?>">
                                        <?= ucfirst($order['payment_status']) ?>
                                    </span>
                                    <small><?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?></small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($order['order_status']) ?>">
                                        <?= ucfirst($order['order_status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y H:i', strtotime($order['create_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="order_details.php?id=<?= $order['order_id'] ?>" class="btn-action btn-view" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button onclick="updateOrderStatus(<?= $order['order_id'] ?>)" class="btn-action btn-edit" title="Update Status">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
            </div>
        </main>
    </div>

    <script>
    // Sidebar toggle functionality
    document.getElementById('sidebar-toggle')?.addEventListener('click', function() {
        document.getElementById('admin-sidebar')?.classList.toggle('collapsed');
    });

    function updateOrderStatus(orderId) {
        // Future implementation for updating order status
        alert('Update order status feature - Order #' + String(orderId).padStart(6, '0'));
    }
    </script>
</body>
</html>