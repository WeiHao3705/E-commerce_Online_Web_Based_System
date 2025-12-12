<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user'])) {
    header('Location: ../security/LoginForm.php');
    exit;
}

// Check if user has admin role
$userRole = isset($_SESSION['user']->role) ? $_SESSION['user']->role : (isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : '');
if ($userRole !== 'admin') {
    header('Location: ../security/LoginForm.php');
    exit;
}

require_once __DIR__ . '/../../../helpers.php';
require_once __DIR__ . '/../../database/connection.php';
require_once __DIR__ . '/../../repository/MemberRepository.php';
require_once __DIR__ . '/../../service/MemberService.php';

// Compute base paths (web root and public prefix)
$currentFileDir = __DIR__;
$webRootDir = dirname(dirname($currentFileDir)); // /web
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$webBasePath = str_replace('\\', '/', $relativePath) . '/';
$prefix = $webBasePath; // e.g. /E-commerce_Online_Web_Based_System/web/

// Services
$db = new Database();
$repo = new MembershipRepository($db);
$service = new MembershipServices($repo);

$userId = (int)$_SESSION['user']->user_id;
$user = $service->getMemberById($userId);
 
if (!$user) {
    $_SESSION['error_message'] = 'Unable to load your profile.';
    header('Location: AdminDashboard.php');
    exit;
}

function resolveProfilePhotoUrl(array $user, string $prefix, string $webRootDir): string {
    $photo = isset($user['profile_photo']) ? trim((string)$user['profile_photo']) : '';

    if ($photo !== '' && strpos($photo, 'web/') === 0) {
        return $prefix . substr($photo, 4); // drop leading 'web/'
    }

    if ($photo !== '' && (strpos($photo, 'http://') === 0 || strpos($photo, 'https://') === 0 || strpos($photo, '/') === 0)) {
        return $photo;
    }

    $username = isset($user['username']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $user['username']) : '';
    if ($username !== '') {
        $candidates = glob($webRootDir . '/images/profiles/' . $username . '.*');
        if (!empty($candidates)) {
            $basename = basename($candidates[0]);
            return $prefix . 'images/profiles/' . $basename;
        }
    }

    return $prefix . 'images/defaultUserImage.jpg';
}

$photoUrl = resolveProfilePhotoUrl($user, $prefix, $webRootDir);

