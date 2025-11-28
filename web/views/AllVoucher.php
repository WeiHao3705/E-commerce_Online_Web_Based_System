<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// // Check if user is logged in
// if (!isset($_SESSION['user'])) {
//     // Not logged in, redirect to login page
//     header('Location: ../views/LoginForm.php');
//     exit;
// }

// // Check if user role is admin
// if ($_SESSION['user']['role'] !== 'admin') {
//     // Logged in but not admin, redirect to 403 Forbidden page or homepage
//     header('Location: ../views/403.php');
//     exit;
// }

$prefix = '../';

// Calculate base path for images (absolute from document root)
// Since this file is in web/views/, go up one level to get web root
$currentFileDir = dirname(__FILE__); // Gets web/views/
$webRootDir = dirname($currentFileDir); // Gets web/
$projectRoot = dirname($webRootDir); // Gets project root

// Get the relative path from document root
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$imageBasePath = str_replace('\\', '/', $relativePath) . '/'; // Normalize slashes

$pageTitle = 'All Vouchers - Admin Dashboard';

// Get current sort parameters
$currentSortBy = isset($currentSort['sortBy']) ? $currentSort['sortBy'] : 'voucher_id';
$currentSortOrder = isset($currentSort['sortOrder']) ? $currentSort['sortOrder'] : 'DESC';

// Helper function to generate sort URL
function getSortUrl($column, $currentSortBy, $currentSortOrder)
{
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

    return 'VoucherController.php?' . http_build_query($params);
}

