<?php
/**
 * Email Verification Endpoint
 * 
 * Secure endpoint for email verification that doesn't expose
 * internal controller structure or file paths.
 * 
 * Security features:
 * - Clean URL structure (verify-email.php?t=token)
 * - Token format validation
 * - Rate limiting protection
 * - No exposure of internal file structure
 */

session_start();

// Include required files
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/repository/MemberRepository.php';
require_once __DIR__ . '/service/MemberService.php';

// Get token from URL (using short parameter name 't' instead of 'token')
$token = isset($_GET['t']) ? trim($_GET['t']) : '';

// Validate token format (should be exactly 64 hex characters)
if (empty($token)) {
    $_SESSION['error_message'] = 'Invalid verification link. Please check your email for the correct verification link.';
    header('Location: views/security/email_verification.php');
    exit;
}

// Validate token format - must be 64 character hexadecimal string
if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    $_SESSION['error_message'] = 'Invalid verification link format. Please use the link provided in your email.';
    header('Location: views/security/email_verification.php');
    exit;
}

// Basic rate limiting: Check if too many attempts from this IP
$rateLimitKey = 'verify_email_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$attempts = isset($_SESSION[$rateLimitKey]) ? $_SESSION[$rateLimitKey] : 0;
$lastAttempt = isset($_SESSION[$rateLimitKey . '_time']) ? $_SESSION[$rateLimitKey . '_time'] : 0;

// Reset attempts after 1 hour
if (time() - $lastAttempt > 3600) {
    $attempts = 0;
}

// Allow max 10 attempts per hour per IP
if ($attempts >= 10) {
    $_SESSION['error_message'] = 'Too many verification attempts. Please wait before trying again.';
    header('Location: views/security/email_verification.php');
    exit;
}

// Increment attempt counter
$_SESSION[$rateLimitKey] = $attempts + 1;
$_SESSION[$rateLimitKey . '_time'] = time();

try {
    // Initialize services
    $database = new Database();
    $memberRepository = new MemberRepository($database);
    $memberService = new MemberService($memberRepository);
    
    // Verify email
    $result = $memberService->verifyEmail($token);
    
    // Reset rate limit on successful verification
    if ($result['success']) {
        unset($_SESSION[$rateLimitKey]);
        unset($_SESSION[$rateLimitKey . '_time']);
        $_SESSION['success_message'] = 'Email verified successfully! You can now log in.';
        header('Location: views/security/login.php');
    } else {
        $_SESSION['error_message'] = $result['message'];
        header('Location: views/security/email_verification.php');
    }
    exit;
} catch (Exception $e) {
    error_log("Email verification error: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred during verification. Please try again or request a new verification link.';
    header('Location: views/security/email_verification.php');
    exit;
}

