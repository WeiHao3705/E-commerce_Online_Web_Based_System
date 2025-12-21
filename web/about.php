<?php 
session_start();
$pageTitle = 'About Us';

// Calculate base path
$currentFileDir = dirname(__FILE__);
$webBasePath = str_replace('\\', '/', $currentFileDir) . '/';
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webBasePath);
$prefix = str_replace('\\', '/', $relativePath) . '/';
// Calculate root path (one level up from web/)
$rootPath = dirname($prefix);
$rootPath = rtrim($rootPath, '/') . '/';

include 'general/_header.php';
include 'general/_navbar.php';
?>

<!-- About Us Page Styles -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<link rel="stylesheet" href="<?php echo $prefix; ?>css/about.css">

<main class="about-page">
    <div class="container">
        <!-- Hero Section -->
        <section class="about-hero">
            <div class="about-hero-content">
                <h1>Your Ultimate Brand Marketplace</h1>
                <p>Discover premium products from all your favorite brands in one convenient place. Explore our diverse collections.</p>
                <a href="<?php echo $prefix; ?>views/product/ProductPage.php" class="about-hero-btn">Explore Collections</a>
            </div>
        </section>

        <!-- Mission & Vision Section -->
        <section class="about-section">
            <div class="about-section-title">
                <h2>OUR PROMISE</h2>
                <h1>Our Mission & Vision</h1>
                <p>We are committed to bringing customers access to the finest products from all major brands, making quality shopping convenient and affordable.</p>
            </div>

            <div class="mission-vision-grid">
                <div class="mission-vision-card">
                    <span class="material-symbols-outlined">rocket_launch</span>
                    <h3>Our Mission</h3>
                    <p>To be a comprehensive marketplace where customers can find all their favorite brands in one place, offering the best selection and value.</p>
                </div>
                <div class="mission-vision-card">
                    <span class="material-symbols-outlined">visibility</span>
                    <h3>Our Vision</h3>
                    <p>To become the leading destination for multi-brand shopping, connecting customers with diverse products and trusted brands worldwide.</p>
                </div>
            </div>
        </section>

        <!-- History Timeline Section -->
        <section class="about-section">
            <div class="about-section-title">
                <h2>Our History</h2>
            </div>

            <div class="history-timeline">
                <div class="timeline-item">
                    <div class="timeline-icon">
                        <span class="material-symbols-outlined">garage_home</span>
                        <div class="timeline-line"></div>
                    </div>
                    <div class="timeline-content">
                        <div class="year">2015</div>
                        <h3>Platform Launch</h3>
                        <p>Started as a vision to connect customers with multiple brand products in one convenient online marketplace.</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon">
                        <div class="timeline-line" style="min-height: 20px;"></div>
                        <span class="material-symbols-outlined">handshake</span>
                        <div class="timeline-line"></div>
                    </div>
                    <div class="timeline-content">
                        <div class="year">2017</div>
                        <h3>Brand Partnerships Begin</h3>
                        <p>Started partnering with leading brands to bring diverse product selections to our customers.</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon">
                        <div class="timeline-line" style="min-height: 20px;"></div>
                        <span class="material-symbols-outlined">public</span>
                        <div class="timeline-line"></div>
                    </div>
                    <div class="timeline-content">
                        <div class="year">2020</div>
                        <h3>Massive Growth</h3>
                        <p>Expanded to showcase hundreds of brands, becoming a trusted destination for multi-brand shopping.</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon">
                        <div class="timeline-line" style="min-height: 20px;"></div>
                        <span class="material-symbols-outlined">lightbulb</span>
                    </div>
                    <div class="timeline-content">
                        <div class="year">2024</div>
                        <h3>Premium Features</h3>
                        <p>Launched enhanced features to help customers easily discover and compare all brands available in our marketplace.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Meet the Team Section -->
        <section class="about-section">
            <div class="about-section-title">
                <h2>Meet the Team</h2>
                <p style="margin-top: 10px;">The passionate minds behind ProGear Sports.</p>
            </div>

            <div class="team-grid">
                <div class="team-member">
                    <img src="<?php echo $prefix; ?>images/About_Us/lwh.jpg" alt="Team Member - Lead Developer">
                    <h3>Lee Wei Hao</h3>
                    <p class="role">Chief Executive Officer</p>
                </div>
                <div class="team-member">
                    <img src="<?php echo $prefix; ?>images/About_Us/cws.jpg" alt="Team Member - Chief Technology Officer">
                    <h3>Chan Wei Song</h3>
                    <p class="role">Marketing Manager</p>
                </div>
                <div class="team-member">
                    <img src="<?php echo $prefix; ?>images/About_Us/skh.jpg" alt="Team Member - Head of Operations">
                    <h3>Shim Kian Hau</h3>
                    <p class="role">Head of Operations</p>
                </div>
                <div class="team-member">
                    <img src="<?php echo $prefix; ?>images/About_Us/lcb.jpg" alt="Team Member - Product Manager">
                    <h3>Liew Chee Been</h3>
                    <p class="role">Product Manager</p>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="about-section">
            <div class="about-cta">
                <div class="about-cta-card">
                    <h2>Ready to Elevate Your Game?</h2>
                    <p>Explore our collections and find the perfect gear to match your ambition. Join the ProGear family today.</p>
                    <a href="<?php echo $prefix; ?>views/product/ProductPage.php" class="about-cta-btn">Shop Our Gear</a>
                </div>
            </div>
        </section>
    </div>
</main>

<?php include 'general/_footer.php'; ?>

