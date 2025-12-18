<?php 
session_start();

// Check for Remember Me auto-login
if (empty($_SESSION['user']) && isset($_COOKIE['remember_token'])) {
    require_once __DIR__ . '/web/controller/MemberController.php';
    
    $memberController = new MemberController();
    $memberController->checkRememberMe();
}

// Generate device fingerprint for tracking
$device_fingerprint = md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);

// CAPTCHA verification for first-time visitors
// Check if device has verified CAPTCHA (via cookie - valid for 1 week)
$captcha_cookie_valid = isset($_COOKIE['captcha_verified']) && $_COOKIE['captcha_verified'] === $device_fingerprint;
$captcha_session_valid = isset($_SESSION['captcha_verified']) && $_SESSION['captcha_verified'] === true;

if (!$captcha_cookie_valid && !$captcha_session_valid) {
    // Allow admins and logged-in users to bypass CAPTCHA
    if (empty($_SESSION['user'])) {
        // First-time visitor needs to verify CAPTCHA
        header('Location: web/views/security/captcha_verification.php');
        exit;
    } else {
        // Logged-in users automatically have CAPTCHA verified
        $_SESSION['captcha_verified'] = true;
        
        // Also set cookie for 7 days
        $cookie_expiry = time() + (7 * 24 * 60 * 60);
        setcookie('captcha_verified', $device_fingerprint, $cookie_expiry, '/');
    }
} else {
    // If cookie is valid but session isn't, sync them
    if ($captcha_cookie_valid && !$captcha_session_valid) {
        $_SESSION['captcha_verified'] = true;
    }
}

// Redirect admins to AdminDashboard - they should not access member website
if (!empty($_SESSION['user']) && isset($_SESSION['user']->role) && $_SESSION['user']->role === 'admin') {
    header('Location: web/views/admin/AdminDashboard.php');
    exit;
}

require __DIR__ . '/web/database/connection.php';

$db = new Database();
$conn = $db->getConnection();

$page = $_GET['page'] ?? 'home'; // default page

$pageTitle = ucfirst($page);

include 'web/general/_header.php';
include 'web/general/_navbar.php';

// Display and clear success/error messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="success-popup" style="position: fixed; top: 80px; right: 20px; background: #4CAF50; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1000; max-width: 400px; animation: slideIn 0.3s ease;">';
    echo '<i class="fas fa-check-circle" style="margin-right: 8px;"></i>';
    echo htmlspecialchars($_SESSION['success_message']);
    echo '</div>';
    unset($_SESSION['success_message']);
    
    // Auto-hide after 3 seconds
    echo '<script>
        setTimeout(function() {
            var popup = document.querySelector(".success-popup");
            if (popup) {
                popup.style.animation = "slideOut 0.3s ease";
                setTimeout(function() { popup.remove(); }, 300);
            }
        }, 3000);
    </script>';
}

if (isset($_SESSION['error_message'])) {
    echo '<div class="error-popup" style="position: fixed; top: 80px; right: 20px; background: #f44336; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1000; max-width: 400px; animation: slideIn 0.3s ease;">';
    echo '<i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>';
    echo htmlspecialchars($_SESSION['error_message']);
    echo '</div>';
    unset($_SESSION['error_message']);
    
    // Auto-hide after 5 seconds
    echo '<script>
        setTimeout(function() {
            var popup = document.querySelector(".error-popup");
            if (popup) {
                popup.style.animation = "slideOut 0.3s ease";
                setTimeout(function() { popup.remove(); }, 300);
            }
        }, 5000);
    </script>';
}

// ROUTING
switch ($page) {

    case 'home':
    default:
        if (empty($_SESSION['user'])) {
            require __DIR__ . '/web/views/guest/GuestHome.php';
        } else {
            require __DIR__ . '/web/views/member/MemberHome.php';
        }
        break;
}

include 'web/general/_footer.php';
?>