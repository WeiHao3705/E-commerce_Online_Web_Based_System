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

$userId = (int)$_SESSION['user']['user_id'];
$user = $service->getMemberById($userId);

if (!$user) {
    $_SESSION['error_message'] = 'Unable to load your profile.';
    header('Location: ../../index.php');
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

$pageTitle = 'My Profile';
include __DIR__ . '/../../general/_header.php';
include __DIR__ . '/../../general/_navbar.php';
?>

<link rel="stylesheet" href="<?php echo $prefix; ?>css/profile.css">

<main class="profile-page">
  <?php if (!empty($_SESSION['success_message'])): ?>
    <div style="margin-bottom:12px; padding:10px 12px; border-radius:8px; background:#e8f8ef; color:#1b8f4f; font-weight:600;">
      <?php echo html_escape($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
    </div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['error_message'])): ?>
    <div style="margin-bottom:12px; padding:10px 12px; border-radius:8px; background:#fdeaea; color:#c23b3b; font-weight:600;">
      <?php echo html_escape($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
    </div>
  <?php endif; ?>
  <div class="profile-card">
    <div class="profile-header">
      <img class="profile-photo" src="<?php echo html_escape($photoUrl); ?>" alt="Profile photo">
      <div class="profile-meta">
        <div class="profile-name"><?php echo html_escape($user['full_name'] ?? ''); ?></div>
        <div class="profile-username">@<?php echo html_escape($user['username'] ?? ''); ?></div>
        <?php
          $status = strtolower($user['status'] ?? 'active');
          $statusClass = 'status-' . ($status ?: 'active');
          $statusLabel = ucfirst($status ?: 'active');
        ?>
        <span class="status-chip <?php echo html_escape($statusClass); ?>">Status: <?php echo html_escape($statusLabel); ?></span>
      </div>
    </div>

    <div class="profile-body">
      <div class="field"><div class="label">Username</div><div class="value"><?php echo html_escape($user['username'] ?? ''); ?></div></div>
      <div class="field"><div class="label">Full Name</div><div class="value"><?php echo html_escape($user['full_name'] ?? ''); ?></div></div>
      <div class="field"><div class="label">Gender</div><div class="value"><?php echo html_escape($user['gender'] ?? ''); ?></div></div>
      <div class="field"><div class="label">Date of Birth</div><div class="value"><?php echo html_escape($user['DateOfBirth'] ?? ''); ?></div></div>
      <?php
        $rawPhone = (string)($user['contact_no'] ?? '');
        $digits = preg_replace('/\D+/', '', $rawPhone);
        if (strlen($digits) === 10) {
            $formattedPhone = substr($digits,0,3) . '-' . substr($digits,3,3) . ' ' . substr($digits,6,4);
        } else {
            $formattedPhone = $rawPhone;
        }
      ?>
      <div class="field"><div class="label">Contact No</div><div class="value"><?php echo html_escape($formattedPhone); ?></div></div>
      <div class="field"><div class="label">Email</div><div class="value"><?php echo html_escape($user['email'] ?? ''); ?></div></div>
      <div class="field"><div class="label">Status</div><div class="value"><?php echo html_escape(ucfirst(strtolower($user['status'] ?? ''))); ?></div></div>
    </div>
    <div class="profile-actions">
      <a href="#" class="btn-update" id="btnOpenUpdate">Update Profile</a>
      <a class="btn-logout" href="<?php echo $prefix; ?>logout.php">Log out</a>
    </div>
  </div>
</main>

<!-- Update Profile Modal -->
<div class="modal-overlay" id="updateModal" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true" aria-labelledby="updateModalTitle">
    <div class="modal-header" id="updateModalTitle">Update Profile</div>
    <form action="<?php echo $prefix; ?>controller/MemberController.php" method="POST">
      <input type="hidden" name="action" value="update" />
      <input type="hidden" name="return_to" value="profile" />
      <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>" />
      <input type="hidden" name="username" value="<?php echo html_escape($user['username'] ?? ''); ?>" />
      <div class="modal-body">
        <div class="form-row">
          <label for="full_name">Full Name</label>
          <input type="text" id="full_name" name="full_name" value="<?php echo html_escape($user['full_name'] ?? ''); ?>" required />
          <div class="form-error" id="err_full_name" style="display:none;">Full name is required.</div>
        </div>
        <div class="form-row">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?php echo html_escape($user['email'] ?? ''); ?>" required />
          <div class="form-error" id="err_email" style="display:none;">Enter a valid email address.</div>
        </div>
        <div class="form-row">
          <label for="gender">Gender</label>
          <?php $g = strtolower(trim($user['gender'] ?? '')); ?>
          <select id="gender" name="gender" required>
            <option value="Male" <?php echo ($g === 'male') ? 'selected' : ''; ?>>Male</option>
            <option value="Female" <?php echo ($g === 'female') ? 'selected' : ''; ?>>Female</option>
            <option value="Other" <?php echo ($g === 'other') ? 'selected' : ''; ?>>Other</option>
          </select>
          <div class="form-error" id="err_gender" style="display:none;">Please select a gender.</div>
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
      <div class="modal-actions">
        <a href="#" class="btn-cancel" id="btnCancelUpdate">Cancel</a>
        <button type="submit" class="btn-save" id="btnSaveUpdate">Save Changes</button>
      </div>
    </form>
  </div>
  
</div>

<script>
(function(){
  var openBtn = document.getElementById('btnOpenUpdate');
  var modal = document.getElementById('updateModal');
  var cancelBtn = document.getElementById('btnCancelUpdate');
  var saveBtn = document.getElementById('btnSaveUpdate');
  var contact = document.getElementById('contact_no');

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

  function validateForm(){
    var ok = true;
    var full = document.getElementById('full_name');
    var email = document.getElementById('email');
    var gender = document.getElementById('gender');
    var contact = document.getElementById('contact_no');
    // Reset
    ['err_full_name','err_email','err_gender','err_contact'].forEach(function(id){ var el=document.getElementById(id); if(el) el.style.display='none'; });
    // Full name
    if(!full.value.trim()){ document.getElementById('err_full_name').style.display='block'; ok=false; }
    // Email format
    var emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim());
    if(!emailOk){ document.getElementById('err_email').style.display='block'; ok=false; }
    // Gender not empty
    if(!gender.value){ document.getElementById('err_gender').style.display='block'; ok=false; }
    // Phone format
    var phoneOk = /^\d{3}-\d{3} \d{4}$/.test(contact.value.trim());
    if(!phoneOk){ document.getElementById('err_contact').style.display='block'; ok=false; }
    return ok;
  }

  function openModal(e){ if(e) e.preventDefault(); modal.classList.add('open'); modal.setAttribute('aria-hidden','false'); }
  function closeModal(e){ if(e) e.preventDefault(); modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); }

  if(openBtn) openBtn.addEventListener('click', openModal);
  if(cancelBtn) cancelBtn.addEventListener('click', closeModal);
  if(modal) modal.addEventListener('click', function(e){ if(e.target === modal){ closeModal(e); } });
  document.addEventListener('keydown', function(e){ if(e.key === 'Escape' && modal.classList.contains('open')){ closeModal(e); } });
  if(saveBtn){
    saveBtn.addEventListener('click', function(e){
      var ok = validateForm();
      if(!ok){ e.preventDefault(); }
    });
  }
})();
</script>

<?php include __DIR__ . '/../../general/_footer.php'; ?>
