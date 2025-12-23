<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if no 2FA verification session
if (empty($_SESSION['2fa_verify'])) {
    $_SESSION['error_message'] = 'Please log in first.';
    header('Location: login.php');
    exit;
}

// Robust base path to the web folder, regardless of include origin
$webRoot = realpath(__DIR__ . '/../../');
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : '';
$prefix = rtrim(str_replace('\\', '/', str_replace($docRoot, '', $webRoot)), '/') . '/';

$pageTitle = 'Two-Factor Authentication';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - NGEAR' : 'NGEAR - Sports & Fitness Store'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $prefix; ?>css/MemberRegister.css">
    <link rel="stylesheet" href="<?php echo $prefix; ?>css/Login.css">
    <link rel="icon" type="image/png" href="/web/images/logo/logo1.png">
</head>

<body class="login-page-body">

    <?php include __DIR__ . '/../../general/_navbar.php'; ?>

    <?php
    $error_message = '';
    if (isset($_SESSION['error_message'])) {
        $error_message = htmlspecialchars($_SESSION['error_message']);
        unset($_SESSION['error_message']);
    }
    ?>

    <div class="registration-wrapper">
        <div class="registration-container login-container">
            <div class="form-header">
                <h2>Two-Factor Authentication</h2>
                <p>Enter the 6-digit code from your authenticator app</p>
            </div>

            <form id="twoFactorForm" action="<?php echo $prefix; ?>controller/MemberController.php" method="POST">
                <input type="hidden" name="action" value="verify_2fa">

                <div class="form-group">
                    <label for="code">Verification Code</label>
                    <?php if (!empty($error_message)): ?>
                        <div class="field-error"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <div class="input-wrapper">
                        <span class="material-symbols-outlined input-icon">lock</span>
                        <input type="text" id="code" name="code" class="form-control" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus>
                    </div>
                    <small style="color: rgba(255,255,255,0.6); margin-top: 8px; display: block;">Enter the 6-digit code from Google Authenticator</small>
                </div>

                <button type="submit" class="submit-btn">Verify</button>

                <div class="form-footer">
                    <p><a href="<?php echo $prefix; ?>views/security/login.php">Back to login</a></p>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../../general/_footer.php'; ?>

    <script src="<?php echo $prefix; ?>js/twoFactor.js"></script>

</body>

</html>

