<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$prefix = '../../';

// Calculate base path for images (absolute from document root)
// Since this file is in web/views/member_management/, go up two levels to get web root
$currentFileDir = dirname(__FILE__); // Gets web/views/member_management/
$webRootDir = dirname(dirname($currentFileDir)); // Gets web/
$projectRoot = dirname($webRootDir); // Gets project root

// Get the relative path from document root
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$imageBasePath = str_replace('\\', '/', $relativePath) . '/'; // Normalize slashes
$cssBasePath = $imageBasePath . 'css/'; // CSS files are in web/css/
$viewsBasePath = $imageBasePath . 'views/'; // Views files are in web/views/

// if (!isset($_SESSION['user'])) {
//     header('Location: ../../views/LoginForm.php');
//     exit;
// }

// if ($_SESSION['user']['role'] !== 'admin') {
//     header('Location: ../../views/403.php'); // or redirect to home
//     exit;
// }

$pageTitle = 'All Members - Admin Dashboard';

// Get current sort parameters
$currentSortBy = isset($currentSort['sortBy']) ? $currentSort['sortBy'] : 'created_at';
$currentSortOrder = isset($currentSort['sortOrder']) ? $currentSort['sortOrder'] : 'DESC';

// Helper function to generate sort URL
function getSortUrl($column, $currentSortBy, $currentSortOrder) {
    $params = ['action' => 'showAll'];
    
    // Preserve search parameter
    if (!empty($_GET['search'])) {
        $params['search'] = $_GET['search'];
    }
    
    // Preserve page parameter
    if (!empty($_GET['page'])) {
        $params['page'] = $_GET['page'];
    }
    
    // Determine sort order
    if ($currentSortBy === $column && $currentSortOrder === 'ASC') {
        $params['sortBy'] = $column;
        $params['sortOrder'] = 'DESC';
    } else {
        $params['sortBy'] = $column;
        $params['sortOrder'] = 'ASC';
    }
    
    return 'MemberController.php?' . http_build_query($params);
}

// Helper function to get sort arrow icon
function getSortArrow($column, $currentSortBy, $currentSortOrder) {
    if ($currentSortBy !== $column) {
        // No sort - show both arrows (neutral)
        return '<span class="material-symbols-outlined sort-icon-neutral">unfold_more</span>';
    } else {
        // Show active arrow
        if ($currentSortOrder === 'ASC') {
            return '<span class="material-symbols-outlined sort-icon-active">arrow_upward</span>';
        } else {
            return '<span class="material-symbols-outlined sort-icon-active">arrow_downward</span>';
        }
    }
}

