<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header('Location: ../member/login.php');
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .receipt-header {
            text-align: center;
            border-bottom: 3px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-name {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .receipt-title {
            font-size: 24px;
            color: #666;
            margin-top: 10px;
        }

        .receipt-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-section {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .info-section h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-section p {
            font-size: 14px;
            margin: 5px 0;
            color: #333;
        }

        .info-section strong {
            color: #000;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }

        .items-table thead {
            background: #333;
            color: white;
        }

        .items-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .items-table tbody tr:hover {
            background: #f9f9f9;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }

        .totals-row {
            display: flex;
            justify-content: flex-end;
            margin: 10px 0;
            font-size: 15px;
        }

        .totals-row .label {
            width: 150px;
            text-align: right;
            color: #666;
            margin-right: 20px;
        }

        .totals-row .value {
            width: 120px;
            text-align: right;
            font-weight: 600;
        }

        .totals-row.grand-total {
            font-size: 18px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #333;
        }

        .totals-row.grand-total .label,
        .totals-row.grand-total .value {
            color: #000;
            font-weight: bold;
        }

        .receipt-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px dashed #ccc;
            text-align: center;
        }

        .thank-you {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .footer-note {
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }

        .action-buttons {
            margin-top: 30px;
            text-align: center;
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .receipt-container {
                box-shadow: none;
                padding: 20px;
            }

            .action-buttons {
                display: none;
            }
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .transaction-id {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
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
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Receipt
            </button>
            <button onclick="downloadPDF()" class="btn btn-success">
                <i class="fas fa-download"></i> Download PDF
            </button>
            <a href="order_confirmation.php?order_id=<?= $orderId ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Order
            </a>
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
