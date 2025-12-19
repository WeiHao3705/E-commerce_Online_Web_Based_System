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

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    // Not logged in - redirect to login
    header('Location: ../security/login.php');
    exit;
}

// Check if user has admin role
if ($_SESSION['user']->role !== 'admin') {
    // User is logged in but not admin - redirect to member home
    $_SESSION['error_message'] = 'Access denied. Admin privileges required.';
    header('Location: ../../index.php');
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
        .btn-add-admin {
            background: linear-gradient(135deg, #FF523B 0%, #e64a35 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 4px 6px rgba(255, 82, 59, 0.3);
        }
        .btn-add-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(255, 82, 59, 0.4);
        }
        
        /* Statistics Cards */
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
        .stat-icon.blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .stat-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-icon.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
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
        
        /* Filters Section */
        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
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
        
        /* Table Container */
        .table-container {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
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
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .btn-action {
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            border: 1px solid #d1d5db;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            color: #6b7280;
        }
        .btn-action:hover {
            background: #f3f4f6;
            border-color: #9ca3af;
            color: #374151;
        }
        .btn-action.btn-view {
            background: #eff6ff;
            border-color: #3b82f6;
            color: #3b82f6;
        }
        .btn-action.btn-view:hover {
            background: #dbeafe;
            border-color: #2563eb;
            color: #1d4ed8;
        }
        .btn-action.btn-edit {
            background: #fffbeb;
            border-color: #f59e0b;
            color: #f59e0b;
        }
        .btn-action.btn-edit:hover {
            background: #fef3c7;
            border-color: #d97706;
            color: #d97706;
        }
        .btn-action.btn-delete {
            background: #fef2f2;
            border-color: #ef4444;
            color: #ef4444;
        }
        .btn-action.btn-delete:hover {
            background: #fee2e2;
            border-color: #dc2626;
            color: #dc2626;
        }
        .btn-action i,
        .btn-action .material-symbols-outlined {
            font-size: 1.125rem;
        }
        
        /* Status Badges */
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
        
        /* Empty State */
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
        
        /* Bulk Actions Section */
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
        
        /* Pagination */
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
        
        /* Fix action button spacing - Override AllMembers.css */
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
        
        .col-actions {
            white-space: nowrap;
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
        
        /* Message Styles */
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
    <style>
        /* Enhanced Add Admin Modal Styles */
        .modal-content.narrow {
            max-width: 800px;
            width: 90%;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px 24px;
            margin-bottom: 12px;
        }
        
        .form-hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .form-hint .material-symbols-outlined {
            font-size: 14px;
        }
        
        .form-actions.sticky {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding-top: 16px;
            border-top: 2px solid #e5e7eb;
            margin-top: 20px;
        }
        
        .dark .form-actions.sticky {
            border-top-color: #4a5568;
        }
        
        /* Enhanced Form Group with Icons */
        .form-group-enhanced {
            position: relative;
        }
        
        .form-group-enhanced .form-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-group-enhanced .form-label .material-symbols-outlined {
            font-size: 18px;
            color: var(--primary);
        }
        
        .form-group-enhanced .form-input-wrapper {
            position: relative;
        }
        
        .form-group-enhanced .form-input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 18px;
            pointer-events: none;
            z-index: 1;
        }
        
        .form-group-enhanced .form-input.has-icon {
            padding-left: 42px;
        }
        
        .form-group-enhanced .form-input:focus + .form-input-icon,
        .form-group-enhanced .form-input:not(:placeholder-shown) + .form-input-icon {
            color: var(--primary);
        }
        
        /* Password Strength Indicator */
        .password-strength {
            margin-top: 8px;
            padding: 10px;
            border-radius: 6px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        
        .dark .password-strength {
            background-color: #374151;
            border-color: #4b5563;
        }
        
        .password-strength-title {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .dark .password-strength-title {
            color: #9ca3af;
        }
        
        .password-strength-bars {
            display: flex;
            gap: 4px;
            margin-bottom: 8px;
        }
        
        .password-strength-bar {
            flex: 1;
            height: 4px;
            background-color: #e5e7eb;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .dark .password-strength-bar {
            background-color: #4b5563;
        }
        
        .password-strength-bar.active {
            background-color: #ef4444;
        }
        
        .password-strength-bar.active.weak {
            background-color: #ef4444;
        }
        
        .password-strength-bar.active.fair {
            background-color: #f59e0b;
        }
        
        .password-strength-bar.active.good {
            background-color: #3b82f6;
        }
        
        .password-strength-bar.active.strong {
            background-color: #10b981;
        }
        
        .password-strength-text {
            font-size: 11px;
            color: #6b7280;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .dark .password-strength-text {
            color: #9ca3af;
        }
        
        .password-requirements {
            margin-top: 8px;
            padding: 0;
            list-style: none;
        }
        
        .password-requirement {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s ease;
        }
        
        .dark .password-requirement {
            color: #9ca3af;
        }
        
        .password-requirement.met {
            color: #10b981;
        }
        
        .password-requirement .material-symbols-outlined {
            font-size: 16px;
        }
        
        .password-requirement.met .material-symbols-outlined {
            color: #10b981;
        }
        
        /* Password Visibility Toggle */
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            transition: color 0.2s ease;
        }
        
        .password-toggle:hover {
            color: var(--primary);
        }
        
        .password-toggle .material-symbols-outlined {
            font-size: 20px;
        }
        
        /* Validation Icons */
        .validation-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            pointer-events: none;
            z-index: 2;
        }
        
        .validation-icon.success {
            color: #10b981;
        }
        
        .validation-icon.error {
            color: #ef4444;
        }
        
        .form-input-wrapper.has-validation-icon .form-input {
            padding-right: 40px;
        }
        
        /* Enhanced Modal Title */
        .modal-title-enhanced {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 1.5rem 0;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
        }
        
        .dark .modal-title-enhanced {
            color: white;
            border-bottom-color: var(--gray-700);
        }
        
        .modal-title-enhanced .material-symbols-outlined {
            font-size: 28px;
            color: var(--primary);
        }
        
        /* Form Section Divider */
        .form-section {
            margin-bottom: 24px;
        }
        
        .form-section-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .dark .form-section-title {
            color: var(--gray-300);
            border-bottom-color: var(--gray-700);
        }
        
        .form-section-title .material-symbols-outlined {
            font-size: 18px;
            color: var(--primary);
        }
        
        /* Enhanced Select Styling */
        .form-group-enhanced select.form-input {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .modal-content.narrow {
                width: 95%;
                padding: 1rem;
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
                <i class="fas fa-user-shield" style="color: #FF523B;"></i> Admin Management
            </h1>
            <button type="button" class="btn-add-admin" id="openAddAdminBtn">
                <i class="fas fa-user-plus"></i> Add New Admin
            </button>
        </header>

        <!-- Statistics Cards -->
        <section class="stats-grid">
            <?php
            // Calculate statistics
            $totalAdmins = $pagination['total_admins'] ?? 0;
            $activeCount = 0;
            $inactiveCount = 0;
            $bannedCount = 0;
            if (!empty($admins)) {
                foreach ($admins as $admin) {
                    $status = $admin['status'] ?? 'active';
                    if ($status === 'active') $activeCount++;
                    elseif ($status === 'inactive') $inactiveCount++;
                    elseif ($status === 'banned') $bannedCount++;
                }
            }
            ?>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($totalAdmins) ?></h3>
                    <p>Total Admins</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($activeCount) ?></h3>
                    <p>Active Admins</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-pause-circle"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($inactiveCount) ?></h3>
                    <p>Inactive Admins</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-ban"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($bannedCount) ?></h3>
                    <p>Banned Admins</p>
                </div>
            </div>
        </section>

        <!-- Filters -->
        <section class="filters-section">
            <form method="GET" action="AdminController.php" class="filters-form" id="filterForm">
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
                    <a href="AdminController.php?action=showAll" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </form>
        </section>

        <!-- Bulk Actions Section -->
        <div class="bulk-actions-section" style="display: none;" id="bulkActionsSection">
            <button type="button" class="btn btn-danger" id="bulkDeleteBtn">
                <i class="fas fa-trash"></i>
                <span>Delete Selected (<span id="selectedCount">0</span>)</span>
            </button>
            <button type="button" class="btn btn-secondary" id="clearSelectionBtn">
                <i class="fas fa-times"></i>
                <span>Clear Selection</span>
            </button>
        </div>

        <!-- Admins Table -->
        <section class="table-container" id="admins-table-wrapper">
            <table class="orders-table" id="admins-table">
                        <thead>
                            <tr>
                                <th class="col-checkbox">
                                    <input type="checkbox" id="selectAllCheckbox" title="Select all">
                                </th>
                                <th>Photo</th>
                                <th>Admin Info</th>
                                <th>Contact</th>
                                <th>Gender</th>
                                <th>Joined Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($admins)): ?>
                                <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td class="col-checkbox">
                                            <input type="checkbox" class="admin-checkbox" name="admin_ids[]" value="<?php echo $admin['user_id']; ?>" data-admin-name="<?php echo htmlspecialchars($admin['full_name'], ENT_QUOTES); ?>">
                                        </td>
                                        <td>
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
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                                <br><small style="color: #6b7280;"><?php echo htmlspecialchars($admin['full_name']); ?></small>
                                                <br><small style="color: #9ca3af;"><?php echo htmlspecialchars($admin['email']); ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($admin['contact_no']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['gender']); ?></td>
                                        <td>
                                            <?php
                                            $date = new DateTime($admin['created_at']);
                                            echo $date->format('M d, Y');
                                            ?>
                                        </td>
                                        <td>
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
                                                    data-user-id="<?php echo $admin['user_id']; ?>"
                                                    data-username="<?php echo htmlspecialchars($admin['username'], ENT_QUOTES); ?>"
                                                    data-full-name="<?php echo htmlspecialchars($admin['full_name'], ENT_QUOTES); ?>"
                                                    data-email="<?php echo htmlspecialchars($admin['email'], ENT_QUOTES); ?>"
                                                    data-contact-no="<?php echo htmlspecialchars($admin['contact_no'], ENT_QUOTES); ?>"
                                                    data-gender="<?php echo htmlspecialchars($admin['gender'], ENT_QUOTES); ?>"
                                                    title="Edit admin">
                                                    <i class="fas fa-edit"></i>
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
                                                        <i class="fas fa-ban"></i>
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
                                                        <i class="fas fa-pause-circle"></i>
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
                                                        <i class="fas fa-check-circle"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <button
                                                    class="action-btn delete-btn"
                                                    data-action="delete"
                                                    data-user-id="<?php echo $admin['user_id']; ?>"
                                                    data-admin-name="<?php echo htmlspecialchars($admin['full_name'], ENT_QUOTES); ?>"
                                                    title="Delete admin">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p>No admins found<?php echo !empty($_GET['search']) ? '. Try a different search term.' : ''; ?></p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
            
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
        </section>
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

            const tableWrapper = $('#admins-table-wrapper');
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
                tbody.append(`
                    <tr>
                        <td colspan="8" class="text-center">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No admins found. Try a different search term.</p>
                            </div>
                        </td>
                    </tr>
                `);
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
                'banned': { class: 'status-banned', text: 'Banned' },
                'blocked': { class: 'status-blocked', text: 'Blocked' }
            };
            const statusInfo = statusLabels[status] || statusLabels['active'];

            let statusButtons = '';
            if (status !== 'banned') {
                statusButtons += '<button class="action-btn ban-btn" data-action="status" data-user-id="' + admin.user_id + '" data-admin-name="' + escapeHtml(admin.full_name) + '" data-status="banned" title="Ban admin"><i class="fas fa-ban"></i></button>';
            }
            if (status !== 'inactive') {
                statusButtons += '<button class="action-btn inactive-btn" data-action="status" data-user-id="' + admin.user_id + '" data-admin-name="' + escapeHtml(admin.full_name) + '" data-status="inactive" title="Set to inactive"><i class="fas fa-pause-circle"></i></button>';
            }
            if (status !== 'active') {
                statusButtons += '<button class="action-btn activate-btn" data-action="status" data-user-id="' + admin.user_id + '" data-admin-name="' + escapeHtml(admin.full_name) + '" data-status="active" title="Activate admin"><i class="fas fa-check-circle"></i></button>';
            }

            const row = `
                <tr>
                    <td class="col-checkbox">
                        <input type="checkbox" class="admin-checkbox" name="admin_ids[]" value="${admin.user_id}" data-admin-name="${escapeHtml(admin.full_name)}">
                    </td>
                    <td>
                        <img src="${escapeHtml(photoUrl)}"
                             alt="Profile photo"
                             class="member-profile-photo clickable-image"
                             data-image-url="${escapeHtml(photoUrl)}"
                             data-admin-name="${escapeHtml(admin.full_name)}"
                             onerror="this.onerror=null; this.src='${escapeHtml(defaultPhotoUrl)}';"
                             style="cursor: pointer;"
                             title="Click to view full size">
                    </td>
                    <td>
                        <div>
                            <strong>${escapeHtml(admin.username)}</strong>
                            <br><small style="color: #6b7280;">${escapeHtml(admin.full_name)}</small>
                            <br><small style="color: #9ca3af;">${escapeHtml(admin.email)}</small>
                        </div>
                    </td>
                    <td>${escapeHtml(admin.contact_no)}</td>
                    <td>${escapeHtml(admin.gender)}</td>
                    <td>${createdDateDisplay}</td>
                    <td>
                        <span class="status-badge ${statusInfo.class}">${statusInfo.text}</span>
                    </td>
                    <td class="col-actions">
                        <div class="action-buttons">
                            <button class="action-btn edit-btn" data-user-id="${admin.user_id}" data-username="${escapeHtml(admin.username)}" data-full-name="${escapeHtml(admin.full_name)}" data-email="${escapeHtml(admin.email)}" data-contact-no="${escapeHtml(admin.contact_no)}" data-gender="${escapeHtml(admin.gender)}" title="Edit admin">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${statusButtons}
                            <button class="action-btn delete-btn" data-action="delete" data-user-id="${admin.user_id}" data-admin-name="${escapeHtml(admin.full_name)}" title="Delete admin">
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

            if (pagination.total_admins > 0) {
                $('.pagination-info').html(`
                    Showing <span class="pagination-number">${pagination.showing_from}-${pagination.showing_to}</span> of <span class="pagination-number">${pagination.total_admins}</span>
                `);

                const paginationList = $('.pagination-list');
                paginationList.empty();

                const status = $('#filterStatus').val();
                const sortBy = response.sortBy || 'created_at';
                const sortOrder = response.sortOrder || 'DESC';
                const searchTerm = $('#simple-search').val();

                const prevUrl = pagination.current_page > 1 ?
                    `AdminController.php?action=showAll&page=${pagination.current_page - 1}&search=${encodeURIComponent(searchTerm)}&status=${status}&sortBy=${sortBy}&sortOrder=${sortOrder}` : '#';
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
                    const pageUrl = `AdminController.php?action=showAll&page=${i}&search=${encodeURIComponent(searchTerm)}&status=${status}&sortBy=${sortBy}&sortOrder=${sortOrder}`;
                    paginationList.append(`
                        <li>
                            <a href="${pageUrl}" class="pagination-link ${activeClass}">${i}</a>
                        </li>
                    `);
                }

                const nextUrl = pagination.current_page < pagination.total_pages ?
                    `AdminController.php?action=showAll&page=${pagination.current_page + 1}&search=${encodeURIComponent(searchTerm)}&status=${status}&sortBy=${sortBy}&sortOrder=${sortOrder}` : '#';
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
                return { countryCode: '+60', phoneNumber: '' };
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
            
            return { countryCode, phoneNumber };
        }

        function openEditModal(userId, username, fullName, email, contactNo, gender) {
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

        function confirmStatusChange(userId, adminName, newStatus) {
            const statusLabels = {
                'active': 'activate',
                'inactive': 'set to inactive',
                'banned': 'ban'
            };
            const action = statusLabels[newStatus] || newStatus;

            showConfirmationModal(
                'Are you sure you want to ' + action + ' admin: ' + adminName + '?',
                'warning',
                function() {
                    $('#statusUserId').val(userId);
                    $('#statusValue').val(newStatus);
                    $('#statusForm').submit();
                }
            );
        }

        function confirmDelete(userId, adminName) {
            showConfirmationModal(
                'Are you sure you want to delete admin: ' + adminName + '?<br><br>This action cannot be undone.',
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

            showConfirmationModal(
                `Are you sure you want to delete ${count} admin(s)?<br><br>This action cannot be undone.`,
                'danger',
                function() {
                    $('#bulkDeleteForm input[name="user_ids[]"]').remove();

                    selectedAdmins.forEach(function(adminId) {
                        $('#bulkDeleteForm').append(`<input type="hidden" name="user_ids[]" value="${adminId}">`);
                    });

                    $('#bulkDeleteForm').submit();
                }
            );
        });

        // Phone validation for add admin form
        function validateAddAdminPhoneNumber() {
            const countryCode = $('#addAdminCountryCode').val();
            const phoneNumber = $('#addAdminPhoneNumber').val().replace(/\s+/g, ' ').trim();
            const config = phonePatterns[countryCode];
            const $phoneNumber = $('#addAdminPhoneNumber');
            const $phoneValidationError = $('#addAdminPhoneValidationError');

            if (!phoneNumber) {
                $phoneNumber.removeClass('input-error input-success');
                $phoneValidationError.text('').hide();
                $('#addAdminContactNo').val('');
                return false;
            }

            // Remove spaces and dashes for validation
            const cleanPhone = phoneNumber.replace(/[- ]/g, '');
            
            // Check length
            if (cleanPhone.length < config.minLength || cleanPhone.length > config.maxLength) {
                $phoneNumber.addClass('input-error').removeClass('input-success');
                $phoneValidationError.text(`Phone number must be ${config.minLength}-${config.maxLength} digits. Example: ${config.example}`).show();
                $('#addAdminContactNo').val('');
                return false;
            }

            // Check pattern
            if (!config.pattern.test(phoneNumber)) {
                $phoneNumber.addClass('input-error').removeClass('input-success');
                $phoneValidationError.text(`Invalid phone format. Example: ${config.example}`).show();
                $('#addAdminContactNo').val('');
                return false;
            }

            // Valid phone number
            $phoneNumber.removeClass('input-error').addClass('input-success');
            $phoneValidationError.text('').hide();
            
            // Combine country code and phone number
            const fullPhoneNumber = countryCode + ' ' + phoneNumber;
            $('#addAdminContactNo').val(fullPhoneNumber);
            
            return true;
        }

        function updateAddAdminPhoneFormatHint() {
            const countryCode = $('#addAdminCountryCode').val();
            const config = phonePatterns[countryCode];
            if (config) {
                $('#addAdminPhoneFormatHint').text(`Format: ${config.example} (${config.minLength}-${config.maxLength} digits)`);
                $('#addAdminPhoneNumber').attr('placeholder', `e.g., ${config.example}`);
            }
        }

        // Phone validation event handlers for add admin
        $(document).on('change', '#addAdminCountryCode', function() {
            updateAddAdminPhoneFormatHint();
            validateAddAdminPhoneNumber();
        });

        $(document).on('input', '#addAdminPhoneNumber', function() {
            validateAddAdminPhoneNumber();
        });

        // Add Admin form validation
        $('#addAdminForm').on('submit', function(e) {
            let isValid = true;
            let errorMessage = '';

            // Validate username
            const username = $('#addAdminUsername').val().trim();
            if (!username || username.length < 3 || !/^[a-zA-Z0-9._-]+$/.test(username)) {
                isValid = false;
                errorMessage = 'Please enter a valid username (at least 3 characters, alphanumeric with . _ -).';
                $('#addAdminUsername').focus();
            }

            // Validate email
            const email = $('#addAdminEmail').val().trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email || !emailRegex.test(email)) {
                isValid = false;
                if (!errorMessage) {
                    errorMessage = 'Please enter a valid email address.';
                    $('#addAdminEmail').focus();
                }
            }

            // Validate password
            const pwd = $('#addAdminPassword').val() || '';
            const passwordStrength = checkPasswordStrength(pwd);
            if (passwordStrength.strength < 4) {
                isValid = false;
                if (!errorMessage) {
                    errorMessage = 'Password must meet all requirements (8+ chars, uppercase, lowercase, number, special character).';
                    $('#addAdminPassword').focus();
                }
            }

            // Validate password match
            const repeat = $('#addAdminRepeatPassword').val() || '';
            if (pwd !== repeat) {
                isValid = false;
                if (!errorMessage) {
                    errorMessage = 'Passwords do not match.';
                    $('#addAdminRepeatPassword').focus();
                }
            }

            // Validate phone number
            if (!validateAddAdminPhoneNumber()) {
                isValid = false;
                if (!errorMessage) {
                    errorMessage = 'Please enter a valid phone number.';
                    $('#addAdminPhoneNumber').focus();
                }
            }

            if (!isValid) {
                e.preventDefault();
                alert(errorMessage);
                return false;
            }

            return true;
        });

        // Password visibility toggle
        $('#togglePassword').on('click', function() {
            const passwordInput = $('#addAdminPassword');
            const icon = $(this).find('.material-symbols-outlined');
            if (passwordInput.attr('type') === 'password') {
                passwordInput.attr('type', 'text');
                icon.text('visibility_off');
            } else {
                passwordInput.attr('type', 'password');
                icon.text('visibility');
            }
        });

        $('#toggleRepeatPassword').on('click', function() {
            const passwordInput = $('#addAdminRepeatPassword');
            const icon = $(this).find('.material-symbols-outlined');
            if (passwordInput.attr('type') === 'password') {
                passwordInput.attr('type', 'text');
                icon.text('visibility_off');
            } else {
                passwordInput.attr('type', 'password');
                icon.text('visibility');
            }
        });

        // Password strength checker
        function checkPasswordStrength(password) {
            const checks = {
                length: password ? password.length >= 8 : false,
                upper: password ? /[A-Z]/.test(password) : false,
                lower: password ? /[a-z]/.test(password) : false,
                number: password ? /[0-9]/.test(password) : false,
                special: password ? /[!@#$%^&*]/.test(password) : false
            };

            if (!password) {
                return { strength: 0, text: 'Enter a password', class: '', checks };
            }

            let strength = Object.values(checks).filter(Boolean).length;

            let strengthText = '';
            let strengthClass = '';
            
            if (strength <= 2) {
                strengthText = 'Weak';
                strengthClass = 'weak';
            } else if (strength === 3) {
                strengthText = 'Fair';
                strengthClass = 'fair';
            } else if (strength === 4) {
                strengthText = 'Good';
                strengthClass = 'good';
            } else {
                strengthText = 'Strong';
                strengthClass = 'strong';
            }

            return { strength, text: strengthText, class: strengthClass, checks };
        }

        function updatePasswordStrength(password) {
            const result = checkPasswordStrength(password);
            const $indicator = $('#passwordStrengthIndicator');
            const $bars = $('.password-strength-bar');
            const $text = $('#passwordStrengthText');

            if (password) {
                $indicator.show();
                $bars.removeClass('active weak fair good strong');
                
                for (let i = 0; i < result.strength; i++) {
                    $bars.eq(i).addClass('active ' + result.class);
                }

                $text.text('Password strength: ' + result.text);
            } else {
                $indicator.hide();
            }

            // Update requirements
            updatePasswordRequirements(result.checks);
        }

        function updatePasswordRequirements(checks) {
            $('#reqLength').toggleClass('met', checks.length);
            $('#reqLength .material-symbols-outlined').text(checks.length ? 'check_circle' : 'close');
            
            $('#reqUpper').toggleClass('met', checks.upper);
            $('#reqUpper .material-symbols-outlined').text(checks.upper ? 'check_circle' : 'close');
            
            $('#reqLower').toggleClass('met', checks.lower);
            $('#reqLower .material-symbols-outlined').text(checks.lower ? 'check_circle' : 'close');
            
            $('#reqNumber').toggleClass('met', checks.number);
            $('#reqNumber .material-symbols-outlined').text(checks.number ? 'check_circle' : 'close');
            
            $('#reqSpecial').toggleClass('met', checks.special);
            $('#reqSpecial .material-symbols-outlined').text(checks.special ? 'check_circle' : 'close');
        }

        // Real-time password validation
        $('#addAdminPassword').on('input', function() {
            const password = $(this).val();
            updatePasswordStrength(password);
            validatePassword();
            checkPasswordMatch();
        });

        $('#addAdminRepeatPassword').on('input', function() {
            checkPasswordMatch();
        });

        function validatePassword() {
            const password = $('#addAdminPassword').val();
            const result = checkPasswordStrength(password);
            const $input = $('#addAdminPassword');
            const $icon = $('#passwordValidationIcon');

            if (!password) {
                $input.removeClass('input-error input-success');
                $icon.hide();
                return false;
            }

            if (result.strength >= 4) {
                $input.removeClass('input-error').addClass('input-success');
                $icon.removeClass('error').addClass('success').text('check_circle').show();
                return true;
            } else {
                $input.removeClass('input-success').addClass('input-error');
                $icon.removeClass('success').addClass('error').text('error').show();
                return false;
            }
        }

        function checkPasswordMatch() {
            const password = $('#addAdminPassword').val();
            const repeatPassword = $('#addAdminRepeatPassword').val();
            const $repeatInput = $('#addAdminRepeatPassword');
            const $icon = $('#repeatPasswordValidationIcon');
            const $message = $('#passwordMatchMessage');

            if (!repeatPassword) {
                $repeatInput.removeClass('input-error input-success');
                $icon.hide();
                $message.hide();
                return false;
            }

            if (password === repeatPassword && password) {
                $repeatInput.removeClass('input-error').addClass('input-success');
                $icon.removeClass('error').addClass('success').text('check_circle').show();
                $message.removeClass('input-error').addClass('input-success').html('<span class="material-symbols-outlined">check_circle</span><span>Passwords match</span>').show();
                return true;
            } else {
                $repeatInput.removeClass('input-success').addClass('input-error');
                $icon.removeClass('success').addClass('error').text('error').show();
                $message.removeClass('input-success').addClass('input-error').html('<span class="material-symbols-outlined">error</span><span>Passwords do not match</span>').show();
                return false;
            }
        }

        // Email validation
        $('#addAdminEmail').on('blur', function() {
            const email = $(this).val();
            const $input = $(this);
            const $icon = $('#emailValidationIcon');

            if (!email) {
                $icon.hide();
                return;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailRegex.test(email)) {
                $input.removeClass('input-error').addClass('input-success');
                $icon.removeClass('error').addClass('success').text('check_circle').show();
            } else {
                $input.removeClass('input-success').addClass('input-error');
                $icon.removeClass('success').addClass('error').text('error').show();
            }
        });

        // Username validation
        $('#addAdminUsername').on('blur', function() {
            const username = $(this).val();
            const $input = $(this);
            const $icon = $('#usernameValidationIcon');

            if (!username) {
                $icon.hide();
                return;
            }

            if (username.length >= 3 && /^[a-zA-Z0-9._-]+$/.test(username)) {
                $input.removeClass('input-error').addClass('input-success');
                $icon.removeClass('error').addClass('success').text('check_circle').show();
            } else {
                $input.removeClass('input-success').addClass('input-error');
                $icon.removeClass('success').addClass('error').text('error').show();
            }
        });

        // Add admin modal
        $('#openAddAdminBtn').on('click', function() {
            const form = $('#addAdminForm');
            form[0].reset();
            
            // Reset phone fields
            $('#addAdminCountryCode').val('+60');
            updateAddAdminPhoneFormatHint();
            $('#addAdminPhoneNumber').val('');
            $('#addAdminContactNo').val('');
            $('#addAdminPhoneValidationError').text('').hide();
            $('#addAdminPhoneNumber').removeClass('input-error input-success');
            
            // Reset password fields
            $('#addAdminPassword').attr('type', 'password');
            $('#addAdminRepeatPassword').attr('type', 'password');
            $('#togglePassword .material-symbols-outlined').text('visibility');
            $('#toggleRepeatPassword .material-symbols-outlined').text('visibility');
            $('#passwordStrengthIndicator').hide();
            $('.password-requirement').removeClass('met');
            $('.password-requirement .material-symbols-outlined').text('close');
            $('#passwordMatchMessage').hide();
            
            // Reset validation icons
            $('.validation-icon').hide();
            $('.form-input').removeClass('input-error input-success');
            
            $('#addAdminModal').removeClass('hidden');
        });

        $(document).on('click', '.btn-close-add-modal', function() {
            $('#addAdminModal').addClass('hidden');
        });

        // Close modal on overlay click
        $(document).on('click', '#addAdminModal', function(e) {
            if ($(e.target).hasClass('modal-overlay')) {
                $('#addAdminModal').addClass('hidden');
            }
        });
    </script>

    <!-- Add Admin Modal -->
    <div id="addAdminModal" class="modal-overlay hidden">
        <div class="modal-content narrow">
            <div class="modal-body">
                <h3 class="modal-title-enhanced">
                    <span class="material-symbols-outlined">person_add</span>
                    <span>Add New Admin</span>
                </h3>

                <form id="addAdminForm" method="POST" action="AdminController.php" class="modal-form">
                    <input type="hidden" name="action" value="create">

                    <div class="form-section">
                        <div class="form-section-title">
                            <span class="material-symbols-outlined">badge</span>
                            <span>Basic Information</span>
                        </div>
                        <div class="form-grid">
                            <div class="form-group form-group-enhanced">
                                <label class="form-label">
                                    <span class="material-symbols-outlined">person</span>
                                    <span>Username</span>
                                </label>
                                <div class="form-input-wrapper">
                                    <input type="text" name="username" id="addAdminUsername" class="form-input has-icon" placeholder="e.g. jane.admin" required />
                                    <span class="form-input-icon material-symbols-outlined">person</span>
                                    <span class="validation-icon" id="usernameValidationIcon" style="display: none;"></span>
                                </div>
                            </div>

                            <div class="form-group form-group-enhanced">
                                <label class="form-label">
                                    <span class="material-symbols-outlined">badge</span>
                                    <span>Full Name</span>
                                </label>
                                <div class="form-input-wrapper">
                                    <input type="text" name="full_name" id="addAdminFullName" class="form-input has-icon" placeholder="Jane Admin" required />
                                    <span class="form-input-icon material-symbols-outlined">badge</span>
                                </div>
                            </div>

                            <div class="form-group form-group-enhanced">
                                <label class="form-label">
                                    <span class="material-symbols-outlined">email</span>
                                    <span>Email</span>
                                </label>
                                <div class="form-input-wrapper">
                                    <input type="email" name="email" id="addAdminEmail" class="form-input has-icon" placeholder="jane@example.com" required />
                                    <span class="form-input-icon material-symbols-outlined">email</span>
                                    <span class="validation-icon" id="emailValidationIcon" style="display: none;"></span>
                                </div>
                                <p class="form-hint">
                                    <span class="material-symbols-outlined">verified</span>
                                    <span>Will be marked verified automatically</span>
                                </p>
                            </div>

                            <div class="form-group form-group-enhanced">
                                <label class="form-label">
                                    <span class="material-symbols-outlined">phone</span>
                                    <span>Contact Number</span>
                                </label>
                                <div class="phone-input-group">
                                    <div class="country-code-wrapper">
                                        <select id="addAdminCountryCode" name="country_code" class="country-code-select" required>
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
                                        <input type="tel" id="addAdminPhoneNumber" name="phone_number" class="form-input phone-number-input" placeholder="e.g., 11-5550 5761" required>
                                    </div>
                                </div>
                                <input type="hidden" name="contact_no" id="addAdminContactNo"/>
                                <div id="addAdminPhoneValidationError" class="phone-validation-error"></div>
                                <small class="input-hint" id="addAdminPhoneFormatHint">Enter phone number without country code</small>
                            </div>

                            <div class="form-group form-group-enhanced">
                                <label class="form-label">
                                    <span class="material-symbols-outlined">wc</span>
                                    <span>Gender</span>
                                </label>
                                <div class="form-input-wrapper">
                                    <select name="gender" id="addAdminGender" class="form-input">
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">
                            <span class="material-symbols-outlined">lock</span>
                            <span>Security</span>
                        </div>
                        <div class="form-grid">
                            <div class="form-group form-group-enhanced">
                                <label class="form-label">
                                    <span class="material-symbols-outlined">lock</span>
                                    <span>Password</span>
                                </label>
                                <div class="form-input-wrapper has-validation-icon">
                                    <input type="password" id="addAdminPassword" name="password" class="form-input has-icon" placeholder="Enter a strong password" required />
                                    <span class="form-input-icon material-symbols-outlined">lock</span>
                                    <button type="button" class="password-toggle" id="togglePassword" title="Show/Hide Password">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                    <span class="validation-icon" id="passwordValidationIcon" style="display: none;"></span>
                                </div>
                                
                                <div id="passwordStrengthIndicator" class="password-strength" style="display: none;">
                                    <div class="password-strength-title">
                                        <span class="material-symbols-outlined">security</span>
                                        <span>Password Strength</span>
                                    </div>
                                    <div class="password-strength-bars">
                                        <div class="password-strength-bar"></div>
                                        <div class="password-strength-bar"></div>
                                        <div class="password-strength-bar"></div>
                                        <div class="password-strength-bar"></div>
                                    </div>
                                    <div class="password-strength-text" id="passwordStrengthText">Enter a password to check strength</div>
                                </div>
                                
                                <ul class="password-requirements" id="passwordRequirements">
                                    <li class="password-requirement" id="reqLength">
                                        <span class="material-symbols-outlined">close</span>
                                        <span>At least 8 characters</span>
                                    </li>
                                    <li class="password-requirement" id="reqUpper">
                                        <span class="material-symbols-outlined">close</span>
                                        <span>One uppercase letter</span>
                                    </li>
                                    <li class="password-requirement" id="reqLower">
                                        <span class="material-symbols-outlined">close</span>
                                        <span>One lowercase letter</span>
                                    </li>
                                    <li class="password-requirement" id="reqNumber">
                                        <span class="material-symbols-outlined">close</span>
                                        <span>One number</span>
                                    </li>
                                    <li class="password-requirement" id="reqSpecial">
                                        <span class="material-symbols-outlined">close</span>
                                        <span>One special character (!@#$%^&*)</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="form-group form-group-enhanced">
                                <label class="form-label">
                                    <span class="material-symbols-outlined">lock_reset</span>
                                    <span>Repeat Password</span>
                                </label>
                                <div class="form-input-wrapper has-validation-icon">
                                    <input type="password" id="addAdminRepeatPassword" name="repeat_password" class="form-input has-icon" placeholder="Confirm your password" required />
                                    <span class="form-input-icon material-symbols-outlined">lock_reset</span>
                                    <button type="button" class="password-toggle" id="toggleRepeatPassword" title="Show/Hide Password">
                                        <span class="material-symbols-outlined">visibility</span>
                                    </button>
                                    <span class="validation-icon" id="repeatPasswordValidationIcon" style="display: none;"></span>
                                </div>
                                <p id="passwordMatchMessage" class="form-hint" style="display: none;"></p>
                            </div>
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

                    <div class="modal-form-grid">
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
                            <input type="hidden" name="contact_no" id="editContactNo"/>
                            <div id="editPhoneValidationError" class="phone-validation-error"></div>
                            <small class="input-hint" id="editPhoneFormatHint">Enter phone number without country code</small>
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
