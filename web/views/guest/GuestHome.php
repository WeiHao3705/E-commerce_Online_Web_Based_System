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
                <a href="<?php echo $prefix; ?>views/member_management/MemberRegisterForm.php" class="btn-primary">Sign up</a>
                <a href="<?php echo $prefix; ?>account.php" class="btn-ghost">Login</a>
            </div>
        </div>
    </div>
</section>
