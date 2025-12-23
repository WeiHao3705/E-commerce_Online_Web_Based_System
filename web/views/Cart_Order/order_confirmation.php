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
    SELECT o.*, p.payment_method, p.paid_amount, p.payment_date, p.transaction_id,
           v.code AS voucher_code, v.type AS discount_type, v.discount_value, v.max_discount
    FROM orders o
    LEFT JOIN payment p ON o.order_id = p.order_id
    LEFT JOIN voucher v ON o.voucher_id = v.voucher_id
    WHERE o.order_id = :order_id AND o.user_id = :user_id
";

$orderStmt = $conn->prepare($orderQuery);
$orderStmt->execute([':order_id' => $orderId, ':user_id' => $userId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: cart.php');
    exit;
}

// Fetch order items with review status
$itemsQuery = "
    SELECT oi.*, 
           pi.image_path,
           CASE 
               WHEN pr.review_id IS NOT NULL THEN 1 
               ELSE 0 
           END as already_reviewed
    FROM order_item oi
    LEFT JOIN product_image pi ON oi.product_id = pi.product_id
    LEFT JOIN product_review pr ON oi.order_item_id = pr.order_item_id AND pr.user_id = :user_id
    WHERE oi.order_id = :order_id
    GROUP BY oi.order_item_id
";
$itemsStmt = $conn->prepare($itemsQuery);
$itemsStmt->execute([':order_id' => $orderId, ':user_id' => $userId]);
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
    <!-- If the order status is not refunded or cancelled then only display the order placed successfully-->
     <?php if (!in_array($order['order_status'], ['refunded', 'canceled'])): ?>
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
    <?php endif; ?>
    
    <div class="success-header">
        <div class="success-icon">✓</div>
        <?php if ($order['order_status'] === 'refunded'): ?>
            <h1>Order Refunded</h1>
            <p>Your order has been refunded. If you have any questions, please contact support.</p>
        <?php elseif ($order['order_status'] === 'canceled'): ?>
            <h1>Order Cancelled</h1>
            <p>Your order has been cancelled. If you have any questions, please contact support.</p>
        <?php else: ?>
        <h1>Order Placed Successfully!</h1>
        <p>Thank you for your purchase. Your order has been confirmed.</p>
        <?php endif; ?>
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
                    <?php
                        // Always use images/products/ as base
                        $imgFile = 'default.png';
                        if (!empty($item['image_path'])) {
                            $imgFile = basename($item['image_path']);
                        }
                        $imgFullPath = '../../images/products/' . $imgFile;
                    ?>
                    <img src="<?= $imgFullPath ?>"
                         alt="<?= htmlspecialchars($item['product_name_snapshot']) ?>"
                         class="item-image">
                    <div class="item-details">
                        <div class="item-name"><?= htmlspecialchars($item['product_name_snapshot']) ?></div>
                        <div class="item-qty">Quantity: <?= $item['quantity'] ?> × RM <?= number_format($item['product_price_snapshot'], 2) ?></div>
                        <?php if ($order['order_status'] === 'delivered'): ?>
                            <div class="item-actions">
                                <?php if (!$item['already_reviewed']): ?>
                                    <a href="../../views/product/ProductDetails.php?id=<?= $item['product_id'] ?>&review_order_item=<?= $item['order_item_id'] ?>&review_order_id=<?= $orderId ?>"
                                       class="review-btn">
                                        <i class="fas fa-star"></i> Write Review
                                    </a>
                                <?php else: ?>
                                    <span class="reviewed-badge">
                                        <i class="fas fa-check-circle"></i> Reviewed
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="item-price">RM <?= number_format($item['subtotal'], 2) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="total-section">
            <?php
            $subtotal = array_sum(array_column($orderItems, 'subtotal'));
            $voucherDiscount = 0;
            $voucherLabel = '';
            $shippingFee = 10.00;
            // Only use order/payment data for slip
            if (!empty($order['voucher_id'])) {
                if ($order['discount_type'] === 'percent') {
                    $voucherDiscount = $subtotal * ($order['discount_value'] / 100);
                    if (!empty($order['max_discount'])) {
                        $voucherDiscount = min($voucherDiscount, $order['max_discount']);
                    }
                    $voucherLabel = $order['voucher_code'] . ' (' . $order['discount_value'] . '% OFF)';
                } elseif ($order['discount_type'] === 'fixed') {
                    $voucherDiscount = $order['discount_value'];
                    $voucherLabel = $order['voucher_code'] . ' (RM ' . number_format($order['discount_value'], 2) . ' OFF)';
                } elseif ($order['discount_type'] === 'freeshipping') {
                    $voucherDiscount = $shippingFee;
                    $shippingFee = 0;
                    $voucherLabel = $order['voucher_code'] . ' (Free Shipping)';
                }
            }
            $tax = ($subtotal - $voucherDiscount) * 0.06;
            // Always use paid_amount from payment table for final slip
            $grandTotal = isset($order['paid_amount']) ? $order['paid_amount'] : ($subtotal - $voucherDiscount + $shippingFee + $tax);
            ?>
            <div class="total-row"><span>Subtotal</span><span>RM <?= number_format($subtotal, 2) ?></span></div>
            <div class="total-row"><span>Shipping Fee</span><span>RM <?= number_format($shippingFee, 2) ?></span></div>
            <div class="total-row"><span>Tax (6%)</span><span>RM <?= number_format($tax, 2) ?></span></div>
            <?php
            $voucherCodeDisplay = '';
            if (isset($_SESSION['pending_order_data']['voucher']['code'])) {
                $voucherCodeDisplay = $_SESSION['pending_order_data']['voucher']['code'];
            } elseif (!empty($order['voucher_code'])) {
                $voucherCodeDisplay = $order['voucher_code'];
            }
            ?>
            <?php if ($voucherCodeDisplay): ?>
                <div class="total-row"><span>Voucher Used</span><span><?= htmlspecialchars($voucherCodeDisplay) ?></span></div>
            <?php endif; ?>
            <?php if ($voucherDiscount > 0): ?>
                <div class="total-row" style="color:#28a745;"><span>Voucher Discount<?= $voucherLabel ? ' (' . htmlspecialchars($voucherLabel) . ')' : '' ?></span><span>-RM <?= number_format($voucherDiscount, 2) ?></span></div>
            <?php endif; ?>
            <div class="total-row" style="font-weight:700; color:#FF523B;"><span>Total Paid</span><span>RM <?= number_format($grandTotal, 2) ?></span></div>
        </div>
    </div>

    <div class="action-buttons">
        <a href="../../orders.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
        <?php if (!in_array($order['order_status'], ['refunded', 'canceled'])): ?>
        <a href="generate_receipt.php?order_id=<?= $orderId ?>" class="btn btn-success">
            <i class="fas fa-receipt"></i> Generate E-Receipt
        <?php endif;?>
        </a>
        <?php if ($order['order_status'] === 'delivered'): ?>
            <?php
            // Check if there are any unreviewed items
            $unreviewedQuery = "
                SELECT COUNT(*) as count
                FROM order_item oi
                LEFT JOIN product_review pr ON oi.order_item_id = pr.order_item_id AND pr.user_id = :user_id
                WHERE oi.order_id = :order_id AND pr.review_id IS NULL
            ";
            $unreviewedStmt = $conn->prepare($unreviewedQuery);
            $unreviewedStmt->execute([':order_id' => $orderId, ':user_id' => $userId]);
            $unreviewedResult = $unreviewedStmt->fetch(PDO::FETCH_ASSOC);
            $hasUnreviewedItems = (int)$unreviewedResult['count'] > 0;
            ?>
            <?php if ($hasUnreviewedItems): ?>
                <a href="order_confirmation.php?order_id=<?= $orderId ?>&show_reviews=1" class="btn btn-primary">
                    <i class="fas fa-star"></i> Write Reviews
                </a>
            <?php endif; ?>
        <?php endif; ?>
        <a href="../../views/product/ProductPage.php" class="btn btn-primary">Continue Shopping</a>
    </div>
</div>

<script>
console.log('Order Status: <?= isset($order['order_status']) ? addslashes($order['order_status']) : 'N/A' ?>');
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

// Handle show_reviews parameter - scroll to first review link
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('show_reviews') === '1') {
        setTimeout(function() {
            const firstReviewBtn = document.querySelector('.review-btn');
            if (firstReviewBtn) {
                firstReviewBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Highlight the review button briefly
                firstReviewBtn.style.boxShadow = '0 0 20px rgba(251, 191, 36, 0.6)';
                setTimeout(function() {
                    firstReviewBtn.style.boxShadow = '';
                }, 2000);
            }
        }, 300);
    }
});
</script>

<?php include '../../general/_footer.php'; ?>
