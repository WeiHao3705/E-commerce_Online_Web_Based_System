<?php 
session_start();
$pageTitle = 'About Us';

// Calculate base path
$currentFileDir = dirname(__FILE__);
$webBasePath = str_replace('\\', '/', $currentFileDir) . '/';
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webBasePath);
$prefix = str_replace('\\', '/', $relativePath) . '/';

include 'general/_header.php';
include 'general/_navbar.php';
?>

<!-- About Us Page Styles -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<style>
    .about-page {
        min-height: 100vh;
        background-color: #f8f6f6;
    }

    /* Hero Section */
    .about-hero {
        position: relative;
        min-height: 480px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(rgba(0, 0, 0, 0.2) 0%, rgba(0, 0, 0, 0.5) 100%), 
                    url('<?php echo $prefix; ?>images/About_Us/AboutUs.jpg'),
                    linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        padding: 40px 20px;
        margin: 20px 0;
        border-radius: 12px;
    }

    .about-hero-content {
        text-align: center;
        color: white;
        max-width: 800px;
        padding: 0 20px;
    }

    .about-hero h1 {
        font-size: 48px;
        font-weight: 900;
        margin-bottom: 20px;
        line-height: 1.2;
        letter-spacing: -0.02em;
    }

    .about-hero p {
        font-size: 18px;
        margin-bottom: 30px;
        line-height: 1.6;
        opacity: 0.95;
    }

    .about-hero-btn {
        display: inline-block;
        padding: 14px 32px;
        background-color: #FF5252;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 700;
        font-size: 16px;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .about-hero-btn:hover {
        background-color: #e04848;
        transform: translateY(-2px);
    }

    /* Section Styles */
    .about-section {
        padding: 60px 0;
    }

    .about-section-title {
        text-align: center;
        margin-bottom: 50px;
    }

    .about-section-title h2 {
        font-size: 22px;
        font-weight: 700;
        color: #FF5252;
        margin-bottom: 10px;
        letter-spacing: -0.01em;
    }

    .about-section-title h1 {
        font-size: 36px;
        font-weight: 900;
        color: #1b0d0d;
        margin-bottom: 15px;
        letter-spacing: -0.02em;
    }

    .about-section-title p {
        font-size: 16px;
        color: #9a4c4c;
        max-width: 720px;
        margin: 0 auto;
        line-height: 1.6;
    }

    /* Mission & Vision Cards */
    .mission-vision-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    @media (min-width: 768px) {
        .mission-vision-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .mission-vision-card {
        background: #fcf8f8;
        border: 1px solid #f3e7e7;
        border-radius: 12px;
        padding: 40px;
        text-align: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .mission-vision-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .mission-vision-card .material-symbols-outlined {
        font-size: 48px;
        color: #FF5252;
        margin-bottom: 20px;
    }

    .mission-vision-card h3 {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 15px;
        color: #1b0d0d;
    }

    .mission-vision-card p {
        font-size: 14px;
        color: #9a4c4c;
        line-height: 1.6;
    }

    /* History Timeline */
    .history-timeline {
        max-width: 1000px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .timeline-item {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 20px;
        margin-bottom: 40px;
        position: relative;
    }

    .timeline-icon {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .timeline-icon .material-symbols-outlined {
        font-size: 36px;
        color: #FF5252;
        background: white;
        padding: 10px;
        border-radius: 50%;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .timeline-line {
        width: 2px;
        background: #f3e7e7;
        flex: 1;
        min-height: 40px;
    }

    .timeline-content {
        padding-bottom: 20px;
    }

    .timeline-content .year {
        font-size: 16px;
        color: #9a4c4c;
        margin-bottom: 5px;
    }

    .timeline-content h3 {
        font-size: 20px;
        font-weight: 600;
        color: #1b0d0d;
        margin-bottom: 8px;
    }

    .timeline-content p {
        font-size: 14px;
        color: #9a4c4c;
        line-height: 1.6;
    }

    /* Team Section */
    .team-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 40px;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    @media (min-width: 640px) {
        .team-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (min-width: 1024px) {
        .team-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
        }
    }

    .team-member {
        text-align: center;
    }

    .team-member img {
        width: 160px;
        height: 160px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 20px;
        border: 4px solid #FF5252;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    /* Adjust image positioning for specific team members */
    .team-member img[src*="lwh.jpg"] {
        object-position: center 50%;
    }
    
    .team-member img[src*="cws.jpg"] {
        object-position: center 15%;
    }

    .team-member h3 {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 5px;
        color: #1b0d0d;
    }

    .team-member .role {
        font-size: 14px;
        font-weight: 600;
        color: #FF5252;
        margin-bottom: 10px;
    }

    /* CTA Section */
    .about-cta {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .about-cta-card {
        background: #fcf8f8;
        border: 1px solid #f3e7e7;
        border-radius: 12px;
        padding: 60px 40px;
        text-align: center;
    }

    .about-cta-card h2 {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 15px;
        color: #1b0d0d;
    }

    .about-cta-card p {
        font-size: 16px;
        color: #9a4c4c;
        margin-bottom: 30px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.6;
    }

    .about-cta-btn {
        display: inline-block;
        padding: 14px 32px;
        background-color: #FF5252;
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 700;
        font-size: 16px;
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .about-cta-btn:hover {
        background-color: #e04848;
        transform: translateY(-2px);
    }

    /* Material Icons */
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .about-hero h1 {
            font-size: 36px;
        }

        .about-hero p {
            font-size: 16px;
        }

        .about-section-title h1 {
            font-size: 28px;
        }

        .mission-vision-card {
            padding: 30px 20px;
        }

        .about-cta-card {
            padding: 40px 20px;
        }
    }

    @media (max-width: 480px) {
        .about-hero {
            min-height: 400px;
            margin: 10px 0;
            border-radius: 8px;
        }

        .about-hero h1 {
            font-size: 28px;
        }

        .about-section {
            padding: 40px 0;
        }

        .about-section-title h1 {
            font-size: 24px;
        }

        .timeline-item {
            gap: 15px;
        }

        .team-member img {
            width: 120px;
            height: 120px;
        }
    }
</style>

<main class="about-page">
    <div class="container">
        <!-- Hero Section -->
        <section class="about-hero">
            <div class="about-hero-content">
                <h1>Driven by Passion for Sport</h1>
                <p>Crafting the finest equipment for athletes who demand the best. Discover the story behind our gear.</p>
                <a href="<?php echo $prefix; ?>index.php" class="about-hero-btn">Explore Collections</a>
            </div>
        </section>

        <!-- Mission & Vision Section -->
        <section class="about-section">
            <div class="about-section-title">
                <h2>OUR PROMISE</h2>
                <h1>Our Mission & Vision</h1>
                <p>We are dedicated to pushing the boundaries of performance and innovation, empowering every athlete to achieve their personal best.</p>
            </div>

            <div class="mission-vision-grid">
                <div class="mission-vision-card">
                    <span class="material-symbols-outlined">rocket_launch</span>
                    <h3>Our Mission</h3>
                    <p>To provide athletes with superior-quality sports equipment that enhances performance and inspires greatness.</p>
                </div>
                <div class="mission-vision-card">
                    <span class="material-symbols-outlined">visibility</span>
                    <h3>Our Vision</h3>
                    <p>To be the world's most trusted and innovative sports equipment brand, fostering a global community of passionate athletes.</p>
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
                        <div class="year">2010</div>
                        <h3>Founded in a Garage</h3>
                        <p>Our journey began with a single idea and a lot of passion in a humble garage.</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon">
                        <div class="timeline-line" style="min-height: 20px;"></div>
                        <span class="material-symbols-outlined">handshake</span>
                        <div class="timeline-line"></div>
                    </div>
                    <div class="timeline-content">
                        <div class="year">2014</div>
                        <h3>First Pro Partnership</h3>
                        <p>Teamed up with our first professional athlete, validating our product's performance at the highest level.</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon">
                        <div class="timeline-line" style="min-height: 20px;"></div>
                        <span class="material-symbols-outlined">public</span>
                        <div class="timeline-line"></div>
                    </div>
                    <div class="timeline-content">
                        <div class="year">2018</div>
                        <h3>International Expansion</h3>
                        <p>ProGear Sports goes global, making our equipment available to athletes worldwide.</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-icon">
                        <div class="timeline-line" style="min-height: 20px;"></div>
                        <span class="material-symbols-outlined">lightbulb</span>
                    </div>
                    <div class="timeline-content">
                        <div class="year">2022</div>
                        <h3>Innovation Lab Launch</h3>
                        <p>Opened our state-of-the-art R&D lab to pioneer the next generation of sports technology.</p>
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
                    <a href="<?php echo $prefix; ?>index.php" class="about-cta-btn">Shop Our Gear</a>
                </div>
            </div>
        </section>
    </div>
</main>

<?php include 'general/_footer.php'; ?>

