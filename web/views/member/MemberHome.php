<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_views = (strpos($current_dir, '/views') !== false);
$prefix = $is_in_views ? '../' : '';

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

<script>
    (function(){
        var carousel = document.getElementById('homeCarousel');
        if (!carousel) return;
        var slides = carousel.querySelectorAll('.slide');
        var indicators = carousel.querySelectorAll('.indicator');
        var prev = carousel.querySelector('.prev');
        var next = carousel.querySelector('.next');
        var current = 0;
        var interval = null;
        var delay = 3000; // 3 seconds

        function show(n) {
            if (!slides.length) return;
            slides[current].classList.remove('active');
            if (indicators[current]) indicators[current].classList.remove('active');
            current = (n + slides.length) % slides.length;
            slides[current].classList.add('active');
            if (indicators[current]) indicators[current].classList.add('active');
        }

        function nextSlide(){ show(current + 1); }
        function prevSlide(){ show(current - 1); }

        function start(){ if (interval) clearInterval(interval); interval = setInterval(nextSlide, delay); }
        function stop(){ if (interval) { clearInterval(interval); interval = null; } }

        if (next) next.addEventListener('click', function(e){ e.preventDefault(); nextSlide(); stop(); start(); });
        if (prev) prev.addEventListener('click', function(e){ e.preventDefault(); prevSlide(); stop(); start(); });

        indicators.forEach(function(btn){ btn.addEventListener('click', function(){ var idx = parseInt(this.getAttribute('data-index')) || 0; show(idx); stop(); start(); }); });

        carousel.addEventListener('mouseenter', stop);
        carousel.addEventListener('mouseleave', start);

        // Start autoplay
        start();
    })();
</script>

<section class="product-intro">
    <div class="intro-grid">
        <!-- Sport Shoes -->
        <article class="intro-card">
            <div class="intro-media">
                <img src="<?php echo $prefix; ?>images/home/intro/intro_shoes.jpg" alt="Sport Shoes" onerror="this.style.display='none'">
            </div>
            <div class="intro-content">
                <h2 class="intro-title">Step Into Performance</h2>
                <p class="intro-text">Engineered sport shoes built for speed, stability, and comfort. Train harder with breathable uppers and responsive cushioning.</p>
                <div class="intro-actions">
                    <a class="btn primary" href="<?php echo $prefix; ?>views/product/ProductPage.php">Shop sport shoes</a>
                    <a class="btn link" href="<?php echo $prefix; ?>views/product/ProductPage.php#shoes">Learn more</a>
                </div>
            </div>
        </article>

        <!-- Pants -->
        <article class="intro-card">
            <div class="intro-content">
                <h2 class="intro-title">Move With Ease</h2>
                <p class="intro-text">Performance pants with stretch, moisture-wicking fabrics, and streamlined fits for workouts and daily wear.</p>
                <div class="intro-actions">
                    <a class="btn primary" href="<?php echo $prefix; ?>views/product/ProductPage.php">Browse pants</a>
                    <a class="btn link" href="<?php echo $prefix; ?>views/product/ProductPage.php#pants">Learn more</a>
                </div>
            </div>
            <div class="intro-media">
                <img src="<?php echo $prefix; ?>images/home/intro/intro_pants.jpg" alt="Performance Pants" onerror="this.style.display='none'">
            </div>
        </article>

        <!-- Wear (Tops/Jackets) -->
        <article class="intro-card">
            <div class="intro-media">
                <img src="<?php echo $prefix; ?>images/home/intro/intro_shirt.jpg" alt="Sports Wear" onerror="this.style.display='none'">
            </div>
            <div class="intro-content">
                <h2 class="intro-title">Ready For Every Run</h2>
                <p class="intro-text">Lightweight tops and weather-ready layers designed to keep you cool, dry, and focused on your goals.</p>
                <div class="intro-actions">
                    <a class="btn primary" href="<?php echo $prefix; ?>views/product/ProductPage.php">Explore wear</a>
                    <a class="btn link" href="<?php echo $prefix; ?>views/product/ProductPage.php#wear">Learn more</a>
                </div>
            </div>
        </article>
    </div>
</section>
