<?php 
session_start();
require __DIR__ . '/../../database/connection.php';
$db = new Database();
$conn = $db->getConnection();
$pageTitle = "Shopping Cart";
include '../../general/_header.php'; 
include '../../general/_navbar.php'; 

// query of fetching vouchers from db
$voucherQuery = "SELECT * FROM voucher 
WHERE status = 'active' 
AND start_date <= CURDATE() 
AND end_date >= CURDATE() 
ORDER BY type, min_spend";
$voucherStmt = $conn->prepare($voucherQuery);
$voucherStmt->execute();
// fetch all vouchers as an array
$vouchers = $voucherStmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------- Accept incoming item from ProductDetails -----------------
$incomingItem = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['product_id'])) {
    $pid = (int) ($_POST['product_id'] ?? 0);
    $vid = isset($_POST['variant_id']) && $_POST['variant_id'] !== '' ? (int) $_POST['variant_id'] : null;
    $size = trim($_POST['size'] ?? '');
    $qty = max(1, (int) ($_POST['quantity'] ?? 1));

    // fetch product name & price
    $pStmt = $conn->prepare("SELECT p.product_id, p.product_name, pr.original_price FROM product p LEFT JOIN product_price pr ON p.product_id = pr.product_id WHERE p.product_id = :pid LIMIT 1");
    $pStmt->execute([':pid' => $pid]);
    $pRow = $pStmt->fetch(PDO::FETCH_ASSOC);

    if ($pRow) {
        // try variant image first (by variant_id), fallback to product image, fallback to placeholder
        $imgPath = null;
        if ($vid) {
            $imgStmt = $conn->prepare("SELECT image_path FROM product_image WHERE variant_id = :vid LIMIT 1");
            $imgStmt->execute([':vid' => $vid]);
            $imgRow = $imgStmt->fetch(PDO::FETCH_ASSOC);
            if ($imgRow && !empty($imgRow['image_path'])) $imgPath = $imgRow['image_path'];
        }
        if (!$imgPath) {
            $imgStmt = $conn->prepare("SELECT image_path FROM product_image WHERE product_id = :pid LIMIT 1");
            $imgStmt->execute([':pid' => $pid]);
            $imgRow = $imgStmt->fetch(PDO::FETCH_ASSOC);
            if ($imgRow && !empty($imgRow['image_path'])) $imgPath = $imgRow['image_path'];
        }
        if (!$imgPath) $imgPath = '../../images/no-image.png'; // adjust placeholder path as needed

        // build variant label (color - size) if possible
        $variantLabel = '';
        if ($vid) {
            $vStmt = $conn->prepare("SELECT color, size FROM product_variant WHERE variant_id = :vid LIMIT 1");
            $vStmt->execute([':vid' => $vid]);
            $vRow = $vStmt->fetch(PDO::FETCH_ASSOC);
            if ($vRow) {
                $variantLabel = trim(($vRow['color'] ?? '') . ($vRow['size'] ? ' - ' . $vRow['size'] : ''));
            } else {
                $variantLabel = $size ?: '';
            }
        } else {
            $variantLabel = $size ?: '';
        }

        $incomingItem = [
            'id' => $pid,
            'image' => $imgPath,
            'name' => $pRow['product_name'],
            'variant' => $variantLabel,
            'price' => (float) ($pRow['original_price'] ?? 0),
            'quantity' => $qty
        ];
    }
}

// Sample cart data - replace with actual database/session data later
$cartItems = [
    [
        'id' => 1,
        'image' => '../../images/products/AJ1.png',
        'name' => 'Air Jordan 1',
        'variant' => 'Black/Red - Size 9',
        'price' => 299.90,
        'quantity' => 1
    ],
    [
        'id' => 2,
        'image' => '../../images/products/Dunk_Panda.png',
        'name' => 'Nike Dunk Panda',
        'variant' => 'White/Black - Size 10',
        'price' => 155.50,
        'quantity' => 2
    ]
];

// if an incoming item exists, add it to the top of cart items
if ($incomingItem) {
    array_unshift($cartItems, $incomingItem);
}

// calculate initial values from the cart items
// array_column() gets the 'quantity' values from each item
// array_sum() adds them all together
$cartItemCount = array_sum(array_column($cartItems, 'quantity'));

// calculate subtotal by looping through each item
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

// set fixed values for shipping and tax
$shippingFee = 15.00;
$tax = $subtotal * 0.06; // 6% tax
$grandTotal = $subtotal + $shippingFee + $tax;

?>

<link rel="stylesheet" href="../../css/cart.css">

