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

<style>
.profile-page { max-width: 960px; margin: 30px auto; padding: 0 16px; }
.profile-card { background: #fff; border: 1px solid #eee; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); overflow: hidden; }
.profile-header { display: flex; align-items: center; gap: 20px; padding: 24px; border-bottom: 1px solid #f1f1f1; }
.profile-photo { width: 96px; height: 96px; border-radius: 50%; object-fit: cover; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.profile-meta { display: flex; flex-direction: column; gap: 6px; }
.profile-name { font-size: 20px; font-weight: 700; color: #333; }
.profile-username { color: #777; font-size: 14px; }
.status-chip { display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
.status-active { background: #e8f8ef; color: #1b8f4f; }
.status-inactive { background: #f6f6f6; color: #777; }
.status-banned { background: #fdeaea; color: #c23b3b; }

.profile-body { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
.field { display: flex; padding: 16px 24px; border-bottom: 1px solid #f4f4f4; }
.field:nth-child(odd) { background: #fff; }
.field:nth-child(even) { background: #fff; }
.label { width: 40%; color: #777; font-weight: 600; }
.value { width: 60%; color: #333; word-break: break-word; }

@media (max-width: 720px) {
  .profile-body { grid-template-columns: 1fr; }
  .label { width: 40%; }
  .value { width: 60%; }
}
</style>

<main class="profile-page">
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
      <div class="field"><div class="label">Contact No</div><div class="value"><?php echo html_escape($user['contact_no'] ?? ''); ?></div></div>
      <div class="field"><div class="label">Email</div><div class="value"><?php echo html_escape($user['email'] ?? ''); ?></div></div>
      <div class="field"><div class="label">Hashed Password</div><div class="value" style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace; font-size: 12px; color:#555;"><?php echo html_escape($user['password'] ?? ''); ?></div></div>
      <div class="field"><div class="label">Security Question</div><div class="value"><?php echo html_escape($user['security_question'] ?? ''); ?></div></div>
      <div class="field"><div class="label">Security Answer</div><div class="value"><?php echo html_escape($user['security_answer'] ?? ''); ?></div></div>
      <div class="field"><div class="label">Status</div><div class="value"><?php echo html_escape(ucfirst(strtolower($user['status'] ?? ''))); ?></div></div>
    </div>
  </div>
</main>

<?php include __DIR__ . '/../../general/_footer.php'; ?>
