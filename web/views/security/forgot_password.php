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

// Robust base path to the web folder, regardless of include origin
$webRoot = realpath(__DIR__ . '/../../');
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : '';
$prefix = rtrim(str_replace('\\', '/', str_replace($docRoot, '', $webRoot)), '/') . '/';

$pageTitle = 'Forgot Password';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - NGEAR' : 'NGEAR - Sports & Fitness Store'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $prefix; ?>css/MemberRegister.css">
    <link rel="stylesheet" href="<?php echo $prefix; ?>css/Login.css">
    <link rel="icon" type="image/png" href="/web/images/logo/logo1.png">
    <style>
        .navbar .search-input-group { position:relative; overflow:hidden; border-radius:25px; }
        .navbar .search-input { padding-right:48px; }
        .navbar .search-btn { position:absolute; top:0; right:0; height:100%; width:48px; margin:0 !important; border-radius:0 !important; display:flex; align-items:center; justify-content:center; padding:0 !important; }
        .navbar .search-btn i { color:#fff; font-size:16px; line-height:1; display:block; }
        .back-link:hover { opacity: 1 !important; }
        .input-wrapper { position: relative; }
        .input-wrapper .input-icon { position: absolute; left:12px; top:50%; transform:translateY(-50%); color:#999; font-size:14px; }
        .input-wrapper .form-control { padding-left:36px; }
        .input-wrapper .input-right-icon { position:absolute; right:12px; top:50%; transform:translateY(-50%); color:#999; font-size:14px; cursor:pointer; }
        .hash-preview { margin-top:8px; }
        .hash-preview pre { margin:0; font-family:monospace; color:#cfcfcf; background:transparent; white-space:pre-wrap; word-break:break-all; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../../general/_navbar.php'; ?>

<div class="registration-wrapper">
    <div class="registration-container" style="max-width:560px;padding:36px;">
        <div style="margin-bottom:16px;">
            <?php if (!empty($_SESSION['reset_user'])): ?>
                <a href="?start=1" class="back-link" style="display:inline-flex;align-items:center;gap:8px;color:#fff;text-decoration:none;font-size:14px;opacity:0.8;transition:opacity 0.2s;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            <?php else: ?>
                <a href="<?php echo $prefix; ?>views/security/login.php" class="back-link" style="display:inline-flex;align-items:center;gap:8px;color:#fff;text-decoration:none;font-size:14px;opacity:0.8;transition:opacity 0.2s;">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            <?php endif; ?>
        </div>
        <h2>Reset your password</h2>

        <?php if (empty($_SESSION['reset_user'])): ?>
            <p>Step 1 — enter your username and we'll send a one-time password (OTP) to your email.</p>
        <?php endif; ?>

        <?php
            $alertMsg = '';
            if (!empty($_SESSION['fp_message'])) {
                $alertMsg = htmlspecialchars($_SESSION['fp_message']);
                unset($_SESSION['fp_message']);
            } else {
                if (!empty($_SESSION['reset_user']) && empty($_SESSION['reset_verified'])) {
                    $alertMsg = 'An OTP has been sent to your registered email address.';
                } elseif (!empty($_SESSION['reset_user']) && !empty($_SESSION['reset_verified'])) {
                    $alertMsg = 'OTP verified. You may now set a new password.';
                }
            }
        ?>

        <?php if ($alertMsg !== ''): ?>
            <div class="login-alert no-bg" style="margin-bottom:12px;color:#e74c3c;">
                <div class="login-alert-message"><?php echo $alertMsg; ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['reset_user'])):
            if (!empty($_SESSION['reset_verified'])):
        ?>
            <form action="<?php echo $prefix; ?>controller/MemberController.php" method="POST">
                <input type="hidden" name="action" value="complete_reset">
                <div class="form-group">
                    <label for="new_password">New password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon" aria-hidden="true"></i>
                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="New password" required>
                        <i class="fas fa-eye input-right-icon" data-target="new_password" title="Show / hide password" aria-hidden="true"></i>
                    </div>
                    <div id="passwordStrength" class="password-strength" style="margin-top:8px;"></div>
                    <div class="password-requirements" style="font-size:13px;color:#bbb;margin-top:6px;">
                        <strong>Password Requirements:</strong>
                        <ul style="margin:6px 0 0 18px;padding:0;">
                            <li id="req-length">At least 8 characters</li>
                            <li id="req-uppercase">At least one uppercase letter</li>
                            <li id="req-lowercase">At least one lowercase letter</li>
                            <li id="req-number">At least one number</li>
                            <li id="req-special">At least one special character (!@#$%^&*)</li>
                        </ul>
                    </div>
                    <div class="hash-preview" style="display:none;" id="new_password_hash_container">
                        <label style="font-size:12px;color:#bbb;">SHA-256 (hex)</label>
                        <pre id="new_password_hash"></pre>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password_confirm">Confirm new password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon" aria-hidden="true"></i>
                        <input type="password" id="new_password_confirm" name="new_password_confirm" class="form-control" placeholder="Confirm new password" required>
                        <i class="fas fa-eye input-right-icon" data-target="new_password_confirm" title="Show / hide password" aria-hidden="true"></i>
                    </div>
                    <div id="passwordMatchError" class="password-match-error" style="color:#e74c3c;display:none;margin-top:6px;">Passwords do not match!</div>
                    <div id="passwordMatchSuccess" class="password-match-success" style="color:#2ecc71;display:none;margin-top:6px;">Passwords match!</div>
                    <div style="margin-top:8px;font-size:13px;color:#bbb;display:flex;gap:12px;align-items:center;">
                        <label style="display:inline-flex;gap:6px;align-items:center;"><input type="checkbox" id="show_hash_checkbox"> <span style="font-size:13px;color:#bbb;">Show hashed value</span></label>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Change password</button>
            </form>
        <?php else: ?>
            <form action="<?php echo $prefix; ?>controller/MemberController.php" method="POST">
                <input type="hidden" name="action" value="verify_reset">
                <div class="form-group">
                    <label for="otp_code">Enter OTP</label>
                    <div class="input-wrapper">
                        <i class="fas fa-key input-icon" aria-hidden="true"></i>
                        <input type="text" id="otp_code" name="otp_code" class="form-control" placeholder="6-digit OTP" maxlength="6" required autocomplete="one-time-code">
                    </div>
                </div>
                <button type="submit" class="submit-btn">Verify OTP</button>
            </form>
        <?php endif; ?>
        <?php else: ?>
            <form action="<?php echo $prefix; ?>controller/MemberController.php" method="POST">
                <input type="hidden" name="action" value="send_reset">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon" aria-hidden="true"></i>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Your username" required>
                    </div>
                </div>
                <button type="submit" class="submit-btn">Continue</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../general/_footer.php'; ?>

<script src="<?php echo $prefix; ?>js/forgotPassword.js"></script>

<script>
// Toggle password visibility for inputs with .input-right-icon
document.addEventListener('click', function(e){
    var t = e.target;
    if (t && t.classList && t.classList.contains('input-right-icon')){
        var targetId = t.getAttribute('data-target');
        var input = document.getElementById(targetId);
        if (!input) return;
        if (input.type === 'password') { input.type = 'text'; t.classList.remove('fa-eye'); t.classList.add('fa-eye-slash'); }
        else { input.type = 'password'; t.classList.remove('fa-eye-slash'); t.classList.add('fa-eye'); }
    }
});

// Compute SHA-256 hex using SubtleCrypto
async function sha256Hex(text){
    const enc = new TextEncoder();
    const data = enc.encode(text);
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2,'0')).join('');
}

// Show hashed preview when checkbox is checked
document.addEventListener('DOMContentLoaded', function(){
    var checkbox = document.getElementById('show_hash_checkbox');
    var container = document.getElementById('new_password_hash_container');
    var outPre = document.getElementById('new_password_hash');
    var pwdInput = document.getElementById('new_password');

    if (!checkbox || !container || !outPre || !pwdInput) return;

    async function updateHash(){
        var val = pwdInput.value || '';
        if (val === '') { outPre.textContent = '' ; return; }
        try { outPre.textContent = await sha256Hex(val); } catch(e){ outPre.textContent = 'n/a'; }
    }

    checkbox.addEventListener('change', function(){
        if (checkbox.checked){ container.style.display = 'block'; updateHash(); }
        else { container.style.display = 'none'; outPre.textContent = ''; }
    });

    // Update hash as user types
    pwdInput.addEventListener('input', function(){ if (checkbox.checked) updateHash(); });
});

// Password requirement and match validation (replicates registration rules)
(function(){
    var pwd = document.getElementById('new_password');
    var pwdConfirm = document.getElementById('new_password_confirm');
    var reqs = {
        length: document.getElementById('req-length'),
        uppercase: document.getElementById('req-uppercase'),
        lowercase: document.getElementById('req-lowercase'),
        number: document.getElementById('req-number'),
        special: document.getElementById('req-special')
    };
    var strengthEl = document.getElementById('passwordStrength');
    var matchErr = document.getElementById('passwordMatchError');
    var matchOk = document.getElementById('passwordMatchSuccess');
    var resetForm = document.querySelector('form[action*="MemberController.php"][method="POST"]');

    if (!pwd) return;

    function evaluatePassword(password){
        return {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };
    }

    function updateRequirements(password){
        var r = evaluatePassword(password);
        Object.keys(r).forEach(function(k){
            var el = reqs[k];
            if (!el) return;
            el.style.color = r[k] ? '#2ecc71' : '#bbb';
            el.style.textDecoration = r[k] ? 'line-through' : 'none';
        });
        var score = Object.values(r).filter(Boolean).length;
        if (score === 0) { strengthEl.textContent = ''; strengthEl.className = 'password-strength'; }
        else if (score <= 2) { strengthEl.textContent = 'Weak Password'; strengthEl.className = 'password-strength strength-weak'; }
        else if (score <= 4) { strengthEl.textContent = 'Medium Password'; strengthEl.className = 'password-strength strength-medium'; }
        else { strengthEl.textContent = 'Strong Password'; strengthEl.className = 'password-strength strength-strong'; }
    }

    function checkMatch(){
        if (!pwdConfirm) return true;
        var a = pwd.value || '';
        var b = pwdConfirm.value || '';
        if (b === '') { matchErr.style.display='none'; matchOk.style.display='none'; return true; }
        if (a === b) { matchErr.style.display='none'; matchOk.style.display='block'; return true; }
        else { matchErr.style.display='block'; matchOk.style.display='none'; return false; }
    }

    pwd.addEventListener('input', function(){ updateRequirements(pwd.value); if (pwdConfirm) checkMatch(); });
    if (pwdConfirm) pwdConfirm.addEventListener('input', checkMatch);

    // Prevent form submission if requirements not met
    if (resetForm){
        resetForm.addEventListener('submit', function(e){
            var r = evaluatePassword(pwd.value || '');
            var ok = r.length && r.uppercase && r.lowercase && r.number && r.special && checkMatch();
            if (!ok){
                e.preventDefault();
                // show a brief visual cue
                strengthEl.style.color = '#e74c3c';
                setTimeout(function(){ strengthEl.style.color = ''; }, 2000);
                if (!checkMatch()) { matchErr.scrollIntoView({behavior:'smooth', block:'center'}); }
                else { strengthEl.scrollIntoView({behavior:'smooth', block:'center'}); }
            }
        });
    }
})();
</script>

</body>
</html>
