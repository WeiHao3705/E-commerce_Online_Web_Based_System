<?php 
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header('Location: ../security/login.php');
    exit;
}

// Get order ID from URL
$orderId = $_GET['order_id'] ?? null;
if (!$orderId) {
    header('Location: cart.php');
    exit;
}

require __DIR__ . '/../../database/connection.php';
$db = new Database();
$conn = $db->getConnection();

$userId = $_SESSION['user_id'] ?? $_SESSION['user']->user_id;

// Fetch order details
$orderQuery = "
    SELECT o.*, p.payment_method, p.paid_amount, p.payment_date, p.transaction_id
    FROM orders o
    LEFT JOIN payment p ON o.order_id = p.order_id
    WHERE o.order_id = :order_id AND o.user_id = :user_id
";
$orderStmt = $conn->prepare($orderQuery);
$orderStmt->execute([':order_id' => $orderId, ':user_id' => $userId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: cart.php');
    exit;
}

// Fetch order items
$itemsQuery = "
    SELECT oi.*, pi.image_path
    FROM order_item oi
    LEFT JOIN product_image pi ON oi.product_id = pi.product_id
    WHERE oi.order_id = :order_id
    GROUP BY oi.order_item_id
";
$itemsStmt = $conn->prepare($itemsQuery);
$itemsStmt->execute([':order_id' => $orderId]);
$orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Order Confirmation";
include '../../general/_header.php'; 
?>

<link rel="stylesheet" href="../../css/order_confirmation.css">

<!-- Image Slideshow Background -->
<div class="slideshow-container">
    <div class="slide fade">
        <img src="../../images/products/adidas_ultraboost_green.webp" alt="Product 1">
    </div>
    <div class="slide fade">
        <img src="../../images/products/Nike_Air_Max_90_beige.webp" alt="Product 2">
    </div>
    <div class="slide fade">
        <img src="../../images/products/Nike_Air_Max_90_black.webp" alt="Product 3">
    </div>
    <div class="slide fade">
        <img src="../../images/products/Nike_Air_Max_90_grey.webp" alt="Product 4">
    </div>
    <div class="slide fade">
        <img src="../../images/products/AJ1.png" alt="Product 5">
    </div>
    <div class="slide fade">
        <img src="../../images/products/Dunk_Panda.png" alt="Product 6">
    </div>
</div>

<div class="confirmation-container">
    <!-- Progress Steps -->
    <div class="progress-steps">
        <div class="step completed">
            <div class="step-circle">
                <span class="step-number">1</span>
                <i class="fas fa-check step-check"></i>
            </div>
            <span class="step-label">Checkout</span>
        </div>
        <div class="step-line completed"></div>
        <div class="step completed">
            <div class="step-circle">
                <span class="step-number">2</span>
                <i class="fas fa-check step-check"></i>
            </div>
            <span class="step-label">Payment</span>
        </div>
        <div class="step-line completed"></div>
        <div class="step active">
            <div class="step-circle">
                <span class="step-number">3</span>
                <i class="fas fa-check step-check"></i>
            </div>
            <span class="step-label">Confirmed</span>
        </div>
    </div>

    <div class="success-header">
        <div class="success-icon">✓</div>
        <h1>Order Placed Successfully!</h1>
        <p>Thank you for your purchase. Your order has been confirmed.</p>
    </div>

    <div class="order-card">
        <h2>Order Details</h2>
        
        <div class="order-info">
            <div class="info-item">
                <div class="info-label">Order Number</div>
                <div class="info-value">#<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Order Date</div>
                <div class="info-value"><?= date('M d, Y', strtotime($order['create_at'])) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Payment Method</div>
                <div class="info-value"><?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Order Status</div>
                <div class="info-value"><?= ucfirst($order['order_status']) ?></div>
            </div>
        </div>

        <div class="order-items">
            <h3>Items Ordered (<?= count($orderItems) ?>)</h3>
            <?php foreach ($orderItems as $item): ?>
                <div class="item-row">
                    <img src="<?= $item['image_path'] ?? '../../images/products/default.png' ?>" 
                         alt="<?= htmlspecialchars($item['product_name_snapshot']) ?>" 
                         class="item-image">
                    <div class="item-details">
                        <div class="item-name"><?= htmlspecialchars($item['product_name_snapshot']) ?></div>
                        <div class="item-qty">Quantity: <?= $item['quantity'] ?> × RM <?= number_format($item['product_price_snapshot'], 2) ?></div>
                    </div>
                    <div class="item-price">RM <?= number_format($item['subtotal'], 2) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="total-section">
            <div>Total Paid</div>
            <div class="total-amount">RM <?= number_format($order['total_amount'], 2) ?></div>
        </div>
    </div>

    <div class="action-buttons">
        <a href="../../orders.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
        <a href="generate_receipt.php?order_id=<?= $orderId ?>" class="btn btn-success" target="_blank">
            <i class="fas fa-receipt"></i> Generate E-Receipt
        </a>
        <a href="../../index.php" class="btn btn-primary">Continue Shopping</a>
        
    </div>
</div>

<script>
let slideIndex = 0;
showSlides();

function showSlides() {
    let slides = document.getElementsByClassName("slide");
    for (let i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
    }
    slideIndex++;
    if (slideIndex > slides.length) {
        slideIndex = 1;
    }
    slides[slideIndex - 1].style.display = "block";
    setTimeout(showSlides, 3000); // Change image every 3 seconds
}
</script>

<?php include '../../general/_footer.php'; ?>
