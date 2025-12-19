<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']->role !== 'admin') {
    header('Location: ../../views/security/login.php');
    exit;
}

$prefix = '../../';

// Calculate base path for images (absolute from document root)
// Since this file is in web/views/voucher_management/, go up two levels to get web root
$currentFileDir = dirname(__FILE__); // Gets web/views/voucher_management/
$webRootDir = dirname(dirname($currentFileDir)); // Gets web/
$projectRoot = dirname($webRootDir); // Gets project root

// Get the relative path from document root
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$imageBasePath = str_replace('\\', '/', $relativePath) . '/'; // Normalize slashes
$cssBasePath = $imageBasePath . 'css/'; // CSS files are in web/css/
$viewsBasePath = $imageBasePath . 'views/'; // Views files are in web/views/

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
    <title><?php echo $pageTitle; ?> - NGear</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>AdminOrder.css">
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>AllTables.css">
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>AllVouchers.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: transparent; color: #0f172a; }
        .page-container { max-width: 100%; margin: 0; padding: 20px; }
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .btn-add-voucher { background: linear-gradient(135deg, #FF523B 0%, #e64a35 100%); color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem; border: none; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; cursor: pointer; transition: all 0.3s ease; text-decoration: none; box-shadow: 0 4px 6px rgba(255, 82, 59, 0.3); }
        .btn-add-voucher:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(255, 82, 59, 0.4); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { display: flex; align-items: center; gap: 1.25rem; background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,0.05); transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-color: #FF523B; }
        .stat-icon { width: 3.5rem; height: 3.5rem; display: flex; align-items: center; justify-content: center; border-radius: 0.75rem; font-size: 1.5rem; color: white; flex-shrink: 0; }
        .stat-icon.blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-icon.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .stat-icon.purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
        .stat-info h3 { font-size: 1.875rem; font-weight: 700; color: #0f172a; margin: 0; }
        .stat-info p { font-size: 0.875rem; color: #6b7280; margin: 0; }
        .filters-section { background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; margin-bottom: 1.5rem; }
        .filters-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end; }
        .filter-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .filter-group label { font-size: 0.875rem; font-weight: 600; color: #374151; display: flex; align-items: center; gap: 0.5rem; }
        .filter-group label i { color: #FF523B; }
        .filter-group input, .filter-group select { padding: 0.625rem 0.875rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s; }
        .filter-group input:focus, .filter-group select:focus { outline: none; border-color: #FF523B; box-shadow: 0 0 0 3px rgba(255, 82, 59, 0.1); }
        .filter-actions { display: flex; gap: 0.5rem; align-items: flex-end; }
        .btn { padding: 0.625rem 1.25rem; border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem; cursor: pointer; transition: all 0.2s; border: none; display: flex; align-items: center; gap: 0.5rem; }
        .btn-primary { background: #FF523B; color: white; }
        .btn-primary:hover { background: #e64a35; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        .table-container { background: white; border-radius: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; overflow: hidden; }
        .orders-table { width: 100%; border-collapse: collapse; }
        .orders-table thead { background: #f9fafb; }
        .orders-table thead th { padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: #374151; border-bottom: 2px solid #e5e7eb; }
        .orders-table tbody td { padding: 1rem; border-bottom: 1px solid #f3f4f6; font-size: 0.875rem; }
        .orders-table tbody tr:hover { background: #f9fafb; }
        .orders-table tbody tr:last-child td { border-bottom: none; }
        .action-buttons { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .action-btn { width: 2rem !important; height: 2rem !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; border-radius: 0.375rem !important; border: 1px solid #d1d5db !important; background: white !important; cursor: pointer !important; transition: all 0.2s !important; color: #6b7280 !important; margin-right: 0.25rem; padding: 0 !important; }
        .action-btn:hover { background: #f3f4f6 !important; border-color: #9ca3af !important; color: #374151 !important; }
        .action-btn.view-btn { background: #eff6ff !important; border-color: #3b82f6 !important; color: #3b82f6 !important; }
        .action-btn.view-btn:hover { background: #dbeafe !important; border-color: #2563eb !important; color: #1d4ed8 !important; }
        .action-btn.edit-btn { background: #fffbeb !important; border-color: #f59e0b !important; color: #f59e0b !important; }
        .action-btn.edit-btn:hover { background: #fef3c7 !important; border-color: #d97706 !important; color: #d97706 !important; }
        .action-btn.delete-btn { background: #fef2f2 !important; border-color: #ef4444 !important; color: #ef4444 !important; }
        .action-btn.delete-btn:hover { background: #fee2e2 !important; border-color: #dc2626 !important; color: #dc2626 !important; }
        .action-btn .material-symbols-outlined { font-size: 1.125rem !important; }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-badge.status-active { background: #d1fae5; color: #065f46; }
        .status-badge.status-expired { background: #fee2e2; color: #991b1b; }
        .status-badge.status-pending { background: #fef3c7; color: #92400e; }
        .empty-state { display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 3rem; color: #9ca3af; }
        .empty-state i { font-size: 3rem; }
        .empty-state p { font-size: 1rem; font-weight: 500; }
        .bulk-actions-section { margin-top: 1rem; padding: 1rem; background: #fef3c7; border-radius: 0.5rem; border: 1px solid #fcd34d; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .col-checkbox { width: 40px; }
        .pagination { display: flex; justify-content: space-between; align-items: center; padding: 1rem 1.5rem; background: white; border-top: 1px solid #e5e7eb; }
        .pagination-info { font-size: 0.875rem; color: #6b7280; }
        .pagination-number { font-weight: 600; color: #0f172a; }
        .pagination-list { display: flex; gap: 0.25rem; list-style: none; }
        .pagination-link { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; color: #374151; text-decoration: none; transition: all 0.2s; }
        .pagination-link:hover:not(.pagination-disabled) { background: #f3f4f6; border-color: #9ca3af; }
        .pagination-link.pagination-active { background: #FF523B; color: white; border-color: #FF523B; }
        .pagination-link.pagination-disabled { opacity: 0.5; cursor: not-allowed; }
        .text-center { text-align: center; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border-width: 0; }
        .message { padding: 1rem 1.5rem; border-radius: 0.5rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; font-weight: 500; }
        .message-success { background: #d1fae5; color: #065f46; border: 1px solid #10b981; }
        .message-error { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
        .voucher-code { font-family: 'Courier New', monospace; background: #f3f4f6; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-weight: 600; }

        /* Confirmation Modal Styles */
        .confirmation-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .confirmation-modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .confirmation-modal-content {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            max-width: 500px;
            width: 90%;
            padding: 0;
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }

        .confirmation-modal-overlay.show .confirmation-modal-content {
            transform: scale(1);
        }

        .confirmation-modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .confirmation-modal-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .confirmation-modal-icon.warning {
            background: #fef3c7;
            color: #f59e0b;
        }

        .confirmation-modal-icon.danger {
            background: #fee2e2;
            color: #ef4444;
        }

        .confirmation-modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0f172a;
            margin: 0;
        }

        .confirmation-modal-body {
            padding: 1.5rem;
        }

        .confirmation-modal-message {
            color: #6b7280;
            font-size: 0.875rem;
            line-height: 1.5;
            margin: 0;
        }

        .confirmation-modal-actions {
            padding: 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .confirmation-modal-btn {
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .confirmation-modal-btn-cancel {
            background: #f3f4f6;
            color: #374151;
        }

        .confirmation-modal-btn-cancel:hover {
            background: #e5e7eb;
        }

        .confirmation-modal-btn-confirm {
            background: #ef4444;
            color: white;
        }

        .confirmation-modal-btn-confirm:hover {
            background: #dc2626;
        }

        .confirmation-modal-btn-confirm.warning {
            background: #f59e0b;
        }

        .confirmation-modal-btn-confirm.warning:hover {
            background: #d97706;
        }

        /* Enhanced Edit Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-overlay:not(.hidden) {
            opacity: 1;
            visibility: visible;
        }

        .modal-overlay.hidden {
            display: none;
        }

        .modal-content {
            background: white;
            border-radius: 1rem;
            max-width: 900px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: scale(0.95);
            transition: transform 0.3s ease;
            margin: 2rem;
        }

        .modal-overlay:not(.hidden) .modal-content {
            transform: scale(1);
        }

        .modal-body {
            padding: 2rem;
        }

        .modal-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 1.5rem 0;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .modal-title::before {
            content: '';
            width: 4px;
            height: 2rem;
            background: linear-gradient(135deg, #FF523B 0%, #e64a35 100%);
            border-radius: 2px;
        }

        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .modal-form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group-full {
            grid-column: 1 / -1;
        }

        .form-label {
            font-size: 0.9375rem;
            font-weight: 600;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label::before {
            content: '';
            width: 3px;
            height: 1rem;
            background: #FF523B;
            border-radius: 2px;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.625rem;
            font-size: 0.9375rem;
            transition: all 0.2s ease;
            background: white;
            color: #0f172a;
        }

        .form-input:focus {
            outline: none;
            border-color: #FF523B;
            box-shadow: 0 0 0 4px rgba(255, 82, 59, 0.1);
        }

        .form-input-readonly {
            background: #f9fafb;
            cursor: not-allowed;
            color: #6b7280;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #e5e7eb;
        }

        @media (max-width: 768px) {
            .modal-content {
                max-width: 100%;
                width: 100%;
                margin: 1rem;
                max-height: 95vh;
            }

            .modal-body {
                padding: 1.5rem;
            }

            .modal-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="page-body">

    <div class="page-container">
        <div class="page-content">
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="message message-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="message message-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <div class="header-actions">
                <h1 style="font-size: 1.875rem; font-weight: 700; display: flex; align-items: center; gap: 0.75rem; margin: 0; color: #0f172a;">
                    <i class="fas fa-ticket-alt" style="color: #FF523B;"></i>
                    Voucher Management
                </h1>
                <?php
                $voucherFormUrl = $viewsBasePath . 'voucher_management/VoucherRegisterForm.php?return_to=admin';
                ?>
                <a href="<?php echo $voucherFormUrl; ?>" class="btn-add-voucher">
                    <i class="fas fa-plus"></i>
                    Add New Voucher
                </a>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo isset($pagination['total_vouchers']) ? $pagination['total_vouchers'] : count($vouchers); ?></h3>
                        <p>Total Vouchers</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php
                            $activeCount = 0;
                            foreach ($vouchers as $v) {
                                if (($v['status'] ?? 'active') === 'active') $activeCount++;
                            }
                            echo $activeCount;
                        ?></h3>
                        <p>Active Vouchers</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-pause-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php
                            $inactiveCount = 0;
                            foreach ($vouchers as $v) {
                                if (($v['status'] ?? 'active') === 'inactive') $inactiveCount++;
                            }
                            echo $inactiveCount;
                        ?></h3>
                        <p>Inactive Vouchers</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php
                            $expiredCount = 0;
                            foreach ($vouchers as $v) {
                                if (($v['status'] ?? 'active') === 'expired') $expiredCount++;
                            }
                            echo $expiredCount;
                        ?></h3>
                        <p>Expired Vouchers</p>
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <form class="filters-form" id="filtersForm">
                    <div class="filter-group">
                        <label for="filterSearch">
                            <i class="fas fa-search"></i>
                            Search
                        </label>
                        <input type="text" id="filterSearch" name="search" placeholder="Search vouchers..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    <div class="filter-group">
                        <label for="filterStatus">
                            <i class="fas fa-filter"></i>
                            Status
                        </label>
                        <select id="filterStatus" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (isset($_GET['status']) && $_GET['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="expired" <?php echo (isset($_GET['status']) && $_GET['status'] === 'expired') ? 'selected' : ''; ?>>Expired</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filterType">
                            <i class="fas fa-tag"></i>
                            Type
                        </label>
                        <select id="filterType" name="type">
                            <option value="">All Types</option>
                            <option value="percent" <?php echo (isset($_GET['type']) && $_GET['type'] === 'percent') ? 'selected' : ''; ?>>Percent</option>
                            <option value="fixed" <?php echo (isset($_GET['type']) && $_GET['type'] === 'fixed') ? 'selected' : ''; ?>>Fixed</option>
                            <option value="freeshipping" <?php echo (isset($_GET['type']) && $_GET['type'] === 'freeshipping') ? 'selected' : ''; ?>>Free Shipping</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="sortBy">
                            <i class="fas fa-sort"></i>
                            Sort By
                        </label>
                        <select id="sortBy" name="sortBy">
                            <option value="voucher_id" <?php echo $currentSortBy === 'voucher_id' ? 'selected' : ''; ?>>ID</option>
                            <option value="code" <?php echo $currentSortBy === 'code' ? 'selected' : ''; ?>>Code</option>
                            <option value="type" <?php echo $currentSortBy === 'type' ? 'selected' : ''; ?>>Type</option>
                            <option value="start_date" <?php echo $currentSortBy === 'start_date' ? 'selected' : ''; ?>>Start Date</option>
                            <option value="end_date" <?php echo $currentSortBy === 'end_date' ? 'selected' : ''; ?>>End Date</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="sortOrder">
                            <i class="fas fa-arrow-down-wide-short"></i>
                            Order
                        </label>
                        <select id="sortOrder" name="sortOrder">
                            <option value="DESC" <?php echo $currentSortOrder === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                            <option value="ASC" <?php echo $currentSortOrder === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Bulk Actions Section -->
            <div class="bulk-actions-section" style="display: none;" id="bulkActionsSection">
                <button type="button" class="btn btn-danger" id="bulkDeleteBtn">
                    <i class="fas fa-trash"></i>
                    Delete Selected (<span id="selectedCount">0</span>)
                </button>
                <button type="button" class="btn btn-secondary" id="clearSelectionBtn">
                    <i class="fas fa-times"></i>
                    Clear Selection
                </button>
            </div>

            <!-- Table Container -->
            <div class="table-container">
                <table class="orders-table" id="vouchers-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="selectAllCheckbox" title="Select all">
                            </th>
                            <th>Voucher Info</th>
                            <th>Discount</th>
                            <th>Validity Period</th>
                            <th>Usage</th>
                            <th>Status</th>
                            <th style="width: 250px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($vouchers)): ?>
                            <?php foreach ($vouchers as $voucher): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="voucher-checkbox" name="voucher_ids[]" value="<?php echo $voucher['voucher_id']; ?>" data-voucher-code="<?php echo htmlspecialchars($voucher['code'], ENT_QUOTES); ?>">
                                    </td>
                                    <td>
                                        <strong style="display: block; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($voucher['code']); ?></strong>
                                        <small style="display: block; color: #6b7280; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($voucher['description'] ?? '-'); ?></small>
                                        <small style="display: block; color: #9ca3af; font-size: 0.75rem;">
                                            <?php
                                            $typeLabels = [
                                                'percent' => '<i class="fas fa-percent"></i> Percent',
                                                'fixed' => '<i class="fas fa-dollar-sign"></i> Fixed',
                                                'freeshipping' => '<i class="fas fa-truck"></i> Free Shipping'
                                            ];
                                            echo $typeLabels[$voucher['type']] ?? ucfirst($voucher['type']);
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong style="display: block; color: #10b981; margin-bottom: 0.25rem;">
                                            <?php
                                            $maxDiscount = isset($voucher['max_discount']) ? $voucher['max_discount'] : null;
                                            echo formatDiscountValue($voucher['type'], $voucher['discount_value'], $maxDiscount);
                                            ?>
                                        </strong>
                                        <small style="display: block; color: #6b7280;">
                                            Min: <?php
                                            if (!empty($voucher['min_spend']) && $voucher['min_spend'] > 0) {
                                                echo 'RM' . number_format($voucher['min_spend'], 2);
                                            } else {
                                                echo 'None';
                                            }
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small style="display: block; margin-bottom: 0.25rem;">
                                            <i class="fas fa-calendar-plus" style="color: #10b981;"></i>
                                            <?php
                                            if (!empty($voucher['start_date'])) {
                                                $date = new DateTime($voucher['start_date']);
                                                echo $date->format('Y-m-d');
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </small>
                                        <small style="display: block;">
                                            <i class="fas fa-calendar-times" style="color: #ef4444;"></i>
                                            <?php
                                            if (!empty($voucher['end_date'])) {
                                                $date = new DateTime($voucher['end_date']);
                                                echo $date->format('Y-m-d');
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php
                                        $isRedeemable = isset($voucher['is_redeemable']) ? (bool)$voucher['is_redeemable'] : true;
                                        $redeemableClass = $isRedeemable ? 'status-badge status-active' : 'status-badge status-pending';
                                        $redeemableText = $isRedeemable ? '<i class="fas fa-check"></i> Redeemable' : '<i class="fas fa-lock"></i> Admin Only';
                                        ?>
                                        <span class="<?php echo $redeemableClass; ?>" style="display: inline-block; font-size: 0.75rem; padding: 0.15rem 0.5rem;"><?php echo $redeemableText; ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $voucher['status'] ?? 'active';
                                        $statusClass = '';
                                        $statusText = ucfirst($status);

                                        switch ($status) {
                                            case 'active':
                                                $statusClass = 'status-badge status-active';
                                                break;
                                            case 'inactive':
                                                $statusClass = 'status-badge status-pending';
                                                break;
                                            case 'expired':
                                                $statusClass = 'status-badge status-expired';
                                                break;
                                            default:
                                                $statusClass = 'status-badge status-active';
                                        }
                                        ?>
                                        <span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button
                                                class="action-btn edit-btn"
                                                data-action="edit"
                                                data-voucher-id="<?php echo $voucher['voucher_id']; ?>"
                                                data-code="<?php echo htmlspecialchars($voucher['code'], ENT_QUOTES); ?>"
                                                data-description="<?php echo htmlspecialchars($voucher['description'] ?? '', ENT_QUOTES); ?>"
                                                data-type="<?php echo htmlspecialchars($voucher['type'], ENT_QUOTES); ?>"
                                                data-discount-value="<?php echo htmlspecialchars($voucher['discount_value'], ENT_QUOTES); ?>"
                                                data-min-spend="<?php echo htmlspecialchars($voucher['min_spend'] ?? '0', ENT_QUOTES); ?>"
                                                data-max-discount="<?php echo htmlspecialchars($voucher['max_discount'] ?? '', ENT_QUOTES); ?>"
                                                data-start-date="<?php echo htmlspecialchars($voucher['start_date'], ENT_QUOTES); ?>"
                                                data-end-date="<?php echo htmlspecialchars($voucher['end_date'], ENT_QUOTES); ?>"
                                                data-is-redeemable="<?php echo isset($voucher['is_redeemable']) ? ($voucher['is_redeemable'] ? '1' : '0') : '1'; ?>"
                                                title="Edit voucher">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <?php
                                            $currentStatus = $voucher['status'] ?? 'active';
                                            ?>
                                            <?php if ($currentStatus !== 'inactive'): ?>
                                                <button
                                                    class="action-btn" style="background: #fef3c7 !important; border-color: #f59e0b !important; color: #f59e0b !important;"
                                                    data-action="status"
                                                    data-voucher-id="<?php echo $voucher['voucher_id']; ?>"
                                                    data-code="<?php echo htmlspecialchars($voucher['code'], ENT_QUOTES); ?>"
                                                    data-status="inactive"
                                                    title="Set to inactive">
                                                    <i class="fas fa-pause-circle"></i>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($currentStatus !== 'active'): ?>
                                                <button
                                                    class="action-btn" style="background: #d1fae5 !important; border-color: #10b981 !important; color: #10b981 !important;"
                                                    data-action="status"
                                                    data-voucher-id="<?php echo $voucher['voucher_id']; ?>"
                                                    data-code="<?php echo htmlspecialchars($voucher['code'], ENT_QUOTES); ?>"
                                                    data-status="active"
                                                    title="Activate voucher">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            <?php endif; ?>

                                            <button
                                                class="action-btn delete-btn"
                                                data-action="delete"
                                                data-voucher-id="<?php echo $voucher['voucher_id']; ?>"
                                                data-code="<?php echo htmlspecialchars($voucher['code'], ENT_QUOTES); ?>"
                                                title="Delete voucher">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            
                                            <button
                                                class="action-btn" style="background: #dbeafe !important; border-color: #3b82f6 !important; color: #3b82f6 !important;"
                                                data-action="assign"
                                                data-voucher-id="<?php echo $voucher['voucher_id']; ?>"
                                                data-code="<?php echo htmlspecialchars($voucher['code'], ENT_QUOTES); ?>"
                                                title="Assign voucher">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>

                                            <a
                                                class="action-btn" style="background: #f3e8ff !important; border-color: #8b5cf6 !important; color: #8b5cf6 !important;"
                                                href="VoucherController.php?action=showVoucherQr&amp;voucher_id=<?php echo $voucher['voucher_id']; ?>&amp;code=<?php echo urlencode($voucher['code']); ?>"
                                                title="View voucher QR code">
                                                <i class="fas fa-qrcode"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p>No vouchers found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

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

    <!-- Bulk Delete Form (Hidden) -->
    <form id="bulkDeleteForm" method="POST" action="VoucherController.php" style="display: none;">
        <input type="hidden" name="action" value="bulkDelete">
    </form>

    <!-- Assign Voucher Form (Hidden) -->
    <form id="assignForm" method="POST" action="VoucherController.php" style="display: none;">
        <input type="hidden" name="action" value="assign">
        <input type="hidden" name="voucher_id" id="assignVoucherId">
        <input type="hidden" name="assignment_type" id="assignType">
        <!-- Member IDs will be added dynamically -->
    </form>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // AJAX Filter functionality
        let filterTimeout;

        // Debounced AJAX filtering
        function performAjaxFilter() {
            const searchTerm = $('#filterSearch').val();
            const status = $('#filterStatus').val();
            const type = $('#filterType').val();
            const sortBy = $('#sortBy').val() || 'voucher_id';
            const sortOrder = $('#sortOrder').val() || 'DESC';
            
            const tableContainer = $('.table-container');
            tableContainer.css('opacity', '0.6');
            
            $.ajax({
                url: 'VoucherController.php',
                method: 'GET',
                data: {
                    action: 'showAll',
                    ajax: '1',
                    search: searchTerm,
                    status: status,
                    type: type,
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
                        console.error('Filter error:', response.error);
                    }
                    tableContainer.css('opacity', '1');
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    tableContainer.css('opacity', '1');
                }
            });
        }

        // Attach filter event listeners
        $(document).ready(function() {
            // Search input with debounce
            $('#filterSearch').on('input', function() {
                clearTimeout(filterTimeout);
                filterTimeout = setTimeout(performAjaxFilter, 500);
            });

            // Dropdown filters - immediate
            $('#filterStatus, #filterType, #sortBy, #sortOrder').on('change', performAjaxFilter);
        });

        function updateTable(response) {
            const tbody = $('#vouchers-table tbody');
            tbody.empty();
            
            // Clear selection when table updates
            selectedVouchers.clear();
            $('#selectAllCheckbox').prop('checked', false);
            updateBulkActions();
            
            if (response.vouchers && response.vouchers.length > 0) {
                response.vouchers.forEach(function(voucher) {
                    const row = buildVoucherRow(voucher);
                    tbody.append(row);
                });
            } else {
                tbody.append(`
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No vouchers found</p>
                            </div>
                        </td>
                    </tr>
                `);
            }
        }

        function buildVoucherRow(voucher) {
            const typeLabels = {
                'percent': '<i class="fas fa-percent"></i> Percent',
                'fixed': '<i class="fas fa-dollar-sign"></i> Fixed',
                'freeshipping': '<i class="fas fa-truck"></i> Free Shipping'
            };
            
            const statusLabels = {
                'active': { class: 'status-active', text: 'Active' },
                'inactive': { class: 'status-pending', text: 'Inactive' },
                'expired': { class: 'status-expired', text: 'Expired' }
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
            const minSpend = voucher.min_spend && voucher.min_spend > 0 ? 'RM' + parseFloat(voucher.min_spend).toFixed(2) : 'None';
            const isRedeemable = voucher.is_redeemable !== undefined ? (voucher.is_redeemable ? true : false) : true;
            const redeemableText = isRedeemable ? '<i class="fas fa-check"></i> Redeemable' : '<i class="fas fa-lock"></i> Admin Only';
            const redeemableClass = isRedeemable ? 'status-active' : 'status-pending';
            const qrUrl = `VoucherController.php?action=showVoucherQr&voucher_id=${voucher.voucher_id}&code=${encodeURIComponent(voucher.code)}`;
            
            // Build status buttons
            let statusButtons = '';
            if (status !== 'inactive') {
                statusButtons += `<button class="action-btn" style="background: #fef3c7 !important; border-color: #f59e0b !important; color: #f59e0b !important;" data-action="status" data-voucher-id="${voucher.voucher_id}" data-code="${escapeHtml(voucher.code)}" data-status="inactive" title="Set to inactive"><i class="fas fa-pause-circle"></i></button>`;
            }
            if (status !== 'active') {
                statusButtons += `<button class="action-btn" style="background: #d1fae5 !important; border-color: #10b981 !important; color: #10b981 !important;" data-action="status" data-voucher-id="${voucher.voucher_id}" data-code="${escapeHtml(voucher.code)}" data-status="active" title="Activate voucher"><i class="fas fa-check-circle"></i></button>`;
            }
            
            const row = `
                <tr>
                    <td>
                        <input type="checkbox" class="voucher-checkbox" name="voucher_ids[]" value="${voucher.voucher_id}" data-voucher-code="${escapeHtml(voucher.code)}">
                    </td>
                    <td>
                        <strong style="display: block; margin-bottom: 0.25rem;">${escapeHtml(voucher.code)}</strong>
                        <small style="display: block; color: #6b7280; margin-bottom: 0.25rem;">${escapeHtml(voucher.description || '-')}</small>
                        <small style="display: block; color: #9ca3af; font-size: 0.75rem;">${typeLabels[voucher.type] || voucher.type}</small>
                    </td>
                    <td>
                        <strong style="display: block; color: #10b981; margin-bottom: 0.25rem;">${discountDisplay}</strong>
                        <small style="display: block; color: #6b7280;">Min: ${minSpend}</small>
                    </td>
                    <td>
                        <small style="display: block; margin-bottom: 0.25rem;"><i class="fas fa-calendar-plus" style="color: #10b981;"></i> ${startDate}</small>
                        <small style="display: block;"><i class="fas fa-calendar-times" style="color: #ef4444;"></i> ${endDate}</small>
                    </td>
                    <td>
                        <span class="status-badge ${redeemableClass}" style="display: inline-block; font-size: 0.75rem; padding: 0.15rem 0.5rem;">${redeemableText}</span>
                    </td>
                    <td>
                        <span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="action-btn edit-btn" data-action="edit" data-voucher-id="${voucher.voucher_id}" data-code="${escapeHtml(voucher.code)}" data-description="${escapeHtml(voucher.description || '')}" data-type="${escapeHtml(voucher.type)}" data-discount-value="${escapeHtml(voucher.discount_value)}" data-min-spend="${escapeHtml(voucher.min_spend || '0')}" data-max-discount="${escapeHtml(voucher.max_discount || '')}" data-start-date="${escapeHtml(voucher.start_date)}" data-end-date="${escapeHtml(voucher.end_date)}" data-is-redeemable="${isRedeemable ? '1' : '0'}" title="Edit voucher">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${statusButtons}
                            <button class="action-btn delete-btn" data-action="delete" data-voucher-id="${voucher.voucher_id}" data-code="${escapeHtml(voucher.code)}" title="Delete voucher">
                                <i class="fas fa-trash"></i>
                            </button>
                            <button class="action-btn" style="background: #dbeafe !important; border-color: #3b82f6 !important; color: #3b82f6 !important;" data-action="assign" data-voucher-id="${voucher.voucher_id}" data-code="${escapeHtml(voucher.code)}" title="Assign voucher">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                            <a href="${qrUrl}" class="action-btn" style="background: #f3e8ff !important; border-color: #8b5cf6 !important; color: #8b5cf6 !important;" title="View voucher QR code">
                                <i class="fas fa-qrcode"></i>
                            </a>
                        </div>
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
                
                const searchTerm = $('#filterSearch').val();
                const status = $('#filterStatus').val();
                const type = $('#filterType').val();
                const sortBy = response.sortBy || 'voucher_id';
                const sortOrder = response.sortOrder || 'DESC';
                
                // Previous button
                const prevUrl = pagination.current_page > 1 ? 
                    `VoucherController.php?action=showAll&page=${pagination.current_page - 1}&search=${encodeURIComponent(searchTerm)}&status=${status}&type=${type}&sortBy=${sortBy}&sortOrder=${sortOrder}` : '#';
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
                    const pageUrl = `VoucherController.php?action=showAll&page=${i}&search=${encodeURIComponent(searchTerm)}&status=${status}&type=${type}&sortBy=${sortBy}&sortOrder=${sortOrder}`;
                    paginationList.append(`
                        <li>
                            <a href="${pageUrl}" class="pagination-link ${activeClass}">${i}</a>
                        </li>
                    `);
                }
                
                // Next button
                const nextUrl = pagination.current_page < pagination.total_pages ? 
                    `VoucherController.php?action=showAll&page=${pagination.current_page + 1}&search=${encodeURIComponent(searchTerm)}&status=${status}&type=${type}&sortBy=${sortBy}&sortOrder=${sortOrder}` : '#';
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
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        // jQuery event handlers - following conventions (no inline JavaScript)
        $(document).ready(function() {
            // Edit button handler
            $(document).on('click', '.edit-btn[data-action="edit"]', function() {
                var $btn = $(this);
                var voucherId = $btn.data('voucher-id');
                var code = $btn.data('code');
                var description = $btn.data('description');
                var type = $btn.data('type');
                var discountValue = $btn.data('discount-value');
                var minSpend = $btn.data('min-spend');
                var maxDiscount = $btn.data('max-discount');
                var startDate = $btn.data('start-date');
                var endDate = $btn.data('end-date');
                var isRedeemable = $btn.data('is-redeemable') === '1' || $btn.data('is-redeemable') === 1;
                
                $('#editVoucherId').val(voucherId);
                $('#editCode').val(code);
                $('#editDescription').val(description || '');
                $('#editType').val(type);
                $('#editDiscountValue').val(discountValue);
                $('#editMinSpend').val(minSpend || '0');
                $('#editMaxDiscount').val(maxDiscount || '');
                $('#editStartDate').val(startDate);
                $('#editEndDate').val(endDate);
                $('#editIsRedeemable').prop('checked', isRedeemable);
                $('#editModal').removeClass('hidden');
            });
            
            // Status change button handler
            $(document).on('click', '.action-btn[data-action="status"]', function() {
                var $btn = $(this);
                var voucherId = $btn.data('voucher-id');
                var voucherCode = $btn.data('code');
                var newStatus = $btn.data('status');
                
                var statusLabels = {
                    'active': 'activate',
                    'inactive': 'set to inactive',
                    'expired': 'expire'
                };
                var action = statusLabels[newStatus] || newStatus;
                
                showConfirmationModal(
                    'Are you sure you want to ' + action + ' voucher: ' + voucherCode + '?',
                    'warning',
                    function() {
                        $('#statusVoucherId').val(voucherId);
                        $('#statusValue').val(newStatus);
                        $('#statusForm').submit();
                    }
                );
            });
            
            // Delete button handler
            $(document).on('click', '.delete-btn[data-action="delete"]', function() {
                var $btn = $(this);
                var voucherId = $btn.data('voucher-id');
                var voucherCode = $btn.data('code');
                
                showConfirmationModal(
                    'Are you sure you want to delete voucher: ' + voucherCode + '?<br><br>This action cannot be undone.',
                    'danger',
                    function() {
                        $('#deleteVoucherId').val(voucherId);
                        $('#deleteForm').submit();
                    }
                );
            });
            
            // Assign button handler
            $(document).on('click', '.action-btn[data-action="assign"]', function() {
                var $btn = $(this);
                var voucherId = $btn.data('voucher-id');
                var voucherCode = $btn.data('code');
                
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
            });
            
            // Close edit modal handler
            $(document).on('click', '.btn-close-edit-modal', function() {
                $('#editModal').addClass('hidden');
            });
            
            // Close assign modal handler
            $(document).on('click', '.btn-close-assign-modal', function() {
                $('#assignModal').addClass('hidden');
            });
        });
        
        // Legacy function names for backward compatibility (if needed)
            function openEditModal(voucherId, code, description, type, discountValue, minSpend, maxDiscount, startDate, endDate, isRedeemable) {
            $('#editVoucherId').val(voucherId);
            $('#editCode').val(code);
            $('#editDescription').val(description || '');
            $('#editType').val(type);
            $('#editDiscountValue').val(discountValue);
            $('#editMinSpend').val(minSpend || '0');
            $('#editMaxDiscount').val(maxDiscount || '');
            $('#editStartDate').val(startDate);
            $('#editEndDate').val(endDate);
            $('#editIsRedeemable').prop('checked', isRedeemable !== undefined ? isRedeemable : true);
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

            showConfirmationModal(
                'Are you sure you want to ' + action + ' voucher: ' + voucherCode + '?',
                'warning',
                function() {
                    $('#statusVoucherId').val(voucherId);
                    $('#statusValue').val(newStatus);
                    $('#statusForm').submit();
                }
            );
        }

        function confirmDelete(voucherId, voucherCode) {
            showConfirmationModal(
                'Are you sure you want to delete voucher: ' + voucherCode + '?<br><br>This action cannot be undone.',
                'danger',
                function() {
                    $('#deleteVoucherId').val(voucherId);
                    $('#deleteForm').submit();
                }
            );
        }

        function showConfirmationModal(message, type, onConfirm) {
            type = type || 'warning';
            const iconClass = type === 'danger' ? 'danger' : 'warning';
            const confirmBtnClass = type === 'danger' ? 'confirmation-modal-btn-confirm' : 'confirmation-modal-btn-confirm warning';
            const confirmText = type === 'danger' ? 'Delete' : 'Confirm';

            $('#confirmationModalMessage').html(message);
            $('#confirmationModalIcon').removeClass('warning danger').addClass(iconClass);
            $('#confirmationModalConfirmBtn').removeClass('warning confirmation-modal-btn-confirm').addClass(confirmBtnClass).text(confirmText);

            // Store the confirm callback
            $('#confirmationModalConfirmBtn').off('click').on('click', function() {
                hideConfirmationModal();
                if (onConfirm) onConfirm();
            });

            $('#confirmationModal').addClass('show');
            $('body').css('overflow', 'hidden');
        }

        function hideConfirmationModal() {
            $('#confirmationModal').removeClass('show');
            $('body').css('overflow', 'auto');
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
                    
                showConfirmationModal(
                    confirmMsg,
                    'warning',
                    function() {
                        $('#assignForm').submit();
                    }
                );
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

        // Bulk selection functionality
        let selectedVouchers = new Set();

        // Select all checkbox
        $('#selectAllCheckbox').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.voucher-checkbox').prop('checked', isChecked);
            
            if (isChecked) {
                $('.voucher-checkbox').each(function() {
                    selectedVouchers.add($(this).val());
                });
            } else {
                selectedVouchers.clear();
            }
            
            updateBulkActions();
        });

        // Individual checkbox change
        $(document).on('change', '.voucher-checkbox', function() {
            const voucherId = $(this).val();
            if ($(this).is(':checked')) {
                selectedVouchers.add(voucherId);
            } else {
                selectedVouchers.delete(voucherId);
                $('#selectAllCheckbox').prop('checked', false);
            }
            updateBulkActions();
        });

        // Update bulk actions visibility and count
        function updateBulkActions() {
            const count = selectedVouchers.size;
            $('#selectedCount').text(count);
            
            if (count > 0) {
                $('#bulkActionsSection').show();
            } else {
                $('#bulkActionsSection').hide();
            }
        }

        // Clear selection
        $('#clearSelectionBtn').on('click', function() {
            $('.voucher-checkbox').prop('checked', false);
            $('#selectAllCheckbox').prop('checked', false);
            selectedVouchers.clear();
            updateBulkActions();
        });

        // Bulk delete
        $('#bulkDeleteBtn').on('click', function() {
            const count = selectedVouchers.size;
            if (count === 0) {
                alert('Please select at least one voucher to delete.');
                return;
            }

            showConfirmationModal(
                `Are you sure you want to delete ${count} voucher(s)?<br><br>This action cannot be undone.`,
                'danger',
                function() {
                    // Clear existing hidden inputs
                    $('#bulkDeleteForm input[name="voucher_ids[]"]').remove();
                    
                    // Add selected voucher IDs
                    selectedVouchers.forEach(function(voucherId) {
                        $('#bulkDeleteForm').append(`<input type="hidden" name="voucher_ids[]" value="${voucherId}">`);
                    });
                    
                    $('#bulkDeleteForm').submit();
                }
            );
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
                        <button type="button" class="btn btn-secondary btn-close-assign-modal">
                            <span class="material-symbols-outlined">close</span>
                            <span>Cancel</span>
                        </button>
                        <button type="button" id="assignFormSubmit" class="btn btn-primary">
                            <span class="material-symbols-outlined">send</span>
                            <span>Assign Voucher</span>
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

                    <div class="modal-form-grid">
                        <div class="form-group">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" id="editCode" class="form-input">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Type</label>
                            <select name="type" id="editType" class="form-input">
                                <option value="percent">Percent</option>
                                <option value="fixed">Fixed</option>
                                <option value="freeshipping">Free Shipping</option>
                            </select>
                        </div>

                        <div class="form-group form-group-full">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" id="editDescription" class="form-input">
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

                        <div class="form-group form-group-full">
                            <label class="form-label" style="flex-direction: row; align-items: center; cursor: pointer;">
                                <input type="checkbox" name="is_redeemable" id="editIsRedeemable" value="1" checked style="width: auto; margin-right: 0.5rem;">
                                <span>Allow members to redeem this voucher</span>
                            </label>
                            <small style="display: block; margin-top: 0.5rem; color: #6b7280; font-size: 0.8125rem;">If unchecked, only admins can assign this voucher to members.</small>
                        </div>
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

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="confirmation-modal-overlay">
        <div class="confirmation-modal-content">
            <div class="confirmation-modal-header">
                <div class="confirmation-modal-icon" id="confirmationModalIcon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="confirmation-modal-title">Confirm Action</h3>
            </div>
            <div class="confirmation-modal-body">
                <p class="confirmation-modal-message" id="confirmationModalMessage"></p>
            </div>
            <div class="confirmation-modal-actions">
                <button type="button" class="confirmation-modal-btn confirmation-modal-btn-cancel" onclick="hideConfirmationModal()">
                    <span>Cancel</span>
                </button>
                <button type="button" class="confirmation-modal-btn confirmation-modal-btn-confirm" id="confirmationModalConfirmBtn">
                    <span>Confirm</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Close confirmation modal on overlay click
        $(document).on('click', '#confirmationModal', function(e) {
            if ($(e.target).hasClass('confirmation-modal-overlay')) {
                hideConfirmationModal();
            }
        });

        // Close confirmation modal on Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#confirmationModal').hasClass('show')) {
                hideConfirmationModal();
            }
        });
    </script>

</body>

</html>

