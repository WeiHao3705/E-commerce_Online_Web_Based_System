<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Redirect admins to AdminDashboard - they should not access member pages
if (isset($_SESSION['user']) && isset($_SESSION['user']->role) && $_SESSION['user']->role === 'admin') {
    header('Location: ../../views/admin/AdminDashboard.php');
    exit;
}

$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_views = (strpos($current_dir, '/views') !== false);
// Check if we're being included from root index.php
$is_root = (basename($_SERVER['SCRIPT_FILENAME']) === 'index.php' && dirname($_SERVER['SCRIPT_FILENAME']) !== __DIR__);
$prefix = $is_root ? 'web/' : ($is_in_views ? '../' : '');

$pageTitle = 'Home';

// Gather slide images from web/images/home/slide
$imgDir = __DIR__ . '/../../images/home/slide';
$images = [];
if (is_dir($imgDir)) {
    $images = glob($imgDir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
}

?>

<link rel="stylesheet" href="<?php echo $prefix; ?>css/MemberHome.css?v=<?php echo filemtime(__DIR__ . '/../../css/MemberHome.css'); ?>">

<section class="member-hero">
        <div class="carousel fullwidth" id="homeCarousel">
            <div class="slides">
                <?php if (!empty($images)): ?>
                    <?php foreach ($images as $idx => $imgPath):
                        $file = basename($imgPath);
                        $active = $idx === 0 ? ' active' : '';
                    ?>
                        <div class="slide<?php echo $active; ?>">
                            <img src="<?php echo $prefix; ?>images/home/slide/<?php echo htmlspecialchars($file); ?>" alt="Banner <?php echo $idx + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="slide active"><div class="slide-fallback">No banner images found. Add files to <code>images/home/slide/</code></div></div>
                <?php endif; ?>
            </div>

            <button class="carousel-btn prev" aria-label="Previous slide">‹</button>
            <button class="carousel-btn next" aria-label="Next slide">›</button>

            <div class="carousel-indicators">
                <?php if (!empty($images)): ?>
                    <?php for ($i = 0; $i < count($images); $i++): ?>
                        <button class="indicator<?php echo $i === 0 ? ' active' : ''; ?>" data-index="<?php echo $i; ?>" aria-label="Go to slide <?php echo $i+1; ?>"></button>
                    <?php endfor; ?>
                <?php else: ?>
                    <span class="indicator active"></span>
                <?php endif; ?>
            </div>
        </div>
    </section>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?php echo $prefix; ?>js/memberHome.js"></script>

<section class="product-intro">
    <div class="intro-grid">
        <!-- Sport Shoes -->
        <article class="intro-card" data-href="views/product/ProductPage.php?category=Shoes">
            <div class="intro-media">
                <img src="<?php echo $prefix; ?>images/home/intro/intro_shoes.jpg" alt="Sport Shoes" onerror="this.style.display='none'">
            </div>
            <div class="intro-content">
                <h2 class="intro-title">Step Into Performance</h2>
                <p class="intro-text">Engineered sport shoes built for speed, stability, and comfort. Train harder with breathable uppers and responsive cushioning.</p>
                <div class="intro-actions">
                    <a class="btn primary" href="<?php echo $prefix; ?>views/product/ProductPage.php?category=Shoes">Shop sport shoes</a>
                    <a class="btn link" href="<?php echo $prefix; ?>views/product/ProductPage.php?category=Shoes#shoes">Learn more</a>
                </div>
            </div>
        </article>

        <!-- Pants -->
        <article class="intro-card" data-href="views/product/ProductPage.php?category=Pants">
            <div class="intro-content">
                <h2 class="intro-title">Move With Ease</h2>
                <p class="intro-text">Performance pants with stretch, moisture-wicking fabrics, and streamlined fits for workouts and daily wear.</p>
                <div class="intro-actions">
                    <a class="btn primary" href="<?php echo $prefix; ?>views/product/ProductPage.php?category=Pants">Browse pants</a>
                    <a class="btn link" href="<?php echo $prefix; ?>views/product/ProductPage.php?category=Pants#pants">Learn more</a>
                </div>
            </div>
            <div class="intro-media">
                <img src="<?php echo $prefix; ?>images/home/intro/intro_pants.jpg" alt="Performance Pants" onerror="this.style.display='none'">
            </div>
        </article>

        <!-- Wear (Tops/Jackets) -->
        <article class="intro-card" data-href="views/product/ProductPage.php?category=Wear">
            <div class="intro-media">
                <img src="<?php echo $prefix; ?>images/home/intro/intro_shirt.jpg" alt="Sports Wear" onerror="this.style.display='none'">
            </div>
            <div class="intro-content">
                <h2 class="intro-title">Ready For Every Run</h2>
                <p class="intro-text">Lightweight tops and weather-ready layers designed to keep you cool, dry, and focused on your goals.</p>
                <div class="intro-actions">
                    <a class="btn primary" href="<?php echo $prefix; ?>views/product/ProductPage.php?category=Sportwear">Explore wear</a>
                    <a class="btn link" href="<?php echo $prefix; ?>views/product/ProductPage.php?category=Sportwear#wear">Learn more</a>
                </div>
            </div>
        </article>
    </div>
</section>
<script>
// Make entire intro-card act as link to category (ignore clicks on internal anchors/buttons)
document.addEventListener('DOMContentLoaded', function() {
    var cards = document.querySelectorAll('.intro-card[data-href]');
    cards.forEach(function(card) {
        card.style.cursor = 'pointer';
        card.addEventListener('click', function(e) {
            if (e.target.closest('a') || e.target.closest('button')) return;
            var href = card.getAttribute('data-href');
            if (!href) return;
            var prefix = '<?php echo $prefix; ?>';
            window.location.href = prefix + href;
        });
    });
});
</script>
