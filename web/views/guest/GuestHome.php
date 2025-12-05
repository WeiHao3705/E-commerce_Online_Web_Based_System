<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_views = (strpos($current_dir, '/views') !== false);
$prefix = $is_in_views ? '../' : '';

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
    <div class="guest-container" style="--guest-image-vw:60vw;">
        <img class="guest-img" src="<?php echo $prefix; ?>images/guest/<?php echo htmlspecialchars($randomGuestImg); ?>" alt="Guest hero">
        <div class="hero-content">
            <h2>ONLINE SHOPPING</h2>
            <p>Explore our wide selection of sports equipment and accessories. Sign up or log in to get the best deals and faster checkout.</p>
            <div class="hero-actions">
                <a href="<?php echo $prefix; ?>products.php" class="btn-primary">Sign up</a>
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
    
    <script>
        (function(){
            const track = document.getElementById('productSlider');
            if(!track) return;

            let offset = 0;
            let speed = 0.6; // px per frame
            let slideWidth = 0; // width of a single slide (first)
            let totalWidth = 0; // total width of all slides in the track
            let rafId = null;

            // Create a seamless loop by cloning slides to ensure continuous width
            (function duplicateForLoop(){
                const slides = Array.from(track.children);
                const minTotalWidth = window.innerWidth * 2; // ensure at least 2x viewport width
                totalWidth = slides.reduce((acc, el)=> acc + el.offsetWidth + parseFloat(getComputedStyle(el).marginRight||0), 0);
                let i = 0;
                while(totalWidth < minTotalWidth && slides.length){
                    const clone = slides[i % slides.length].cloneNode(true);
                    track.appendChild(clone);
                    totalWidth += clone.offsetWidth + parseFloat(getComputedStyle(clone).marginRight||0);
                    i++;
                }
            })();

            function computeSlideWidth(){
                const first = track.firstElementChild;
                if(!first) return 0;
                const style = window.getComputedStyle(first);
                const mr = parseFloat(style.marginRight) || 0;
                return first.offsetWidth + mr; // use offsetWidth to avoid forced layout each frame
            }

            function ensureImagesLoaded(callback){
                const imgs = track.querySelectorAll('img');
                let remaining = imgs.length;
                if(remaining === 0){ callback(); return; }
                imgs.forEach(img => {
                    if(img.complete){
                        if(--remaining === 0) callback();
                    } else {
                        img.addEventListener('load', () => { if(--remaining === 0) callback(); });
                        img.addEventListener('error', () => { if(--remaining === 0) callback(); });
                    }
                });
            }

            function step(){
                offset -= speed;
                // use translate3d for GPU acceleration
                track.style.transform = `translate3d(${offset}px,0,0)`;
                if(slideWidth <= 0 || totalWidth <= 0){ rafId = requestAnimationFrame(step); return; }
                // Wrap when we've scrolled the full width of the track to avoid visible restart
                if(Math.abs(offset) >= totalWidth){
                    offset += totalWidth; // wrap seamlessly without moving DOM
                }
                rafId = requestAnimationFrame(step);
            }

            function computeTotalWidth(){
                let sum = 0;
                for(const el of track.children){
                    const style = window.getComputedStyle(el);
                    const mr = parseFloat(style.marginRight) || 0;
                    sum += el.offsetWidth + mr;
                }
                return sum;
            }

            function onResize(){
                slideWidth = computeSlideWidth();
                totalWidth = computeTotalWidth();
            }

            window.addEventListener('resize', () => { onResize(); });

            ensureImagesLoaded(() => {
                onResize();
                // set initial transform to avoid jump
                track.style.transform = 'translate3d(0,0,0)';
                rafId = requestAnimationFrame(step);
            });
        })();
    </script>
</section>

<div class="slider-cta">
    <button type="button" class="slider-learn-btn">Learn more</button>
</div>
