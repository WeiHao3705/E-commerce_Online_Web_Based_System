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

// Fetch order details with user information
$orderQuery = "
    SELECT o.*, p.payment_method, p.paid_amount, p.payment_date,
           v.code AS voucher_code, v.type AS discount_type, v.discount_value, v.max_discount, v.min_spend,
           u.username, u.email
    FROM orders o
    LEFT JOIN payment p ON o.order_id = p.order_id
    LEFT JOIN voucher v ON o.voucher_id = v.voucher_id
    LEFT JOIN users u ON o.user_id = u.user_id
    WHERE o.order_id = :order_id AND o.user_id = :user_id
";

$orderStmt = $conn->prepare($orderQuery);
$orderStmt->execute([':order_id' => $orderId, ':user_id' => $userId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('Order not found or access denied');
}

// Fetch order items
$itemsQuery = "
    SELECT oi.*
    FROM order_item oi
    WHERE oi.order_id = :order_id
";
$itemsStmt = $conn->prepare($itemsQuery);
$itemsStmt->execute([':order_id' => $orderId]);
$orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals

$subtotal = 0;
foreach ($orderItems as $item) {
    $subtotal += $item['subtotal'];
}
$discount = 0;
if (!empty($order['voucher_id'])) {
    if ($order['discount_type'] === 'percent') {
        $discount = $subtotal * ($order['discount_value'] / 100);
        if (!empty($order['max_discount'])) {
            $discount = min($discount, $order['max_discount']);
        }
    } elseif ($order['discount_type'] === 'fixed') {
        $discount = $order['discount_value'];
    }
    // freeshipping type doesn't affect discount, but affects shipping fee
}
$shippingFee = 10.00;
if (!empty($order['voucher_id']) && $order['discount_type'] === 'freeshipping') {
    $shippingFee = 0.00;
    $discount = 0; // no monetary discount, just free shipping
}
$tax = ($subtotal - $discount) * 0.06;
$grandTotal = $subtotal - $discount + $shippingFee + $tax;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Receipt #<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></title>
    <link rel="stylesheet" href="../../css/receipt.css">
    <link rel="icon" type="image/png" href="/web/images/logo/logo1.png">
    <!-- jsPDF and html2canvas CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <div class="company-name">NGEAR Store</div>
            <div class="receipt-title">E-RECEIPT</div>
        </div>

        <!-- Receipt Info -->
        <div class="receipt-info">
            <div class="info-section">
                <h3>Order Information</h3>
                <p><strong>Order Number:</strong> #<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></p>
                <p><strong>Order Date:</strong> <?= date('F d, Y', strtotime($order['create_at'])) ?></p>
                <p><strong>Status:</strong> <span class="status-badge status-<?= strtolower($order['order_status']) ?>"><?= ucfirst($order['order_status']) ?></span></p>
            </div>
            <div class="info-section">
                <h3>Customer Information</h3>
                <p><strong>Name:</strong> <?= htmlspecialchars($order['username']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
            </div>
            <div class="info-section">
                <h3>Voucher Applied</h3>
                <?php if (!empty($order['voucher_code'])): ?>
                    <p><strong>Voucher Code:</strong> <?= htmlspecialchars($order['voucher_code']) ?></p>
                    <p><strong>Type:</strong> <?= htmlspecialchars(ucfirst($order['discount_type'])) ?></p>
                    <?php if ($order['discount_type'] === 'percent'): ?>
                        <p><strong>Value:</strong> <?= htmlspecialchars($order['discount_value']) ?>%<?php if (!empty($order['max_discount'])): ?> (Max RM <?= number_format($order['max_discount'],2) ?>)<?php endif; ?></p>
                    <?php elseif ($order['discount_type'] === 'fixed'): ?>
                        <p><strong>Value:</strong> RM <?= number_format($order['discount_value'],2) ?></p>
                    <?php elseif ($order['discount_type'] === 'freeshipping'): ?>
                        <p><strong>Value:</strong> Free Shipping</p>
                    <?php endif; ?>
                    <?php if (!empty($order['min_spend'])): ?>
                        <p><strong>Min Spend:</strong> RM <?= number_format($order['min_spend'],2) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>No voucher applied.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $index => $item): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($item['product_name_snapshot']) ?></td>
                        <td class="text-center"><?= $item['quantity'] ?></td>
                        <td class="text-right">RM <?= number_format($item['product_price_snapshot'], 2) ?></td>
                        <td class="text-right">RM <?= number_format($item['subtotal'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-row">
                <div class="label">Subtotal:</div>
                <div class="value">RM <?= number_format($subtotal, 2) ?></div>
            </div>
            <div class="totals-row">
                <div class="label">Shipping Fee:</div>
                <div class="value">RM <?= number_format($shippingFee, 2) ?></div>
            </div>
            <div class="totals-row">
                <div class="label">Tax (6%):</div>
                <div class="value">RM <?= number_format($tax, 2) ?></div>
            </div>
            <?php if ($discount > 0): ?>
                <div class="totals-row">
                    <div class="label">Discount<?= !empty($order['voucher_code']) ? ' (' . htmlspecialchars($order['voucher_code']) . ')' : '' ?>:</div>
                    <div class="value">-RM <?= number_format($discount, 2) ?></div>
                </div>
            <?php endif; ?>
            <div class="totals-row grand-total">
                <div class="label">TOTAL PAID:</div>
                <div class="value">RM <?= number_format($grandTotal, 2) ?></div>
            </div>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <div class="thank-you">Thank You For Your Purchase!</div>
            <div class="footer-note">
                This is an electronic receipt for your records.<br>
                For any inquiries, please contact our customer support.<br>
                Generated on: <?= date('F d, Y h:i A') ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="order_confirmation.php?order_id=<?= $orderId ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Order
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Receipt
            </button>
            <button onclick="downloadPDF()" class="btn btn-success">
                <i class="fas fa-download"></i> Download PDF
            </button>
        </div>
    </div>
</body>
<script>
function downloadPDF() {
    const receipt = document.querySelector('.receipt-container');
    html2canvas(receipt, { scale: 2 }).then(canvas => {
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jspdf.jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });
        // Calculate width/height for A4
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        // Fit image to page width, keep aspect ratio
        const imgWidth = pageWidth - 40;
        const imgHeight = canvas.height * imgWidth / canvas.width;
        pdf.addImage(imgData, 'PNG', 20, 20, imgWidth, imgHeight);
        pdf.save('receipt_<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?>.pdf');
    });
}
</script>
</html>