// Helper function to get profile photo URL
function getProfilePhotoUrl($photoPath, $imageBasePath) {
    // Default image if no photo path
    if (empty($photoPath) || $photoPath === null || trim($photoPath) === '') {
        return $imageBasePath . 'images/defaultUserImage.jpg';
    }

    // If it's already a full URL, return as is
    if (strpos($photoPath, 'http://') === 0 || strpos($photoPath, 'https://') === 0) {
        return $photoPath;
    }
    
    // Remove 'web/' prefix if present
    if (strpos($photoPath, 'web/') === 0) {
        $photoPath = substr($photoPath, 4);
    }
    
    // Remove leading slash if present
    $photoPath = ltrim($photoPath, '/');
    
    return $imageBasePath . $photoPath;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - NGear</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>AllTables.css">
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>AllMembers.css">
</head>

<body class="page-body">

    <div class="page-container">
        <div class="page-content">
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="message message-success">
                    <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="message message-error">
                    <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <!-- Main Content Card -->
            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">All Registered Members</h2>

                    <!-- Search and Actions Bar -->
                    <div class="toolbar">
                        <div class="search-section">
                            <form method="GET" action="MemberController.php" class="search-form">
                                <input type="hidden" name="action" value="showAll">
                                <?php if (!empty($_GET['sortBy'])): ?>
                                    <input type="hidden" name="sortBy" value="<?php echo htmlspecialchars($_GET['sortBy']); ?>">
                                <?php endif; ?>
                                <?php if (!empty($_GET['sortOrder'])): ?>
                                    <input type="hidden" name="sortOrder" value="<?php echo htmlspecialchars($_GET['sortOrder']); ?>">
                                <?php endif; ?>
                                <label class="sr-only" for="simple-search">Search</label>
                                <div class="search-input-wrapper">
                                    <input
                                        class="search-input"
                                        id="simple-search"
                                        name="search"
                                        placeholder="Search for members..."
                                        type="text"
                                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
                                </div>
                                <button type="submit" class="btn btn-primary btn-search">
                                    <span class="material-symbols-outlined">search</span>
                                    <span>Search</span>
                                </button>
                            </form>
                        </div>
                        <div class="actions-section">
                            <?php
                            // Calculate the path to MemberRegisterForm.php using the same base path calculation
                            $memberFormUrl = $viewsBasePath . 'member_management/MemberRegisterForm.php?return_to=admin';
                            ?>
                            <a href="<?php echo $memberFormUrl; ?>" class="btn btn-primary btn-add">
                                <span class="material-symbols-outlined">add</span>
                                Add new member
                            </a>
                        </div>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="members-table">
                        <thead>
                            <tr>
                                <th class="col-photo">
                                    <span>Photo</span>
                                </th>
                                <th class="col-sortable">
                                    <a href="<?php echo getSortUrl('username', $currentSortBy, $currentSortOrder); ?>" class="sort-link">
                                        <span>Username</span>
                                        <?php echo getSortArrow('username', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="col-sortable">
                                    <a href="<?php echo getSortUrl('full_name', $currentSortBy, $currentSortOrder); ?>" class="sort-link">
                                        <span>Full Name</span>
                                        <?php echo getSortArrow('full_name', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="col-sortable">
                                    <a href="<?php echo getSortUrl('email', $currentSortBy, $currentSortOrder); ?>" class="sort-link">
                                        <span>Email</span>
                                        <?php echo getSortArrow('email', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="col-sortable">
                                    <a href="<?php echo getSortUrl('contact_no', $currentSortBy, $currentSortOrder); ?>" class="sort-link">
                                        <span>Contact Number</span>
                                        <?php echo getSortArrow('contact_no', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="col-sortable">
                                    <a href="<?php echo getSortUrl('gender', $currentSortBy, $currentSortOrder); ?>" class="sort-link">
                                        <span>Gender</span>
                                        <?php echo getSortArrow('gender', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="col-sortable">
                                    <a href="<?php echo getSortUrl('DateOfBirth', $currentSortBy, $currentSortOrder); ?>" class="sort-link">
                                        <span>Date of Birth</span>
                                        <?php echo getSortArrow('DateOfBirth', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="col-sortable">
                                    <a href="<?php echo getSortUrl('created_at', $currentSortBy, $currentSortOrder); ?>" class="sort-link">
                                        <span>Joined Date</span>
                                        <?php echo getSortArrow('created_at', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="col-sortable">
                                    <a href="<?php echo getSortUrl('status', $currentSortBy, $currentSortOrder); ?>" class="sort-link">
                                        <span>Status</span>
                                        <?php echo getSortArrow('status', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="col-actions">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($members)): ?>
                                <?php foreach ($members as $member): ?>
                                    <tr class="table-row">
                                        <td class="col-photo">
                                            <?php
                                            $photoUrl = getProfilePhotoUrl($member['profile_photo'] ?? '', $imageBasePath);
                                            $defaultPhotoUrl = $imageBasePath . 'images/defaultUserImage.jpg';
                                            ?>
                                            <img src="<?php echo htmlspecialchars($photoUrl); ?>" 
                                                 alt="Profile photo"
                                                 class="member-profile-photo clickable-image"
                                                 data-image-url="<?php echo htmlspecialchars($photoUrl, ENT_QUOTES); ?>"
                                                 data-member-name="<?php echo htmlspecialchars($member['full_name'], ENT_QUOTES); ?>"
                                                 onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($defaultPhotoUrl); ?>';"
                                                 style="cursor: pointer;"
                                                 title="Click to view full size">
                                        </td>
                                        <td class="col-username">
                                            <?php echo htmlspecialchars($member['username']); ?>
                                        </td>
                                        <td class="col-name"><?php echo htmlspecialchars($member['full_name']); ?></td>
                                        <td class="col-email"><?php echo htmlspecialchars($member['email']); ?></td>
                                        <td class="col-contact"><?php echo htmlspecialchars($member['contact_no']); ?></td>
                                        <td class="col-gender"><?php echo htmlspecialchars($member['gender']); ?></td>
                                        <td class="col-dob">
                                            <?php
                                            if (!empty($member['DateOfBirth'])) {
                                                $dob = new DateTime($member['DateOfBirth']);
                                                echo $dob->format('Y-m-d');
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td class="col-date">
                                            <?php
                                            $date = new DateTime($member['created_at']);
                                            echo $date->format('Y-m-d');
                                            ?>
                                        </td>
                                        <td class="col-status">
                                            <?php
                                            $status = $member['status'] ?? 'active';
                                            $statusClass = '';
                                            $statusText = ucfirst($status);
                                            
                                            switch($status) {
                                                case 'active':
                                                    $statusClass = 'status-badge status-active';
                                                    break;
                                                case 'inactive':
                                                    $statusClass = 'status-badge status-inactive';
                                                    break;
                                                case 'banned':
                                                    $statusClass = 'status-badge status-banned';
                                                    break;
                                                default:
                                                    $statusClass = 'status-badge status-active';
                                            }
                                            ?>
                                            <span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span>
                                        </td>
                                        <td class="col-actions">
                                            <button
                                                class="action-btn edit-btn"
                                                data-user-id="<?php echo $member['user_id']; ?>"
                                                data-username="<?php echo htmlspecialchars($member['username'], ENT_QUOTES); ?>"
                                                data-full-name="<?php echo htmlspecialchars($member['full_name'], ENT_QUOTES); ?>"
                                                data-email="<?php echo htmlspecialchars($member['email'], ENT_QUOTES); ?>"
                                                data-contact-no="<?php echo htmlspecialchars($member['contact_no'], ENT_QUOTES); ?>"
                                                data-gender="<?php echo htmlspecialchars($member['gender'], ENT_QUOTES); ?>"
                                                data-date-of-birth="<?php echo !empty($member['DateOfBirth']) ? htmlspecialchars($member['DateOfBirth'], ENT_QUOTES) : ''; ?>"
                                                title="Edit member">
                                                <span class="material-symbols-outlined">edit</span>
                                            </button>

                                            <?php
                                            $currentStatus = $member['status'] ?? 'active';
                                            ?>
                                            <?php if ($currentStatus !== 'banned'): ?>
                                                <button
                                                    class="action-btn ban-btn"
                                                    data-action="status"
                                                    data-user-id="<?php echo $member['user_id']; ?>"
                                                    data-user-name="<?php echo htmlspecialchars($member['full_name'], ENT_QUOTES); ?>"
                                                    data-status="banned"
                                                    title="Ban member">
                                                    <span class="material-symbols-outlined">block</span>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($currentStatus !== 'inactive'): ?>
                                                <button
                                                    class="action-btn inactive-btn"
                                                    data-action="status"
                                                    data-user-id="<?php echo $member['user_id']; ?>"
                                                    data-user-name="<?php echo htmlspecialchars($member['full_name'], ENT_QUOTES); ?>"
                                                    data-status="inactive"
                                                    title="Set to inactive">
                                                    <span class="material-symbols-outlined">pause_circle</span>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($currentStatus !== 'active'): ?>
                                                <button
                                                    class="action-btn activate-btn"
                                                    data-action="status"
                                                    data-user-id="<?php echo $member['user_id']; ?>"
                                                    data-user-name="<?php echo htmlspecialchars($member['full_name'], ENT_QUOTES); ?>"
                                                    data-status="active"
                                                    title="Activate member">
                                                    <span class="material-symbols-outlined">check_circle</span>
                                                </button>
                                            <?php endif; ?>

                                            <button
                                                class="action-btn delete-btn"
                                                data-action="delete"
                                                data-user-id="<?php echo $member['user_id']; ?>"
                                                data-user-name="<?php echo htmlspecialchars($member['full_name'], ENT_QUOTES); ?>"
                                                title="Delete member">
                                                <span class="material-symbols-outlined">delete</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="table-row table-row-empty">
                                    <td colspan="10" class="col-empty">
                                        No members found. <?php echo !empty($_GET['search']) ? 'Try a different search term.' : ''; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (!empty($members)): ?>
                    <nav class="pagination" aria-label="Table navigation">
                        <span class="pagination-info">
                            Showing
                            <span class="pagination-number"><?php echo $pagination['showing_from']; ?>-<?php echo $pagination['showing_to']; ?></span>
                            of
                            <span class="pagination-number"><?php echo $pagination['total_members']; ?></span>
                        </span>
                        <ul class="pagination-list">
                            <!-- Previous Button -->
                            <li>
                                <?php 
                                $prevParams = ['action' => 'showAll', 'page' => $pagination['current_page'] - 1];
                                if (!empty($_GET['search'])) $prevParams['search'] = $_GET['search'];
                                if (!empty($_GET['sortBy'])) $prevParams['sortBy'] = $_GET['sortBy'];
                                if (!empty($_GET['sortOrder'])) $prevParams['sortOrder'] = $_GET['sortOrder'];
                                $prevUrl = 'MemberController.php?' . http_build_query($prevParams);
                                ?>
                                <?php if ($pagination['current_page'] > 1): ?>
                                    <a href="<?php echo $prevUrl; ?>" class="pagination-link pagination-prev">
                                        <span class="material-symbols-outlined">chevron_left</span>
                                    </a>
                                <?php else: ?>
                                    <span class="pagination-link pagination-prev pagination-disabled">
                                        <span class="material-symbols-outlined">chevron_left</span>
                                    </span>
                                <?php endif; ?>
                            </li>

                            <!-- Page Numbers -->
                            <?php
                            $startPage = max(1, $pagination['current_page'] - 2);
                            $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);

                            for ($i = $startPage; $i <= $endPage; $i++):
                                $pageParams = ['action' => 'showAll', 'page' => $i];
                                if (!empty($_GET['search'])) $pageParams['search'] = $_GET['search'];
                                if (!empty($_GET['sortBy'])) $pageParams['sortBy'] = $_GET['sortBy'];
                                if (!empty($_GET['sortOrder'])) $pageParams['sortOrder'] = $_GET['sortOrder'];
                                $pageUrl = 'MemberController.php?' . http_build_query($pageParams);
                            ?>
                                <li>
                                    <a href="<?php echo $pageUrl; ?>" class="pagination-link <?php echo $i == $pagination['current_page'] ? 'pagination-active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next Button -->
                            <li>
                                <?php 
                                $nextParams = ['action' => 'showAll', 'page' => $pagination['current_page'] + 1];
                                if (!empty($_GET['search'])) $nextParams['search'] = $_GET['search'];
                                if (!empty($_GET['sortBy'])) $nextParams['sortBy'] = $_GET['sortBy'];
                                if (!empty($_GET['sortOrder'])) $nextParams['sortOrder'] = $_GET['sortOrder'];
                                $nextUrl = 'MemberController.php?' . http_build_query($nextParams);
                                ?>
                                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                    <a href="<?php echo $nextUrl; ?>" class="pagination-link pagination-next">
                                        <span class="material-symbols-outlined">chevron_right</span>
                                    </a>
                                <?php else: ?>
                                    <span class="pagination-link pagination-next pagination-disabled">
                                        <span class="material-symbols-outlined">chevron_right</span>
                                    </span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Status Change Form (Hidden) -->
    <form id="statusForm" method="POST" action="MemberController.php" style="display: none;">
        <input type="hidden" name="action" value="updateStatus">
        <input type="hidden" name="user_id" id="statusUserId">
        <input type="hidden" name="status" id="statusValue">
    </form>

    <!-- Delete Confirmation Modal (Hidden Form) -->
    <form id="deleteForm" method="POST" action="MemberController.php" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="deleteUserId">
    </form>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function openEditModal(userId, username, fullName, email, contactNo, gender, dateOfBirth) {
            $('#editUserId').val(userId);
            $('#editUsername').val(username);
            $('#editFullName').val(fullName);
            $('#editEmail').val(email);
            $('#editContactNo').val(contactNo);
            $('#editGender').val(gender);
            $('#editDateOfBirth').val(dateOfBirth || '');

            $('#editModal').removeClass('hidden');
        }

        function closeEditModal() {
            $('#editModal').addClass('hidden');
        }

        function confirmStatusChange(userId, userName, newStatus) {
            var statusLabels = {
                'active': 'activate',
                'inactive': 'set to inactive',
                'banned': 'ban'
            };
            var action = statusLabels[newStatus] || newStatus;
            
            if (confirm('Are you sure you want to ' + action + ' member: ' + userName + '?')) {
                $('#statusUserId').val(userId);
                $('#statusValue').val(newStatus);
                $('#statusForm').submit();
            }
        }

        function confirmDelete(userId, userName) {
            if (confirm('Are you sure you want to delete member: ' + userName + '?\n\nThis action cannot be undone.')) {
                $('#deleteUserId').val(userId);
                $('#deleteForm').submit();
            }
        }

        function viewMemberImage(imageUrl, memberName) {
            $('#viewImageSrc').attr('src', imageUrl);
            $('#viewImageTitle').text(memberName + ' - Profile Photo');
            $('#viewImageModal').removeClass('hidden');
            // Prevent body scroll when modal is open
            $('body').css('overflow', 'hidden');
        }

        function closeImageViewModal() {
            $('#viewImageModal').addClass('hidden');
            // Restore body scroll
            $('body').css('overflow', 'auto');
        }

        // Close modal when clicking outside the image
        $(document).on('click', '#viewImageModal .image-modal-overlay', function(e) {
            if (e.target === this) {
                closeImageViewModal();
            }
        });

        // Close modal with Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && !$('#viewImageModal').hasClass('hidden')) {
                closeImageViewModal();
            }
        });

        // Close edit modal button handler
        $(document).on('click', '.btn-close-edit-modal', function() {
            closeEditModal();
        });

        // Close image modal button handler
        $(document).on('click', '.btn-close-image-modal', function() {
            closeImageViewModal();
        });

        // Edit button handler using data attributes
        $(document).on('click', '.edit-btn', function() {
            const $btn = $(this);
            openEditModal(
                $btn.data('user-id'),
                $btn.data('username'),
                $btn.data('full-name'),
                $btn.data('email'),
                $btn.data('contact-no'),
                $btn.data('gender'),
                $btn.data('date-of-birth')
            );
        });

        // Status change button handler using data attributes
        $(document).on('click', '.action-btn[data-action="status"]', function() {
            const $btn = $(this);
            confirmStatusChange(
                $btn.data('user-id'),
                $btn.data('user-name'),
                $btn.data('status')
            );
        });

        // Delete button handler using data attributes
        $(document).on('click', '.action-btn[data-action="delete"]', function() {
            const $btn = $(this);
            confirmDelete(
                $btn.data('user-id'),
                $btn.data('user-name')
            );
        });

        // View member image handler using data attributes
        $(document).on('click', '.clickable-image', function() {
            const $img = $(this);
            viewMemberImage(
                $img.data('image-url'),
                $img.data('member-name')
            );
        });
    </script>

    <!-- Edit Modal -->
    <div id="editModal" class="modal-overlay hidden">
        <div class="modal-content">
            <div class="modal-body">
                <h3 class="modal-title">Edit Member</h3>

                <form id="editForm" method="POST" action="MemberController.php" class="modal-form">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" id="editUserId">

                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="editUsername" readonly
                            class="form-input form-input-readonly"
                            title="Username cannot be changed">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="editFullName" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="editEmail" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact_no" id="editContactNo" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" id="editGender" class="form-input">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="DateOfBirth" id="editDateOfBirth" class="form-input">
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary btn-close-edit-modal">
                            <span class="material-symbols-outlined">close</span>
                            <span>Cancel</span>
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Viewer Modal -->
    <div id="viewImageModal" class="image-modal-overlay hidden">
        <div class="image-modal-container">
            <div class="image-modal-header">
                <h3 id="viewImageTitle" class="image-modal-title">Profile Photo</h3>
                <button class="image-modal-close btn-close-image-modal" title="Close">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="image-modal-body">
                <img id="viewImageSrc" src="" alt="Member profile photo" class="image-modal-image">
            </div>
        </div>
    </div>

</body>

</html>