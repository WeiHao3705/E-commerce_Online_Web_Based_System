<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if no 2FA setup session
if (empty($_SESSION['2fa_setup'])) {
    $_SESSION['error_message'] = 'Please log in first.';
    header('Location: login.php');
    exit;
}

// Robust base path to the web folder, regardless of include origin
$webRoot = realpath(__DIR__ . '/../../');
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
$prefix = rtrim(str_replace($docRoot, '', str_replace('\\', '/', $webRoot)), '/') . '/';

$pageTitle = 'Setup Two-Factor Authentication';
$setupData = $_SESSION['2fa_setup'];
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
        <div class="registration-container login-container" style="max-width: 600px;">
            <div class="form-header">
                <h2>Setup Two-Factor Authentication</h2>
            </div>

            <div style="background: rgba(255,255,255,0.05); padding: 20px; border-radius: 12px; margin: 30px 0;">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="background: white; padding: 20px; border-radius: 12px; display: inline-block; min-height: 250px; min-width: 250px; display: flex; align-items: center; justify-content: center;">
                        <?php 
                        $qrCodeData = $setupData['qr_code'] ?? '';
                        if (!empty($qrCodeData) && strpos($qrCodeData, 'data:image') === 0): 
                        ?>
                            <img src="<?php echo htmlspecialchars($qrCodeData); ?>" alt="QR Code" style="max-width: 250px; height: auto; display: block;">
                        <?php else: ?>
                            <div style="text-align: center; color: #666; padding: 20px;">
                                <p style="margin: 0 0 10px 0; font-size: 14px;">QR Code unavailable</p>
                                <p style="margin: 0; font-size: 12px;">Please use manual entry below</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="color: rgba(255,255,255,0.8); font-size: 14px; line-height: 1.8;">
                    <p style="margin: 10px 0;">
                        <strong>Account Name:</strong> 
                        <code style="background: rgba(0,0,0,0.3); padding: 6px 10px; border-radius: 6px; font-size: 13px; margin-left: 8px; font-family: monospace;">
                            <?php echo htmlspecialchars($setupData['username']); ?>
                        </code>
                    </p>
                    <p style="margin: 10px 0;">
                        <strong>Secret Key:</strong> 
                        <code style="background: rgba(0,0,0,0.5); padding: 6px 10px; border-radius: 6px; font-size: 13px; letter-spacing: 1px; margin-left: 8px; font-family: 'Courier New', monospace; color: #fff; border: 1px solid rgba(255,255,255,0.2); word-break: break-all;">
                            <?php 
                            $secretKey = $setupData['secret_key'] ?? '';
                            if (empty($secretKey)) {
                                echo '<span style="color: #ff9800;">Secret key not available. Please refresh the page.</span>';
                            } else {
                                echo htmlspecialchars($secretKey);
                            }
                            ?>
                        </code>
                    </p>
                </div>
            </div>

            <form id="twoFactorSetupForm" action="<?php echo $prefix; ?>controller/MemberController.php" method="POST">
                <input type="hidden" name="action" value="setup_2fa">

                <div class="form-group">
                    <label for="code">Enter Verification Code</label>
                    <?php if (!empty($error_message)): ?>
                        <div class="field-error"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <div class="input-wrapper">
                        <span class="material-symbols-outlined input-icon">lock</span>
                        <input type="text" id="code" name="code" class="form-control" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus>
                    </div>
                    <small style="color: rgba(255,255,255,0.6); margin-top: 8px; display: block;">Enter the 6-digit code from your authenticator app to complete setup</small>
                </div>

                <button type="submit" class="submit-btn">Verify & Complete Setup</button>

                <div class="form-footer">
                    <p><a href="<?php echo $prefix; ?>controller/MemberController.php?action=cancel_2fa_setup">Cancel and return to login</a></p>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../../general/_footer.php'; ?>

    <script src="<?php echo $prefix; ?>js/twoFactor.js"></script>

</body>

</html>

