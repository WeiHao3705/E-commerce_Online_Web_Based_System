<?php
session_start();

if (empty($_SESSION['user'])) {
    header('Location: ../../account.php');
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

<link rel="stylesheet" href="<?php echo $prefix; ?>css/profile.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">

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
    <a href="<?php echo $prefix; ?>index.php"><i class="fas fa-home"></i> Home</a>
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
              $formatted2 = strlen($digits2) === 10 ? (substr($digits2,0,3) . '-' . substr($digits2,3,3) . ' ' . substr($digits2,6,4)) : $rawPhone2;
            ?>
            <input type="tel" id="contact_no" name="contact_no" value="<?php echo html_escape($formatted2); ?>" required pattern="^\d{3}-\d{3} \d{4}$" title="Enter phone as 000-000 0000." />
            <div class="form-error" id="err_contact" style="display:none;">Enter a valid phone number (10-11 digits).</div>
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
              </label>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <button type="button" class="btn-add-address" id="btnOpenAddAddress">
          <i class="fas fa-plus-circle"></i> Add New Address
        </button>
      </div>
      
      <div class="modal-actions">
        <button type="button" class="btn-cancel" id="btnCancelUpdate">Cancel</button>
        <button type="submit" class="btn-save" id="btnSaveUpdate">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Add New Address Modal -->
<div class="modal-overlay" id="addAddressModal" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-header">
      Add New Address
      <button type="button" class="modal-close" id="btnCloseAddAddress" aria-label="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <form id="addAddressForm">
      <input type="hidden" name="action" value="add_address" />
      <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>" />
      
      <div class="modal-body">
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
          <i class="fas fa-plus"></i> Add Address
        </button>
      </div>
    </form>
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
<script>
(function(){
  // Photo Upload and Cropping
  var uploadPhotoBtn = document.getElementById('uploadPhotoBtn');
  var photoUpload = document.getElementById('photoUpload');
  var photoCropperModal = document.getElementById('photoCropperModal');
  var cropperImage = document.getElementById('cropperImage');
  var btnCancelCrop = document.getElementById('btnCancelCrop');
  var btnSaveCrop = document.getElementById('btnSaveCrop');
  var cropper = null;
  var scaleX = 1;
  var scaleY = 1;

  // Trigger file input when camera badge is clicked
  if(uploadPhotoBtn) {
    uploadPhotoBtn.addEventListener('click', function(e) {
      e.preventDefault();
      photoUpload.click();
    });
  }

  // Handle file selection
  if(photoUpload) {
    photoUpload.addEventListener('change', function(e) {
      var file = e.target.files[0];
      if(file && file.type.startsWith('image/')) {
        var reader = new FileReader();
        reader.onload = function(event) {
          cropperImage.src = event.target.result;
          photoCropperModal.classList.add('open');
          photoCropperModal.setAttribute('aria-hidden', 'false');
          
          // Initialize cropper
          if(cropper) {
            cropper.destroy();
          }
          cropper = new Cropper(cropperImage, {
            aspectRatio: 1,
            viewMode: 2,
            dragMode: 'move',
            autoCropArea: 1,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
          });
          scaleX = 1;
          scaleY = 1;
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // Cropper controls
  document.getElementById('zoomIn')?.addEventListener('click', function() {
    if(cropper) cropper.zoom(0.1);
  });
  document.getElementById('zoomOut')?.addEventListener('click', function() {
    if(cropper) cropper.zoom(-0.1);
  });
  document.getElementById('rotateLeft')?.addEventListener('click', function() {
    if(cropper) cropper.rotate(-45);
  });
  document.getElementById('rotateRight')?.addEventListener('click', function() {
    if(cropper) cropper.rotate(45);
  });
  document.getElementById('flipH')?.addEventListener('click', function() {
    if(cropper) { scaleX = -scaleX; cropper.scaleX(scaleX); }
  });
  document.getElementById('flipV')?.addEventListener('click', function() {
    if(cropper) { scaleY = -scaleY; cropper.scaleY(scaleY); }
  });

  // Cancel crop
  if(btnCancelCrop) {
    btnCancelCrop.addEventListener('click', function() {
      photoCropperModal.classList.remove('open');
      photoCropperModal.setAttribute('aria-hidden', 'true');
      if(cropper) {
        cropper.destroy();
        cropper = null;
      }
      photoUpload.value = '';
    });
  }

  // Save cropped image
  if(btnSaveCrop) {
    btnSaveCrop.addEventListener('click', function() {
      if(cropper) {
        var canvas = cropper.getCroppedCanvas({
          width: 400,
          height: 400,
          imageSmoothingEnabled: true,
          imageSmoothingQuality: 'high',
        });
        
        canvas.toBlob(function(blob) {
          var formData = new FormData();
          formData.append('photo', blob, 'profile.jpg');
          formData.append('action', 'update_photo');
          formData.append('user_id', <?php echo (int)$user['user_id']; ?>);
          
          // Upload the photo
          fetch('<?php echo $prefix; ?>controller/MemberController.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if(data.success) {
              // Update the profile photo
              var profilePhoto = document.getElementById('profilePhoto');
              profilePhoto.src = canvas.toDataURL('image/jpeg');
              
              // Close modal
              photoCropperModal.classList.remove('open');
              photoCropperModal.setAttribute('aria-hidden', 'true');
              
              // Show success message
              var successDiv = document.createElement('div');
              successDiv.className = 'alert alert-success';
              successDiv.textContent = 'Profile photo updated successfully!';
              document.querySelector('.profile-page').insertBefore(successDiv, document.querySelector('.breadcrumb'));
              setTimeout(function() { successDiv.remove(); }, 3000);
              
              if(cropper) {
                cropper.destroy();
                cropper = null;
              }
              photoUpload.value = '';
            } else {
              alert('Error: ' + (data.message || 'Failed to upload photo'));
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while uploading the photo.');
          });
        }, 'image/jpeg', 0.9);
      }
    });
  }

  // Profile update modal
  var openBtn = document.getElementById('btnOpenUpdate');
  var modal = document.getElementById('updateModal');
  var cancelBtn = document.getElementById('btnCancelUpdate');
  var closeBtn = document.getElementById('btnCloseUpdate');
  var saveBtn = document.getElementById('btnSaveUpdate');
  var contact = document.getElementById('contact_no');

  // Add Address modal
  var addAddressModal = document.getElementById('addAddressModal');
  var btnOpenAddAddress = document.getElementById('btnOpenAddAddress');
  var btnCancelAddAddress = document.getElementById('btnCancelAddAddress');
  var btnCloseAddAddress = document.getElementById('btnCloseAddAddress');
  var addAddressForm = document.getElementById('addAddressForm');

  // Photo Cropper modal
  var btnClosePhotoCropper = document.getElementById('btnClosePhotoCropper');

  // Simple input masking: auto-insert dashes and space for 000-000 0000
  if(contact){
    contact.addEventListener('input', function(){
      var d = contact.value.replace(/\D+/g,'').slice(0,10);
      var out = d;
      if(d.length >= 4 && d.length <= 6){ out = d.slice(0,3) + '-' + d.slice(3); }
      else if(d.length >= 7){ out = d.slice(0,3) + '-' + d.slice(3,6) + ' ' + d.slice(6); }
      contact.value = out;
    });
  }

  // Update selected address ID before form submission
  document.getElementById('updateForm')?.addEventListener('submit', function(e) {
    var selectedRadio = document.querySelector('input[name=\"address_selection\"]:checked');
    if(selectedRadio) {
      document.getElementById('selectedAddressId').value = selectedRadio.value;
    }
  });

  // Label buttons for address
  document.querySelectorAll('.label-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.label-btn').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      document.getElementById('address_label').value = this.getAttribute('data-label');
    });
  });

  // Open Add Address modal
  if(btnOpenAddAddress) {
    btnOpenAddAddress.addEventListener('click', function(e) {
      e.preventDefault();
      addAddressModal.classList.add('open');
      addAddressModal.setAttribute('aria-hidden', 'false');
    });
  }

  // Close Add Address modal
  if(btnCancelAddAddress) {
    btnCancelAddAddress.addEventListener('click', function(e) {
      e.preventDefault();
      addAddressModal.classList.remove('open');
      addAddressModal.setAttribute('aria-hidden', 'true');
      addAddressForm.reset();
      document.querySelectorAll('.label-btn').forEach(b => b.classList.remove('active'));
      document.querySelector('.label-btn[data-label=\"home\"]').classList.add('active');
    });
  }

  // Submit Add Address form
  if(addAddressForm) {
    addAddressForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      var formData = new FormData(this);
      
      fetch('<?php echo $prefix; ?>controller/MemberController.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if(data.success) {
          // Reload to show new address
          location.reload();
        } else {
          alert('Error: ' + (data.message || 'Failed to add address'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the address.');
      });
    });
  }

  function validateForm(){
    var ok = true;
    var full = document.getElementById('full_name');
    var email = document.getElementById('email');
    var contact = document.getElementById('contact_no');
    // Reset
    ['err_full_name','err_email','err_contact'].forEach(function(id){ var el=document.getElementById(id); if(el) el.style.display='none'; });
    // Full name
    if(!full.value.trim()){ document.getElementById('err_full_name').style.display='block'; ok=false; }
    // Email format
    var emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim());
    if(!emailOk){ document.getElementById('err_email').style.display='block'; ok=false; }
    // Phone format
    var phoneOk = /^\d{3}-\d{3} \d{4}$/.test(contact.value.trim());
    if(!phoneOk){ document.getElementById('err_contact').style.display='block'; ok=false; }
    return ok;
  }

  function openModal(e){ if(e) e.preventDefault(); modal.classList.add('open'); modal.setAttribute('aria-hidden','false'); }
  function closeModal(e){ if(e) e.preventDefault(); modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); }

  if(openBtn) openBtn.addEventListener('click', openModal);
  if(cancelBtn) cancelBtn.addEventListener('click', closeModal);
  if(closeBtn) closeBtn.addEventListener('click', closeModal);
  if(btnCloseAddAddress) btnCloseAddAddress.addEventListener('click', function(e) { 
    e.preventDefault(); 
    btnCancelAddAddress.click(); 
  });
  if(btnClosePhotoCropper) btnClosePhotoCropper.addEventListener('click', function(e) {
    e.preventDefault();
    btnCancelCrop.click();
  });
  if(modal) modal.addEventListener('click', function(e){ if(e.target === modal){ closeModal(e); } });
  if(addAddressModal) addAddressModal.addEventListener('click', function(e){ if(e.target === addAddressModal){ btnCancelAddAddress.click(); } });
  document.addEventListener('keydown', function(e){ 
    if(e.key === 'Escape') {
      if(addAddressModal.classList.contains('open')) { btnCancelAddAddress.click(); }
      else if(modal.classList.contains('open')) { closeModal(e); }
    }
  });
  if(saveBtn){
    saveBtn.addEventListener('click', function(e){
      var ok = validateForm();
      if(!ok){ e.preventDefault(); }
    });
  }
})();
</script>

<?php include __DIR__ . '/../../general/_footer.php'; ?>