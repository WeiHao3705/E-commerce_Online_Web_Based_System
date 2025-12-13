<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$prefix = '../../';

$currentFileDir = dirname(__FILE__);
$webRootDir = dirname(dirname($currentFileDir));
$projectRoot = dirname($webRootDir);

$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$imageBasePath = str_replace('\\', '/', $relativePath) . '/';
$cssBasePath = $imageBasePath . 'css/';

if (!isset($_SESSION['user']) || $_SESSION['user']->role !== 'admin') {
    header('Location: ../security/LoginForm.php');
    exit;
}

$pageTitle = 'All Admins - Admin Dashboard';

$currentSortBy = isset($currentSort['sortBy']) ? $currentSort['sortBy'] : 'created_at';
$currentSortOrder = isset($currentSort['sortOrder']) ? $currentSort['sortOrder'] : 'DESC';

function getSortUrl($column, $currentSortBy, $currentSortOrder) {
    $params = ['action' => 'showAll'];

    if (!empty($_GET['search'])) {
        $params['search'] = $_GET['search'];
    }

    if (!empty($_GET['page'])) {
        $params['page'] = $_GET['page'];
    }

    if ($currentSortBy === $column && $currentSortOrder === 'ASC') {
        $params['sortBy'] = $column;
        $params['sortOrder'] = 'DESC';
    } else {
        $params['sortBy'] = $column;
        $params['sortOrder'] = 'ASC';
    }

    return 'AdminController.php?' . http_build_query($params);
}

function getSortArrow($column, $currentSortBy, $currentSortOrder) {
    if ($currentSortBy !== $column) {
        return '<span class="material-symbols-outlined sort-icon-neutral">unfold_more</span>';
    }

    if ($currentSortOrder === 'ASC') {
        return '<span class="material-symbols-outlined sort-icon-active">arrow_upward</span>';
    }

    return '<span class="material-symbols-outlined sort-icon-active">arrow_downward</span>';
}

