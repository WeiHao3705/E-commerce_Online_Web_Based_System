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

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
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
                <h2>Create New Account</h2>
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

            <form id="registrationForm" action="<?php echo $prefix; ?>controller/MemberController.php" method="POST" enctype="multipart/form-data">
                <?php if (isset($_GET['return_to'])): ?>
                    <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($_GET['return_to']); ?>">
                <?php endif; ?>
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

                    <div class="form-group full-width">
                        <label for="profile-photo">Profile Photo (optional)</label>
                        <div class="photo-upload-options">
                            <div class="photo-drop-zone" id="photoDropZone">
                                <span class="material-symbols-outlined drop-icon">image</span>
                                <p>Drag & drop a photo here</p>
                                <button type="button" class="drop-select-btn" id="triggerFileSelect">Browse Files</button>
                                <small>Supported: JPG, PNG, GIF, WEBP. Max 2MB.</small>
                            </div>
                            <div class="webcam-wrapper">
                                <div class="webcam-video-container" id="webcamVideoContainer">
                                    <video id="profilePhotoVideo" autoplay playsinline muted></video>
                                </div>
                                <div class="webcam-controls">
                                    <button type="button" class="webcam-btn" id="startWebcam">
                                        <span class="material-symbols-outlined">videocam</span> Start Camera
                                    </button>
                                    <button type="button" class="webcam-btn" id="captureWebcamPhoto" disabled>
                                        <span class="material-symbols-outlined">photo_camera</span> Capture
                                    </button>
                                    <button type="button" class="webcam-btn" id="stopWebcam" disabled>
                                        <span class="material-symbols-outlined">stop_circle</span> Stop
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="input-wrapper file-input-wrapper sr-only">
                            <span class="material-symbols-outlined input-icon">image</span>
                            <input type="file" id="profile-photo" name="profile_photo" class="form-control file-input" accept="image/png, image/jpeg, image/gif, image/webp">
                        </div>
                        <small class="input-hint">You can drag & drop, browse, or take a photo using your webcam.</small>
                        <input type="hidden" name="profile_photo_cropped" id="profilePhotoCropped">
                        <div class="photo-adjust-wrapper" id="photoAdjustWrapper">
                            <div class="photo-adjust-header">
                                <div>
                                    <strong>Adjust your photo</strong>
                                    <p>Drag to reposition, zoom, rotate, or flip before saving.</p>
                                </div>
                                <div class="adjust-controls">
                                    <button type="button" class="adjust-btn" id="zoomOutPhoto" title="Zoom out">
                                        <span class="material-symbols-outlined">remove</span>
                                    </button>
                                    <button type="button" class="adjust-btn" id="zoomInPhoto" title="Zoom in">
                                        <span class="material-symbols-outlined">add</span>
                                    </button>
                                    <button type="button" class="adjust-btn" id="rotateLeftPhoto" title="Rotate left">
                                        <span class="material-symbols-outlined">rotate_left</span>
                                    </button>
                                    <button type="button" class="adjust-btn" id="rotateRightPhoto" title="Rotate right">
                                        <span class="material-symbols-outlined">rotate_right</span>
                                    </button>
                                    <button type="button" class="adjust-btn" id="flipHorizontalPhoto" title="Flip horizontal">
                                        <span class="material-symbols-outlined">swap_horiz</span>
                                    </button>
                                    <button type="button" class="adjust-btn" id="flipVerticalPhoto" title="Flip vertical">
                                        <span class="material-symbols-outlined">swap_vert</span>
                                    </button>
                                    <button type="button" class="adjust-btn" id="resetPhotoAdjust" title="Reset">
                                        <span class="material-symbols-outlined">refresh</span>
                                    </button>
                                </div>
                            </div>
                            <div class="photo-cropper-container">
                                <img id="profilePhotoCropper" src="" alt="Adjust profile photo">
                            </div>
                            <div class="photo-adjust-footer">
                                <button type="button" class="apply-photo-btn" id="applyPhotoAdjust">Apply crop</button>
                            </div>
                        </div>
                        <div class="profile-photo-preview" id="profilePhotoPreviewWrapper">
                            <img id="profilePhotoPreview" src="" alt="Profile preview" />
                            <div class="preview-text">
                                <strong>Preview</strong>
                                <p>Select an image to see it here before submitting.</p>
                            </div>
                            <button type="button" class="clear-photo-btn" id="clearProfilePhoto">Remove</button>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
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

            const allowedPhotoTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            const maxPhotoSize = 2 * 1024 * 1024;
            const $photoInput = $('#profile-photo');
            const $previewWrapper = $('#profilePhotoPreviewWrapper');
            const $previewImg = $('#profilePhotoPreview');
            const $photoAdjustWrapper = $('#photoAdjustWrapper');
            const $cropperImage = $('#profilePhotoCropper');
            const $croppedInput = $('#profilePhotoCropped');
            const $applyPhotoAdjust = $('#applyPhotoAdjust');
            const $zoomInPhoto = $('#zoomInPhoto');
            const $zoomOutPhoto = $('#zoomOutPhoto');
            const $resetPhotoAdjust = $('#resetPhotoAdjust');
            const $rotateLeftPhoto = $('#rotateLeftPhoto');
            const $rotateRightPhoto = $('#rotateRightPhoto');
            const $flipHorizontalPhoto = $('#flipHorizontalPhoto');
            const $flipVerticalPhoto = $('#flipVerticalPhoto');
            const $photoDropZone = $('#photoDropZone');
            const $triggerFileSelect = $('#triggerFileSelect');
            const $startWebcam = $('#startWebcam');
            const $captureWebcamPhoto = $('#captureWebcamPhoto');
            const $stopWebcam = $('#stopWebcam');
            const videoElement = document.getElementById('profilePhotoVideo');
            const defaultPhotoPath = '<?php echo $prefix; ?>images/defaultUserImage.jpg';
            let cropper = null;
            let currentScaleX = 1;
            let currentScaleY = 1;
            let webcamStream = null;

            function resetPhotoPreview(showAlert = false, message = '') {
                $photoInput.val('');
                $previewImg.attr('src', defaultPhotoPath);
                $previewWrapper.show();
                $photoAdjustWrapper.hide();
                $croppedInput.val('');
                destroyCropper();
                if (showAlert && message) {
                    alert(message);
                }
            }

            $previewWrapper.hide();
            $photoAdjustWrapper.hide();
            resetPhotoPreview();

            function destroyCropper() {
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                    currentScaleX = 1;
                    currentScaleY = 1;
                }
            }

            function initCropper() {
                destroyCropper();
                cropper = new Cropper($cropperImage[0], {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 1,
                    background: false,
                    responsive: true,
                    minCropBoxWidth: 150,
                    minCropBoxHeight: 150
                });
                currentScaleX = 1;
                currentScaleY = 1;
            }

            function applyCropToPreview() {
                if (!cropper) {
                    return;
                }
                const canvas = cropper.getCroppedCanvas({
                    width: 400,
                    height: 400,
                    fillColor: '#ffffff'
                });
                if (!canvas) {
                    return;
                }
                const dataUrl = canvas.toDataURL('image/png');
                $previewImg.attr('src', dataUrl);
                $previewWrapper.show();
                $croppedInput.val(dataUrl);
            }

            function handleSelectedFile(file) {
                if (!file) {
                    resetPhotoPreview();
                    return;
                }

                if (!allowedPhotoTypes.includes(file.type)) {
                    resetPhotoPreview(true, 'Invalid photo format. Please choose JPG, PNG, GIF, or WEBP.');
                    return;
                }

                if (file.size > maxPhotoSize) {
                    resetPhotoPreview(true, 'Profile photo must be 2MB or smaller.');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    $cropperImage.one('load', function() {
                        initCropper();
                    });
                    $cropperImage.attr('src', e.target.result);
                    $photoAdjustWrapper.show();
                    $previewWrapper.hide();
                    $croppedInput.val('');
                };
                reader.readAsDataURL(file);
            }

            $photoInput.on('change', function() {
                const file = this.files && this.files[0] ? this.files[0] : null;
                handleSelectedFile(file);
            });

            $('#clearProfilePhoto').on('click', function() {
                resetPhotoPreview();
            });

            $applyPhotoAdjust.on('click', function() {
                applyCropToPreview();
            });

            $zoomInPhoto.on('click', function() {
                if (cropper) {
                    cropper.zoom(0.1);
                }
            });

            $zoomOutPhoto.on('click', function() {
                if (cropper) {
                    cropper.zoom(-0.1);
                }
            });

            $resetPhotoAdjust.on('click', function() {
                if (cropper) {
                    cropper.reset();
                    $previewWrapper.hide();
                    $croppedInput.val('');
                    currentScaleX = 1;
                    currentScaleY = 1;
                }
            });

            $rotateLeftPhoto.on('click', function() {
                if (cropper) {
                    cropper.rotate(-90);
                }
            });

            $rotateRightPhoto.on('click', function() {
                if (cropper) {
                    cropper.rotate(90);
                }
            });

            $flipHorizontalPhoto.on('click', function() {
                if (cropper) {
                    currentScaleX = currentScaleX * -1;
                    cropper.scaleX(currentScaleX);
                }
            });

            $flipVerticalPhoto.on('click', function() {
                if (cropper) {
                    currentScaleY = currentScaleY * -1;
                    cropper.scaleY(currentScaleY);
                }
            });

            // Drag & drop handling
            $photoDropZone.on('click', function() {
                $photoInput.trigger('click');
            });

            $triggerFileSelect.on('click', function(e) {
                e.stopPropagation();
                $photoInput.trigger('click');
            });

            $photoDropZone.on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $photoDropZone.addClass('dragover');
            });

            $photoDropZone.on('dragleave dragend drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $photoDropZone.removeClass('dragover');
            });

            $photoDropZone.on('drop', function(e) {
                const files = e.originalEvent.dataTransfer.files;
                if (files && files.length > 0) {
                    handleSelectedFile(files[0]);
                }
            });

            // Webcam handling
            async function startWebcam() {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    alert('Webcam is not supported in this browser.');
                    return;
                }

                try {
                    if (webcamStream) {
                        stopWebcam();
                    }

                    webcamStream = await navigator.mediaDevices.getUserMedia({ video: true });
                    videoElement.srcObject = webcamStream;
                    $('#webcamVideoContainer').addClass('active');
                    $startWebcam.prop('disabled', true);
                    $captureWebcamPhoto.prop('disabled', false);
                    $stopWebcam.prop('disabled', false);
                } catch (error) {
                    alert('Unable to access webcam: ' + error.message);
                }
            }

            function stopWebcam() {
                if (webcamStream) {
                    webcamStream.getTracks().forEach(track => track.stop());
                    webcamStream = null;
                }
                videoElement.srcObject = null;
                $('#webcamVideoContainer').removeClass('active');
                $startWebcam.prop('disabled', false);
                $captureWebcamPhoto.prop('disabled', true);
                $stopWebcam.prop('disabled', true);
            }

            function captureWebcamPhoto() {
                if (!webcamStream || !videoElement.videoWidth) {
                    alert('Webcam is not ready yet.');
                    return;
                }

                const canvas = document.createElement('canvas');
                canvas.width = videoElement.videoWidth;
                canvas.height = videoElement.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);

                canvas.toBlob(blob => {
                    if (!blob) {
                        alert('Unable to capture photo.');
                        return;
                    }
                    const capturedFile = new File([blob], 'webcam_photo.png', { type: blob.type || 'image/png' });
                    handleSelectedFile(capturedFile);
                }, 'image/png');
            }

            $startWebcam.on('click', startWebcam);
            $stopWebcam.on('click', stopWebcam);
            $captureWebcamPhoto.on('click', captureWebcamPhoto);

            function handleBeforeUnload() {
                stopWebcam();
            }

            window.addEventListener('beforeunload', handleBeforeUnload);

            // Form submission validation
            $('#registrationForm').on('submit', function(e) {
                if (cropper && !$croppedInput.val() && $photoInput[0].files.length > 0) {
                    applyCropToPreview();
                }

                stopWebcam();

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