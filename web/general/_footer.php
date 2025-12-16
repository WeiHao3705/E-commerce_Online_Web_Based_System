<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <!-- Logo and Purpose -->
            <div class="footer-section footer-brand">
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
                <div class="footer-social">
                    <a href="https://www.facebook.com/chan.w.song.73" target="_blank" class="social-icon" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://www.instagram.com/hermen__chan?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" target="_blank" class="social-icon" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-section">
                <h3 class="footer-heading">Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="<?php echo $webBasePath; ?>index.php">Home</a></li>
                    <li><a href="<?php echo $webBasePath; ?>views/product/ProductPage.php">Products</a></li>
                    <li><a href="<?php echo $webBasePath; ?>about.php">About Us</a></li>
                </ul>
            </div>

            <!-- Customer Service -->
            <div class="footer-section">
                <h3 class="footer-heading">Customer Service</h3>
                <ul class="footer-links">
                    <?php
                    $controllerBasePath = $webBasePath . 'controller/';
                    ?>
                    <li><a href="<?php echo $controllerBasePath; ?>VoucherController.php?action=showMemberVouchers">My Vouchers</a></li>
                    <li><a href="<?php echo $webBasePath; ?>return-policy.php">Return Policy</a></li>
                    <li><a href="<?php echo $webBasePath; ?>contact.php">Contact</a></li>
                </ul>
            </div>

            <!-- Download App Section -->
            <div class="footer-section footer-app">
                <h3 class="footer-heading">Download Our App</h3>
                <p class="footer-description">Get the best shopping experience on your mobile device.</p>
                <div class="app-buttons">
                    <a href="#" class="app-btn" aria-label="Download on Google Play">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Get it on Google Play">
                    </a>
                    <a href="#" class="app-btn" aria-label="Download on App Store">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="Download on the App Store">
                    </a>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <div class="footer-divider"></div>

        <!-- Copyright -->
        <div class="footer-bottom">
            <p class="copyright-text">&copy; <?php echo date('Y'); ?> <?php echo isset($_SESSION['developer_name']) ? $_SESSION['developer_name'] : 'NGear Sports Equipment'; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

<style>
    .footer {
        background: linear-gradient(180deg, #ffffff 0%, #FFF0F0 100%);
        color: #555;
        padding: 70px 0 30px;
        margin-top: 80px;
        width: 100%;
        border-top: 1px solid rgba(255, 82, 82, 0.1);
    }

    .footer .container {
        width: 100%;
        max-width: 1920px;
        margin: 0 auto;
        padding: 0 clamp(10px, 2vw, 40px);
    }

    .footer-content {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1.5fr;
        gap: 50px;
        margin-bottom: 40px;
        width: 100%;
    }

    .footer-section {
        display: flex;
        flex-direction: column;
    }

    .footer-brand {
        max-width: 350px;
    }

    .footer-logo {
        margin-bottom: 20px;
    }

    .footer-logo img {
        height: 50px;
        width: auto;
        display: block;
    }

    .footer-purpose {
        font-size: 14px;
        line-height: 1.7;
        color: #666;
        margin-bottom: 25px;
    }

    .footer-social {
        display: flex;
        gap: 12px;
        margin-top: 10px;
    }

    .social-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        border: 2px solid #e0e0e0;
        border-radius: 50%;
        color: #555;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 16px;
    }

    .social-icon:hover {
        background: #FF5252;
        border-color: #FF5252;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 82, 82, 0.3);
    }

    .footer-heading {
        color: #333;
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 20px;
        position: relative;
        padding-bottom: 10px;
    }

    .footer-heading::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 3px;
        background: #FF5252;
        border-radius: 2px;
    }

    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .footer-links li {
        margin: 0;
    }

    .footer-links a {
        color: #666;
        text-decoration: none;
        font-size: 14px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        position: relative;
        padding-left: 0;
    }

    .footer-links a::before {
        content: '';
        position: absolute;
        left: 0;
        bottom: -2px;
        width: 0;
        height: 2px;
        background: #FF5252;
        transition: width 0.3s ease;
    }

    .footer-links a:hover {
        color: #FF5252;
        padding-left: 8px;
    }

    .footer-links a:hover::before {
        width: 20px;
    }

    .footer-description {
        font-size: 14px;
        line-height: 1.6;
        color: #666;
        margin-bottom: 20px;
    }

    .app-buttons {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .app-btn {
        display: inline-block;
        width: 140px;
        transition: transform 0.3s ease;
    }

    .app-btn:hover {
        transform: translateY(-2px);
    }

    .app-btn img {
        width: 100%;
        height: auto;
        display: block;
        border-radius: 6px;
    }

    .footer-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(255, 82, 82, 0.2), transparent);
        margin: 30px 0 25px;
    }

    .footer-bottom {
        text-align: center;
        padding-top: 20px;
    }

    .copyright-text {
        font-size: 13px;
        color: #888;
        margin: 0;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .footer-content {
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 40px;
        }

        .footer-app {
            grid-column: 1 / -1;
            text-align: center;
        }

        .app-buttons {
            flex-direction: row;
            justify-content: center;
        }
    }

    @media (max-width: 768px) {
        .footer {
            padding: 50px 0 25px;
        }

        .footer-content {
            grid-template-columns: 1fr;
            gap: 35px;
            text-align: center;
        }

        .footer-brand {
            max-width: 100%;
            align-items: center;
        }

        .footer-logo {
            margin: 0 auto 20px;
        }

        .footer-social {
            justify-content: center;
        }

        .footer-heading::after {
        left: 50%;
        transform: translateX(-50%);
        }

        .footer-links {
            align-items: center;
        }

        .footer-links a:hover {
            padding-left: 0;
        }

        .app-buttons {
            align-items: center;
        }
    }

    @media (max-width: 480px) {
        .footer {
            padding: 40px 0 20px;
        }

        .footer-content {
            gap: 30px;
        }

        .footer-logo img {
            height: 40px;
        }

        .footer-heading {
            font-size: 16px;
        }

        .app-buttons {
            flex-direction: column;
            align-items: center;
        }

        .app-btn {
            width: 130px;
        }
    }
</style>

</body>

</html>