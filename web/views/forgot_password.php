<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Prevent browser caching so Back/Forward will re-request the page from server
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// If the page is opened fresh (from login link with ?start=1), clear any previous reset state
if (isset($_GET['start']) && $_GET['start']) {
    unset($_SESSION['reset_user']);
    unset($_SESSION['reset_verified']);
}

// If a reset_user exists but is too old, expire it to avoid returning to later steps
if (!empty($_SESSION['reset_user']) && !empty($_SESSION['reset_user']['created_at'])) {
    $age = time() - (int)$_SESSION['reset_user']['created_at'];
    $expiry_seconds = 10 * 60; // 10 minutes expiry for reset flow
    if ($age > $expiry_seconds) {
        unset($_SESSION['reset_user']);
        unset($_SESSION['reset_verified']);
        $_SESSION['fp_message'] = 'Reset session expired. Please start again.';
    }
}

// Safety: if verified flag exists but no reset_user, clear it
if (!empty($_SESSION['reset_verified']) && empty($_SESSION['reset_user'])) {
    unset($_SESSION['reset_verified']);
}

$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_views = (strpos($current_dir, '/views') !== false);
$prefix = $is_in_views ? '../' : '';

$pageTitle = 'Forgot Password';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="<?php echo $prefix; ?>css/Login.css">
</head>
<body>
<?php include __DIR__ . '/../general/_navbar.php'; ?>
<style>
    /* Make navbar blend with the dark login/forgot background on this page */
    .navbar { background: transparent !important; box-shadow: none !important; }
    .navbar .nav-menu li a, .navbar .logo a, .navbar .nav-icons, .menu-toggle { color: #fff !important; }
    .navbar .logo img { filter: none; }
    .navbar .search-input { background: transparent; color: #fff !important }
    /* make the search pill match dark glass style */
    .navbar .search-input-group { background: rgba(255,255,255,0.04) !important; border: 1px solid rgba(255,255,255,0.08) !important; }
    .navbar .search-input::placeholder { color: rgba(255,255,255,0.7) !important; }
    .navbar .search-btn { background: var(--accent, #FF523B) !important; border: none !important; color: #fff !important; }
    .navbar .search-btn i { color: #fff !important; }
    /* tighten pill shape so the button doesn't look like a separate white block */
    .navbar .search-input-group, .navbar .search-btn { border-radius: 24px !important; }
    .navbar .search-btn { padding: 8px 12px !important; margin-left: 6px !important; }
</style>

<div class="registration-wrapper">
    <div class="registration-container" style="max-width:560px;padding:36px;">
        <h2>Reset your password</h2>
        <?php if (empty($_SESSION['reset_user'])): ?>
            <p>Step 1 — enter your username and we'll show your security question.</p>
        <?php elseif (empty($_SESSION['reset_verified'])): ?>
            <p>Step 2 — answer your security question to verify your identity.</p>
        <?php else: ?>
            <p>Step 3 — set a new password for your account.</p>
        <?php endif; ?>

        <?php if (isset($_SESSION['fp_message'])): ?>
            <div class="login-alert"><div class="login-alert-icon">i</div><div class="login-alert-message"><?php echo htmlspecialchars($_SESSION['fp_message']); unset($_SESSION['fp_message']); ?></div></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['reset_user'])):
            $reset = $_SESSION['reset_user'];
            if (!empty($_SESSION['reset_verified'])):
        ?>
            <form action="<?php echo $prefix; ?>controller/MemberController.php" method="POST">
                <input type="hidden" name="action" value="complete_reset">
                <div class="form-group">
                    <label for="new_password">New password</label>
                    <div class="input-wrapper">
                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="New password" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password_confirm">Confirm new password</label>
                    <div class="input-wrapper">
                        <input type="password" id="new_password_confirm" name="new_password_confirm" class="form-control" placeholder="Confirm new password" required>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Change password</button>
            </form>
        <?php else: ?>
            <form action="<?php echo $prefix; ?>controller/MemberController.php" method="POST">
                <input type="hidden" name="action" value="verify_reset">
                <div class="form-group">
                    <label>Security question</label>
                    <div class="input-wrapper">
                        <div class="form-control" style="background:transparent;border:none;padding-left:0;color:#fff;"><?php echo htmlspecialchars($reset['security_question']); ?></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="security_answer">Answer</label>
                    <div class="input-wrapper">
                        <input type="text" id="security_answer" name="security_answer" class="form-control" placeholder="Your answer" required>
                    </div>
                </div>
                <button type="submit" class="submit-btn">Verify answer</button>
            </form>
        <?php endif; ?>
        <?php else: ?>
            <form action="<?php echo $prefix; ?>controller/MemberController.php" method="POST">
                <input type="hidden" name="action" value="send_reset">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" class="form-control" placeholder="Your username" required>
                    </div>
                </div>
                <button type="submit" class="submit-btn">Continue</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../general/_footer.php'; ?>
</body>
</html>

<script>
// If the user navigates via browser Back/Forward to this page, force a fresh start
document.addEventListener('DOMContentLoaded', function(){
    window.addEventListener('pageshow', function(event){
        var navEntries = [];
        try {
            var entries = performance.getEntriesByType && performance.getEntriesByType('navigation');
            if (entries && entries.length) navEntries = entries;
        } catch(e){}

        var isBackForward = (event.persisted === true) || (navEntries.length && navEntries[0].type === 'back_forward');
        if (isBackForward) {
            // Redirect to the start of the flow so Back cannot show step 3
            var url = window.location.href.split('?')[0] + '?start=1';
            // Use replace so this redirect doesn't create an extra history entry
            window.location.replace(url);
        }
    });
});
</script>
