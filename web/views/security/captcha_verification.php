<?php
/**
 * CAPTCHA Verification for First-Time Website Visitors
 * Uses a simple PHP-based CAPTCHA system with session management
 */

/**
 * Generate a random CAPTCHA code
 */
function generateCaptchaCode($length = 6) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Excluding similar looking characters
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

session_start();

// Generate device fingerprint for tracking
$device_fingerprint = md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);

// Check if device has already verified CAPTCHA (via cookie)
if (isset($_COOKIE['captcha_verified']) && $_COOKIE['captcha_verified'] === $device_fingerprint) {
    // Device already verified within the last week, redirect to home
    $_SESSION['captcha_verified'] = true; // Also set session for consistency
    header('Location: ../../index.php');
    exit;
}

// Check if user has already verified CAPTCHA in this session
if (isset($_SESSION['captcha_verified']) && $_SESSION['captcha_verified'] === true) {
    // User already verified, redirect to home
    header('Location: ../../index.php');
    exit;
}

// Handle refresh CAPTCHA request (do this before checking verification)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh_captcha'])) {
    $_SESSION['captcha_code'] = generateCaptchaCode();
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Generate CAPTCHA code if not exists
if (!isset($_SESSION['captcha_code'])) {
    $_SESSION['captcha_code'] = generateCaptchaCode();
}

// Handle CAPTCHA verification
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_captcha'])) {
    $user_input = $_POST['captcha_input'] ?? '';
    
    if (empty($user_input)) {
        $error_message = 'Please enter the CAPTCHA code.';
    } elseif (strtolower($user_input) !== strtolower($_SESSION['captcha_code'])) {
        $error_message = 'Incorrect CAPTCHA code. Please try again.';
        // Generate new CAPTCHA on failure
        $_SESSION['captcha_code'] = generateCaptchaCode();
    } else {
        // CAPTCHA verified successfully
        $_SESSION['captcha_verified'] = true;
        
        // Set cookie valid for 7 days (1 week)
        $cookie_expiry = time() + (7 * 24 * 60 * 60); // 7 days
        setcookie('captcha_verified', $device_fingerprint, $cookie_expiry, '/');
        
        unset($_SESSION['captcha_code']);
        header('Location: ../../index.php');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Verification</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/captcha.css">
</head>
<body>
    <div class="captcha-container">
        <div class="security-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        
        <h1>Security Verification</h1>
        <p class="subtitle">Please verify that you're a human to continue</p>

        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Refresh Form (separate from main form) -->
        <form method="POST" action="" id="refreshForm">
            <div class="captcha-display">
                <button type="submit" name="refresh_captcha" class="refresh-btn" title="Refresh CAPTCHA">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <div class="captcha-code"><?php echo htmlspecialchars($_SESSION['captcha_code'] ?? 'ERROR'); ?></div>
            </div>
        </form>

        <!-- Verification Form -->
        <form method="POST" action="" id="verifyForm">
            <div class="form-group">
                <input 
                    type="text" 
                    name="captcha_input" 
                    placeholder="Enter code above"
                    maxlength="6"
                    autocomplete="off"
                    required
                    autofocus
                >
            </div>

            <button type="submit" name="verify_captcha" class="verify-btn">
                <i class="fas fa-check-circle"></i> Verify & Continue
            </button>
        </form>

        <div class="info-text">
            <i class="fas fa-info-circle"></i>
            This verification helps protect our website from automated access.
        </div>
    </div>

    <script src="../../js/captcha.js"></script>
</body>
</html>