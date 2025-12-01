<?php
$currentFileDir = dirname(__FILE__);
$webRootDir = dirname($currentFileDir);
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$webBasePath = str_replace('\\', '/', $relativePath) . '/';
$prefix = $webBasePath;
?>
<link rel="stylesheet" href="<?php echo $prefix; ?>css/navbar.css?v=<?php echo filemtime(__DIR__ . '/../css/navbar.css'); ?>">

<nav class="navbar">
    <div class="container">
        <div class="nav-wrapper">
            <!-- Logo -->
            <div class="logo">
                <a href="<?php echo $prefix; ?>index.php">
                    <img src="<?php echo $prefix; ?>images/logo/logo2.png" alt="NGEAR">
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
                    if ($isGuest):
                    ?>
                        <li><a href="<?php echo $prefix; ?>account.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'account.php' ? 'active' : ''; ?>">Login</a></li>
                    <?php else: ?>
                        <li class="dropdown">
                            <a href="<?php echo $prefix; ?>index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'account.php') ? 'active' : ''; ?>">
                                Account <i class="fas fa-caret-down"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="<?php echo $prefix; ?>index.php"><i class="fas fa-user"></i> My Profile</a></li>
                                <li><a href="<?php echo $prefix; ?>orders.php"><i class="fas fa-box"></i> My Orders</a></li>
                                <li><a href="<?php echo $prefix; ?>controller/VoucherController.php?action=showMemberVouchers"><i class="fas fa-ticket-alt"></i> My Vouchers</a></li>
                                <li><a href="<?php echo $prefix; ?>wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
                                <li><a href="<?php echo $prefix; ?>settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                                <li class="divider"></li>
                                <li><a href="<?php echo $prefix; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>

                <!-- Search Bar -->
                <div class="search-container">
                    <form class="search-form" action="<?php echo $prefix; ?>search.php" method="GET">
                        <div class="search-input-group">
                            <input type="text" name="q" class="search-input" placeholder="Search products..." autocomplete="off" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                            <button type="submit" class="search-btn">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Cart Icon -->
                <div class="nav-icons">
                    <?php if (!$isGuest): ?>
                    <a href="<?php echo $prefix; ?>views/Cart_Order/cart.php" class="cart-icon">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="cart-count" id="cartCount">0</span>
                    </a>
                    <?php endif; ?>
                    <div class="menu-toggle" id="menuToggle">
                        <i class="fas fa-bars"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

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