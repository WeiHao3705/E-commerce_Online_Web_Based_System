<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_views = (strpos($current_dir, '/views') !== false);
$prefix = $is_in_views ? '../' : '';
?>
<section class="guest-hero" style="padding:40px 0;background:#f4f6f9;">
    <div class="container" style="display:flex;gap:30px;align-items:center;">
        <div style="flex:1;">
            <h1 style="font-size:40px;margin-bottom:10px;color:#2b2b5c;">Buy Everything You Need</h1>
            <h2 style="font-size:34px;margin-bottom:18px;color:#333;">ONLINE SHOPPING</h2>
            <p style="color:#666;margin-bottom:20px;max-width:560px;">Explore our wide selection of sports equipment and accessories. Sign up or log in to get the best deals and faster checkout.</p>
            <div style="display:flex;gap:12px;">
                <a href="<?php echo $prefix; ?>products.php" class="submit-btn" style="background:#FF523B;padding:12px 20px;border-radius:8px;color:#fff;text-decoration:none;">Order Now</a>
                <a href="<?php echo $prefix; ?>account.php" style="text-decoration:none;color:#FF523B;font-weight:600;align-self:center;">Login</a>
            </div>
        </div>
        <div style="flex:1;display:flex;justify-content:center;">
            <img src="<?php echo $prefix; ?>images/hero-guest.png" alt="Online shopping" style="max-width:420px;border-radius:8px;box-shadow:0 10px 30px rgba(0,0,0,0.08);">
        </div>
    </div>
</section>
