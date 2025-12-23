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

// Try to get top sellers from DB (sum of all sales per product)
require_once __DIR__ . '/../../database/connection.php';
require_once __DIR__ . '/../../repository/ProductRepository.php';
$db = new Database();
$conn = $db->getConnection();
$productRepo = new ProductRepository($conn);
$topSellers = $productRepo->getTopSellingProducts(5);

// If we have at least 5 top sellers with data, use them; otherwise fallback to guest slider images
$useTopSellers = is_array($topSellers) && count($topSellers) >= 5;

$productImages = [];
if ($useTopSellers) {
    foreach ($topSellers as $p) {
        $rawPath = $p['image_path'] ?? '';
        $imgPath = !empty($rawPath) ? $rawPath : 'images/products/default.jpg';

        if (strpos($imgPath, 'web/') === 0) $imgPath = substr($imgPath, 4);
        if (preg_match('#[a-zA-Z]:\\\\|/#', $imgPath)) {
            $imgPath = preg_replace('#.*images[\\/]#', 'images/', $imgPath);
        }
        $imgPath = str_replace('\\', '/', $imgPath);
        if (strpos($imgPath, 'images/') !== 0) {
            $imgPath = 'images/' . ltrim($imgPath, '/');
        }

        $productImages[] = [
            'img' => $imgPath,
            'link' => 'views/product/ProductDetails.php?id=' . urlencode($p['product_id'])
        ];
    }
} else {
    // fallback random 5 from images/guest/slider
    if (!empty($productImages)) {
        shuffle($productImages);
        $fallback = array_slice($productImages, 0, 5);
        $productImages = [];
        foreach ($fallback as $p) {
            $productImages[] = [
                'img' => 'images/guest/slider/' . basename($p),
                'link' => 'views/product/ProductPage.php'
            ];
        }
    } else {
        // if folder doesn't exist, use a repeat of the hero image
        $productImages = [];
        for ($i = 0; $i < 5; $i++) {
            $productImages[] = [
                'img' => 'images/guest/' . $randomGuestImg,
                'link' => 'views/product/ProductPage.php'
            ];
        }
    }
}
?>

<section class="product-slider-section">
    <div class="product-slider-container">
        <div class="product-slider" id="productSlider">
            <?php foreach ($productImages as $index => $item): $rank = $index + 1; ?>
                <div class="slide">
                    <a href="<?php echo $prefix; ?><?php echo htmlspecialchars($item['link']); ?>">
                        <div class="rank-badge">#<?php echo $rank; ?></div>
                        <img src="<?php echo $prefix; ?><?php echo htmlspecialchars($item['img']); ?>" alt="Product image">
                    </a>
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