<div class="container">
    <h1>Your Shopping Cart</h1>
        
    <!-- create dynamic cart message with IDs for JavaScript control -->
    <!-- The span elements allow JavaScript to update specific parts -->
    <p class="cart-count-message" id="cart-message">
        You have <strong><span id="cart-item-count"><?= $cartItemCount ?></span></strong> item<span id="item-plural"><?= $cartItemCount !== 1 ? 's' : '' ?></span> in your shopping cart.
    </p>

    <div class="cart-layout">
        <!-- Cart Items Section -->
        <div class="cart-items-section">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="select-all" class="select-checkbox" title="Select All">
                        </th>
                        <th>Item</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                    <tr data-item-id="<?= $item['id'] ?>">
                        <td class="item-select">
                            <input type="checkbox" class="item-checkbox" data-item-id="<?= $item['id'] ?>">
                        </td>
                        <td class="item-details">
                            <div class="item-info">
                                <img src="<?= $item['image'] ?>" alt="<?= $item['name'] ?>" class="item-image">
                                <div class="item-text">
                                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                                    <p class="item-variant"><?= htmlspecialchars($item['variant']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="item-price">RM <?= number_format($item['price'], 2) ?></td>
                        <td class="item-quantity">
                            <div class="quantity-controls">
                                <button class="qty-btn minus-btn" data-item-id="<?= $item['id'] ?>">-</button>
                                <span class="qty-display"><?= $item['quantity'] ?></span>
                                <button class="qty-btn plus-btn" data-item-id="<?= $item['id'] ?>">+</button>
                            </div>
                        </td>
                        <td class="item-total">RM <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                        <td class="item-action">
                            <button class="remove-btn" data-item-id="<?= $item['id'] ?>" title="Remove item">
                                🗑️
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Continue Shopping Section -->
            <div class="continue-shopping">
                <a href="../../index.php" class="continue-shopping-link">
                    <span class="arrow-left">←</span>
                    <span>Continue Shopping</span>
                </a>
            </div>
        </div>

        <!-- Order Summary Section -->
        <div class="order-summary-section">
            <div class="order-summary">
                <h3>Order Summary</h3>
                
                <div class="summary-line">
                    <span>Subtotal:</span>
                    <span>RM <?= number_format($subtotal, 2) ?></span>
                </div>
                
                <div class="summary-line">
                    <span>Shipping Fee:</span>
                    <span>RM <?= number_format($shippingFee, 2) ?></span>
                </div>
                
                <div class="summary-line">
                    <span>Tax (6%):</span>
                    <span>RM <?= number_format($tax, 2) ?></span>
                </div>

                <!-- Voucher discount (hidden by default, shown by JavaScript when applied) -->
                <div class="summary-line voucher-discount-applied">
                    <span>Voucher Discount:</span>
                    <span id="voucher-discount-amount">- RM 0.00</span>
                </div>
                
                <hr>
                
                <div class="summary-line total">
                    <span><strong>Grand Total:</strong></span>
                    <span><strong>RM <?= number_format($grandTotal, 2) ?></strong></span>
                </div>
                
                <div class="promo-section">
                    <h4>Promo Code / Voucher</h4>
                    <!--Select the voucher-->
                    <button type="button" class="select-voucher-btn" id="selectVoucherBtn">
                        <i class="fas fa-ticket-alt"></i> Select Available Voucher
                    </button>
                    <div class="promo-input">
                        <input type="text" id="promo-code" placeholder="Or enter promo code manually" class="form-input" readonly>
                        <button class="apply-btn" id="applyBtn">Apply</button>
                    </div>
                    
                    <!-- Applied Voucher Display -->
                    <div id="appliedVoucher" class="applied-voucher" style="display: none;">
                        <div class="voucher-info">
                            <span class="voucher-label"></span>
                            <button class="remove-voucher-btn" id="removeVoucherBtn">&times;</button>
                        </div>
                    </div>
                </div>
                
                <button class="checkout-btn">
                    <a href="checkout.php" style="color: white; text-decoration: none;">
                        Proceed to Checkout
                    </a>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- This section shows what is inside the modal after users click the select voucher button -->
<!-- Voucher Selection Modal -->
<div id="voucherModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Select Voucher</h3>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <?php if (empty($vouchers)): ?>
                <p class="no-vouchers">No vouchers available at the moment.</p>
            <?php else: ?>
                <div class="voucher-list">
                    <?php foreach ($vouchers as $voucher): ?>
                        <div class="voucher-card" 
                             data-code="<?= htmlspecialchars($voucher['code']) ?>"
                             data-type="<?= htmlspecialchars($voucher['type']) ?>"
                             data-value="<?= htmlspecialchars($voucher['discount_value']) ?>"
                             data-min="<?= htmlspecialchars($voucher['min_spend']) ?? '' ?>"
                             data-max="<?= htmlspecialchars($voucher['max_discount'] ?? '') ?>">
                        <div class="voucher-icon">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <div class="voucher-details">
                                <h4><?= htmlspecialchars($voucher['code']) ?></h4>
                                <p class="voucher-desc"><?= htmlspecialchars($voucher['description']) ?></p>
                                <div class="voucher-info-row">
                                    <span class="voucher-discount">
                                        <?php 
                                        if ($voucher['type'] == 'percent') {
                                            echo number_format($voucher['discount_value'], 0) . '% OFF';
                                        } elseif ($voucher['type'] == 'fixed') {
                                            echo 'RM ' . number_format($voucher['discount_value'], 2) . ' OFF';
                                        } else {
                                            echo 'FREE SHIPPING';
                                        }
                                        ?>
                                    </span>
                                    <?php if ($voucher['min_spend'] > 0): ?>
                                        <span class="voucher-min">Min: RM <?= number_format($voucher['min_spend'], 2) ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="voucher-validity">Valid until: <?= date('d M Y', strtotime($voucher['end_date'])) ?></p>
                            </div>
                            <button class="use-voucher-btn">Use</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="../../js/cart.js"></script>

<?php include '../../general/_footer.php'; ?>