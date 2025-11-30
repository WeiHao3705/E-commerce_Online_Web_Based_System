<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path
$base_path = '/E-commerce_Online_Web_Based_System/web/';

$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_views = (strpos($current_dir, '/views') !== false);
$prefix = $is_in_views ? '../' : '';

$pageTitle = 'Member Login';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - REDSTORE' : 'REDSTORE - Sports & Fitness Store'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $prefix; ?>css/MemberRegister.css">
    <link rel="stylesheet" href="<?php echo $prefix; ?>css/Login.css">
</head>

<body style="background: url('<?php echo $prefix; ?>images/Login_Signup/login.jpg') center top / cover no-repeat;">

    <?php include __DIR__ . '/../general/_navbar.php'; ?>

    <?php
    $login_error = '';
    if (isset($_SESSION['success_message'])) {
        echo '<div class="success-popup">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        // capture the login error and render it near the username field below
        $login_error = htmlspecialchars($_SESSION['error_message']);
        unset($_SESSION['error_message']);
    }
    ?>

    <div class="registration-wrapper">
        <div class="registration-container" style="max-width:500px;padding:40px;">
            <div class="form-header">
                <h2>Log In</h2>
                <p>Welcome back — please enter your details.</p>
            </div>

            <form id="loginForm" action="<?php echo $prefix; ?>controller/MemberController.php" method="POST">
                <input type="hidden" name="action" value="login">

                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <span class="material-symbols-outlined input-icon">person</span>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Username" required>
                    </div>
                    <?php if (!empty($login_error)): ?>
                        <div class="field-error"><?php echo $login_error; ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <span class="material-symbols-outlined input-icon">lock</span>
                        <input type="password" id="password" name="password" class="form-control with-toggle" placeholder="Password" required>
                        <i class="fa fa-eye toggle-password" id="togglePassword" aria-hidden="true" title="Show password"></i>
                    </div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                    <div></div>
                    <div class="forgot-link"><a href="<?php echo $prefix; ?>views/forgot_password.php?start=1">Forgot your password?</a></div>
                </div>

                <button type="submit" class="submit-btn">Log In</button>

                <div class="form-footer">
                    <p>Don't have an account? <a href="<?php echo $prefix; ?>views/member_management/MemberRegisterForm.php">Sign up</a></p>
                </div>
            </form>
        </div>
    </div>

    <?php include __DIR__ . '/../general/_footer.php'; ?>

    <script>
        (function(){
            document.addEventListener('DOMContentLoaded', function(){
                var toggle = document.getElementById('togglePassword');
                var input = document.getElementById('password');
                if (!toggle || !input) return;

                toggle.addEventListener('click', function(){
                    var type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    // toggle icon class
                    if(type === 'text'){
                        toggle.classList.remove('fa-eye');
                        toggle.classList.add('fa-eye-slash');
                        toggle.setAttribute('title','Hide password');
                    } else {
                        toggle.classList.remove('fa-eye-slash');
                        toggle.classList.add('fa-eye');
                        toggle.setAttribute('title','Show password');
                    }
                });
            });
        })();
    </script>

</body>

</html>
