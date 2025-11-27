<?php 
session_start();
require __DIR__ . '/../../database/connection.php';
$db = new Database();
$conn = $db->getConnection();
$pageTitle = "Shopping Cart";
include '../../general/_header.php'; 
include '../../general/_navbar.php'; 
?>

<link rel="stylesheet" href="../../css/cart.css">

<div class="container">
    <h1>Your Shopping Cart</h1>
        
    <?php
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
                
                <hr>
                
                <div class="summary-line total">
                    <span><strong>Grand Total:</strong></span>
                    <span><strong>RM <?= number_format($grandTotal, 2) ?></strong></span>
                </div>
                
                <div class="promo-section">
                    <h4>Promo Code</h4>
                    <div class="promo-input">
                        <input type="text" id="promo-code" placeholder="Enter promo code" class="form-input">
                        <button class="apply-btn">Apply</button>
                    </div>
                </div>
                
                <button class="checkout-btn">
                    <a href="../Checkout/checkout.php" style="color: white; text-decoration: none;">
                        Proceed to Checkout
                    </a>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="../../js/cart.js"></script>

<?php include '../../general/_footer.php'; ?>