function getProfilePhotoUrl($photoPath, $imageBasePath) {
    if (empty($photoPath) || $photoPath === null || trim($photoPath) === '') {
        return $imageBasePath . 'images/defaultUserImage.jpg';
    }

    if (strpos($photoPath, 'http://') === 0 || strpos($photoPath, 'https://') === 0) {
        return $photoPath;
    }

    if (strpos($photoPath, 'web/') === 0) {
        $photoPath = substr($photoPath, 4);
    }

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
    <style>
        /* Compact, two-column form layout for the Add Admin modal */
        .modal-content.narrow {
            max-width: 720px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px 20px;
            margin-bottom: 8px;
        }
        .form-hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        .form-actions.sticky {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            margin-top: 8px;
        }
    </style>
</head>

<body class="page-body">

    <div class="page-container">
        <div class="page-content">
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

            <div class="content-card">
                <div class="card-header">
                    <h2 class="card-title">All Admins</h2>

                    <div class="toolbar" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between;">
                        <div class="search-section" style="flex: 1 1 320px;">
                            <form method="GET" action="AdminController.php" class="search-form" style="display: flex; gap: 8px; align-items: center;">
                                <input type="hidden" name="action" value="showAll">
                                <?php if (!empty($_GET['sortBy'])): ?>
                                    <input type="hidden" name="sortBy" value="<?php echo htmlspecialchars($_GET['sortBy']); ?>">
                                <?php endif; ?>
                                <?php if (!empty($_GET['sortOrder'])): ?>
                                    <input type="hidden" name="sortOrder" value="<?php echo htmlspecialchars($_GET['sortOrder']); ?>">
                                <?php endif; ?>
                                <label class="sr-only" for="simple-search">Search</label>
                                <div class="search-input-wrapper" style="flex: 1 1 auto;">
                                    <input
                                        class="search-input"
                                        id="simple-search"
                                        name="search"
                                        placeholder="Search for admins..."
                                        type="text"
                                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
                                </div>
                                <button type="submit" class="btn btn-primary btn-search">
                                    <span class="material-symbols-outlined">search</span>
                                    <span>Search</span>
                                </button>
                            </form>
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <button type="button" class="btn btn-primary" id="openAddAdminBtn">
                                <span class="material-symbols-outlined">add</span>
                                <span>Add Admin</span>
                            </button>
                        </div>
                        <div class="bulk-actions-section" style="margin-bottom: 0; display: none; width: 100%;" id="bulkActionsSection">
                            <button type="button" class="btn btn-danger" id="bulkDeleteBtn" style="margin-right: 0.5rem;">
                                <span class="material-symbols-outlined">delete</span>
                                <span>Delete Selected (<span id="selectedCount">0</span>)</span>
                            </button>
                            <button type="button" class="btn btn-secondary" id="clearSelectionBtn">
                                <span class="material-symbols-outlined">close</span>
                                <span>Clear Selection</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="table-wrapper" id="admins-table-wrapper">
                    <table class="members-table" id="admins-table">
                        <thead>
                            <tr>
                                <th class="col-checkbox" style="width: 40px;">
                                    <input type="checkbox" id="selectAllCheckbox" title="Select all">
                                </th>
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
                            <?php if (!empty($admins)): ?>
                                <?php foreach ($admins as $admin): ?>
                                    <tr class="table-row">
                                        <td class="col-checkbox">
                                            <input type="checkbox" class="admin-checkbox" name="admin_ids[]" value="<?php echo $admin['user_id']; ?>" data-admin-name="<?php echo htmlspecialchars($admin['full_name'], ENT_QUOTES); ?>">
                                        </td>
                                        <td class="col-photo">
                                            <?php
                                            $photoUrl = getProfilePhotoUrl($admin['profile_photo'] ?? '', $imageBasePath);
                                            $defaultPhotoUrl = $imageBasePath . 'images/defaultUserImage.jpg';
                                            ?>
                                            <img src="<?php echo htmlspecialchars($photoUrl); ?>"
                                                 alt="Profile photo"
                                                 class="member-profile-photo clickable-image"
                                                 data-image-url="<?php echo htmlspecialchars($photoUrl, ENT_QUOTES); ?>"
                                                 data-admin-name="<?php echo htmlspecialchars($admin['full_name'], ENT_QUOTES); ?>"
                                                 onerror="this.onerror=null; this.src='<?php echo htmlspecialchars($defaultPhotoUrl); ?>';"
                                                 style="cursor: pointer;"
                                                 title="Click to view full size">
                                        </td>
                                        <td class="col-username">
                                            <?php echo htmlspecialchars($admin['username']); ?>
                                        </td>
                                        <td class="col-name"><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                        <td class="col-email"><?php echo htmlspecialchars($admin['email']); ?></td>
                                        <td class="col-contact"><?php echo htmlspecialchars($admin['contact_no']); ?></td>
                                        <td class="col-gender"><?php echo htmlspecialchars($admin['gender']); ?></td>
                                        <td class="col-date">
                                            <?php
                                            $date = new DateTime($admin['created_at']);
                                            echo $date->format('Y-m-d');
                                            ?>
                                        </td>
                                        <td class="col-status">
                                            <?php
                                            $status = $admin['status'] ?? 'active';
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
                                                data-user-id="<?php echo $admin['user_id']; ?>"
                                                data-username="<?php echo htmlspecialchars($admin['username'], ENT_QUOTES); ?>"
                                                data-full-name="<?php echo htmlspecialchars($admin['full_name'], ENT_QUOTES); ?>"
                                                data-email="<?php echo htmlspecialchars($admin['email'], ENT_QUOTES); ?>"
                                                data-contact-no="<?php echo htmlspecialchars($admin['contact_no'], ENT_QUOTES); ?>"
                                                data-gender="<?php echo htmlspecialchars($admin['gender'], ENT_QUOTES); ?>"
                                                title="Edit admin">
                                                <span class="material-symbols-outlined">edit</span>
                                            </button>

                                            <?php $currentStatus = $admin['status'] ?? 'active'; ?>
                                            <?php if ($currentStatus !== 'banned'): ?>
                                                <button
                                                    class="action-btn ban-btn"
                                                    data-action="status"
                                                    data-user-id="<?php echo $admin['user_id']; ?>"
                                                    data-admin-name="<?php echo htmlspecialchars($admin['full_name'], ENT_QUOTES); ?>"
                                                    data-status="banned"
                                                    title="Ban admin">
                                                    <span class="material-symbols-outlined">block</span>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($currentStatus !== 'inactive'): ?>
                                                <button
                                                    class="action-btn inactive-btn"
                                                    data-action="status"
                                                    data-user-id="<?php echo $admin['user_id']; ?>"
                                                    data-admin-name="<?php echo htmlspecialchars($admin['full_name'], ENT_QUOTES); ?>"
                                                    data-status="inactive"
                                                    title="Set to inactive">
                                                    <span class="material-symbols-outlined">pause_circle</span>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($currentStatus !== 'active'): ?>
                                                <button
                                                    class="action-btn activate-btn"
                                                    data-action="status"
                                                    data-user-id="<?php echo $admin['user_id']; ?>"
                                                    data-admin-name="<?php echo htmlspecialchars($admin['full_name'], ENT_QUOTES); ?>"
                                                    data-status="active"
                                                    title="Activate admin">
                                                    <span class="material-symbols-outlined">check_circle</span>
                                                </button>
                                            <?php endif; ?>

                                            <button
                                                class="action-btn delete-btn"
                                                data-action="delete"
                                                data-user-id="<?php echo $admin['user_id']; ?>"
                                                data-admin-name="<?php echo htmlspecialchars($admin['full_name'], ENT_QUOTES); ?>"
                                                title="Delete admin">
                                                <span class="material-symbols-outlined">delete</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="table-row table-row-empty">
                                    <td colspan="10" class="col-empty">
                                        No admins found. <?php echo !empty($_GET['search']) ? 'Try a different search term.' : ''; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($admins)): ?>
                    <nav class="pagination" aria-label="Table navigation">
                        <span class="pagination-info">
                            Showing
                            <span class="pagination-number"><?php echo $pagination['showing_from']; ?>-<?php echo $pagination['showing_to']; ?></span>
                            of
                            <span class="pagination-number"><?php echo $pagination['total_admins']; ?></span>
                        </span>
                        <ul class="pagination-list">
                            <li>
                                <?php
                                $prevParams = ['action' => 'showAll', 'page' => $pagination['current_page'] - 1];
                                if (!empty($_GET['search'])) $prevParams['search'] = $_GET['search'];
                                if (!empty($_GET['sortBy'])) $prevParams['sortBy'] = $_GET['sortBy'];
                                if (!empty($_GET['sortOrder'])) $prevParams['sortOrder'] = $_GET['sortOrder'];
                                $prevUrl = 'AdminController.php?' . http_build_query($prevParams);
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

                            <?php
                            $startPage = max(1, $pagination['current_page'] - 2);
                            $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);

                            for ($i = $startPage; $i <= $endPage; $i++):
                                $pageParams = ['action' => 'showAll', 'page' => $i];
                                if (!empty($_GET['search'])) $pageParams['search'] = $_GET['search'];
                                if (!empty($_GET['sortBy'])) $pageParams['sortBy'] = $_GET['sortBy'];
                                if (!empty($_GET['sortOrder'])) $pageParams['sortOrder'] = $_GET['sortOrder'];
                                $pageUrl = 'AdminController.php?' . http_build_query($pageParams);
                            ?>
                                <li>
                                    <a href="<?php echo $pageUrl; ?>" class="pagination-link <?php echo $i == $pagination['current_page'] ? 'pagination-active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <li>
                                <?php
                                $nextParams = ['action' => 'showAll', 'page' => $pagination['current_page'] + 1];
                                if (!empty($_GET['search'])) $nextParams['search'] = $_GET['search'];
                                if (!empty($_GET['sortBy'])) $nextParams['sortBy'] = $_GET['sortBy'];
                                if (!empty($_GET['sortOrder'])) $nextParams['sortOrder'] = $_GET['sortOrder'];
                                $nextUrl = 'AdminController.php?' . http_build_query($nextParams);
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

    <form id="statusForm" method="POST" action="AdminController.php" style="display: none;">
        <input type="hidden" name="action" value="updateStatus">
        <input type="hidden" name="user_id" id="statusUserId">
        <input type="hidden" name="status" id="statusValue">
    </form>

    <form id="deleteForm" method="POST" action="AdminController.php" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="deleteUserId">
    </form>

    <form id="bulkDeleteForm" method="POST" action="AdminController.php" style="display: none;">
        <input type="hidden" name="action" value="bulkDelete">
    </form>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const imageBasePath = '<?php echo $imageBasePath; ?>';
        let searchTimeout;
        const searchInput = $('#simple-search');
        const searchForm = $('.search-form');

        searchForm.on('submit', function(e) {
            e.preventDefault();
            performSearch();
        });

        searchInput.on('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val();
            if (!searchTerm || searchTerm.trim() === '') {
                performSearch();
            } else {
                searchTimeout = setTimeout(function() {
                    performSearch();
                }, 500);
            }
        });

        function performSearch() {
            const searchTerm = searchInput.val() || '';
            const trimmedSearch = searchTerm.trim();
            const sortBy = $('input[name="sortBy"]').val() || 'created_at';
            const sortOrder = $('input[name="sortOrder"]').val() || 'DESC';

            const tableWrapper = $('#admins-table-wrapper');
            tableWrapper.css('opacity', '0.6');

            const requestData = {
                action: 'showAll',
                ajax: '1',
                search: trimmedSearch,
                sortBy: sortBy,
                sortOrder: sortOrder,
                page: 1
            };

            $.ajax({
                url: 'AdminController.php',
                method: 'GET',
                data: requestData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        updateTable(response);
                        updatePagination(response);
                    } else {
                        alert('Error: ' + response.error);
                    }
                    tableWrapper.css('opacity', '1');
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.error('Response:', xhr.responseText);
                    alert('An error occurred while searching. Please try again.');
                    tableWrapper.css('opacity', '1');
                }
            });
        }

        function updateTable(response) {
            const tbody = $('#admins-table tbody');
            tbody.empty();
            selectedAdmins.clear();
            $('#selectAllCheckbox').prop('checked', false);
            updateBulkActions();

            if (response.admins && response.admins.length > 0) {
                response.admins.forEach(function(admin) {
                    const row = buildAdminRow(admin);
                    tbody.append(row);
                });
            } else {
                tbody.append('<tr class="table-row table-row-empty"><td colspan="10" class="col-empty">No admins found. Try a different search term.</td></tr>');
            }
        }

        function buildAdminRow(admin) {
            let photoUrl = '';
            if (admin.profile_photo && admin.profile_photo.trim() !== '') {
                let photoPath = admin.profile_photo;
                if (photoPath.indexOf('web/') === 0) {
                    photoPath = photoPath.substring(4);
                }
                photoPath = photoPath.replace(/^\/+/,'');
                photoUrl = imageBasePath + photoPath;
            } else {
                photoUrl = imageBasePath + 'images/defaultUserImage.jpg';
            }
            const defaultPhotoUrl = imageBasePath + 'images/defaultUserImage.jpg';

            let createdDateDisplay = '-';
            if (admin.created_at) {
                const createdDate = new Date(admin.created_at);
                if (!isNaN(createdDate.getTime())) {
                    createdDateDisplay = createdDate.toISOString().split('T')[0];
                }
            }

            const status = admin.status || 'active';
            const statusLabels = {
                'active': { class: 'status-active', text: 'Active' },
                'inactive': { class: 'status-inactive', text: 'Inactive' },
                'banned': { class: 'status-banned', text: 'Banned' }
            };
            const statusInfo = statusLabels[status] || statusLabels['active'];

            let statusButtons = '';
            if (status !== 'banned') {
                statusButtons += '<button class="action-btn ban-btn" data-action="status" data-user-id="' + admin.user_id + '" data-admin-name="' + escapeHtml(admin.full_name) + '" data-status="banned" title="Ban admin"><span class="material-symbols-outlined">block</span></button>';
            }
            if (status !== 'inactive') {
                statusButtons += '<button class="action-btn inactive-btn" data-action="status" data-user-id="' + admin.user_id + '" data-admin-name="' + escapeHtml(admin.full_name) + '" data-status="inactive" title="Set to inactive"><span class="material-symbols-outlined">pause_circle</span></button>';
            }
            if (status !== 'active') {
                statusButtons += '<button class="action-btn activate-btn" data-action="status" data-user-id="' + admin.user_id + '" data-admin-name="' + escapeHtml(admin.full_name) + '" data-status="active" title="Activate admin"><span class="material-symbols-outlined">check_circle</span></button>';
            }

            const row = `
                <tr class="table-row">
                    <td class="col-checkbox">
                        <input type="checkbox" class="admin-checkbox" name="admin_ids[]" value="${admin.user_id}" data-admin-name="${escapeHtml(admin.full_name)}">
                    </td>
                    <td class="col-photo">
                        <img src="${escapeHtml(photoUrl)}"
                             alt="Profile photo"
                             class="member-profile-photo clickable-image"
                             data-image-url="${escapeHtml(photoUrl)}"
                             data-admin-name="${escapeHtml(admin.full_name)}"
                             onerror="this.onerror=null; this.src='${escapeHtml(defaultPhotoUrl)}';"
                             style="cursor: pointer;"
                             title="Click to view full size">
                    </td>
                    <td class="col-username">${escapeHtml(admin.username)}</td>
                    <td class="col-name">${escapeHtml(admin.full_name)}</td>
                    <td class="col-email">${escapeHtml(admin.email)}</td>
                    <td class="col-contact">${escapeHtml(admin.contact_no)}</td>
                    <td class="col-gender">${escapeHtml(admin.gender)}</td>
                    <td class="col-date">${createdDateDisplay}</td>
                    <td class="col-status">
                        <span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>
                    </td>
                    <td class="col-actions">
                        <button class="action-btn edit-btn" data-user-id="${admin.user_id}" data-username="${escapeHtml(admin.username)}" data-full-name="${escapeHtml(admin.full_name)}" data-email="${escapeHtml(admin.email)}" data-contact-no="${escapeHtml(admin.contact_no)}" data-gender="${escapeHtml(admin.gender)}" title="Edit admin">
                            <span class="material-symbols-outlined">edit</span>
                        </button>
                        ${statusButtons}
                        <button class="action-btn delete-btn" data-action="delete" data-user-id="${admin.user_id}" data-admin-name="${escapeHtml(admin.full_name)}" title="Delete admin">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </td>
                </tr>
            `;
            return row;
        }

        function updatePagination(response) {
            const pagination = response.pagination;
            const paginationNav = $('.pagination');

            if (pagination.total_admins > 0) {
                $('.pagination-info').html(`
                    Showing <span class="pagination-number">${pagination.showing_from}-${pagination.showing_to}</span> of <span class="pagination-number">${pagination.total_admins}</span>
                `);

                const paginationList = $('.pagination-list');
                paginationList.empty();

                const prevUrl = pagination.current_page > 1 ?
                    `AdminController.php?action=showAll&page=${pagination.current_page - 1}&search=${encodeURIComponent($('#simple-search').val())}&sortBy=${response.sortBy}&sortOrder=${response.sortOrder}` : '#';
                paginationList.append(`
                    <li>
                        ${pagination.current_page > 1 ?
                            `<a href="${prevUrl}" class="pagination-link pagination-prev"><span class="material-symbols-outlined">chevron_left</span></a>` :
                            `<span class="pagination-link pagination-prev pagination-disabled"><span class="material-symbols-outlined">chevron_left</span></span>`
                        }
                    </li>
                `);

                const startPage = Math.max(1, pagination.current_page - 2);
                const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

                for (let i = startPage; i <= endPage; i++) {
                    const activeClass = i === pagination.current_page ? 'pagination-active' : '';
                    const pageUrl = `AdminController.php?action=showAll&page=${i}&search=${encodeURIComponent($('#simple-search').val())}&sortBy=${response.sortBy}&sortOrder=${response.sortOrder}`;
                    paginationList.append(`
                        <li>
                            <a href="${pageUrl}" class="pagination-link ${activeClass}">${i}</a>
                        </li>
                    `);
                }

                const nextUrl = pagination.current_page < pagination.total_pages ?
                    `AdminController.php?action=showAll&page=${pagination.current_page + 1}&search=${encodeURIComponent($('#simple-search').val())}&sortBy=${response.sortBy}&sortOrder=${response.sortOrder}` : '#';
                paginationList.append(`
                    <li>
                        ${pagination.current_page < pagination.total_pages ?
                            `<a href="${nextUrl}" class="pagination-link pagination-next"><span class="material-symbols-outlined">chevron_right</span></a>` :
                            `<span class="pagination-link pagination-next pagination-disabled"><span class="material-symbols-outlined">chevron_right</span></span>`
                        }
                    </li>
                `);

                paginationNav.show();
            } else {
                paginationNav.hide();
            }
        }

        function escapeHtml(text) {
            if (text === null || text === undefined) {
                return '';
            }
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        function openEditModal(userId, username, fullName, email, contactNo, gender) {
            $('#editUserId').val(userId);
            $('#editUsername').val(username);
            $('#editFullName').val(fullName);
            $('#editEmail').val(email);
            $('#editContactNo').val(contactNo);
            $('#editGender').val(gender);
            $('#editModal').removeClass('hidden');
        }

        function closeEditModal() {
            $('#editModal').addClass('hidden');
        }

        function confirmStatusChange(userId, adminName, newStatus) {
            const statusLabels = {
                'active': 'activate',
                'inactive': 'set to inactive',
                'banned': 'ban'
            };
            const action = statusLabels[newStatus] || newStatus;

            if (confirm('Are you sure you want to ' + action + ' admin: ' + adminName + '?')) {
                $('#statusUserId').val(userId);
                $('#statusValue').val(newStatus);
                $('#statusForm').submit();
            }
        }

        function confirmDelete(userId, adminName) {
            if (confirm('Are you sure you want to delete admin: ' + adminName + '?\n\nThis action cannot be undone.')) {
                $('#deleteUserId').val(userId);
                $('#deleteForm').submit();
            }
        }

        function viewAdminImage(imageUrl, adminName) {
            $('#viewImageSrc').attr('src', imageUrl);
            $('#viewImageTitle').text(adminName + ' - Profile Photo');
            $('#viewImageModal').removeClass('hidden');
            $('body').css('overflow', 'hidden');
        }

        function closeImageViewModal() {
            $('#viewImageModal').addClass('hidden');
            $('body').css('overflow', 'auto');
        }

        $(document).on('click', '#viewImageModal .image-modal-overlay', function(e) {
            if (e.target === this) {
                closeImageViewModal();
            }
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && !$('#viewImageModal').hasClass('hidden')) {
                closeImageViewModal();
            }
        });

        $(document).on('click', '.btn-close-edit-modal', function() {
            closeEditModal();
        });

        $(document).on('click', '.btn-close-image-modal', function() {
            closeImageViewModal();
        });

        $(document).on('click', '.edit-btn', function() {
            const $btn = $(this);
            openEditModal(
                $btn.data('user-id'),
                $btn.data('username'),
                $btn.data('full-name'),
                $btn.data('email'),
                $btn.data('contact-no'),
                $btn.data('gender')
            );
        });

        $(document).on('click', '.action-btn[data-action="status"]', function() {
            const $btn = $(this);
            confirmStatusChange(
                $btn.data('user-id'),
                $btn.data('admin-name'),
                $btn.data('status')
            );
        });

        $(document).on('click', '.action-btn[data-action="delete"]', function() {
            const $btn = $(this);
            confirmDelete(
                $btn.data('user-id'),
                $btn.data('admin-name')
            );
        });

        $(document).on('click', '.clickable-image', function() {
            const $img = $(this);
            viewAdminImage(
                $img.data('image-url'),
                $img.data('admin-name')
            );
        });

        let selectedAdmins = new Set();

        $('#selectAllCheckbox').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.admin-checkbox').prop('checked', isChecked);

            if (isChecked) {
                $('.admin-checkbox').each(function() {
                    selectedAdmins.add($(this).val());
                });
            } else {
                selectedAdmins.clear();
            }

            updateBulkActions();
        });

        $(document).on('change', '.admin-checkbox', function() {
            const adminId = $(this).val();
            if ($(this).is(':checked')) {
                selectedAdmins.add(adminId);
            } else {
                selectedAdmins.delete(adminId);
                $('#selectAllCheckbox').prop('checked', false);
            }
            updateBulkActions();
        });

        function updateBulkActions() {
            const count = selectedAdmins.size;
            $('#selectedCount').text(count);

            if (count > 0) {
                $('#bulkActionsSection').show();
            } else {
                $('#bulkActionsSection').hide();
            }
        }

        $('#clearSelectionBtn').on('click', function() {
            $('.admin-checkbox').prop('checked', false);
            $('#selectAllCheckbox').prop('checked', false);
            selectedAdmins.clear();
            updateBulkActions();
        });

        $('#bulkDeleteBtn').on('click', function() {
            const count = selectedAdmins.size;
            if (count === 0) {
                alert('Please select at least one admin to delete.');
                return;
            }

            if (confirm(`Are you sure you want to delete ${count} admin(s)?\n\nThis action cannot be undone.`)) {
                $('#bulkDeleteForm input[name="user_ids[]"]').remove();

                selectedAdmins.forEach(function(adminId) {
                    $('#bulkDeleteForm').append(`<input type="hidden" name="user_ids[]" value="${adminId}">`);
                });

                $('#bulkDeleteForm').submit();
            }
        });

        // Add Admin password validation (match Member requirements)
        $('#addAdminForm').on('submit', function(e) {
            const pwd = $('#addAdminPassword').val() || '';
            const repeat = $('#addAdminRepeatPassword').val() || '';

            const lengthOk = pwd.length >= 8;
            const hasUpper = /[A-Z]/.test(pwd);
            const hasLower = /[a-z]/.test(pwd);
            const hasNumber = /[0-9]/.test(pwd);
            const hasSpecial = /[!@#$%^&*]/.test(pwd);

            if (!lengthOk || !hasUpper || !hasLower || !hasNumber || !hasSpecial) {
                e.preventDefault();
                alert('Password must be at least 8 characters and include uppercase, lowercase, number, and special character (!@#$%^&*).');
                return;
            }

            if (pwd !== repeat) {
                e.preventDefault();
                alert('Passwords do not match.');
            }
        });

        // Add admin modal
        $('#openAddAdminBtn').on('click', function() {
            const form = $('#addAdminForm');
            form[0].reset();
            $('#addAdminModal').removeClass('hidden');
        });

        $(document).on('click', '.btn-close-add-modal', function() {
            $('#addAdminModal').addClass('hidden');
        });
    </script>

    <!-- Add Admin Modal -->
    <div id="addAdminModal" class="modal-overlay hidden">
        <div class="modal-content narrow">
            <div class="modal-body">
                <h3 class="modal-title">Add Admin</h3>

                <form id="addAdminForm" method="POST" action="AdminController.php" class="modal-form">
                    <input type="hidden" name="action" value="create">

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-input" placeholder="e.g. jane.admin" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-input" placeholder="Jane Admin" required />
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input" placeholder="jane@example.com" required />
                            <p class="form-hint">Will be marked verified automatically.</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_no" class="form-input" placeholder="000-000 0000" />
                        </div>

                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-input">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" id="addAdminPassword" name="password" class="form-input" placeholder="8+ chars, upper, lower, number, special" required />
                            <p class="form-hint">Must include uppercase, lowercase, number, and special (!@#$%^&*).</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Repeat Password</label>
                            <input type="password" id="addAdminRepeatPassword" name="repeat_password" class="form-input" placeholder="Repeat password" required />
                        </div>
                    </div>

                    <div class="form-actions sticky">
                        <button type="button" class="btn btn-secondary btn-close-add-modal">
                            <span class="material-symbols-outlined">close</span>
                            <span>Cancel</span>
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined">save</span>
                            <span>Create Admin</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="editModal" class="modal-overlay hidden">
        <div class="modal-content">
            <div class="modal-body">
                <h3 class="modal-title">Edit Admin</h3>

                <form id="editForm" method="POST" action="AdminController.php" class="modal-form">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" id="editUserId">

                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="editUsername" readonly
                            class="form-input form-input-readonly"
                            title="Username cannot be changed"/>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="editFullName" class="form-input"/>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="editEmail" class="form-input"/>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Contact Number</label>
                        <input type="text" name="contact_no" id="editContactNo" class="form-input"/>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" id="editGender" class="form-input">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
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

    <div id="viewImageModal" class="image-modal-overlay hidden">
        <div class="image-modal-container">
            <div class="image-modal-header">
                <h3 id="viewImageTitle" class="image-modal-title">Profile Photo</h3>
                <button class="image-modal-close btn-close-image-modal" title="Close">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="image-modal-body">
                <img id="viewImageSrc" src="" alt="Admin profile photo" class="image-modal-image">
            </div>
        </div>
    </div>

</body>

</html>
