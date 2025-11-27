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
                    <a href="<?php echo $prefix; ?>views/Cart_Order.php/cart.php" class="cart-icon">
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
    * {
        box-sizing: border-box;
    }

    .navbar {
        background-color: #FFF0F0;
        padding: 15px 0;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        position: sticky;
        top: 0;
        z-index: 1000;
        width: 100%;
    }

    .navbar .container {
        width: 100%;
        max-width: 1920px;
        margin: 0 auto;
        padding: 0 clamp(10px, 2vw, 40px);
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
        z-index: 1001;
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
        flex: 1;
        justify-content: flex-end;
        min-width: 0;
    }

    .nav-menu {
        display: flex;
        list-style: none;
        gap: 25px;
        margin: 0;
        padding: 0;
        align-items: center;
        flex-wrap: nowrap;
    }

    .nav-menu li {
        position: relative;
        white-space: nowrap;
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
        flex-shrink: 0;
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
        z-index: 1001;
    }

    /* Dropdown Menu Styles */
    .dropdown {
        position: relative;
    }

    .dropdown>a i {
        font-size: 12px;
        margin-left: 5px;
        transition: transform 0.3s ease;
    }

    .dropdown:hover>a i {
        transform: rotate(180deg);
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        background-color: #fff;
        min-width: 200px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
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
        white-space: nowrap;
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
    .dropdown>a.active::after {
        display: none;
    }

    /* Extra large screens */
    @media (min-width: 1920px) {
        .navbar {
            padding: 20px 0;
        }
        
        .logo img {
            height: 50px;
        }
        
        .nav-menu {
            gap: 40px;
        }
        
        .nav-menu li a {
            font-size: 16px;
        }
    }
    
    /* Large desktop screens */
    @media (min-width: 1440px) and (max-width: 1919px) {
        .nav-menu {
            gap: 35px;
        }
    }
    
    /* Medium desktop screens */
    @media (min-width: 1200px) and (max-width: 1439px) {
        .nav-menu {
            gap: 30px;
        }
    }
    
    /* Tablet landscape / Small desktop */
    @media (min-width: 951px) and (max-width: 1199px) {
        .nav-menu {
            gap: 20px;
        }

        .nav-menu li a {
            font-size: 14px;
        }
    }

    /* Tablet view - reduce gaps */
    @media (max-width: 1100px) {
        .nav-menu {
            gap: 18px;
        }

        .nav-menu li a {
            font-size: 14px;
        }
    }

    /* Switch to mobile menu earlier */
    @media (max-width: 950px) {
        .nav-wrapper {
            gap: 10px;
        }

        .nav-right {
            gap: 15px;
        }

        .nav-menu {
            position: fixed;
            left: -100%;
            top: 70px;
            flex-direction: column;
            background-color: #fff;
            width: 100%;
            text-align: center;
            transition: left 0.3s ease;
            box-shadow: 0 10px 27px rgba(0, 0, 0, 0.05);
            padding: 20px 0;
            gap: 0;
            max-height: calc(100vh - 70px);
            overflow-y: auto;
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

        .dropdown>a i {
            transition: transform 0.3s ease;
        }

        .dropdown.active>a i {
            transform: rotate(180deg);
        }

        .nav-right {
            flex: 0 0 auto;
        }
    }

    /* Tablet Portrait */
    @media (max-width: 950px) and (min-width: 601px) {
        .navbar {
            padding: 12px 0;
        }
        
        .nav-wrapper {
            gap: 15px;
        }
        
        .logo img {
            height: 35px;
        }
    }

    /* Mobile Landscape */
    @media (max-width: 600px) {
        .navbar {
            padding: 10px 0;
        }
        
        .nav-wrapper {
            gap: 10px;
        }
        
        .logo img {
            height: 32px;
        }
        
        .cart-icon {
            font-size: 18px;
        }
        
        .menu-toggle {
            font-size: 22px;
        }
        
        .nav-menu {
            top: 62px;
            max-height: calc(100vh - 62px);
        }
    }
    
    /* Mobile Portrait */
    @media (max-width: 480px) {
        .navbar .container {
            padding: 0 12px;
        }
        
        .logo img {
            height: 30px;
        }
        
        .cart-icon {
            font-size: 16px;
        }
        
        .cart-count {
            font-size: 10px;
            padding: 1px 5px;
            min-width: 16px;
            top: -6px;
            right: -8px;
        }
        
        .nav-menu {
            top: 60px;
            max-height: calc(100vh - 60px);
            padding: 15px 0;
        }
        
        .nav-menu li {
            padding: 12px 0;
        }
        
        .nav-menu li a {
            padding: 10px 15px;
            font-size: 14px;
        }
    }
    
    /* Extra small mobile devices */
    @media (max-width: 360px) {
        .navbar .container {
            padding: 0 10px;
        }
        
        .nav-wrapper {
            gap: 8px;
        }
        
        .logo img {
            height: 28px;
        }
        
        .nav-icons {
            gap: 12px;
        }
        
        .menu-toggle {
            font-size: 20px;
        }
    }

    /* Ensure proper alignment on larger screens */
    @media (min-width: 951px) {
        .nav-wrapper {
            justify-content: space-between;
        }

        .logo {
            flex: 0 0 auto;
        }

        .nav-right {
            flex: 1;
            justify-content: flex-end;
        }
    }
    
    /* Prevent horizontal scrolling */
    @media (max-width: 950px) {
        .nav-menu {
            -webkit-overflow-scrolling: touch;
        }
    }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
    $(document).ready(function() {
        var resizeTimer;
        var isMobileView = function() {
            return $(window).width() <= 950;
        };
        
        // Mobile menu toggle
        $('#menuToggle').on('click', function() {
            $('#navMenu').toggleClass('active');
            // Prevent body scroll when menu is open on mobile
            if ($('#navMenu').hasClass('active')) {
                $('body').css('overflow', 'hidden');
            } else {
                $('body').css('overflow', '');
            }
        });

        // Mobile dropdown toggle
        $('.dropdown > a').on('click', function(e) {
            if (isMobileView()) {
                e.preventDefault();
                $(this).parent().toggleClass('active');
            }
        });

        // Close mobile menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.nav-wrapper').length) {
                $('#navMenu').removeClass('active');
                $('.dropdown').removeClass('active');
                $('body').css('overflow', '');
            }
        });

        // Close menu when clicking a link (except dropdown triggers)
        $('.nav-menu li a').on('click', function(e) {
            if (!$(this).parent().hasClass('dropdown')) {
                $('#navMenu').removeClass('active');
                $('body').css('overflow', '');
            }
        });

        // Update cart count
        updateCartCount();

        // Debounced window resize handler for better performance
        function handleResize() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                // Close mobile menu if switching to desktop view
                if (!isMobileView()) {
                    $('#navMenu').removeClass('active');
                    $('.dropdown').removeClass('active');
                    $('body').css('overflow', '');
                }
                
                // Recalculate menu position for mobile
                if (isMobileView() && $('#navMenu').hasClass('active')) {
                    var navbarHeight = $('.navbar').outerHeight();
                    $('#navMenu').css('top', navbarHeight + 'px');
                }
            }, 250);
        }

        // Handle window resize with debounce
        $(window).on('resize', handleResize);
        
        // Handle orientation change
        $(window).on('orientationchange', function() {
            setTimeout(function() {
                handleResize();
                // Force reflow
                $('#navMenu').hide().show();
            }, 100);
        });
        
        // Prevent zoom on double tap (iOS Safari)
        var lastTouchEnd = 0;
        document.addEventListener('touchend', function(event) {
            var now = Date.now();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
        
        // Update menu top position on load
        if (isMobileView()) {
            var navbarHeight = $('.navbar').outerHeight();
            $('#navMenu').css('top', navbarHeight + 'px');
        }
    });

    function updateCartCount() {
        var cartCount = localStorage.getItem('cartCount') || 0;
        $('#cartCount').text(cartCount);
    }
    
    // Handle browser back/forward navigation
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            // Page was loaded from cache
            $('#navMenu').removeClass('active');
            $('.dropdown').removeClass('active');
            $('body').css('overflow', '');
        }
    });
</script>