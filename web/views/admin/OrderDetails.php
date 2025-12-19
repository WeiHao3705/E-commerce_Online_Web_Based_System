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

$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    $_SESSION['error_message'] = 'Order ID is required';
    header('Location: AdminOrder.php');
    exit;
}

// Get order details
$orderQuery = "
    SELECT o.*, 
           u.username, 
           u.email,
           u.full_name,
           v.code as voucher_code,
           v.type as discount_type,
           v.discount_value,
           a.address1,
           a.address2,
           a.city,
           a.postcode,
           a.state
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    LEFT JOIN voucher v ON o.voucher_id = v.voucher_id
    LEFT JOIN address a ON u.user_id = a.user_id AND a.is_default = 1
    WHERE o.order_id = :order_id
";
$stmt = $conn->prepare($orderQuery);
$stmt->execute([':order_id' => $orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['error_message'] = 'Order not found';
    header('Location: AdminOrder.php');
    exit;
}

// Get order items
$itemsQuery = "
    SELECT oi.*, 
           p.product_name,
           pi.image_path
    FROM order_item oi
    LEFT JOIN product p ON oi.product_id = p.product_id
    LEFT JOIN product_image pi ON p.product_id = pi.product_id AND pi.type = 'main'
    WHERE oi.order_id = :order_id
";
$itemsStmt = $conn->prepare($itemsQuery);
$itemsStmt->execute([':order_id' => $orderId]);
$orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get order notes
$notesQuery = "
    SELECT n.*, u.username as admin_username, u.full_name as admin_name
    FROM order_notes n
    LEFT JOIN users u ON n.admin_id = u.user_id
    WHERE n.order_id = :order_id
    ORDER BY n.created_at DESC
";
$notesStmt = $conn->prepare($notesQuery);
$notesStmt->execute([':order_id' => $orderId]);
$orderNotes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate base path
$currentFileDir = dirname(__FILE__);
$webRootDir = dirname(dirname($currentFileDir));
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$webBasePath = str_replace('\\', '/', $relativePath) . '/';
$cssBasePath = $webBasePath . 'css/';
$imagesBasePath = $webBasePath . 'images/';

$pageTitle = "Order #" . str_pad($orderId, 6, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - NGEAR Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>AdminOrder.css">
</head>
<body>
    <div class="details-container">
        <!-- Back Button -->
        <a href="AdminOrder.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>

        <!-- Order Header -->
        <div class="order-header">
            <div class="order-title">
                <h1><i class="fas fa-receipt"></i> Order #<?= str_pad($orderId, 6, '0', STR_PAD_LEFT) ?></h1>
                <div class="order-meta">
                    <span><i class="far fa-calendar"></i> <?= date('M d, Y H:i', strtotime($order['create_at'])) ?></span>
                    <span>
                        <i class="fas fa-box"></i>
                        <span class="status-badge status-<?= strtolower($order['order_status']) ?>">
                            <?= ucfirst($order['order_status']) ?>
                        </span>
                    </span>
                    <span>
                        <i class="fas fa-credit-card"></i>
                        <span class="payment-badge payment-<?= strtolower($order['payment_status']) ?>">
                            <?= ucfirst($order['payment_status']) ?>
                        </span>
                    </span>
                </div>
            </div>
            <div class="order-actions">
                <button onclick="window.print()" class="btn btn-secondary">
                    <i class="fas fa-print"></i> Print Invoice
                </button>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="details-grid">
            <!-- Left Column -->
            <div>
                <!-- Order Items -->
                <div class="details-card">
                    <div class="card-header">
                        <i class="fas fa-shopping-cart"></i>
                        <h2>Order Items (<?= count($orderItems) ?>)</h2>
                    </div>
                    <?php foreach ($orderItems as $item): ?>
                        <div class="order-item">
                            <img src="<?= $imagesBasePath . ($item['image_path'] ?? 'products/default.jpg') ?>" 
                                 alt="<?= htmlspecialchars($item['product_name_snapshot']) ?>" 
                                 class="item-image">
                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['product_name_snapshot']) ?></div>
                                <div class="item-price">RM <?= number_format($item['product_price_snapshot'], 2) ?> each</div>
                                <div class="item-quantity">Quantity: <?= $item['quantity'] ?></div>
                            </div>
                            <div class="item-subtotal">
                                RM <?= number_format($item['subtotal'], 2) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Order Summary -->
                    <div class="order-summary">
                        <?php
                        $subtotal = array_sum(array_column($orderItems, 'subtotal'));
                        $discount = 0;
                        if ($order['voucher_id']) {
                            if ($order['discount_type'] === 'percent') {
                                $discount = $subtotal * ($order['discount_value'] / 100);
                            } elseif ($order['discount_type'] === 'fixed') {
                                $discount = $order['discount_value'];
                            }
                            // freeshipping type doesn't affect total here
                        }
                        ?>
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>RM <?= number_format($subtotal, 2) ?></span>
                        </div>
                        <?php if ($discount > 0): ?>
                            <div class="summary-row">
                                <span>Discount (<?= htmlspecialchars($order['voucher_code']) ?>)</span>
                                <span>-RM <?= number_format($discount, 2) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span>RM <?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Admin Notes -->
                <div class="details-card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <i class="fas fa-sticky-note"></i>
                        <h2>Admin Notes</h2>
                    </div>
                    <?php if (empty($orderNotes)): ?>
                        <div class="empty-notes">
                            <i class="fas fa-clipboard" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p>No admin notes yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($orderNotes as $note): ?>
                            <div class="note-item">
                                <div class="note-header">
                                    <span class="note-author">
                                        <i class="fas fa-user-shield"></i> 
                                        <?= htmlspecialchars($note['admin_name'] ?? $note['admin_username']) ?>
                                    </span>
                                    <span class="note-date">
                                        <?= date('M d, Y H:i', strtotime($note['created_at'])) ?>
                                    </span>
                                </div>
                                <div class="note-text"><?= nl2br(htmlspecialchars($note['note_text'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column -->
            <div>
                <!-- Customer Information -->
                <div class="details-card">
                    <div class="card-header">
                        <i class="fas fa-user"></i>
                        <h2>Customer Information</h2>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Name</span>
                        <span class="info-value"><?= htmlspecialchars($order['full_name'] ?? $order['username']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?= htmlspecialchars($order['email']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">User ID</span>
                        <span class="info-value">#<?= $order['user_id'] ?></span>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="details-card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <i class="fas fa-credit-card"></i>
                        <h2>Payment Information</h2>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Method</span>
                        <span class="info-value"><?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Status</span>
                        <span class="info-value">
                            <span class="payment-badge payment-<?= strtolower($order['payment_status']) ?>">
                                <?= ucfirst($order['payment_status']) ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Amount</span>
                        <span class="info-value" style="color: var(--primary); font-weight: 700;">
                            RM <?= number_format($order['total_amount'], 2) ?>
                        </span>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="details-card" style="margin-top: 2rem;">
                    <div class="card-header">
                        <i class="fas fa-truck"></i>
                        <h2>Shipping Address</h2>
                    </div>
                    <div class="address-box">
                        <?php 
                        if ($order['shipping_address']) {
                            echo nl2br(htmlspecialchars($order['shipping_address']));
                        } elseif ($order['address1']) {
                            // Display formatted address from address table
                            echo htmlspecialchars($order['address1']) . '<br>';
                            if (!empty($order['address2'])) {
                                echo htmlspecialchars($order['address2']) . '<br>';
                            }
                            echo htmlspecialchars($order['postcode']) . ' ' . htmlspecialchars($order['city']) . '<br>';
                            echo htmlspecialchars($order['state']);
                        } else {
                            echo '<em style="color: var(--subtle-text-light);">No shipping address provided</em>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
