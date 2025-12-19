<?php 
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header('Location: views/security/login.php');
    exit;
}

require __DIR__ . '/database/connection.php';
$db = new Database();
$conn = $db->getConnection();

$userId = $_SESSION['user_id'] ?? $_SESSION['user']->user_id;

// Fetch all orders for the user
$ordersQuery = "
    SELECT o.*, p.payment_method, p.paid_amount, p.payment_date,
           COUNT(oi.order_item_id) as total_items
    FROM orders o
    LEFT JOIN payment p ON o.order_id = p.order_id
    LEFT JOIN order_item oi ON o.order_id = oi.order_id
    WHERE o.user_id = :user_id
    GROUP BY o.order_id
    ORDER BY o.create_at DESC
";
$ordersStmt = $conn->prepare($ordersQuery);
$ordersStmt->execute([':user_id' => $userId]);
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "My Orders";
include 'general/_header.php'; 
include 'general/_navbar.php';
?>

<link rel="stylesheet" href="css/orders.css">

<div class="orders-container">
    <div class="orders-header">
        <h1><i class="fas fa-box"></i> My Orders</h1>
        <p>Track and manage all your orders</p>
    </div>

    <?php if (empty($orders)): ?>
        <div class="empty-orders">
            <i class="fas fa-shopping-bag"></i>
            <h2>No Orders Yet</h2>
            <p>You haven't placed any orders yet. Start shopping to see your orders here!</p>
            <a href="../index.php" class="btn btn-primary">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-number">
                            <strong>Order #<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></strong>
                            <span class="order-date"><?= date('M d, Y', strtotime($order['create_at'])) ?></span>
                        </div>
                        <div class="order-status">
                            <span class="status-badge status-<?= strtolower($order['order_status']) ?>">
                                <?= ucfirst($order['order_status']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="order-body">
                        <div class="order-info-grid">
                            <div class="info-item">
                                <i class="fas fa-shopping-cart"></i>
                                <div>
                                    <span class="label">Items</span>
                                    <span class="value"><?= $order['total_items'] ?> item(s)</span>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-credit-card"></i>
                                <div>
                                    <span class="label">Payment Method</span>
                                    <span class="value"><?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></span>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-money-bill-wave"></i>
                                <div>
                                    <span class="label">Total Amount</span>
                                    <span class="value amount">RM <?= number_format($order['total_amount'], 2) ?></span>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-calendar-check"></i>
                                <div>
                                    <span class="label">Payment Date</span>
                                    <span class="value"><?= $order['payment_date'] ? date('M d, Y', strtotime($order['payment_date'])) : 'Pending' ?></span>
                                </div>
                            </div>
                        </div>

                        <?php
                        // Fetch order items for this order
                        $itemsQuery = "
                            SELECT oi.*, pi.image_path
                            FROM order_item oi
                            LEFT JOIN product_image pi ON oi.product_id = pi.product_id
                            WHERE oi.order_id = :order_id
                            GROUP BY oi.order_item_id
                            LIMIT 3
                        ";
                        $itemsStmt = $conn->prepare($itemsQuery);
                        $itemsStmt->execute([':order_id' => $order['order_id']]);
                        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php if (!empty($items)): ?>
                            <div class="order-items-preview">
                                <?php foreach ($items as $item): ?>
                                    <div class="item-preview">
                                        <img src="<?= $item['image_path'] ?? 'images/products/default.png' ?>" 
                                             alt="<?= htmlspecialchars($item['product_name_snapshot']) ?>">
                                        <div class="item-info">
                                            <span class="item-name"><?= htmlspecialchars($item['product_name_snapshot']) ?></span>
                                            <span class="item-qty">Qty: <?= $item['quantity'] ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($order['total_items'] > 3): ?>
                                    <div class="more-items">
                                        +<?= $order['total_items'] - 3 ?> more
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="order-footer">
                        <a href="views/Cart_Order/order_confirmation.php?order_id=<?= $order['order_id'] ?>" class="btn btn-secondary">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <?php if ($order['order_status'] === 'paid'): ?>
                            <a href="views/Cart_Order/generate_receipt.php?order_id=<?= $order['order_id'] ?>" class="btn btn-success" target="_blank">
                                <i class="fas fa-receipt"></i> E-Receipt
                            </a>
                        <?php endif; ?>
                        <?php if ($order['order_status'] === 'pending' || $order['order_status'] === 'paid'): ?>
                            <button class="btn btn-outline" onclick="trackOrder(<?= $order['order_id'] ?>)">
                                <i class="fas fa-truck"></i> Track Order
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function trackOrder(orderId) {
    // Future implementation for order tracking
    alert('Order tracking feature coming soon for Order #' + String(orderId).padStart(6, '0'));
}
</script>

<?php include 'general/_footer.php'; ?>
