<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <!-- Download App Section -->
            <div class="footer-section">
                <h3>Download Our App</h3>
                <p class="footer-description">Download App for Android and iOS mobile phone.</p>
                <div class="app-buttons">
                    <a href="#" class="app-btn">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Get it on Google Play">
                    </a>
                    <a href="#" class="app-btn">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="Download on the App Store">
                    </a>
                </div>
            </div>

            <!-- Logo and Purpose -->
            <div class="footer-section footer-center">
                <div class="footer-logo">
                    <?php
                    $currentFileDir = dirname(__FILE__);
                    $webRootDir = dirname($currentFileDir);
                    $docRoot = $_SERVER['DOCUMENT_ROOT'];
                    $relativePath = str_replace($docRoot, '', $webRootDir);
                    $webBasePath = str_replace('\\', '/', $relativePath) . '/';
                    ?>
                    <img src="<?php echo $webBasePath; ?>images/logo/logo2.png" alt="NGEAR">
                </div>
                <p class="footer-purpose">Our Purpose Is To Provide Affordable Sports Equipment to all.</p>
            </div>

            <!-- Useful Links -->
            <div class="footer-section">
                <h3>Useful Links</h3>
                <ul class="footer-links">
                    <?php
                    // Calculate base path dynamically to work from any location
                    $currentFileDir = dirname(__FILE__); // Gets web/general/
                    $webRootDir = dirname($currentFileDir); // Gets web/
                    $docRoot = $_SERVER['DOCUMENT_ROOT'];
                    $relativePath = str_replace($docRoot, '', $webRootDir);
                    $webBasePath = str_replace('\\', '/', $relativePath) . '/';
                    $controllerBasePath = $webBasePath . 'controller/';
                    ?>
                    <li><a href="<?php echo $controllerBasePath; ?>VoucherController.php?action=showMemberVouchers">Vouchers</a></li>
                    <li><a href="blog.php">Blog Post</a></li>
                    <li><a href="return-policy.php">Return Policy</a></li>
                    <li><a href="affiliate.php">Join Affiliate</a></li>
                </ul>
            </div>

            <!-- Follow Us -->
            <div class="footer-section">
                <h3>Follow Us</h3>
                <ul class="footer-links">
                    <li><a href="#"><i class="fab fa-facebook"></i> Facebook</a></li>
                    <li><a href="#"><i class="fab fa-twitter"></i> Twitter</a></li>
                    <li><a href="#"><i class="fab fa-instagram"></i> Instagram</a></li>
                    <li><a href="#"><i class="fab fa-youtube"></i> Youtube</a></li>
                </ul>
            </div>
        </div>

        <!-- Copyright -->
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> - <?php echo isset($_SESSION['developer_name']) ? $_SESSION['developer_name'] : 'NGear Sports equipment'; ?></p>
        </div>
    </div>
</footer>

<style>
    .footer .container {
        width: 100%;
        max-width: 1920px;
        margin: 0 auto;
        padding: 0 clamp(10px, 2vw, 40px);
    }

    .footer {
        background-color: #000;
        color: #8a8a8a;
        padding: 60px 0 20px;
        margin-top: 80px;
        width: 100%;
    }

    .footer-content {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 40px;
        margin-bottom: 50px;
        width: 100%;
        justify-items: center;
    }

    .footer-section {
        width: 100%;
        display: flex;
        flex-direction: column;
        text-align: center;
        align-items: center;
    }

    .footer-section h3 {
        color: #fff;
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
    }

    .footer-description {
        font-size: 14px;
        line-height: 1.8;
        margin-bottom: 20px;
    }

    .app-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .app-btn {
        display: inline-block;
        width: 150px;
    }

    .app-btn img {
        width: 100%;
        height: auto;
        transition: transform 0.3s ease;
    }

    .app-btn:hover img {
        transform: scale(1.05);
    }

    .footer-center {
        text-align: center;
    }

    .footer-logo {
        margin-bottom: 20px;
    }

    .footer-logo img {
        height: 60px;
        width: auto;
        filter: brightness(0) invert(1);
    }

    .footer-purpose {
        font-size: 14px;
        line-height: 1.8;
        max-width: 350px;
        margin: 0 auto;
    }

    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .footer-links li {
        margin-bottom: 12px;
    }

    .footer-links a {
        color: #8a8a8a;
        text-decoration: none;
        font-size: 14px;
        transition: color 0.3s ease;
        display: inline-block;
    }

    .footer-links a:hover {
        color: #FF5252;
    }

    .footer-links i {
        margin-right: 8px;
        width: 20px;
    }

    .footer-bottom {
        text-align: center;
        padding-top: 30px;
    }

    .footer-bottom p {
        font-size: 14px;
        color: #666;
    }

    .navbar {
        text-align: left;
        /* Add this */
    }

    .navbar .container {
        text-align: left;
        /* Add this */
    }

    /* Responsive */
    @media (max-width: 768px) {
        .footer {
            padding: 40px 0 20px;
        }

        .footer-content {
            grid-template-columns: 1fr;
            gap: 30px;
        }

        .footer-section {
            text-align: center;
        }

        .app-buttons {
            align-items: center;
        }

        .brand-partners {
            gap: 25px;
        }

        .brand-logo {
            height: 25px;
        }
    }
</style>

</body>

</html>