// Helper function to get sort arrow icon
function getSortArrow($column, $currentSortBy, $currentSortOrder)
{
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

// Helper function to format discount value
function formatDiscountValue($type, $discountValue, $maxDiscount = null)
{
    switch ($type) {
        case 'percent':
            $formatted = $discountValue . '%';
            if ($maxDiscount !== null && $maxDiscount > 0) {
                $formatted .= ' (max: RM' . number_format($maxDiscount, 2) . ')';
            }
            return $formatted;
        case 'fixed':
            return 'RM' . number_format($discountValue, 2);
        case 'freeshipping':
            return 'Free Shipping';
        default:
            return number_format($discountValue, 2);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - REDSTORE</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $prefix; ?>css/AllTables.css">
    <link rel="stylesheet" href="<?php echo $prefix; ?>css/AllVouchers.css">
</head>

<body class="page-body">

    <?php include $prefix . 'general/_header.php'; ?>
    <?php include $prefix . 'general/_navbar.php'; ?>

    <div class="page-container">
        <div class="page-content">
            <!-- Header -->
            <header class="page-header">
                <div class="header-logo">
                    <svg class="logo-svg" fill="none" viewBox="0 0 162 42" xmlns="http://www.w3.org/2000/svg">
                        <text fill="#FF523B" font-family="Poppins, sans-serif" font-size="28" font-weight="bold" letter-spacing="0em" style="white-space: pre" xml:space="preserve">
                            <tspan x="0" y="29.9219">REDSTORE</tspan>
                        </text>
                        <text class="logo-subtitle" fill="#555" font-family="Poppins, sans-serif" font-size="8" font-style="italic" letter-spacing="0.05em" style="white-space: pre" xml:space="preserve">
                            <tspan x="100" y="38">athlete's choice</tspan>
                        </text>
                        <rect height="42" rx="4" stroke="#FF523B" stroke-width="2" width="95" x="0" y="0"></rect>
                    </svg>
                </div>
                <div class="header-title">
                    <h1 class="page-title">Admin Dashboard</h1>
                    <p class="page-subtitle">Manage Vouchers</p>
                </div>
            </header>

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
                    <h2 class="card-title">All Vouchers</h2>

                    <!-- Search and Actions Bar -->
                    <div class="toolbar">
                        <div class="search-section">
                            <form method="GET" action="VoucherController.php" class="search-form">
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
                                        placeholder="Search for vouchers..."
                                        type="text"
                                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
                                </div>
                                <button type="submit" class="btn btn-primary btn-search">
                                    Search
                                </button>
                            </form>
                        </div>
                        <div class="actions-section">
                            <?php
                            // Calculate the path to VoucherRegisterForm.php
                            // Since AllVoucher.php is in web/views/ and accessed via controller
                            // We need to go from controller location to views location
                            $currentScript = $_SERVER['SCRIPT_NAME']; // e.g., /E-commerce_Online_Web_Based_System/web/controller/VoucherController.php
                            $basePath = dirname(dirname($currentScript)); // Gets to /E-commerce_Online_Web_Based_System/web/
                            $voucherFormUrl = $basePath . '/views/VoucherRegisterForm.php?return_to=admin';
                            ?>
                            <a href="<?php echo $voucherFormUrl; ?>" class="btn btn-primary btn-add">
                                <span class="material-symbols-outlined">add</span>
                                Add new voucher
                            </a>
                        </div>
                    </div>
                </div>

                <div class="table-wrapper" id="vouchers-table-wrapper">
                    <table class="members-table vouchers-table" id="vouchers-table">
                        <thead>
                            <tr>
                                <th class="col-sortable">
                                    <a href="<?php echo getSortUrl('code', $currentSortBy, $currentSortOrder); ?>" class="sort-link">
                                        <span>Code</span>
                                        <?php echo getSortArrow('code', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="col-sortable">
                                    <a href="<?php echo getSortUrl('description', $currentSortBy, $currentSortOrder); ?>" class="sort-link">
                                        <span>Description</span>
                                        <?php echo getSortArrow('description', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="col-sortable">
                                    <a href="<?php echo getSortUrl('type', $currentSortBy, $currentSortOrder); ?>" class="sort-link">
                                        <span>Type</span>
                                        <?php echo getSortArrow('type', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="col-sortable">
                                    <a href="<?php echo getSortUrl('discount_value', $currentSortBy, $currentSortOrder); ?>" class="sort-link">
                                        <span>Discount</span>
                                        <?php echo getSortArrow('discount_value', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="col-sortable">
                                    <a href="<?php echo getSortUrl('min_spend', $currentSortBy, $currentSortOrder); ?>" class="sort-link">
                                        <span>Min Spend</span>
                                        <?php echo getSortArrow('min_spend', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="col-sortable">
                                    <a href="<?php echo getSortUrl('start_date', $currentSortBy, $currentSortOrder); ?>" class="sort-link">
                                        <span>Start Date</span>
                                        <?php echo getSortArrow('start_date', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="col-sortable">
                                    <a href="<?php echo getSortUrl('end_date', $currentSortBy, $currentSortOrder); ?>" class="sort-link">
                                        <span>End Date</span>
                                        <?php echo getSortArrow('end_date', $currentSortBy, $currentSortOrder); ?>
                                    </a>
                                </th>
                                <th class="col-sortable col-voucher-status">
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
                            <?php if (!empty($vouchers)): ?>
                                <?php foreach ($vouchers as $voucher): ?>
                                    <tr class="table-row">
                                        <td class="col-username">
                                            <strong><?php echo htmlspecialchars($voucher['code']); ?></strong>
                                        </td>
                                        <td class="col-name">
                                            <?php echo htmlspecialchars($voucher['description'] ?? '-'); ?>
                                        </td>
                                        <td class="col-gender">
                                            <?php
                                            $typeLabels = [
                                                'percent' => 'Percent',
                                                'fixed' => 'Fixed',
                                                'freeshipping' => 'Free Shipping'
                                            ];
                                            echo htmlspecialchars($typeLabels[$voucher['type']] ?? ucfirst($voucher['type']));
                                            ?>
                                        </td>
                                        <td class="col-contact">
                                            <?php
                                            $maxDiscount = isset($voucher['max_discount']) ? $voucher['max_discount'] : null;
                                            echo formatDiscountValue($voucher['type'], $voucher['discount_value'], $maxDiscount);
                                            ?>
                                        </td>
                                        <td class="col-dob">
                                            <?php
                                            if (!empty($voucher['min_spend']) && $voucher['min_spend'] > 0) {
                                                echo 'RM' . number_format($voucher['min_spend'], 2);
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td class="col-date">
                                            <?php
                                            if (!empty($voucher['start_date'])) {
                                                $date = new DateTime($voucher['start_date']);
                                                echo $date->format('Y-m-d');
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td class="col-date">
                                            <?php
                                            if (!empty($voucher['end_date'])) {
                                                $date = new DateTime($voucher['end_date']);
                                                echo $date->format('Y-m-d');
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td class="col-status col-voucher-status">
                                            <?php
                                            $status = $voucher['status'] ?? 'active';
                                            $statusClass = '';
                                            $statusText = ucfirst($status);

                                            switch ($status) {
                                                case 'active':
                                                    $statusClass = 'status-badge status-active';
                                                    break;
                                                case 'inactive':
                                                    $statusClass = 'status-badge status-inactive';
                                                    break;
                                                case 'expired':
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
                                                onclick="openEditModal(<?php echo $voucher['voucher_id']; ?>, '<?php echo htmlspecialchars($voucher['code'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($voucher['description'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($voucher['type'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($voucher['discount_value'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($voucher['min_spend'] ?? '0', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($voucher['max_discount'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($voucher['start_date'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($voucher['end_date'], ENT_QUOTES); ?>')"
                                                class="action-btn edit-btn"
                                                title="Edit voucher">
                                                <span class="material-symbols-outlined">edit</span>
                                            </button>

                                            <?php
                                            $currentStatus = $voucher['status'] ?? 'active';
                                            ?>
                                            <?php if ($currentStatus !== 'inactive'): ?>
                                                <button
                                                    onclick="confirmStatusChange(<?php echo $voucher['voucher_id']; ?>, '<?php echo htmlspecialchars($voucher['code'], ENT_QUOTES); ?>', 'inactive')"
                                                    class="action-btn inactive-btn"
                                                    title="Set to inactive">
                                                    <i class="fas fa-pause-circle"></i>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($currentStatus !== 'active'): ?>
                                                <button
                                                    onclick="confirmStatusChange(<?php echo $voucher['voucher_id']; ?>, '<?php echo htmlspecialchars($voucher['code'], ENT_QUOTES); ?>', 'active')"
                                                    class="action-btn activate-btn"
                                                    title="Activate voucher">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            <?php endif; ?>

                                            <button
                                                onclick="confirmDelete(<?php echo $voucher['voucher_id']; ?>, '<?php echo htmlspecialchars($voucher['code'], ENT_QUOTES); ?>')"
                                                class="action-btn delete-btn"
                                                title="Delete voucher">
                                                <span class="material-symbols-outlined">delete</span>
                                            </button>
                                            
                                            <button
                                                onclick="openAssignModal(<?php echo $voucher['voucher_id']; ?>, '<?php echo htmlspecialchars($voucher['code'], ENT_QUOTES); ?>')"
                                                class="action-btn assign-btn"
                                                title="Assign voucher">
                                                <span class="material-symbols-outlined">send</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="table-row table-row-empty">
                                    <td colspan="9" class="col-empty">
                                        No vouchers found. <?php echo !empty($_GET['search']) ? 'Try a different search term.' : ''; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (!empty($vouchers)): ?>
                    <nav class="pagination" aria-label="Table navigation">
                        <span class="pagination-info">
                            Showing
                            <span class="pagination-number"><?php echo $pagination['showing_from']; ?>-<?php echo $pagination['showing_to']; ?></span>
                            of
                            <span class="pagination-number"><?php echo $pagination['total_vouchers']; ?></span>
                        </span>
                        <ul class="pagination-list">
                            <!-- Previous Button -->
                            <li>
                                <?php
                                $prevParams = ['action' => 'showAll', 'page' => $pagination['current_page'] - 1];
                                if (!empty($_GET['search'])) $prevParams['search'] = $_GET['search'];
                                if (!empty($_GET['sortBy'])) $prevParams['sortBy'] = $_GET['sortBy'];
                                if (!empty($_GET['sortOrder'])) $prevParams['sortOrder'] = $_GET['sortOrder'];
                                $prevUrl = 'VoucherController.php?' . http_build_query($prevParams);
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
                                $pageUrl = 'VoucherController.php?' . http_build_query($pageParams);
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
                                $nextUrl = 'VoucherController.php?' . http_build_query($nextParams);
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
    <form id="statusForm" method="POST" action="VoucherController.php" style="display: none;">
        <input type="hidden" name="action" value="updateStatus">
        <input type="hidden" name="voucher_id" id="statusVoucherId">
        <input type="hidden" name="status" id="statusValue">
    </form>

    <!-- Delete Confirmation Modal (Hidden Form) -->
    <form id="deleteForm" method="POST" action="VoucherController.php" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="voucher_id" id="deleteVoucherId">
    </form>

    <!-- Assign Voucher Form (Hidden) -->
    <form id="assignForm" method="POST" action="VoucherController.php" style="display: none;">
        <input type="hidden" name="action" value="assign">
        <input type="hidden" name="voucher_id" id="assignVoucherId">
        <input type="hidden" name="assignment_type" id="assignType">
        <!-- Member IDs will be added dynamically -->
    </form>

    <?php include __DIR__ . '/../general/_footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // AJAX Search functionality
        let searchTimeout;
        const searchInput = $('#simple-search');
        const searchForm = $('.search-form');
        
        // Prevent form submission on Enter key
        searchForm.on('submit', function(e) {
            e.preventDefault();
            performSearch();
        });

        // AJAX search on input with debouncing
        searchInput.on('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = $(this).val();
            
            // Debounce: wait 500ms after user stops typing
            searchTimeout = setTimeout(function() {
                performSearch();
            }, 500);
        });

        function performSearch() {
            const searchTerm = searchInput.val();
            const sortBy = $('input[name="sortBy"]').val() || 'voucher_id';
            const sortOrder = $('input[name="sortOrder"]').val() || 'DESC';
            
            // Show loading indicator
            const tableWrapper = $('#vouchers-table-wrapper');
            tableWrapper.css('opacity', '0.6');
            
            // Make AJAX request
            $.ajax({
                url: 'VoucherController.php',
                method: 'GET',
                data: {
                    action: 'showAll',
                    ajax: '1',
                    search: searchTerm,
                    sortBy: sortBy,
                    sortOrder: sortOrder,
                    page: 1
                },
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
                    alert('An error occurred while searching. Please try again.');
                    tableWrapper.css('opacity', '1');
                }
            });
        }

        function updateTable(response) {
            const tbody = $('#vouchers-table tbody');
            tbody.empty();
            
            if (response.vouchers && response.vouchers.length > 0) {
                response.vouchers.forEach(function(voucher) {
                    const row = buildVoucherRow(voucher);
                    tbody.append(row);
                });
            } else {
                tbody.append('<tr class="table-row table-row-empty"><td colspan="9" class="col-empty">No vouchers found. Try a different search term.</td></tr>');
            }
        }

        function buildVoucherRow(voucher) {
            const typeLabels = {
                'percent': 'Percent',
                'fixed': 'Fixed',
                'freeshipping': 'Free Shipping'
            };
            
            const statusLabels = {
                'active': { class: 'status-active', text: 'Active' },
                'inactive': { class: 'status-inactive', text: 'Inactive' },
                'expired': { class: 'status-banned', text: 'Expired' }
            };
            
            const status = voucher.status || 'active';
            const statusInfo = statusLabels[status] || statusLabels['active'];
            
            // Format discount value
            let discountDisplay = '';
            if (voucher.type === 'percent') {
                discountDisplay = voucher.discount_value + '%';
                if (voucher.max_discount && voucher.max_discount > 0) {
                    discountDisplay += ' (max: RM' + parseFloat(voucher.max_discount).toFixed(2) + ')';
                }
            } else if (voucher.type === 'fixed') {
                discountDisplay = 'RM' + parseFloat(voucher.discount_value).toFixed(2);
            } else if (voucher.type === 'freeshipping') {
                discountDisplay = 'Free Shipping';
            }
            
            // Format dates
            const startDate = voucher.start_date ? new Date(voucher.start_date).toISOString().split('T')[0] : '-';
            const endDate = voucher.end_date ? new Date(voucher.end_date).toISOString().split('T')[0] : '-';
            const minSpend = voucher.min_spend && voucher.min_spend > 0 ? 'RM' + parseFloat(voucher.min_spend).toFixed(2) : '-';
            
            // Build status buttons
            let statusButtons = '';
            if (status !== 'inactive') {
                statusButtons += '<button onclick="confirmStatusChange(' + voucher.voucher_id + ', \'' + escapeHtml(voucher.code) + '\', \'inactive\')" class="action-btn inactive-btn" title="Set to inactive"><i class="fas fa-pause-circle"></i></button>';
            }
            if (status !== 'active') {
                statusButtons += '<button onclick="confirmStatusChange(' + voucher.voucher_id + ', \'' + escapeHtml(voucher.code) + '\', \'active\')" class="action-btn activate-btn" title="Activate voucher"><i class="fas fa-check-circle"></i></button>';
            }
            
            const row = `
                <tr class="table-row">
                    <td class="col-username"><strong>${escapeHtml(voucher.code)}</strong></td>
                    <td class="col-name">${escapeHtml(voucher.description || '-')}</td>
                    <td class="col-gender">${typeLabels[voucher.type] || voucher.type}</td>
                    <td class="col-contact">${discountDisplay}</td>
                    <td class="col-dob">${minSpend}</td>
                    <td class="col-date">${startDate}</td>
                    <td class="col-date">${endDate}</td>
                    <td class="col-status col-voucher-status">
                        <span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>
                    </td>
                    <td class="col-actions">
                        <button onclick="openEditModal(${voucher.voucher_id}, '${escapeHtml(voucher.code)}', '${escapeHtml(voucher.description || '')}', '${escapeHtml(voucher.type)}', '${escapeHtml(voucher.discount_value)}', '${escapeHtml(voucher.min_spend || '0')}', '${escapeHtml(voucher.max_discount || '')}', '${escapeHtml(voucher.start_date)}', '${escapeHtml(voucher.end_date)}')" class="action-btn edit-btn" title="Edit voucher">
                            <span class="material-symbols-outlined">edit</span>
                        </button>
                        ${statusButtons}
                        <button onclick="confirmDelete(${voucher.voucher_id}, '${escapeHtml(voucher.code)}')" class="action-btn delete-btn" title="Delete voucher">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                        <button onclick="openAssignModal(${voucher.voucher_id}, '${escapeHtml(voucher.code)}')" class="action-btn assign-btn" title="Assign voucher">
                            <span class="material-symbols-outlined">send</span>
                        </button>
                    </td>
                </tr>
            `;
            return row;
        }

        function updatePagination(response) {
            const pagination = response.pagination;
            const paginationNav = $('.pagination');
            
            if (pagination.total_vouchers > 0) {
                // Update pagination info
                $('.pagination-info').html(`
                    Showing <span class="pagination-number">${pagination.showing_from}-${pagination.showing_to}</span> of <span class="pagination-number">${pagination.total_vouchers}</span>
                `);
                
                // Update pagination links
                const paginationList = $('.pagination-list');
                paginationList.empty();
                
                // Previous button
                const prevDisabled = pagination.current_page <= 1 ? 'pagination-disabled' : '';
                const prevUrl = pagination.current_page > 1 ? 
                    `VoucherController.php?action=showAll&page=${pagination.current_page - 1}&search=${encodeURIComponent($('#simple-search').val())}&sortBy=${response.sortBy}&sortOrder=${response.sortOrder}` : '#';
                paginationList.append(`
                    <li>
                        ${pagination.current_page > 1 ? 
                            `<a href="${prevUrl}" class="pagination-link pagination-prev"><span class="material-symbols-outlined">chevron_left</span></a>` :
                            `<span class="pagination-link pagination-prev pagination-disabled"><span class="material-symbols-outlined">chevron_left</span></span>`
                        }
                    </li>
                `);
                
                // Page numbers
                const startPage = Math.max(1, pagination.current_page - 2);
                const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
                
                for (let i = startPage; i <= endPage; i++) {
                    const activeClass = i === pagination.current_page ? 'pagination-active' : '';
                    const pageUrl = `VoucherController.php?action=showAll&page=${i}&search=${encodeURIComponent($('#simple-search').val())}&sortBy=${response.sortBy}&sortOrder=${response.sortOrder}`;
                    paginationList.append(`
                        <li>
                            <a href="${pageUrl}" class="pagination-link ${activeClass}">${i}</a>
                        </li>
                    `);
                }
                
                // Next button
                const nextDisabled = pagination.current_page >= pagination.total_pages ? 'pagination-disabled' : '';
                const nextUrl = pagination.current_page < pagination.total_pages ? 
                    `VoucherController.php?action=showAll&page=${pagination.current_page + 1}&search=${encodeURIComponent($('#simple-search').val())}&sortBy=${response.sortBy}&sortOrder=${response.sortOrder}` : '#';
                paginationList.append(`
                    <li>
                        ${pagination.current_page < pagination.total_pages ? 
                            `<a href="${nextUrl}" class="pagination-link pagination-next"><span class="material-symbols-outlined">chevron_right</span></a>` :
                            `<span class="pagination-link pagination-next pagination-disabled"><span class="material-symbols-outlined">chevron_right</span></span>`
                        }
                    </li>
                `);
            } else {
                paginationNav.hide();
            }
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        function openEditModal(voucherId, code, description, type, discountValue, minSpend, maxDiscount, startDate, endDate) {
            $('#editVoucherId').val(voucherId);
            $('#editCode').val(code);
            $('#editDescription').val(description || '');
            $('#editType').val(type);
            $('#editDiscountValue').val(discountValue);
            $('#editMinSpend').val(minSpend || '0');
            $('#editMaxDiscount').val(maxDiscount || '');
            $('#editStartDate').val(startDate);
            $('#editEndDate').val(endDate);

            $('#editModal').removeClass('hidden');
        }

        function closeEditModal() {
            $('#editModal').addClass('hidden');
        }

        function confirmStatusChange(voucherId, voucherCode, newStatus) {
            var statusLabels = {
                'active': 'activate',
                'inactive': 'set to inactive',
                'expired': 'expire'
            };
            var action = statusLabels[newStatus] || newStatus;

            if (confirm('Are you sure you want to ' + action + ' voucher: ' + voucherCode + '?')) {
                $('#statusVoucherId').val(voucherId);
                $('#statusValue').val(newStatus);
                $('#statusForm').submit();
            }
        }

        function confirmDelete(voucherId, voucherCode) {
            if (confirm('Are you sure you want to delete voucher: ' + voucherCode + '?\n\nThis action cannot be undone.')) {
                $('#deleteVoucherId').val(voucherId);
                $('#deleteForm').submit();
            }
        }

        function openAssignModal(voucherId, voucherCode) {
            $('#assignVoucherId').val(voucherId);
            $('#assignVoucherCode').text(voucherCode);
            $('#assignType').val('');
            $('#assignMemberIds').val('');
            $('#assignModal').removeClass('hidden');
            
            // Reset form
            $('#assignmentTypeAll').prop('checked', false);
            $('#assignmentTypeSpecific').prop('checked', false);
            $('#memberSelectionDiv').hide();
            $('#membersList').html('<p style="padding: 1rem; color: #6b7280; text-align: center;">Select "Assign to Specific Members" to load available members...</p>');
            $('#membersList').data('loaded', false);
        }

        function closeAssignModal() {
            $('#assignModal').addClass('hidden');
        }

        function loadMembers() {
            const voucherId = $('#assignVoucherId').val();
            $.ajax({
                url: 'VoucherController.php?action=getMembers&voucher_id=' + voucherId,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.members) {
                        let html = '';
                        if (response.members.length === 0) {
                            html = '<p style="padding: 1rem; color: #6b7280; text-align: center;">No active members found.</p>';
                        } else {
                            response.members.forEach(function(member) {
                                const isAssigned = member.is_assigned === true || member.is_assigned === 1;
                                const checkedAttr = isAssigned ? 'checked' : '';
                                const disabledAttr = isAssigned ? 'disabled' : '';
                                const readonlyAttr = isAssigned ? 'readonly' : '';
                                const assignedClass = isAssigned ? 'member-assigned' : '';
                                const assignedLabel = isAssigned ? ' <span style="color: #10b981; font-size: 0.75rem; font-weight: 500;">(Already Assigned)</span>' : '';
                                
                                html += `<label class="member-checkbox-label ${assignedClass}">
                                            <input type="checkbox" name="member_ids[]" value="${member.user_id}" class="member-checkbox" ${checkedAttr} ${disabledAttr} ${readonlyAttr} data-assigned="${isAssigned ? '1' : '0'}">
                                            <span>${escapeHtml(member.full_name)} (${escapeHtml(member.email)})${assignedLabel}</span>
                                         </label>`;
                            });
                        }
                        $('#membersList').html(html);
                        $('#membersList').data('loaded', true);
                        
                        // Prevent any attempts to uncheck assigned members
                        $('input.member-checkbox[data-assigned="1"]').on('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            return false;
                        });
                        
                        // Also prevent label clicks from affecting disabled checkboxes
                        $('.member-assigned').on('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            return false;
                        });
                    }
                },
                error: function() {
                    alert('Error loading members. Please try again.');
                }
            });
        }

        $(document).ready(function() {
            // Handle assignment type change (using event delegation for dynamically added elements)
            $(document).on('change', 'input[name="assignment_type_radio"]', function() {
                if ($(this).val() === 'all') {
                    $('#assignType').val('all');
                    $('#memberSelectionDiv').hide();
                } else if ($(this).val() === 'specific') {
                    $('#assignType').val('specific');
                    $('#memberSelectionDiv').show();
                    // Always reload members when switching to specific (to get updated list for current voucher)
                    $('#membersList').data('loaded', false);
                    loadMembers();
                }
            });

            // Handle assign form submission
            $('#assignFormSubmit').on('click', function(e) {
                e.preventDefault();
                
                const assignmentType = $('#assignType').val();
                if (!assignmentType) {
                    alert('Please select an assignment type.');
                    return;
                }

                if (assignmentType === 'specific') {
                    // Only get members that are not already assigned (not disabled and not marked as assigned)
                    const selectedMembers = $('input.member-checkbox:checked:not(:disabled):not([data-assigned="1"])').map(function() {
                        return $(this).val();
                    }).get();
                    
                    if (selectedMembers.length === 0) {
                        alert('Please select at least one member who does not already have this voucher assigned.');
                        return;
                    }
                    
                    // Create hidden inputs for each selected member (exclude assigned ones)
                    $('#assignForm input[name="member_ids[]"]').remove();
                    selectedMembers.forEach(function(memberId) {
                        // Double-check that this member is not assigned
                        const checkbox = $(`input.member-checkbox[value="${memberId}"]`);
                        if (checkbox.attr('data-assigned') !== '1') {
                            $('#assignForm').append(`<input type="hidden" name="member_ids[]" value="${memberId}">`);
                        }
                    });
                }

                const voucherCode = $('#assignVoucherCode').text();
                const confirmMsg = assignmentType === 'all' 
                    ? `Are you sure you want to assign voucher "${voucherCode}" to ALL active members?`
                    : `Are you sure you want to assign voucher "${voucherCode}" to the selected members?`;
                    
                if (confirm(confirmMsg)) {
                    $('#assignForm').submit();
                }
            });

            // Member search functionality
            $('#memberSearch').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('.member-checkbox-label').each(function() {
                    const memberText = $(this).text().toLowerCase();
                    if (memberText.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Close modal on outside click
            $(document).on('click', '.modal-overlay', function(e) {
                if ($(e.target).hasClass('modal-overlay')) {
                    closeAssignModal();
                }
            });
        });
    </script>

    <!-- Assign Voucher Modal -->
    <div id="assignModal" class="modal-overlay hidden">
        <div class="modal-content">
            <div class="modal-body">
                <h3 class="modal-title">Assign Voucher: <span id="assignVoucherCode"></span></h3>

                <form id="assignModalForm" class="modal-form">
                    <div class="form-group">
                        <label class="form-label">Assignment Type</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="assignment_type_radio" id="assignmentTypeAll" value="all">
                                <span>Assign to All Active Members</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="assignment_type_radio" id="assignmentTypeSpecific" value="specific">
                                <span>Assign to Specific Members</span>
                            </label>
                        </div>
                    </div>

                    <div id="memberSelectionDiv" class="form-group" style="display: none;">
                        <label class="form-label">Select Members</label>
                        <div id="memberSearchWrapper" style="margin-bottom: 10px;">
                            <input type="text" id="memberSearch" placeholder="Search members..." class="form-input">
                        </div>
                        <div id="membersList" class="members-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                            <p>Loading members...</p>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" onclick="closeAssignModal()" class="btn btn-secondary">
                            Cancel
                        </button>
                        <button type="button" id="assignFormSubmit" class="btn btn-primary">
                            Assign Voucher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal-overlay hidden">
        <div class="modal-content">
            <div class="modal-body">
                <h3 class="modal-title">Edit Voucher</h3>

                <form id="editForm" method="POST" action="VoucherController.php" class="modal-form">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="voucher_id" id="editVoucherId">

                    <div class="form-group">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" id="editCode" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" id="editDescription" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Type</label>
                        <select name="type" id="editType" class="form-input">
                            <option value="percent">Percent</option>
                            <option value="fixed">Fixed</option>
                            <option value="freeshipping">Free Shipping</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Discount Value</label>
                        <input type="number" name="discount_value" id="editDiscountValue" step="0.01" min="0" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Min Spend (RM)</label>
                        <input type="number" name="min_spend" id="editMinSpend" step="0.01" min="0" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Max Discount (RM) - Optional</label>
                        <input type="number" name="max_discount" id="editMaxDiscount" step="0.01" min="0" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="editStartDate" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" id="editEndDate" class="form-input">
                    </div>

                    <div class="form-actions">
                        <button type="button" onclick="closeEditModal()" class="btn btn-secondary">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>

