<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_views = (strpos($current_dir, '/views') !== false);
$prefix = $is_in_views ? '../' : '';

$pageTitle = 'Home';

// Gather images from web/images/home
$imgDir = __DIR__ . '/../images/home';
$images = [];
if (is_dir($imgDir)) {
    $images = glob($imgDir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
}

?>

<link rel="stylesheet" href="<?php echo $prefix; ?>css/MemberHome.css">

<section class="member-hero">
        <div class="carousel fullwidth" id="homeCarousel">
            <div class="slides">
                <?php if (!empty($images)): ?>
                    <?php foreach ($images as $idx => $imgPath):
                        $file = basename($imgPath);
                        $active = $idx === 0 ? ' active' : '';
                    ?>
                        <div class="slide<?php echo $active; ?>">
                            <img src="<?php echo $prefix; ?>images/home/<?php echo htmlspecialchars($file); ?>" alt="Banner <?php echo $idx + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="slide active"><div class="slide-fallback">No banner images found. Add files to <code>images/home/</code></div></div>
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
        var delay = 4000; // 4 seconds

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

        function start(){ interval = setInterval(nextSlide, delay); }
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
