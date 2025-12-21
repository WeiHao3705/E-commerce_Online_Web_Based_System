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

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']->role !== 'admin') {
    header('Location: ../views/security/login.php');
    exit;
}

$pageTitle = 'All Members - Admin Dashboard';

// Get current sort parameters
$currentSortBy = isset($currentSort['sortBy']) ? $currentSort['sortBy'] : 'created_at';
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

    return 'MemberController.php?' . http_build_query($params);
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

// Helper function to get profile photo URL
function getProfilePhotoUrl($photoPath, $imageBasePath)
{
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>AdminOrder.css">
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>AllTables.css">
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>AllMembers.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: transparent;
            color: #0f172a;
        }

        .page-container {
            max-width: 100%;
            margin: 0;
            padding: 20px;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-color: #FF523B;
        }

        .stat-icon {
            width: 3.5rem;
            height: 3.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.75rem;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .stat-icon.red {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .stat-info h3 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .stat-info p {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0;
        }

        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            margin-bottom: 1.5rem;
        }

        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-group label i {
            color: #FF523B;
        }

        .filter-group input,
        .filter-group select {
            padding: 0.625rem 0.875rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #FF523B;
            box-shadow: 0 0 0 3px rgba(255, 82, 59, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
        }

        .btn {
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

        .btn-primary {
            background: #FF523B;
            color: white;
        }

        .btn-primary:hover {
            background: #e64a35;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .table-container {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table thead {
            background: #f9fafb;
        }

        .orders-table thead th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }

        .orders-table tbody td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.875rem;
        }

        .orders-table tbody tr:hover {
            background: #f9fafb;
        }

        .orders-table tbody tr:last-child td {
            border-bottom: none;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .action-btn {
            width: 2rem !important;
            height: 2rem !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 0.375rem !important;
            border: 1px solid #d1d5db !important;
            background: white !important;
            cursor: pointer !important;
            transition: all 0.2s !important;
            color: #6b7280 !important;
            margin-right: 0.25rem;
            padding: 0 !important;
        }

        .action-btn:hover {
            background: #f3f4f6 !important;
            border-color: #9ca3af !important;
            color: #374151 !important;
        }

        .action-btn.edit-btn {
            background: #fffbeb !important;
            border-color: #f59e0b !important;
            color: #f59e0b !important;
        }

        .action-btn.edit-btn:hover {
            background: #fef3c7 !important;
            border-color: #d97706 !important;
            color: #d97706 !important;
        }

        .action-btn.ban-btn {
            background: #fef2f2 !important;
            border-color: #ef4444 !important;
            color: #ef4444 !important;
        }

        .action-btn.ban-btn:hover {
            background: #fee2e2 !important;
            border-color: #dc2626 !important;
            color: #dc2626 !important;
        }

        .action-btn.inactive-btn {
            background: #fffbeb !important;
            border-color: #f59e0b !important;
            color: #f59e0b !important;
        }

        .action-btn.inactive-btn:hover {
            background: #fef3c7 !important;
            border-color: #d97706 !important;
            color: #d97706 !important;
        }

        .action-btn.activate-btn {
            background: #f0fdf4 !important;
            border-color: #10b981 !important;
            color: #10b981 !important;
        }

        .action-btn.activate-btn:hover {
            background: #d1fae5 !important;
            border-color: #059669 !important;
            color: #059669 !important;
        }

        .action-btn.delete-btn {
            background: #fef2f2 !important;
            border-color: #ef4444 !important;
            color: #ef4444 !important;
        }

        .action-btn.delete-btn:hover {
            background: #fee2e2 !important;
            border-color: #dc2626 !important;
            color: #dc2626 !important;
        }

        .action-btn .material-symbols-outlined {
            font-size: 1.125rem !important;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.status-inactive {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.status-banned {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-badge.status-blocked {
            background: #e5e7eb;
            color: #111827;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            padding: 3rem;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 3rem;
        }

        .empty-state p {
            font-size: 1rem;
            font-weight: 500;
        }

        .bulk-actions-section {
            margin-top: 1rem;
            padding: 1rem;
            background: #fef3c7;
            border-radius: 0.5rem;
            border: 1px solid #fcd34d;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .col-checkbox {
            width: 40px;
        }

        .member-profile-photo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: white;
            border-top: 1px solid #e5e7eb;
        }

        .pagination-info {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .pagination-number {
            font-weight: 600;
            color: #0f172a;
        }

        .pagination-list {
            display: flex;
            gap: 0.25rem;
            list-style: none;
        }

        .pagination-link {
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            color: #374151;
            text-decoration: none;
            transition: all 0.2s;
        }

        .pagination-link:hover:not(.pagination-disabled) {
            background: #f3f4f6;
            border-color: #9ca3af;
        }

        .pagination-link.pagination-active {
            background: #FF523B;
            color: white;
            border-color: #FF523B;
        }

        .pagination-link.pagination-disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .text-center {
            text-align: center;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0;
        }

        .message {
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .message-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .message-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

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

        .phone-input-group {
            display: flex;
            gap: 0.75rem;
            align-items: stretch;
        }

        .country-code-wrapper {
            flex-shrink: 0;
            display: flex;
        }

        .country-code-select {
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.625rem;
            font-size: 0.9375rem;
            background: white;
            color: #0f172a;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
            min-height: 48px;
            line-height: 1.5;
            box-sizing: border-box;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        .country-code-select:focus {
            outline: none;
            border-color: #FF523B;
            box-shadow: 0 0 0 4px rgba(255, 82, 59, 0.1);
        }

        .phone-number-wrapper {
            flex: 1;
            position: relative;
            display: flex;
        }

        .phone-number-input {
            padding: 0.875rem 1rem;
            padding-left: 2.75rem;
            width: 100%;
            min-height: 48px;
            box-sizing: border-box;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1rem;
        }

        .input-hint {
            font-size: 0.8125rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }

        .phone-validation-error {
            color: #ef4444;
            font-size: 0.8125rem;
            margin-top: 0.25rem;
            display: none;
        }

        .phone-validation-error:not(:empty) {
            display: block;
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

            .phone-input-group {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="page-container">
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

        <!-- Header -->
        <header class="header-actions">
            <h1 style="font-size: 2rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-users" style="color: #FF523B;"></i> Member Management
            </h1>
        </header>

        <!-- Statistics Cards -->
        <section class="stats-grid">
            <?php
            // Calculate statistics
            $totalMembers = $pagination['total_members'] ?? 0;
            $activeCount = 0;
            $inactiveCount = 0;
            $bannedCount = 0;
            $blockedCount = 0;
            if (!empty($members)) {
                foreach ($members as $member) {
                    $status = $member['status'] ?? 'active';
                    if ($status === 'active') $activeCount++;
                    elseif ($status === 'inactive') $inactiveCount++;
                    elseif ($status === 'banned') $bannedCount++;
                    elseif ($status === 'blocked') $blockedCount++;
                }
            }
            ?>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($totalMembers) ?></h3>
                    <p>Total Members</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-user-check"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($activeCount) ?></h3>
                    <p>Active Members</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-user-clock"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($inactiveCount) ?></h3>
                    <p>Inactive Members</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-user-slash"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($bannedCount) ?></h3>
                    <p>Banned Members</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-user-lock"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($blockedCount) ?></h3>
                    <p>Blocked Members</p>
                </div>
            </div>
        </section>

        <!-- Filters -->
        <section class="filters-section">
            <form method="GET" action="MemberController.php" class="filters-form" id="filterForm">
                <input type="hidden" name="action" value="showAll">
                <input type="hidden" name="sortBy" id="filterSortBy" value="<?= $currentSortBy ?>">
                <input type="hidden" name="sortOrder" id="filterSortOrder" value="<?= $currentSortOrder ?>">

                <div class="filter-group">
                    <label><i class="fas fa-search"></i> Search</label>
                    <input type="text" name="search" id="filterSearch" placeholder="Username, Email, Name..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-filter"></i> Status</label>
                    <select name="status" id="filterStatus">
                        <option value="">All Status</option>
                        <option value="active" <?= (isset($_GET['status']) && $_GET['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= (isset($_GET['status']) && $_GET['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                        <option value="banned" <?= (isset($_GET['status']) && $_GET['status'] === 'banned') ? 'selected' : '' ?>>Banned</option>
                        <option value="blocked" <?= (isset($_GET['status']) && $_GET['status'] === 'blocked') ? 'selected' : '' ?>>Blocked</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-sort"></i> Sort By</label>
                    <select name="sortBy" id="filterSortBySelect">
                        <option value="created_at" <?= $currentSortBy === 'created_at' ? 'selected' : '' ?>>Join Date</option>
                        <option value="username" <?= $currentSortBy === 'username' ? 'selected' : '' ?>>Username</option>
                        <option value="full_name" <?= $currentSortBy === 'full_name' ? 'selected' : '' ?>>Full Name</option>
                        <option value="email" <?= $currentSortBy === 'email' ? 'selected' : '' ?>>Email</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-arrow-up"></i> Order</label>
                    <select name="sortOrder" id="filterSortOrderSelect">
                        <option value="DESC" <?= $currentSortOrder === 'DESC' ? 'selected' : '' ?>>Descending</option>
                        <option value="ASC" <?= $currentSortOrder === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="MemberController.php?action=showAll" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </form>
        </section>

        <!-- Bulk Actions Section -->
        <div class="bulk-actions-section" style="display: none;" id="bulkActionsSection">
            <button type="button" class="btn btn-danger" id="bulkDeleteBtn">
                <span class="material-symbols-outlined">delete</span>
                <span>Delete Selected (<span id="selectedCount">0</span>)</span>
            </button>
            <button type="button" class="btn btn-secondary" id="clearSelectionBtn">
                <span class="material-symbols-outlined">close</span>
                <span>Clear Selection</span>
            </button>
        </div>

        <!-- Members Table -->
        <section class="table-container" id="members-table-wrapper">
            <table class="orders-table" id="members-table">
                <thead>
                    <tr>
                        <th class="col-checkbox">
                            <input type="checkbox" id="selectAllCheckbox" title="Select all">
                        </th>
                        <th>Photo</th>
                        <th>Member Info</th>
                        <th>Contact</th>
                        <th>Gender</th>
                        <th>DOB</th>
                        <th>Joined</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($members)): ?>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td class="col-checkbox">
                                    <input type="checkbox" class="member-checkbox" name="member_ids[]" value="<?php echo $member['user_id']; ?>" data-member-name="<?php echo htmlspecialchars($member['full_name'], ENT_QUOTES); ?>">
                                </td>
                                <td>
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
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($member['username']); ?></strong>
                                        <br><small style="color: #6b7280;"><?php echo htmlspecialchars($member['full_name']); ?></small>
                                        <br><small style="color: #9ca3af;"><?php echo htmlspecialchars($member['email']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($member['contact_no']); ?></td>
                                <td><?php echo htmlspecialchars($member['gender']); ?></td>
                                <td>
                                    <?php
                                    if (!empty($member['DateOfBirth'])) {
                                        $dob = new DateTime($member['DateOfBirth']);
                                        echo $dob->format('M d, Y');
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $date = new DateTime($member['created_at']);
                                    echo $date->format('M d, Y');
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $status = $member['status'] ?? 'active';
                                    $statusClass = '';
                                    $statusText = ucfirst($status);

                                    switch ($status) {
                                        case 'active':
                                            $statusClass = 'status-badge status-active';
                                            break;
                                        case 'inactive':
                                            $statusClass = 'status-badge status-inactive';
                                            break;
                                        case 'banned':
                                            $statusClass = 'status-badge status-banned';
                                            break;
                                        case 'blocked':
                                            $statusClass = 'status-badge status-blocked';
                                            break;
                                        default:
                                            $statusClass = 'status-badge status-active';
                                    }
                                    ?>
                                    <span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span>
                                </td>
                                <td class="col-actions">
                                    <div class="action-buttons">
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
                                            <i class="fas fa-edit"></i>
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
                                                <i class="fas fa-ban"></i>
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
                                                <i class="fas fa-pause-circle"></i>
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
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                        <?php endif; ?>

                                        <button
                                            class="action-btn delete-btn"
                                            data-action="delete"
                                            data-user-id="<?php echo $member['user_id']; ?>"
                                            data-user-name="<?php echo htmlspecialchars($member['full_name'], ENT_QUOTES); ?>"
                                            title="Delete member">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>No members found<?php echo !empty($_GET['search']) ? '. Try a different search term.' : ''; ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

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
                                if (!empty($_GET['status'])) $prevParams['status'] = $_GET['status'];
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
                                if (!empty($_GET['status'])) $pageParams['status'] = $_GET['status'];
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
                                if (!empty($_GET['status'])) $nextParams['status'] = $_GET['status'];
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
        </section>
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

    <!-- Bulk Delete Form (Hidden) -->
    <form id="bulkDeleteForm" method="POST" action="MemberController.php" style="display: none;">
        <input type="hidden" name="action" value="bulkDelete">
    </form>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const imageBasePath = '<?php echo $imageBasePath; ?>';
        let searchTimeout;

        // AJAX Search with real-time filtering
        $('#filterSearch').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                performAjaxFilter();
            }, 500);
        });

        // AJAX Filter on form submit
        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            performAjaxFilter();
        });

        // AJAX Filter on dropdown change
        $('#filterStatus, #filterSortBySelect, #filterSortOrderSelect').on('change', function() {
            performAjaxFilter();
        });

        function performAjaxFilter() {
            const searchTerm = $('#filterSearch').val().trim();
            const status = $('#filterStatus').val();
            const sortBy = $('#filterSortBySelect').val() || 'created_at';
            const sortOrder = $('#filterSortOrderSelect').val() || 'DESC';

            const tableWrapper = $('#members-table-wrapper');
            tableWrapper.css('opacity', '0.6');

            const requestData = {
                action: 'showAll',
                ajax: '1',
                search: searchTerm,
                status: status,
                sortBy: sortBy,
                sortOrder: sortOrder,
                page: 1
            };

            $.ajax({
                url: 'MemberController.php',
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
            const tbody = $('#members-table tbody');
            tbody.empty();

            // Get selected members from sessionStorage
            const selected = getSelectedMembers();

            if (response.members && response.members.length > 0) {
                response.members.forEach(function(member) {
                    const row = buildMemberRow(member);
                    tbody.append(row);
                });
                
                // Restore checkbox states from sessionStorage
                $('.member-checkbox').each(function() {
                    const memberId = $(this).val();
                    if (selected.has(memberId)) {
                        $(this).prop('checked', true);
                    }
                });
                
                // Update select all checkbox state
                const allChecked = $('.member-checkbox').length > 0 && 
                    $('.member-checkbox').length === $('.member-checkbox:checked').length;
                $('#selectAllCheckbox').prop('checked', allChecked);
            } else {
                tbody.append(`
                    <tr>
                        <td colspan="9" class="text-center">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No members found. Try a different search term.</p>
                            </div>
                        </td>
                    </tr>
                `);
            }
            
            updateBulkActions();
        }

        function buildMemberRow(member) {
            // Get profile photo URL
            let photoUrl = '';
            if (member.profile_photo && member.profile_photo.trim() !== '') {
                // Remove 'web/' prefix if present
                let photoPath = member.profile_photo;
                if (photoPath.indexOf('web/') === 0) {
                    photoPath = photoPath.substring(4);
                }
                photoPath = photoPath.replace(/^\/+/, ''); // Remove leading slashes
                photoUrl = imageBasePath + photoPath;
            } else {
                photoUrl = imageBasePath + 'images/defaultUserImage.jpg';
            }
            const defaultPhotoUrl = imageBasePath + 'images/defaultUserImage.jpg';

            // Format date of birth
            let dobDisplay = '-';
            if (member.DateOfBirth) {
                const dob = new Date(member.DateOfBirth);
                if (!isNaN(dob.getTime())) {
                    dobDisplay = dob.toISOString().split('T')[0];
                }
            }

            // Format created date
            let createdDateDisplay = '-';
            if (member.created_at) {
                const createdDate = new Date(member.created_at);
                if (!isNaN(createdDate.getTime())) {
                    createdDateDisplay = createdDate.toISOString().split('T')[0];
                }
            }

            // Status badge
            const status = member.status || 'active';
            const statusLabels = {
                'active': {
                    class: 'status-active',
                    text: 'Active'
                },
                'inactive': {
                    class: 'status-inactive',
                    text: 'Inactive'
                },
                'banned': {
                    class: 'status-banned',
                    text: 'Banned'
                },
                'blocked': {
                    class: 'status-blocked',
                    text: 'Blocked'
                }
            };
            const statusInfo = statusLabels[status] || statusLabels['active'];

            // Build status buttons
            let statusButtons = '';
            if (status !== 'banned') {
                statusButtons += '<button class="action-btn ban-btn" data-action="status" data-user-id="' + member.user_id + '" data-user-name="' + escapeHtml(member.full_name) + '" data-status="banned" title="Ban member"><i class="fas fa-ban"></i></button>';
            }
            if (status !== 'inactive') {
                statusButtons += '<button class="action-btn inactive-btn" data-action="status" data-user-id="' + member.user_id + '" data-user-name="' + escapeHtml(member.full_name) + '" data-status="inactive" title="Set to inactive"><i class="fas fa-pause-circle"></i></button>';
            }
            if (status !== 'active') {
                statusButtons += '<button class="action-btn activate-btn" data-action="status" data-user-id="' + member.user_id + '" data-user-name="' + escapeHtml(member.full_name) + '" data-status="active" title="Activate member"><i class="fas fa-check-circle"></i></button>';
            }

            const row = `
                <tr>
                    <td class="col-checkbox">
                        <input type="checkbox" class="member-checkbox" name="member_ids[]" value="${member.user_id}" data-member-name="${escapeHtml(member.full_name)}">
                    </td>
                    <td>
                        <img src="${escapeHtml(photoUrl)}" 
                             alt="Profile photo"
                             class="member-profile-photo clickable-image"
                             data-image-url="${escapeHtml(photoUrl)}"
                             data-member-name="${escapeHtml(member.full_name)}"
                             onerror="this.onerror=null; this.src='${escapeHtml(defaultPhotoUrl)}';"
                             style="cursor: pointer;"
                             title="Click to view full size">
                    </td>
                    <td>
                        <div>
                            <strong>${escapeHtml(member.username)}</strong>
                            <br><small style="color: #6b7280;">${escapeHtml(member.full_name)}</small>
                            <br><small style="color: #9ca3af;">${escapeHtml(member.email)}</small>
                        </div>
                    </td>
                    <td>${escapeHtml(member.contact_no)}</td>
                    <td>${escapeHtml(member.gender)}</td>
                    <td>${dobDisplay}</td>
                    <td>${createdDateDisplay}</td>
                    <td>
                        <span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>
                    </td>
                    <td class="col-actions">
                        <div class="action-buttons">
                            <button class="action-btn edit-btn" data-user-id="${member.user_id}" data-username="${escapeHtml(member.username)}" data-full-name="${escapeHtml(member.full_name)}" data-email="${escapeHtml(member.email)}" data-contact-no="${escapeHtml(member.contact_no)}" data-gender="${escapeHtml(member.gender)}" data-date-of-birth="${escapeHtml(member.DateOfBirth || '')}" title="Edit member">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${statusButtons}
                            <button class="action-btn delete-btn" data-action="delete" data-user-id="${member.user_id}" data-user-name="${escapeHtml(member.full_name)}" title="Delete member">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            return row;
        }

        function updatePagination(response) {
            const pagination = response.pagination;
            const paginationNav = $('.pagination');

            if (pagination.total_members > 0) {
                // Update pagination info
                $('.pagination-info').html(`
                    Showing <span class="pagination-number">${pagination.showing_from}-${pagination.showing_to}</span> of <span class="pagination-number">${pagination.total_members}</span>
                `);

                // Update pagination links
                const paginationList = $('.pagination-list');
                paginationList.empty();

                const searchTerm = $('#filterSearch').val();
                const status = $('#filterStatus').val();
                const sortBy = response.sortBy || 'created_at';
                const sortOrder = response.sortOrder || 'DESC';

                // Previous button
                const prevUrl = pagination.current_page > 1 ?
                    `MemberController.php?action=showAll&page=${pagination.current_page - 1}&search=${encodeURIComponent(searchTerm)}&status=${status}&sortBy=${sortBy}&sortOrder=${sortOrder}` : '#';
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
                    const pageUrl = `MemberController.php?action=showAll&page=${i}&search=${encodeURIComponent(searchTerm)}&status=${status}&sortBy=${sortBy}&sortOrder=${sortOrder}`;
                    paginationList.append(`
                        <li>
                            <a href="${pageUrl}" class="pagination-link ${activeClass}">${i}</a>
                        </li>
                    `);
                }

                // Next button
                const nextUrl = pagination.current_page < pagination.total_pages ?
                    `MemberController.php?action=showAll&page=${pagination.current_page + 1}&search=${encodeURIComponent(searchTerm)}&status=${status}&sortBy=${sortBy}&sortOrder=${sortOrder}` : '#';
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

        function parseContactNumber(contactNo) {
            if (!contactNo) {
                return {
                    countryCode: '+60',
                    phoneNumber: ''
                };
            }

            // Try to extract country code (format: "+60 11-5550 5761" or "+60 1155505761")
            const countryCodes = ['+60', '+1', '+44', '+65', '+86', '+81', '+61', '+33', '+49'];
            let countryCode = '+60'; // default
            let phoneNumber = contactNo;

            for (const code of countryCodes) {
                if (contactNo.startsWith(code)) {
                    countryCode = code;
                    phoneNumber = contactNo.substring(code.length).trim();
                    break;
                }
            }

            return {
                countryCode,
                phoneNumber
            };
        }

        function openEditModal(userId, username, fullName, email, contactNo, gender, dateOfBirth) {
            $('#editUserId').val(userId);
            $('#editUsername').val(username);
            $('#editFullName').val(fullName);
            $('#editEmail').val(email);

            // Parse contact number
            const parsed = parseContactNumber(contactNo);
            $('#editCountryCode').val(parsed.countryCode);
            $('#editPhoneNumber').val(parsed.phoneNumber);
            updateEditPhoneFormatHint();

            $('#editGender').val(gender);
            $('#editDateOfBirth').val(dateOfBirth || '');

            $('#editModal').removeClass('hidden');
        }

        function closeEditModal() {
            $('#editModal').addClass('hidden');
        }

        // Phone validation patterns by country code
        const phonePatterns = {
            '+60': { // Malaysia
                pattern: /^[0-9]{2,3}[- ]?[0-9]{3,4}[- ]?[0-9]{4}$/,
                example: '11-5550 5761',
                minLength: 9,
                maxLength: 12
            },
            '+1': { // US/Canada
                pattern: /^[0-9]{3}[- ]?[0-9]{3}[- ]?[0-9]{4}$/,
                example: '555-123-4567',
                minLength: 10,
                maxLength: 12
            },
            '+44': { // UK
                pattern: /^[0-9]{2,4}[- ]?[0-9]{3,4}[- ]?[0-9]{3,4}$/,
                example: '20 7946 0958',
                minLength: 10,
                maxLength: 13
            },
            '+65': { // Singapore
                pattern: /^[689][0-9]{7}$/,
                example: '81234567',
                minLength: 8,
                maxLength: 8
            },
            '+86': { // China
                pattern: /^1[3-9][0-9]{9}$/,
                example: '13800138000',
                minLength: 11,
                maxLength: 11
            },
            '+81': { // Japan
                pattern: /^[0-9]{2,4}[- ]?[0-9]{2,4}[- ]?[0-9]{4}$/,
                example: '90-1234-5678',
                minLength: 10,
                maxLength: 13
            },
            '+61': { // Australia
                pattern: /^[0-9]{2}[- ]?[0-9]{4}[- ]?[0-9]{4}$/,
                example: '04 1234 5678',
                minLength: 10,
                maxLength: 12
            },
            '+33': { // France
                pattern: /^[0-9]{2}[- ]?[0-9]{2}[- ]?[0-9]{2}[- ]?[0-9]{2}[- ]?[0-9]{2}$/,
                example: '06 12 34 56 78',
                minLength: 10,
                maxLength: 14
            },
            '+49': { // Germany
                pattern: /^[0-9]{3,4}[- ]?[0-9]{3,8}$/,
                example: '151 23456789',
                minLength: 10,
                maxLength: 13
            }
        };

        function validateEditPhoneNumber() {
            const countryCode = $('#editCountryCode').val();
            const phoneNumber = $('#editPhoneNumber').val().replace(/\s+/g, ' ').trim();
            const config = phonePatterns[countryCode];
            const $phoneNumber = $('#editPhoneNumber');
            const $phoneValidationError = $('#editPhoneValidationError');

            if (!phoneNumber) {
                $phoneNumber.removeClass('input-error input-success');
                $phoneValidationError.text('').hide();
                $('#editContactNo').val('');
                return false;
            }

            // Remove spaces and dashes for validation
            const cleanPhone = phoneNumber.replace(/[- ]/g, '');

            // Check length
            if (cleanPhone.length < config.minLength || cleanPhone.length > config.maxLength) {
                $phoneNumber.addClass('input-error').removeClass('input-success');
                $phoneValidationError.text(`Phone number must be ${config.minLength}-${config.maxLength} digits. Example: ${config.example}`).show();
                $('#editContactNo').val('');
                return false;
            }

            // Check pattern
            if (!config.pattern.test(phoneNumber)) {
                $phoneNumber.addClass('input-error').removeClass('input-success');
                $phoneValidationError.text(`Invalid phone format. Example: ${config.example}`).show();
                $('#editContactNo').val('');
                return false;
            }

            // Valid phone number
            $phoneNumber.removeClass('input-error').addClass('input-success');
            $phoneValidationError.text('').hide();

            // Combine country code and phone number
            const fullPhoneNumber = countryCode + ' ' + phoneNumber;
            $('#editContactNo').val(fullPhoneNumber);

            return true;
        }

        function updateEditPhoneFormatHint() {
            const countryCode = $('#editCountryCode').val();
            const config = phonePatterns[countryCode];
            if (config) {
                $('#editPhoneFormatHint').text(`Format: ${config.example} (${config.minLength}-${config.maxLength} digits)`);
                $('#editPhoneNumber').attr('placeholder', `e.g., ${config.example}`);
            }
        }

        // Phone validation event handlers
        $(document).on('change', '#editCountryCode', function() {
            updateEditPhoneFormatHint();
            validateEditPhoneNumber();
        });

        $(document).on('input', '#editPhoneNumber', function() {
            validateEditPhoneNumber();
        });

        // Form submission validation
        $(document).on('submit', '#editForm', function(e) {
            if (!validateEditPhoneNumber()) {
                e.preventDefault();
                alert('Please enter a valid phone number.');
                $('#editPhoneNumber').focus();
                return false;
            }
            return true;
        });

        function confirmStatusChange(userId, userName, newStatus) {
            var statusLabels = {
                'active': 'activate',
                'inactive': 'set to inactive',
                'banned': 'ban'
            };
            var action = statusLabels[newStatus] || newStatus;

            showConfirmationModal(
                'Are you sure you want to ' + action + ' member: ' + userName + '?',
                'warning',
                function() {
                $('#statusUserId').val(userId);
                $('#statusValue').val(newStatus);
                $('#statusForm').submit();
            }
            );
        }

        function confirmDelete(userId, userName) {
            showConfirmationModal(
                'Are you sure you want to delete member: ' + userName + '?<br><br>This action cannot be undone.',
                'danger',
                function() {
                $('#deleteUserId').val(userId);
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
            $('#confirmationModalConfirmBtn').removeClass('warning').addClass(confirmBtnClass).text(confirmText);

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

        // Bulk selection functionality using sessionStorage
        const STORAGE_KEY = 'selectedMembers';
        
        // Helper functions for sessionStorage
        function getSelectedMembers() {
            const stored = sessionStorage.getItem(STORAGE_KEY);
            return stored ? new Set(JSON.parse(stored)) : new Set();
        }
        
        function saveSelectedMembers(selectedSet) {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(selectedSet)));
        }
        
        function addSelectedMember(memberId) {
            const selected = getSelectedMembers();
            selected.add(memberId);
            saveSelectedMembers(selected);
            return selected;
        }
        
        function removeSelectedMember(memberId) {
            const selected = getSelectedMembers();
            selected.delete(memberId);
            saveSelectedMembers(selected);
            return selected;
        }
        
        function clearSelectedMembers() {
            sessionStorage.removeItem(STORAGE_KEY);
        }

        // Select all checkbox
        $('#selectAllCheckbox').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.member-checkbox').prop('checked', isChecked);

            if (isChecked) {
                // Fetch ALL member IDs matching current filters
                const searchTerm = $('#filterSearch').val().trim();
                const status = $('#filterStatus').val();
                
                $.ajax({
                    url: 'MemberController.php',
                    method: 'GET',
                    data: {
                        action: 'showAll',
                        ajax: '1',
                        getAllIds: '1',
                        search: searchTerm,
                        status: status
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.ids) {
                            // Store all matching IDs in sessionStorage
                            const allIds = new Set(response.ids.map(String));
                            saveSelectedMembers(allIds);
                            
                            // Update checkboxes on current page
                            $('.member-checkbox').each(function() {
                                const memberId = $(this).val();
                                if (allIds.has(memberId)) {
                                    $(this).prop('checked', true);
                                }
                            });
                            
                            updateBulkActions();
                        } else {
                            alert('Error fetching all member IDs: ' + (response.error || 'Unknown error'));
                            $('#selectAllCheckbox').prop('checked', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        alert('Error fetching all member IDs. Please try again.');
                        $('#selectAllCheckbox').prop('checked', false);
                    }
                });
            } else {
                // Remove all current page members from selection
                const selected = getSelectedMembers();
                $('.member-checkbox').each(function() {
                    selected.delete($(this).val());
                });
                saveSelectedMembers(selected);
                updateBulkActions();
            }
        });

        // Individual checkbox change
        $(document).on('change', '.member-checkbox', function() {
            const memberId = $(this).val();
            if ($(this).is(':checked')) {
                addSelectedMember(memberId);
            } else {
                removeSelectedMember(memberId);
                $('#selectAllCheckbox').prop('checked', false);
            }
            updateBulkActions();
        });

        // Update bulk actions visibility and count
        function updateBulkActions() {
            const selected = getSelectedMembers();
            const count = selected.size;
            $('#selectedCount').text(count);

            if (count > 0) {
                $('#bulkActionsSection').show();
            } else {
                $('#bulkActionsSection').hide();
            }
        }

        // Clear selection
        $('#clearSelectionBtn').on('click', function() {
            $('.member-checkbox').prop('checked', false);
            $('#selectAllCheckbox').prop('checked', false);
            clearSelectedMembers();
            updateBulkActions();
        });

        // Bulk delete
        $('#bulkDeleteBtn').on('click', function() {
            const selected = getSelectedMembers();
            const count = selected.size;
            if (count === 0) {
                alert('Please select at least one member to delete.');
                return;
            }

            showConfirmationModal(
                `Are you sure you want to delete ${count} member(s)?<br><br>This action cannot be undone.`,
                'danger',
                function() {
                // Clear existing hidden inputs
                $('#bulkDeleteForm input[name="user_ids[]"]').remove();

                // Add selected member IDs from sessionStorage
                selected.forEach(function(memberId) {
                    $('#bulkDeleteForm').append(`<input type="hidden" name="user_ids[]" value="${memberId}">`);
                });

                // Clear selection after deletion
                clearSelectedMembers();
                
                $('#bulkDeleteForm').submit();
            }
            );
        });

        // Restore selections on initial page load
        $(document).ready(function() {
            // Restore checkbox states from sessionStorage
            const selected = getSelectedMembers();
            $('.member-checkbox').each(function() {
                const memberId = $(this).val();
                if (selected.has(memberId)) {
                    $(this).prop('checked', true);
                }
            });
            
            // Update select all checkbox state
            const allChecked = $('.member-checkbox').length > 0 && 
                $('.member-checkbox').length === $('.member-checkbox:checked').length;
            $('#selectAllCheckbox').prop('checked', allChecked);
            
            // Update bulk actions
            updateBulkActions();
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

                    <div class="modal-form-grid">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="editUsername" readonly
                            class="form-input form-input-readonly"
                            title="Username cannot be changed" />
                    </div>

                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="editFullName" class="form-input" />
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="editEmail" class="form-input" />
                    </div>

                    <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select name="gender" id="editGender" class="form-input">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="form-group form-group-full">
                        <label class="form-label">Contact Number</label>
                        <div class="phone-input-group">
                            <div class="country-code-wrapper">
                                <select id="editCountryCode" name="country_code" class="country-code-select" required>
                                    <option value="+60">🇲🇾 +60 (MY)</option>
                                    <option value="+1">🇺🇸 +1 (US)</option>
                                    <option value="+44">🇬🇧 +44 (UK)</option>
                                    <option value="+65">🇸🇬 +65 (SG)</option>
                                    <option value="+86">🇨🇳 +86 (CN)</option>
                                    <option value="+81">🇯🇵 +81 (JP)</option>
                                    <option value="+61">🇦🇺 +61 (AU)</option>
                                    <option value="+33">🇫🇷 +33 (FR)</option>
                                    <option value="+49">🇩🇪 +49 (DE)</option>
                                </select>
                            </div>
                            <div class="phone-number-wrapper">
                                <i class="fas fa-phone input-icon"></i>
                                <input type="tel" id="editPhoneNumber" name="phone_number" class="form-input phone-number-input" placeholder="e.g., 11-5550 5761" required>
                            </div>
                        </div>
                        <input type="hidden" name="contact_no" id="editContactNo" />
                        <div id="editPhoneValidationError" class="phone-validation-error"></div>
                        <small class="input-hint" id="editPhoneFormatHint">Enter phone number without country code</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="DateOfBirth" id="editDateOfBirth" class="form-input" />
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