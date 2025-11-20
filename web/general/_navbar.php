<?php
// Define base path
$base_path = '/E-commerce_Online_Web_Based_System/web/';

$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_views = (strpos($current_dir, '/views') !== false);
$prefix = $is_in_views ? '../' : '';
?>

<nav class="navbar">
    <div class="container">
        <div class="nav-wrapper">
            <!-- Logo -->
            <div class="logo">
                <a href="<?php echo $prefix; ?>index.php">
                    <img src="<?php echo $prefix; ?>images/logo.png" alt="NGEAR">
                </a>
            </div>
            
            <!-- Right Side: Navigation Menu and Cart -->
            <div class="nav-right">
                <!-- Navigation Menu -->
                <ul class="nav-menu" id="navMenu">
                <li><a href="<?php echo $prefix; ?>index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Home</a></li>
                <li><a href="<?php echo $prefix; ?>products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">Products</a></li>
                <li><a href="<?php echo $prefix; ?>about.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">About</a></li>
                <li><a href="<?php echo $prefix; ?>contact.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active' : ''; ?>">Contact</a></li>
                    <?php
                    if (session_status() === PHP_SESSION_NONE) session_start();
                    $isGuest = empty($_SESSION['user']);
                    $isAdmin = !$isGuest && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
                    if ($isGuest):
                    ?>
                        <li><a href="<?php echo $prefix; ?>account.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'account.php' ? 'active' : ''; ?>">Login</a></li>
                    <?php else: ?>
                        <?php if ($isAdmin): ?>
                        <li class="dropdown">
                            <a href="#" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'AllMembers.php' || basename($_SERVER['PHP_SELF']) == 'VoucherManagement.php') ? 'active' : ''; ?>">
                                Admin <i class="fas fa-caret-down"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo $prefix; ?>controller/MemberController.php?action=showAll"><i class="fas fa-users"></i> All Members</a></li>
                                <li><a href="<?php echo $prefix; ?>controller/VoucherController.php?action=showAll"><i class="fas fa-ticket-alt"></i> Voucher Management</a></li>
                            </ul>
                        </li>
                        <?php endif; ?>
                        <li class="dropdown">
                            <a href="<?php echo $prefix; ?>account.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'account.php' ? 'active' : ''; ?>">
                                Account <i class="fas fa-caret-down"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo $prefix; ?>profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                                <li><a href="<?php echo $prefix; ?>orders.php"><i class="fas fa-box"></i> My Orders</a></li>
                                <li><a href="<?php echo $prefix; ?>wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
                                <li><a href="<?php echo $prefix; ?>settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                                <li class="divider"></li>
                                <li><a href="<?php echo $prefix; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Cart Icon -->
                <div class="nav-icons">
                    <a href="<?php echo $prefix; ?>cart.php" class="cart-icon">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="cart-count" id="cartCount">0</span>
                    </a>
                    <div class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
    .navbar {
        background-color: #FFF0F0;
        padding: 15px 0;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    
    .nav-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        gap: 20px;
    }
    
    .logo {
        flex-shrink: 0;
        margin-right: auto;
    }
    
    .logo img {
        height: 40px;
        width: auto;
        display: block;
    }
    
    .logo a {
        display: block;
        font-size: 24px;
        font-weight: 700;
        color: #333;
        text-decoration: none;
    }
    
    .nav-right {
        display: flex;
        align-items: center;
        gap: 30px;
        margin-left: auto;
    }
    
    .nav-menu {
        display: flex;
        list-style: none;
        gap: 35px;
        margin: 0;
        padding: 0;
        align-items: center;
    }
    
    .nav-menu li {
        position: relative;
    }
    
    .nav-menu li a {
        text-decoration: none;
        color: #555;
        font-size: 15px;
        font-weight: 500;
        transition: color 0.3s ease;
        position: relative;
        display: inline-block;
        padding: 5px 0;
    }
    
    .nav-menu li a:hover,
    .nav-menu li a.active {
        color: #FF5252;
    }
    
    .nav-menu li a.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: #FF5252;
    }
    
    .nav-icons {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .cart-icon {
        position: relative;
        color: #333;
        font-size: 20px;
        transition: color 0.3s ease;
        text-decoration: none;
    }
    
    .cart-icon:hover {
        color: #FF5252;
    }
    
    .cart-count {
        position: absolute;
        top: -8px;
        right: -10px;
        background-color: #FF5252;
        color: white;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 50%;
        min-width: 18px;
        text-align: center;
        line-height: 1.2;
    }
    
    .menu-toggle {
        display: none;
        font-size: 24px;
        color: #333;
        cursor: pointer;
    }
    
    /* Dropdown Menu Styles */
    .dropdown {
        position: relative;
    }
    
    .dropdown > a i {
        font-size: 12px;
        margin-left: 5px;
        transition: transform 0.3s ease;
    }
    
    .dropdown:hover > a i {
        transform: rotate(180deg);
    }
    
    .dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        background-color: #fff;
        min-width: 200px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        border-radius: 8px;
        padding: 10px 0;
        margin-top: 15px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        z-index: 1000;
        list-style: none;
    }
    
    .dropdown:hover .dropdown-menu {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .dropdown-menu li {
        padding: 0;
    }
    
    .dropdown-menu li a {
        display: block;
        padding: 12px 20px;
        color: #555;
        text-decoration: none;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .dropdown-menu li a i {
        margin-right: 10px;
        width: 18px;
        text-align: center;
        color: #FF5252;
    }
    
    .dropdown-menu li a:hover {
        background-color: #FFF0F0;
        color: #FF5252;
        padding-left: 25px;
    }
    
    .dropdown-menu li.divider {
        height: 1px;
        background-color: #eee;
        margin: 8px 0;
    }
    
    /* Remove default active underline for dropdown */
    .dropdown > a.active::after {
        display: none;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .nav-menu {
            position: fixed;
            left: -100%;
            top: 70px;
            flex-direction: column;
            background-color: #fff;
            width: 100%;
            text-align: center;
            transition: left 0.3s ease;
            box-shadow: 0 10px 27px rgba(0,0,0,0.05);
            padding: 20px 0;
            gap: 0;
        }
        
        .nav-menu li {
            padding: 15px 0;
            width: 100%;
        }
        
        .nav-menu.active {
            left: 0;
        }
        
        .menu-toggle {
            display: block;
        }
        
        /* Mobile Dropdown */
        .dropdown-menu {
            position: static;
            box-shadow: none;
            border-radius: 0;
            margin-top: 10px;
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .dropdown.active .dropdown-menu {
            max-height: 400px;
        }
        
        .dropdown > a i {
            transition: transform 0.3s ease;
        }
        
        .dropdown.active > a i {
            transform: rotate(180deg);
        }
        
        .nav-right {
            gap: 15px;
        }
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
    $(document).ready(function() {
        // Mobile menu toggle
        $('#menuToggle').on('click', function() {
            $('#navMenu').toggleClass('active');
        });
        
        // Mobile dropdown toggle
        $('.dropdown > a').on('click', function(e) {
            if ($(window).width() <= 768) {
                e.preventDefault();
                $(this).parent().toggleClass('active');
            }
        });
        
        // Close mobile menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.nav-wrapper').length) {
                $('#navMenu').removeClass('active');
            }
        });
        
        // Update cart count (you can integrate with your cart system)
        updateCartCount();
    });
    
    function updateCartCount() {
        // This is a placeholder - integrate with your actual cart system
        // Example: fetch from localStorage or session
        var cartCount = localStorage.getItem('cartCount') || 0;
        $('#cartCount').text(cartCount);
    }
</script>