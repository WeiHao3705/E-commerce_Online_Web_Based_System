<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path
$base_path = '/E-commerce_Online_Web_Based_System/web/';

$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_views = (strpos($current_dir, '/views') !== false);
$prefix = $is_in_views ? '../' : '';

$pageTitle = 'Member Registration';

// Initialize errors array
$errors = [];

// Check for validation errors from controller
if (isset($_SESSION['validation_errors'])) {
    $errors = array_merge($errors, $_SESSION['validation_errors']);
    unset($_SESSION['validation_errors']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - REDSTORE' : 'REDSTORE - Sports & Fitness Store'; ?></title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

    <link rel="stylesheet" href="<?php echo $prefix; ?>css/MemberRegister.css">

</head>

<body>

    <!-- Include Navbar -->
    <?php include $prefix . 'general/_navbar.php'; ?>

    <?php
    if (isset($_SESSION['success_message'])) {
        echo '<div class="success-popup">' . $_SESSION['success_message'] . '</div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="error-messages">' . $_SESSION['error_message'] . '</div>';
        unset($_SESSION['error_message']);
    }
    ?>

    <!-- Registration Form -->
    <div class="registration-wrapper">
        <div class="registration-container">
            <div class="form-header">
                <div class="logo-container">
                    <svg fill="none" viewBox="0 0 162 42" xmlns="http://www.w3.org/2000/svg">
                        <text fill="#FF523B" font-family="Poppins, sans-serif" font-size="28" font-weight="bold" letter-spacing="0em" style="white-space: pre" xml:space="preserve">
                            <tspan x="0" y="29.9219">REDSTORE</tspan>
                        </text>
                        <text fill="#555" font-family="Poppins, sans-serif" font-size="8" font-style="italic" letter-spacing="0.05em" style="white-space: pre" xml:space="preserve">
                            <tspan x="100" y="38">athlete's choice</tspan>
                        </text>
                        <rect height="42" rx="4" stroke="#FF523B" stroke-width="2" width="95" x="0" y="0"></rect>
                    </svg>
                </div>
                <h2>Create Your Account</h2>
                <p>Join us to get the best sports equipment!</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form id="registrationForm" action="<?php echo $prefix; ?>controller/MemberController.php" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">person</span>
                            <input type="text" id="username" name="username" class="form-control" placeholder="Username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="full-name">Full Name</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">badge</span>
                            <input type="text" id="full-name" name="full_name" class="form-control" placeholder="Full Name" value="<?php echo isset($_POST['full-name']) ? htmlspecialchars($_POST['full-name']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">lock</span>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Password" autocomplete="new-password" required>
                        </div>
                        <div id="passwordStrength" class="password-strength"></div>
                        <div class="password-requirements">
                            <strong>Password Requirements:</strong>
                            <ul>
                                <li id="req-length">At least 8 characters</li>
                                <li id="req-uppercase">At least one uppercase letter</li>
                                <li id="req-lowercase">At least one lowercase letter</li>
                                <li id="req-number">At least one number</li>
                                <li id="req-special">At least one special character (!@#$%^&*)</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="repeat-password">Repeat Password</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">password</span>
                            <input type="password" id="repeat-password" name="repeat_password" class="form-control" placeholder="Repeat Password" autocomplete="new-password" required>
                        </div>
                        <div id="passwordMatchError" class="password-match-error">Passwords do not match!</div>
                        <div id="passwordMatchSuccess" class="password-match-success">Passwords match!</div>
                    </div>

                    <div class="form-group">
                        <label for="email-address">Email Address</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">email</span>
                            <input type="email" id="email-address" name="email" class="form-control" placeholder="Email address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" autocomplete="email" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="contact-number">Contact Number</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">phone</span>
                            <input type="tel" id="contact-number" name="contact_no" class="form-control" placeholder="Contact Number" value="<?php echo isset($_POST['contact-number']) ? htmlspecialchars($_POST['contact-number']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="gender">Gender</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">wc</span>
                            <select id="gender" name="gender" class="form-control" required>
                                <option disabled selected>Select Gender</option>
                                <option <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Prefer not to say') ? 'selected' : ''; ?>>Prefer not to say</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="security-question">Security Question</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">quiz</span>
                            <select id="security-question" name="security_question" class="form-control" required>
                                <option disabled selected>Select a Security Question</option>
                                <option <?php echo (isset($_POST['security-question']) && $_POST['security-question'] == "What was your first pet's name?") ? 'selected' : ''; ?>>What was your first pet's name?</option>
                                <option <?php echo (isset($_POST['security-question']) && $_POST['security-question'] == 'What city were you born in?') ? 'selected' : ''; ?>>What city were you born in?</option>
                                <option <?php echo (isset($_POST['security-question']) && $_POST['security-question'] == "What is your mother's maiden name?") ? 'selected' : ''; ?>>What is your mother's maiden name?</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label for="security-answer">Security Answer</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined input-icon">key</span>
                            <input type="text" id="security-answer" name="security_answer" class="form-control" placeholder="Security Answer" value="<?php echo isset($_POST['security-answer']) ? htmlspecialchars($_POST['security-answer']) : ''; ?>" required>
                        </div>
                    </div>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        I agree to the <a href="#">Terms and Conditions</a>
                    </label>
                </div>

                <button type="submit" class="submit-btn">Sign Up</button>

                <div class="form-footer">
                    <p>Already have an account? <a href="<?php echo $prefix; ?>account.php">Sign in</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include $prefix . 'general/_footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Password strength checker
            function checkPasswordStrength(password) {
                let strength = 0;
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
                };

                // Update requirement indicators
                $('#req-length').toggleClass('valid', requirements.length).toggleClass('invalid', !requirements.length);
                $('#req-uppercase').toggleClass('valid', requirements.uppercase).toggleClass('invalid', !requirements.uppercase);
                $('#req-lowercase').toggleClass('valid', requirements.lowercase).toggleClass('invalid', !requirements.lowercase);
                $('#req-number').toggleClass('valid', requirements.number).toggleClass('invalid', !requirements.number);
                $('#req-special').toggleClass('valid', requirements.special).toggleClass('invalid', !requirements.special);

                // Calculate strength
                if (requirements.length) strength++;
                if (requirements.uppercase) strength++;
                if (requirements.lowercase) strength++;
                if (requirements.number) strength++;
                if (requirements.special) strength++;

                return {
                    strength,
                    requirements
                };
            }

            // Display password strength
            function displayPasswordStrength(strength) {
                const $strengthIndicator = $('#passwordStrength');

                if (strength === 0) {
                    $strengthIndicator.text('').attr('class', 'password-strength');
                } else if (strength <= 2) {
                    $strengthIndicator.text('Weak Password').attr('class', 'password-strength strength-weak');
                } else if (strength <= 4) {
                    $strengthIndicator.text('Medium Password').attr('class', 'password-strength strength-medium');
                } else {
                    $strengthIndicator.text('Strong Password').attr('class', 'password-strength strength-strong');
                }
            }

            // Check if passwords match
            function checkPasswordMatch() {
                const password = $('#password').val();
                const repeatPassword = $('#repeat-password').val();
                const $matchError = $('#passwordMatchError');
                const $matchSuccess = $('#passwordMatchSuccess');
                const $repeatPasswordInput = $('#repeat-password');

                if (repeatPassword === '') {
                    $matchError.hide();
                    $matchSuccess.hide();
                    $repeatPasswordInput.removeClass('input-error input-success');
                    return true;
                }

                if (password === repeatPassword) {
                    $matchError.hide();
                    $matchSuccess.show();
                    $repeatPasswordInput.removeClass('input-error').addClass('input-success');
                    return true;
                } else {
                    $matchError.show();
                    $matchSuccess.hide();
                    $repeatPasswordInput.removeClass('input-success').addClass('input-error');
                    return false;
                }
            }

            // Event listeners
            $('#password').on('input', function() {
                const result = checkPasswordStrength($(this).val());
                displayPasswordStrength(result.strength);

                // Also check password match when password changes
                if ($('#repeat-password').val() !== '') {
                    checkPasswordMatch();
                }
            });

            $('#repeat-password').on('input', checkPasswordMatch);

            // Form submission validation
            $('#registrationForm').on('submit', function(e) {
                const password = $('#password').val();
                const repeatPassword = $('#repeat-password').val();

                // Check if passwords match
                if (password !== repeatPassword) {
                    e.preventDefault();
                    alert('Passwords do not match! Please make sure both passwords are identical.');
                    $('#repeat-password').focus();
                    return false;
                }

                // Check password strength
                const result = checkPasswordStrength(password);
                const allRequirementsMet = Object.values(result.requirements).every(req => req === true);

                if (!allRequirementsMet) {
                    e.preventDefault();
                    alert('Password does not meet all requirements. Please create a stronger password.');
                    $('#password').focus();
                    return false;
                }

                return true;
            });
        });
    </script>

</body>

</html>