<?php
/**
 * Email Verification Page
 * 
 * Displays verification instructions and allows users to resend
 * verification emails if they didn't receive the original.
 */

// ============================================
// SESSION & CONFIGURATION
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Calculate base paths
$webRoot = realpath(__DIR__ . '/../../');
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
$prefix = rtrim(str_replace($docRoot, '', str_replace('\\', '/', $webRoot)), '/') . '/';

// ============================================
// PAGE VARIABLES
// ============================================
$pageTitle = 'Email Verification';
$controllerBasePath = $prefix . 'controller/';

// Get email from session (either from registration or login attempt)
$registeredEmail = '';
if (isset($_SESSION['registered_email'])) {
    $registeredEmail = $_SESSION['registered_email'];
    unset($_SESSION['registered_email']);
} elseif (isset($_SESSION['unverified_email'])) {
    $registeredEmail = $_SESSION['unverified_email'];
    unset($_SESSION['unverified_email']);
}

// Get session messages
$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;

// Clear messages from session after retrieving
if ($successMessage) {
    unset($_SESSION['success_message']);
}
if ($errorMessage) {
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - NGEAR</title>
    
    <!-- External Resources -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?php echo $prefix; ?>css/EmailVerification.css">
</head>
<body>
    <!-- ============================================
         NAVIGATION
         ============================================ -->
    <?php include __DIR__ . '/../../general/_navbar.php'; ?>
    
    <!-- ============================================
         MAIN CONTENT
         ============================================ -->
    <div class="verification-wrapper">
        <div class="verification-container">
            
            <!-- Verification Icon -->
            <div class="verification-icon">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            
            <!-- Success Message -->
            <?php if ($successMessage): ?>
                <div class="message-box success-popup">
                    <i class="fas fa-check-circle"></i> 
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>
            
            <!-- Error Message -->
            <?php if ($errorMessage): ?>
                <div class="message-box error-messages">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            
            <!-- Verification Instructions -->
            <div class="verification-message">
                <h2>Verify Your Email</h2>
                <p>We've sent a verification link to your email address. Please check your inbox and click the link to verify your account.</p>
                <p>The verification link will expire in 24 hours.</p>
                
                <?php if ($registeredEmail): ?>
                    <div class="email-display">
                        <strong>Email sent to:</strong> 
                        <?php echo htmlspecialchars($registeredEmail); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Helpful Information -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Didn't receive the email?</strong> 
                Check your spam folder or request a new verification email below.
            </div>
            
            <!-- Resend Verification Form -->
            <div class="resend-section">
                <h3>Resend Verification Email</h3>
                <form method="POST" action="<?php echo htmlspecialchars($controllerBasePath); ?>MemberController.php" class="resend-form">
                    <input type="hidden" name="action" value="resend_verification">
                    <input 
                        type="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="Enter your email address" 
                        value="<?php echo htmlspecialchars($registeredEmail); ?>" 
                        required
                        autocomplete="email"
                    >
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> 
                        Resend Verification Email
                    </button>
                </form>
            </div>
            
            <!-- Back to Login Link -->
            <div class="back-link">
                <a href="<?php echo htmlspecialchars($prefix); ?>views/security/login.php">
                    <i class="fas fa-arrow-left"></i> 
                    Back to Login
                </a>
            </div>
            
        </div>
    </div>
    
    <!-- ============================================
         FOOTER
         ============================================ -->
    <?php include __DIR__ . '/../../general/_footer.php'; ?>
</body>
</html>
