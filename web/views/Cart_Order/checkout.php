<?php 
if (session_status() === PHP_SESSION_NONE) session_start();

// Get user_id from session (login stores it in $_SESSION['user']['user_id'])
$userId = null;
if (isset($_SESSION['user']) && isset($_SESSION['user']->user_id)) {
    $_SESSION['user_id'] = $_SESSION['user']->user_id;
} elseif (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
}

require __DIR__ . '/../../database/connection.php';
$db = new Database();
$conn = $db->getConnection();
$pageTitle = "Checkout";
include '../../general/_header.php'; 
// include '../../general/_navbar.php'; 

// Get selected item IDs from GET parameter
$selectedItemIds = [];
if (isset($_GET['items']) && !empty($_GET['items'])) {
    $selectedItemIds = explode(',', $_GET['items']);
    $selectedItemIds = array_map('intval', $selectedItemIds);
    $_SESSION['checkout_items'] = $selectedItemIds;
} elseif (isset($_SESSION['checkout_items'])) {
    $selectedItemIds = $_SESSION['checkout_items'];
}

// Debug output
echo "<!-- DEBUG: Selected Item IDs: " . print_r($selectedItemIds, true) . " -->";
echo "<!-- DEBUG: GET data: " . print_r($_GET, true) . " -->";
echo "<!-- DEBUG: User ID: " . ($_SESSION['user_id'] ?? 'not set') . " -->";

// Fetch only selected cart items from database
$cartItems = [];
if (isset($_SESSION['user_id']) && !empty($selectedItemIds)) {
    $userId = $_SESSION['user_id'];
    
    // Create placeholders for IN clause
    $placeholders = implode(',', array_fill(0, count($selectedItemIds), '?'));
    
    // Query to fetch only selected cart items with product details
    $cartQuery = "
        SELECT 
            ci.cart_item_id,
            ci.product_id,
            ci.quantity,
            p.product_name,
            p.description,
            pp.selling_price,
            pi.image_path
        FROM cart_item ci
        JOIN shopping_cart sc ON ci.cart_id = sc.cart_id
        JOIN product p ON ci.product_id = p.product_id
        LEFT JOIN product_price pp ON p.product_id = pp.product_id
        LEFT JOIN product_image pi ON p.product_id = pi.product_id
        WHERE sc.user_id = ? AND ci.cart_item_id IN ($placeholders)
        GROUP BY ci.cart_item_id
        ORDER BY ci.cart_item_id DESC
    ";
    
    echo "<!-- DEBUG: Query: " . $cartQuery . " -->";
    
    $cartStmt = $conn->prepare($cartQuery);
    // Bind user_id first, then selected item IDs
    $params = array_merge([$userId], $selectedItemIds);
    echo "<!-- DEBUG: Query params: " . print_r($params, true) . " -->";
    
    $cartStmt->execute($params);
    $dbCartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<!-- DEBUG: Fetched items count: " . count($dbCartItems) . " -->";
    echo "<!-- DEBUG: Fetched items: " . print_r($dbCartItems, true) . " -->";
    
    // Format cart items for display
    foreach ($dbCartItems as $item) {
        $cartItems[] = [
            'id' => $item['cart_item_id'],
            'product_id' => $item['product_id'],
            'image' => $item['image_path'] ?? '../../images/products/default.png',
            'name' => $item['product_name'],
            'variant' => $item['description'] ?? 'Standard',
            'price' => (float) ($item['selling_price'] ?? 0),
            'quantity' => (int) $item['quantity']
        ];
    }
}

echo "<!-- DEBUG: Final cart items count: " . count($cartItems) . " -->";

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
    <!-- Header with Logo and Title -->
    <div class="checkout-header">
        <div class="checkout-logo">
            <a href="../../index.php">
                <img src="../../images/logo/logo1.png" alt="NGEAR Logo">
            </a>
        </div>
        <h1 class="checkout-title">Checkout</h1>
    </div>
    
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
                <h2>2. Payment Method</h2><br>
                <div class="payment-methods">
                    <label class="payment-option">
                        <input type="radio" name="payment" value="card" checked>
                        <div class="payment-card">
                            <i class="fas fa-credit-card"></i>
                            <span>Credit/Debit Card (Stripe)</span>
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
                
                <!-- Stripe Card Element (shown when card is selected) -->
                <div id="card-payment-section" style="margin-top: 20px;">
                    <div id="card-element" style="padding: 12px; border: 1px solid #ccc; border-radius: 4px; background: white;">
                        <!-- Stripe.js injects the Card Element here -->
                    </div>
                    <div id="card-errors" role="alert" style="color: #fa755a; margin-top: 10px;"></div>
                </div>
                
                <!-- Other payment methods (hidden initially) -->
                <div id="other-payment-section" style="display: none; margin-top: 20px;">
                    <p style="color: #666;">This payment method will be available soon.</p>
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
            </div>
        </div>
    </div>
</div>
<script src="https://js.stripe.com/v3/"></script>
<script>
    <?php 
    require __DIR__ . '/../../config/stripe_config.php';
    if(isset($_SESSION['user_id'])): ?>
        console.log('User is logged in with user_id: <?= $_SESSION['user_id'] ?>');
    <?php else: ?>
        console.log('User is not logged in.');
    <?php endif; ?>
    
    // Stripe configuration
    const STRIPE_PUBLISHABLE_KEY = '<?= STRIPE_PUBLISHABLE_KEY ?>';
    const ORDER_DATA = {
        items: <?= json_encode($cartItems) ?>,
        total_amount: <?= $grandTotal ?>,
        address: '',
        city: '',
        postcode: '',
        state: ''
    };
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="../../js/checkout.js"></script>

<?php include '../../general/_footer.php'; ?>