$pageTitle = 'Admin Profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - NGEAR</title>
    <link rel="stylesheet" href="<?php echo $prefix; ?>css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
        }
        .admin-profile-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .admin-badge {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

    </style>
</head>
<body>
    <div class="admin-profile-wrapper">
        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php echo html_escape($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <?php echo html_escape($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <div class="profile-container">
            <!-- Left Sidebar -->
            <aside class="profile-sidebar">
                <div class="profile-card">
                    <div class="profile-avatar-section">
                        <div class="profile-avatar">
                            <img src="<?php echo html_escape($photoUrl); ?>" alt="Profile photo" id="profilePhoto">
                            <input type="file" id="photoUpload" accept="image/*" style="display: none;">
                            <div class="avatar-badge" id="uploadPhotoBtn">
                                <i class="fas fa-camera"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-info">
                        <h2 class="profile-name"><?php echo html_escape($user['full_name'] ?? 'Admin'); ?></h2>
                        <p class="profile-username">@<?php echo html_escape($user['username'] ?? 'admin'); ?></p>
                        <?php
                            $status = strtolower($user['status'] ?? 'active');
                            $statusClass = ($status === 'active') ? 'active' : 'inactive';
                        ?>
                        <span class="status-badge status-<?php echo $statusClass; ?>">
                            <i class="fas fa-circle"></i> <?php echo strtoupper($statusClass); ?>
                        </span>
                    </div>

                    <div class="profile-actions">
                        <button type="button" class="btn btn-update" id="btnOpenUpdate">
                            <i class="fas fa-edit"></i> Update Profile
                        </button>
                        <a href="<?php echo $prefix; ?>index.php" class="btn btn-logout" onclick="window.parent.location.href='<?php echo $prefix; ?>logout.php'; return false;">
                            <i class="fas fa-sign-out-alt"></i> Log out
                        </a>
                    </div>
                </div>
            </aside>

            <!-- Right Content -->
            <div class="profile-content">
                <!-- Personal Details Section -->
                <div class="details-section">
                    <div class="section-header">
                        <i class="fas fa-id-card"></i>
                        <h3>Personal Details</h3>
                    </div>
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="detail-info">
                                <label>USERNAME</label>
                                <p><?php echo html_escape($user['username'] ?? ''); ?></p>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-user-tag"></i>
                            </div>
                            <div class="detail-info">
                                <label>FULL NAME</label>
                                <p><?php echo html_escape($user['full_name'] ?? ''); ?></p>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-venus-mars"></i>
                            </div>
                            <div class="detail-info">
                                <label>GENDER</label>
                                <p><?php echo html_escape($user['gender'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="detail-info">
                                <label>DATE OF BIRTH</label>
                                <p><?php echo html_escape($user['DateOfBirth'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="details-section">
                    <div class="section-header">
                        <i class="fas fa-address-card"></i>
                        <h3>Contact Information</h3>
                    </div>
                    <div class="details-grid">
                        <div class="detail-item detail-item-wide">
                            <div class="detail-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="detail-info">
                                <label>EMAIL ADDRESS</label>
                                <p><?php echo html_escape($user['email'] ?? ''); ?></p>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="detail-info">
                                <label>CONTACT NUMBER</label>
                                <?php
                                    $rawPhone = (string)($user['contact_no'] ?? '');
                                    $digits = preg_replace('/\D+/', '', $rawPhone);
                                    if (strlen($digits) === 10) {
                                        $formattedPhone = substr($digits,0,3) . '-' . substr($digits,3,3) . ' ' . substr($digits,6,4);
                                    } else {
                                        $formattedPhone = $rawPhone;
                                    }
                                ?>
                                <p><?php echo html_escape($formattedPhone); ?></p>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="detail-info">
                                <label>ACCOUNT STATUS</label>
                                <p class="status-text status-<?php echo $statusClass; ?>">
                                    <?php echo html_escape(ucfirst(strtolower($user['status'] ?? 'active'))); ?>
                                </p>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="detail-info">
                                <label>ROLE</label>
                                <p style="color: #ef4444; font-weight: 700;">Administrator</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Info Modal -->
    <div class="modal-overlay" id="updateModal" aria-hidden="true">
        <div class="modal modal-large" role="dialog" aria-modal="true" aria-labelledby="updateModalTitle">
            <div class="modal-header" id="updateModalTitle">
                Update Admin Info
                <button type="button" class="modal-close" id="btnCloseUpdate" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="<?php echo $prefix; ?>controller/MemberController.php" method="POST" id="updateForm">
                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="return_to" value="admin_profile" />
                <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>" />
                <input type="hidden" name="username" value="<?php echo html_escape($user['username'] ?? ''); ?>" />
                
                <div class="modal-body">
                    <!-- Personal Details Section -->
                    <div class="section-title">PERSONAL DETAILS</div>
                    <div class="form-grid">
                        <div class="form-row">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo html_escape($user['full_name'] ?? ''); ?>" required />
                            <div class="form-error" id="err_full_name" style="display:none;">Full name is required.</div>
                        </div>
                        <div class="form-row">
                            <label for="username_display">Username</label>
                            <input type="text" id="username_display" value="<?php echo html_escape($user['username'] ?? ''); ?>" disabled />
                        </div>
                        <div class="form-row">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo html_escape($user['email'] ?? ''); ?>" required />
                            <div class="form-error" id="err_email" style="display:none;">Enter a valid email address.</div>
                        </div>
                        <div class="form-row">
                            <label for="contact_no">Contact No</label>
                            <?php
                                $rawContactNo = (string)($user['contact_no'] ?? '');
                                $contactDigits = preg_replace('/\D+/', '', $rawContactNo);
                                if (strlen($contactDigits) === 10) {
                                    $formattedContactNo = substr($contactDigits,0,3) . '-' . substr($contactDigits,3,3) . '-' . substr($contactDigits,6,4);
                                } else {
                                    $formattedContactNo = $rawContactNo;
                                }
                            ?>
                            <input type="text" id="contact_no" name="contact_no" value="<?php echo html_escape($formattedContactNo); ?>" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" placeholder="###-###-####" required />
                            <div class="form-error" id="err_contact_no" style="display:none;">Enter valid phone number (###-###-####).</div>
                        </div>
                        <div class="form-row">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($user['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($user['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($user['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <label for="DateOfBirth">Date of Birth</label>
                            <input type="date" id="DateOfBirth" name="DateOfBirth" value="<?php echo html_escape($user['DateOfBirth'] ?? ''); ?>" required />
                        </div>
                    </div>

                    <!-- Password Update Section -->
                    <div class="section-title" style="margin-top: 24px;">UPDATE PASSWORD <span class="optional">(Optional)</span></div>
                    <div class="form-grid">
                        <div class="form-row form-row-full">
                            <label for="current_password">Current Password</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="current_password" name="current_password" placeholder="Enter current password" />
                            </div>
                            <div class="form-error" id="err_current_password" style="display:none;">Current password is required to update password.</div>
                        </div>
                        <div class="form-row">
                            <label for="new_password">New Password</label>
                            <div class="input-with-icon">
                                <i class="fas fa-key"></i>
                                <input type="password" id="new_password" name="new_password" placeholder="Enter new password" />
                            </div>
                            <div class="form-error" id="err_new_password" style="display:none;">Password must be at least 8 characters.</div>
                        </div>
                        <div class="form-row">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="input-with-icon">
                                <i class="fas fa-key"></i>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" />
                            </div>
                            <div class="form-error" id="err_confirm_password" style="display:none;">Passwords do not match.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" id="btnCancelUpdate">Cancel</button>
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Photo Cropper Modal -->
    <div class="modal-overlay" id="photoCropperModal" aria-hidden="true">
        <div class="modal photo-cropper-modal" role="dialog" aria-modal="true">
            <div class="modal-header">
                Crop Profile Photo
                <button type="button" class="modal-close" id="btnCloseCropper" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="cropper-container-wrapper">
                    <img id="cropperImage" style="max-width: 100%;">
                </div>
                <div class="cropper-controls">
                    <button type="button" class="control-btn" id="btnRotateLeft" title="Rotate Left">
                        <i class="fas fa-undo"></i>
                    </button>
                    <button type="button" class="control-btn" id="btnRotateRight" title="Rotate Right">
                        <i class="fas fa-redo"></i>
                    </button>
                    <button type="button" class="control-btn" id="btnZoomIn" title="Zoom In">
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <button type="button" class="control-btn" id="btnZoomOut" title="Zoom Out">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <button type="button" class="control-btn" id="btnFlipHorizontal" title="Flip Horizontal">
                        <i class="fas fa-arrows-alt-h"></i>
                    </button>
                    <button type="button" class="control-btn" id="btnFlipVertical" title="Flip Vertical">
                        <i class="fas fa-arrows-alt-v"></i>
                    </button>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" id="btnCancelCrop">Cancel</button>
                <button type="button" class="btn-save" id="btnSaveCrop">
                    <i class="fas fa-check"></i> Apply & Upload
                </button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        $(document).ready(function() {
            let cropper;
            let currentFile;

            // Open update modal
            $('#btnOpenUpdate').on('click', function() {
                $('#updateModal').addClass('open');
            });

            // Close update modal
            $('#btnCloseUpdate, #btnCancelUpdate').on('click', function() {
                $('#updateModal').removeClass('open');
            });

            // Trigger file input on avatar badge click
            $('#uploadPhotoBtn').on('click', function() {
                $('#photoUpload').click();
            });

            // Handle photo upload
            $('#photoUpload').on('change', function(e) {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    currentFile = file;
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        $('#cropperImage').attr('src', event.target.result);
                        $('#photoCropperModal').addClass('open');
                        
                        if (cropper) {
                            cropper.destroy();
                        }
                        
                        cropper = new Cropper(document.getElementById('cropperImage'), {
                            aspectRatio: 1,
                            viewMode: 2,
                            autoCropArea: 1,
                            responsive: true,
                            restore: false,
                            guides: true,
                            center: true,
                            highlight: false,
                            cropBoxMovable: true,
                            cropBoxResizable: true,
                            toggleDragModeOnDblclick: false
                        });
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Cropper controls
            $('#btnRotateLeft').on('click', () => cropper.rotate(-45));
            $('#btnRotateRight').on('click', () => cropper.rotate(45));
            $('#btnZoomIn').on('click', () => cropper.zoom(0.1));
            $('#btnZoomOut').on('click', () => cropper.zoom(-0.1));
            
            let scaleX = 1, scaleY = 1;
            $('#btnFlipHorizontal').on('click', function() {
                scaleX = -scaleX;
                cropper.scaleX(scaleX);
            });
            $('#btnFlipVertical').on('click', function() {
                scaleY = -scaleY;
                cropper.scaleY(scaleY);
            });

            // Close cropper modal
            $('#btnCloseCropper, #btnCancelCrop').on('click', function() {
                $('#photoCropperModal').removeClass('open');
                if (cropper) {
                    cropper.destroy();
                }
                $('#photoUpload').val('');
            });

            // Save cropped photo
            $('#btnSaveCrop').on('click', function() {
                if (!cropper) return;
                
                cropper.getCroppedCanvas({
                    width: 400,
                    height: 400,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high'
                }).toBlob(function(blob) {
                    const formData = new FormData();
                    formData.append('action', 'update_photo');
                    formData.append('user_id', '<?php echo (int)$user['user_id']; ?>');
                    formData.append('photo', blob, currentFile.name);

                    $.ajax({
                        url: '<?php echo $prefix; ?>controller/MemberController.php',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            const result = typeof response === 'string' ? JSON.parse(response) : response;
                            if (result.success) {
                                $('#profilePhoto').attr('src', result.photoUrl + '?t=' + new Date().getTime());
                                $('#photoCropperModal').removeClass('open');
                                if (cropper) cropper.destroy();
                                alert('Profile photo updated successfully!');
                            } else {
                                alert('Error: ' + (result.message || 'Failed to upload photo'));
                            }
                        },
                        error: function() {
                            alert('Error uploading photo. Please try again.');
                        }
                    });
                }, 'image/jpeg', 0.95);
            });

            // Close modal on overlay click
            $('.modal-overlay').on('click', function(e) {
                if (e.target === this) {
                    $(this).removeClass('open');
                    if (cropper) cropper.destroy();
                }
            });

            // Form validation
            $('#updateForm').on('submit', function(e) {
                let isValid = true;
                $('.form-error').hide();

                const fullName = $('#full_name').val().trim();
                if (fullName === '') {
                    $('#err_full_name').show();
                    isValid = false;
                }

                const email = $('#email').val().trim();
                if (email === '' || !email.includes('@')) {
                    $('#err_email').show();
                    isValid = false;
                }

                const contactNo = $('#contact_no').val().trim();
                const phonePattern = /^[0-9]{3}-[0-9]{3}-[0-9]{4}$/;
                if (!phonePattern.test(contactNo)) {
                    $('#err_contact_no').show();
                    isValid = false;
                }

                // Password validation (only if user is trying to change password)
                const currentPassword = $('#current_password').val().trim();
                const newPassword = $('#new_password').val().trim();
                const confirmPassword = $('#confirm_password').val().trim();

                // If any password field is filled, validate all password fields
                if (currentPassword !== '' || newPassword !== '' || confirmPassword !== '') {
                    if (currentPassword === '') {
                        $('#err_current_password').show();
                        isValid = false;
                    }

                    if (newPassword === '') {
                        $('#err_new_password').text('New password is required.');
                        $('#err_new_password').show();
                        isValid = false;
                    } else if (newPassword.length < 8) {
                        $('#err_new_password').text('Password must be at least 8 characters.');
                        $('#err_new_password').show();
                        isValid = false;
                    }

                    if (confirmPassword === '') {
                        $('#err_confirm_password').text('Please confirm your new password.');
                        $('#err_confirm_password').show();
                        isValid = false;
                    } else if (newPassword !== confirmPassword) {
                        $('#err_confirm_password').text('Passwords do not match.');
                        $('#err_confirm_password').show();
                        isValid = false;
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });

            // Auto-format phone number
            $('#contact_no').on('input', function() {
                let value = $(this).val().replace(/\D/g, '');
                if (value.length >= 6) {
                    value = value.substring(0, 3) + '-' + value.substring(3, 6) + '-' + value.substring(6, 10);
                } else if (value.length >= 3) {
                    value = value.substring(0, 3) + '-' + value.substring(3);
                }
                $(this).val(value);
            });
        });
    </script>
</body>
</html>
