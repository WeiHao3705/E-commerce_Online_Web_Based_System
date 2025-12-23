<?php
session_start();

if (empty($_SESSION['user'])) {
    header('Location: ../../account.php');
    exit;
}

// Redirect admins to AdminDashboard - they should not access member pages
if (isset($_SESSION['user']->role) && $_SESSION['user']->role === 'admin') {
    header('Location: ../../views/admin/AdminDashboard.php');
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
    header('Location: ../../index.php');
    exit;
}

// Fetch user addresses
$conn = $db->getConnection();
$stmtAddr = $conn->prepare("SELECT * FROM address WHERE user_id = ? ORDER BY is_default DESC, id ASC");
$stmtAddr->execute([$userId]);
$addresses = $stmtAddr->fetchAll(PDO::FETCH_ASSOC);

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

$pageTitle = 'My Profile';
require_once __DIR__ . '/../../general/_header.php';
include __DIR__ . '/../../general/_navbar.php';
?>

<body data-user-id="<?php echo (int)$user['user_id']; ?>" data-controller-url="<?php echo $prefix; ?>controller/MemberController.php" data-prefix="<?php echo $prefix; ?>">

<link rel="stylesheet" href="<?php echo $prefix; ?>css/profile.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<!-- Leaflet CSS for Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

<main class="profile-page">
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

  <div class="breadcrumb">
    <a href="../../index.php"><i class="fas fa-home"></i> Home</a>
    <span>/</span>
    <span>Profile</span>
  </div>

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
          <h2 class="profile-name"><?php echo html_escape($user['full_name'] ?? 'Guest'); ?></h2>
          <p class="profile-username">@<?php echo html_escape($user['username'] ?? 'guest_user'); ?></p>
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
          <a href="<?php echo $prefix; ?>logout.php" class="btn btn-logout">
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
              <p><?php echo html_escape($user['gender'] ?? ''); ?></p>
            </div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">
              <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="detail-info">
              <label>DATE OF BIRTH</label>
              <p><?php echo html_escape($user['DateOfBirth'] ?? '0000-00-00'); ?></p>
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
                // Format Malaysian phone number (10-11 digits)
                if (strlen($digits) === 11) {
                    $formattedPhone = substr($digits,0,3) . '-' . substr($digits,3,4) . ' ' . substr($digits,7,4);
                } elseif (strlen($digits) === 10) {
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
          <?php
            // Get default address or first address
            $defaultAddress = null;
            foreach ($addresses as $addr) {
              if ($addr['is_default'] == 1) {
                $defaultAddress = $addr;
                break;
              }
            }
            if (!$defaultAddress && !empty($addresses)) {
              $defaultAddress = $addresses[0];
            }
          ?>
          <?php if ($defaultAddress): ?>
          <div class="detail-item detail-item-wide">
            <div class="detail-icon">
              <i class="fas fa-map-marker-alt"></i>
            </div>
            <div class="detail-info">
              <label>ADDRESS (<?php echo strtoupper(html_escape($defaultAddress['label'])); ?>)</label>
              <p>
                <?php 
                  echo html_escape($defaultAddress['address1']);
                  if (!empty($defaultAddress['address2'])) echo ', ' . html_escape($defaultAddress['address2']);
                  echo ', ' . html_escape($defaultAddress['city']) . ', ' . html_escape($defaultAddress['state']) . ' ' . html_escape($defaultAddress['postcode']);
                ?>
              </p>
            </div>
          </div>
          <?php endif; ?>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Update Info & Select Address Modal -->
<div class="modal-overlay" id="updateModal" aria-hidden="true">
  <div class="modal modal-large" role="dialog" aria-modal="true" aria-labelledby="updateModalTitle">
    <div class="modal-header" id="updateModalTitle">
      Update Info
      <button type="button" class="modal-close" id="btnCloseUpdate" aria-label="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <form action="<?php echo $prefix; ?>controller/MemberController.php" method="POST" id="updateForm">
      <input type="hidden" name="action" value="update" />
      <input type="hidden" name="return_to" value="profile" />
      <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>" />
      <input type="hidden" name="username" value="<?php echo html_escape($user['username'] ?? ''); ?>" />
      <input type="hidden" name="selected_address_id" id="selectedAddressId" value="" />
      
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
              $rawPhone2 = (string)($user['contact_no'] ?? '');
              $digits2 = preg_replace('/\D+/', '', $rawPhone2);
              // Format Malaysian phone number (10-11 digits)
              if (strlen($digits2) === 11) {
                $formatted2 = substr($digits2,0,3) . '-' . substr($digits2,3,4) . ' ' . substr($digits2,7,4);
              } elseif (strlen($digits2) === 10) {
                $formatted2 = substr($digits2,0,3) . '-' . substr($digits2,3,3) . ' ' . substr($digits2,6,4);
              } else {
                $formatted2 = $rawPhone2;
              }
            ?>
            <input
              type="tel"
              id="contact_no"
              name="contact_no"
              value="<?php echo html_escape($formatted2); ?>"
              required
              pattern="^[0-9\s\-]{10,12}$"
              title="Enter a valid Malaysian phone number (10-11 digits, e.g. 011-5550 5761)."
            />
            <div class="form-error" id="err_contact" style="display:none;">Enter a valid Malaysian phone number (10-11 digits, e.g. 011-5550 5761).</div>
          </div>
        </div>

        <!-- Saved Addresses Section -->
        <div class="section-title">SAVED ADDRESSES</div>
        <div id="addressList" class="address-list">
          <?php if (empty($addresses)): ?>
            <p class="no-address-text">No saved addresses yet.</p>
          <?php else: ?>
            <?php foreach ($addresses as $addr): ?>
              <label class="address-card">
                <input type="radio" name="address_selection" value="<?php echo (int)$addr['id']; ?>" <?php echo $addr['is_default'] ? 'checked' : ''; ?> />
                <div class="address-content">
                  <div class="address-label"><?php echo html_escape(ucfirst($addr['label'])); ?></div>
                  <div class="address-text">
                    <?php 
                      echo html_escape($addr['address1']) . ', ';
                      if (!empty($addr['address2'])) echo html_escape($addr['address2']) . ', ';
                      echo html_escape($addr['city']) . ', ' . html_escape($addr['state']) . ' ' . html_escape($addr['postcode']);
                    ?>
                  </div>
                </div>
                <div class="address-actions">
                  <button type="button" class="btn btn-logout btn-edit-address" data-address-id="<?php echo (int)$addr['id']; ?>" data-address='<?php echo html_escape(json_encode($addr)); ?>' title="Edit">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button type="button" class="btn btn-delete-address outlined" data-address-id="<?php echo (int)$addr['id']; ?>" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </label>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <button type="button" class="btn-add-address" id="btnOpenAddAddress">
          <i class="fas fa-plus-circle"></i> Add New Address
        </button>
        
        <!-- Password Update Section -->
        <div class="section-title" style="margin-top: 18px;">UPDATE PASSWORD <span class="optional">(Optional)</span></div>
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
        <button type="submit" class="btn-save" id="btnSaveUpdate">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Add/Edit Address Modal -->
<div class="modal-overlay" id="addAddressModal" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-header">
      <span id="addressModalTitle">Add New Address</span>
      <button type="button" class="modal-close" id="btnCloseAddAddress" aria-label="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <form id="addAddressForm">
      <input type="hidden" name="action" value="add_address" id="addressFormAction" />
      <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>" />
      <input type="hidden" name="address_id" value="" id="address_id" />
      
      <div class="modal-body">
        <!-- Get Current Location Button -->
        <div class="form-row">
          <button type="button" class="btn-get-location" id="btnGetCurrentLocation">
            <i class="fas fa-map-marker-alt"></i>
            <span id="locationBtnText">Get Current Location</span>
            <span class="location-spinner" id="locationSpinner" style="display:none;">
              <i class="fas fa-spinner fa-spin"></i>
            </span>
          </button>
          <div class="location-status" id="locationStatus" style="display:none;"></div>
        </div>
        
        <!-- Map Container -->
        <div class="form-row map-container-wrapper" id="mapWrapper" style="display:none;">
          <div id="locationMap" class="location-map"></div>
          <p class="map-hint"><i class="fas fa-info-circle"></i> Drag the pin to adjust your exact location</p>
        </div>
        
        <div class="form-row">
          <label for="address1">Address Line 1 <span class="required">*</span></label>
          <div class="input-with-icon">
            <i class="fas fa-home"></i>
            <input type="text" id="address1" name="address1" placeholder="123 Main St, Apt 4B" required />
          </div>
          <div class="form-error" id="err_address1" style="display:none;">Address line 1 is required.</div>
        </div>
        
        <div class="form-row">
          <label for="address2">Address Line 2 <span class="optional">(Optional)</span></label>
          <div class="input-with-icon">
            <i class="fas fa-building"></i>
            <input type="text" id="address2" name="address2" placeholder="Building, Floor, etc." />
          </div>
        </div>
        
        <div class="form-grid">
          <div class="form-row">
            <label for="city">City <span class="required">*</span></label>
            <input type="text" id="city" name="city" placeholder="Kuala Lumpur" required />
            <div class="form-error" id="err_city" style="display:none;">City is required.</div>
          </div>
          
          <div class="form-row">
            <label for="state">State / Province <span class="required">*</span></label>
            <select id="state" name="state" required>
              <option value="">Select a State</option>
              <option value="Johor">Johor</option>
              <option value="Kedah">Kedah</option>
              <option value="Kelantan">Kelantan</option>
              <option value="Kuala Lumpur">Kuala Lumpur</option>
              <option value="Labuan">Labuan</option>
              <option value="Melaka">Melaka</option>
              <option value="Negeri Sembilan">Negeri Sembilan</option>
              <option value="Pahang">Pahang</option>
              <option value="Penang">Penang</option>
              <option value="Perak">Perak</option>
              <option value="Perlis">Perlis</option>
              <option value="Putrajaya">Putrajaya</option>
              <option value="Sabah">Sabah</option>
              <option value="Sarawak">Sarawak</option>
              <option value="Selangor">Selangor</option>
              <option value="Terengganu">Terengganu</option>
            </select>
            <div class="form-error" id="err_state" style="display:none;">State is required.</div>
          </div>
        </div>
        
        <div class="form-grid">
          <div class="form-row">
            <label for="postcode">Postcode <span class="required">*</span></label>
            <input type="text" id="postcode" name="postcode" placeholder="53000" required maxlength="5" pattern="\d{5}" />
            <div class="form-error" id="err_postcode" style="display:none;">Valid 5-digit postcode is required.</div>
          </div>
          
          <div class="form-row">
            <label>Label as</label>
            <div class="label-buttons">
              <button type="button" class="label-btn active" data-label="home">Home</button>
              <button type="button" class="label-btn" data-label="work">Work</button>
              <button type="button" class="label-btn" data-label="other">Other</button>
            </div>
            <input type="hidden" id="address_label" name="label" value="home" />
          </div>
        </div>
      </div>
      
      <div class="modal-actions">
        <button type="button" class="btn-cancel" id="btnCancelAddAddress">Cancel</button>
        <button type="submit" class="btn-save" id="btnSaveAddress">
          <i class="fas fa-save"></i> <span id="addressSubmitText">Add Address</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Photo Upload Modal -->
<div class="modal-overlay" id="photoUploadModal" aria-hidden="true">
  <div class="modal photo-upload-modal" role="dialog" aria-modal="true">
    <div class="modal-header">
      Upload Profile Photo
      <button type="button" class="modal-close" id="btnClosePhotoUpload" aria-label="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="modal-body">
      <!-- Current Photo Preview -->
      <div class="current-photo-section">
        <p class="section-label">Current Photo</p>
        <div class="current-photo-preview">
          <img src="<?php echo html_escape($photoUrl); ?>" alt="Current profile photo" id="currentPhotoPreview">
        </div>
      </div>
      
      <!-- Upload Area -->
      <div class="upload-area" id="uploadDropZone">
        <div class="upload-icon">
          <i class="fas fa-cloud-upload-alt"></i>
        </div>
        <p class="upload-text">Drag & drop your photo here</p>
        <p class="upload-subtext">or</p>
        <div class="upload-buttons">
          <button type="button" class="upload-btn upload-btn-file" id="btnSelectFile">
            <i class="fas fa-folder-open"></i> Browse Files
          </button>
          <button type="button" class="upload-btn upload-btn-camera" id="btnOpenCamera">
            <i class="fas fa-camera"></i> Take Photo
          </button>
        </div>
        <p class="upload-hint">Supported formats: JPG, PNG, GIF (Max 5MB)</p>
        <input type="file" id="photoFileInput" accept="image/*" style="display: none;">
        <input type="file" id="cameraInput" accept="image/*" capture="user" style="display: none;">
      </div>
      
      <!-- Camera Preview (hidden by default) -->
      <div class="camera-section" id="cameraSection" style="display: none;">
        <video id="cameraVideo" autoplay playsinline></video>
        <canvas id="cameraCanvas" style="display: none;"></canvas>
        <div class="camera-controls">
          <button type="button" class="upload-btn upload-btn-capture" id="btnCapture">
            <i class="fas fa-camera"></i> Capture
          </button>
          <button type="button" class="upload-btn upload-btn-cancel" id="btnCancelCamera">
            <i class="fas fa-times"></i> Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Photo Cropper Modal -->
<div class="modal-overlay" id="photoCropperModal" aria-hidden="true">
  <div class="modal photo-cropper-modal" role="dialog" aria-modal="true">
    <div class="modal-header">
      Adjust Profile Photo
      <button type="button" class="modal-close" id="btnClosePhotoCropper" aria-label="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="modal-body">
      <div class="cropper-container-wrapper">
        <img id="cropperImage" src="" alt="Crop preview">
      </div>
      <div class="cropper-controls">
        <button type="button" class="control-btn" id="zoomIn" title="Zoom In"><i class="fas fa-search-plus"></i></button>
        <button type="button" class="control-btn" id="zoomOut" title="Zoom Out"><i class="fas fa-search-minus"></i></button>
        <button type="button" class="control-btn" id="rotateLeft" title="Rotate Left"><i class="fas fa-undo"></i></button>
        <button type="button" class="control-btn" id="rotateRight" title="Rotate Right"><i class="fas fa-redo"></i></button>
        <button type="button" class="control-btn" id="flipH" title="Flip Horizontal"><i class="fas fa-arrows-alt-h"></i></button>
        <button type="button" class="control-btn" id="flipV" title="Flip Vertical"><i class="fas fa-arrows-alt-v"></i></button>
      </div>
    </div>
    <div class="modal-actions">
      <button type="button" class="btn-cancel" id="btnCancelCrop">Cancel</button>
      <button type="button" class="btn-save" id="btnSaveCrop">Apply</button>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<!-- Leaflet JS for Map -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="<?php echo $prefix; ?>js/profile.js"></script>

</body>

<?php include __DIR__ . '/../../general/_footer.php'; ?>