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
    SELECT o.*, p.payment_method, p.paid_amount, p.payment_date, p.transaction_id,
           u.username, u.email
    FROM orders o
    LEFT JOIN payment p ON o.order_id = p.order_id
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
$tax = 0; // Add tax calculation if needed
$total = $order['total_amount'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Receipt #<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></title>
    <link rel="stylesheet" href="../../css/receipt.css">
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
                <p><strong>Order Time:</strong> <?= date('h:i A', strtotime($order['create_at'])) ?></p>
                <p><strong>Status:</strong> <span class="status-badge status-<?= strtolower($order['order_status']) ?>"><?= ucfirst($order['order_status']) ?></span></p>
            </div>

            <div class="info-section">
                <h3>Customer Information</h3>
                <p><strong>Name:</strong> <?= htmlspecialchars($order['username']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                <p><strong>Customer ID:</strong> #<?= str_pad($order['user_id'], 5, '0', STR_PAD_LEFT) ?></p>
            </div>

            <div class="info-section">
                <h3>Payment Information</h3>
                <p><strong>Payment Method:</strong> <?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></p>
                <p><strong>Payment Date:</strong> <?= date('F d, Y', strtotime($order['payment_date'])) ?></p>
                <p><strong>Payment Status:</strong> <span style="color: #28a745; font-weight: 600;">Completed</span></p>
                <?php if ($order['transaction_id']): ?>
                    <div class="transaction-id">
                        <strong>Transaction ID:</strong><br>
                        <?= htmlspecialchars($order['transaction_id']) ?>
                    </div>
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
            <?php if ($tax > 0): ?>
                <div class="totals-row">
                    <div class="label">Tax:</div>
                    <div class="value">RM <?= number_format($tax, 2) ?></div>
                </div>
            <?php endif; ?>
            <div class="totals-row grand-total">
                <div class="label">TOTAL PAID:</div>
                <div class="value">RM <?= number_format($total, 2) ?></div>
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

    <script>
        function downloadPDF() {
            // Simple print to PDF functionality
            // Users can use their browser's "Save as PDF" option
            alert('Please use your browser\'s print dialog and select "Save as PDF" as the destination.');
            window.print();
        }
    </script>
</body>
</html>
