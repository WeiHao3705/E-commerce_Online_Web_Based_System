<?php 
session_start();

// Debug: Check session
echo "<!-- Session Debug: ";
echo "user_id = " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET');
echo ", All session vars: ";
print_r($_SESSION);
echo " -->";

require __DIR__ . '/../../database/connection.php';
$db = new Database();
$conn = $db->getConnection();
$pageTitle = "Checkout";
include '../../general/_header.php'; 
include '../../general/_navbar.php'; 

// Get cart items from session (you can modify this to get from database)
// For now, using sample data - replace with actual cart data
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

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shippingFee = 15.00;
$tax = $subtotal * 0.06;
$grandTotal = $subtotal + $shippingFee + $tax;
?>

<link rel="stylesheet" href="../../css/checkout.css">

<div class="checkout-container">
    <h1>Checkout</h1>
    
    <!-- Progress Steps -->
    <div class="progress-steps">
        <div class="step active">
            <div class="step-circle">
                <span class="step-number">1</span>
                <i class="fas fa-check step-check"></i>
            </div>
            <span class="step-label">Checkout</span>
        </div>
        <div class="step-line"></div>
        <div class="step">
            <div class="step-circle">
                <span class="step-number">2</span>
                <i class="fas fa-check step-check"></i>
            </div>
            <span class="step-label">Payment</span>
        </div>
        <div class="step-line"></div>
        <div class="step">
            <div class="step-circle">
                <span class="step-number">3</span>
                <i class="fas fa-check step-check"></i>
            </div>
            <span class="step-label">Order Review</span>
        </div>
    </div>
    
    <div class="checkout-layout">
        <!-- Left Side: Delivery & Payment -->
        <div class="checkout-main">
            
            <!-- Delivery Address Section -->
            <div class="checkout-section">
                <h2>1. Delivery Address</h2>
                
                <!-- Default Address Checkbox -->
                <div class="default-address-option">
                    <label>
                        <input type="checkbox" id="default-address" name="default-address">
                        Use my saved address
                    </label>
                </div>
                
                <form id="addressForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fullName">Full Name *</label>
                            <input type="text" id="fullName" name="fullName" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address1">Address Line 1 *</label>
                        <input type="text" id="address1" name="address1" placeholder="Street address" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address2">Address Line 2</label>
                        <input type="text" id="address2" name="address2" placeholder="Apartment, suite, unit (optional)">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City *</label>
                            <input type="text" id="city" name="city" required>
                        </div>
                        <div class="form-group">
                            <label for="postcode">Postcode *</label>
                            <input type="text" id="postcode" name="postcode" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="state">State *</label>
                        <select id="state" name="state" required>
                            <option value="">Select State</option>
                            <option value="Johor">Johor</option>
                            <option value="Kedah">Kedah</option>
                            <option value="Kelantan">Kelantan</option>
                            <option value="Kuala Lumpur">Kuala Lumpur</option>
                            <option value="Melaka">Melaka</option>
                            <option value="Negeri Sembilan">Negeri Sembilan</option>
                            <option value="Pahang">Pahang</option>
                            <option value="Penang">Penang</option>
                            <option value="Perak">Perak</option>
                            <option value="Perlis">Perlis</option>
                            <option value="Sabah">Sabah</option>
                            <option value="Sarawak">Sarawak</option>
                            <option value="Selangor">Selangor</option>
                            <option value="Terengganu">Terengganu</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <!-- Payment Method Section -->
            <div class="checkout-section">
                <h2>2. Payment Method</h2>
                <div class="payment-methods">
                    <label class="payment-option">
                        <input type="radio" name="payment" value="card" checked>
                        <div class="payment-card">
                            <i class="fas fa-credit-card"></i>
                            <span>Credit/Debit Card</span>
                        </div>
                    </label>
                    
                    <label class="payment-option">
                        <input type="radio" name="payment" value="online-banking">
                        <div class="payment-card">
                            <i class="fas fa-university"></i>
                            <span>Online Banking</span>
                        </div>
                    </label>
                    
                    <label class="payment-option">
                        <input type="radio" name="payment" value="ewallet">
                        <div class="payment-card">
                            <i class="fas fa-wallet"></i>
                            <span>E-Wallet</span>
                        </div>
                    </label>
                </div>
            </div>
            
        </div>
        
        <!-- Right Side: Order Summary -->
        <div class="checkout-sidebar">
            <div class="order-summary-checkout">
                <h2>Order Summary</h2>
                
                <div class="summary-items">
                    <?php foreach ($cartItems as $item): ?>
                    <div class="summary-item">
                        <img src="<?= $item['image'] ?>" alt="<?= $item['name'] ?>">
                        <div class="item-details">
                            <h4><?= htmlspecialchars($item['name']) ?></h4>
                            <p><?= htmlspecialchars($item['variant']) ?></p>
                            <p>Qty: <?= $item['quantity'] ?></p>
                        </div>
                        <div class="item-price">
                            RM <?= number_format($item['price'] * $item['quantity'], 2) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <hr>
                
                <div class="summary-totals">
                    <div class="total-line">
                        <span>Subtotal:</span>
                        <span>RM <?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="total-line">
                        <span>Shipping:</span>
                        <span>RM <?= number_format($shippingFee, 2) ?></span>
                    </div>
                    <div class="total-line">
                        <span>Tax (6%):</span>
                        <span>RM <?= number_format($tax, 2) ?></span>
                    </div>
                    <hr>
                    <div class="total-line grand-total">
                        <span><strong>Total:</strong></span>
                        <span><strong>RM <?= number_format($grandTotal, 2) ?></strong></span>
                    </div>
                </div>
                
                <button class="place-order-btn" id="placeOrderBtn">
                    Place Order
                </button>
                
                <a href="cart.php" class="back-to-cart">
                    ← Back to Cart
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="../../js/checkout.js"></script>

<?php include '../../general/_footer.php'; ?>