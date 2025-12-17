<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_views = (strpos($current_dir, '/views') !== false);
// Check if we're being included from root index.php
$is_root = (basename($_SERVER['SCRIPT_FILENAME']) === 'index.php' && dirname($_SERVER['SCRIPT_FILENAME']) !== __DIR__);
$prefix = $is_root ? 'web/' : ($is_in_views ? '../' : '');

// Get random image from images/guest/
$guestImgDir = __DIR__ . '/../../images/guest';
$guestImages = [];
if (is_dir($guestImgDir)) {
    $guestImages = glob($guestImgDir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
}
$randomGuestImg = !empty($guestImages) ? basename($guestImages[array_rand($guestImages)]) : 'hero-guest.png';
// Optional per-image placement overrides. Add filenames (basename) here to tune text placement.
$placements = [
    // 'example.jpg' => ['left' => '6%', 'width' => '40%'],
    // 'hero-guest.png' => ['left' => '6%', 'width' => '40%'],
];

$placement = isset($placements[$randomGuestImg]) ? $placements[$randomGuestImg] : null;
$heroLeft = $placement['left'] ?? '6%';
$heroWidth = $placement['width'] ?? '40%';
?>
<link rel="stylesheet" href="<?php echo $prefix; ?>css/GuestHome.css?v=<?php echo filemtime(__DIR__ . '/../../css/GuestHome.css'); ?>">

<section class="guest-hero">
    <div class="guest-container">
        <img class="guest-img" src="<?php echo $prefix; ?>images/guest/<?php echo htmlspecialchars($randomGuestImg); ?>" alt="Guest hero">
        <div class="hero-content">
            <h2>ONLINE SHOPPING</h2>
            <p>Explore our wide selection of sports equipment and accessories. Sign up or log in to get the best deals and faster checkout.</p>
            <div class="hero-actions">
                <a href="<?php echo $prefix; ?>views/member_management/MemberRegisterForm.php" class="btn-primary">Sign up</a>
                <a href="<?php echo $prefix; ?>account.php" class="btn-ghost">Login</a>
            </div>
        </div>
    </div>
</section>

<?php
// Build product image list for slider from images/products
$productsDir = __DIR__ . '/../../images/guest/slider';
$productImages = [];
if (is_dir($productsDir)) {
        $productImages = glob($productsDir . '/*.{jpg,jpeg,png,gif,webp,avif}', GLOB_BRACE);
}
// Limit to first 12 to avoid huge DOM
$productImages = array_slice($productImages, 0, 12);
?>

<section class="product-slider-section">
    <div class="product-slider-container">
        <div class="product-slider" id="productSlider">
            <?php foreach ($productImages as $imgPath): $name = basename($imgPath); ?>
                <div class="slide">
                    <img src="<?php echo $prefix; ?>images/guest/slider/<?php echo htmlspecialchars($name); ?>" alt="Product image">
                </div>
            <?php endforeach; ?>
            <?php if (empty($productImages)): ?>
                <div class="slide placeholder">No product images found.</div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="<?php echo $prefix; ?>js/guestHome.js"></script>
</section>

<div class="slider-cta">
    <a href="<?php echo $prefix; ?>views/product/ProductPage.php" class="slider-learn-btn">Learn more</a>
</div>
