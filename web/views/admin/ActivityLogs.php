<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$prefix = '../../';

$currentFileDir = dirname(__FILE__);
$webRootDir = dirname(dirname($currentFileDir));

$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$imageBasePath = str_replace('\\', '/', $relativePath) . '/';
$cssBasePath = $imageBasePath . 'css/';
$controllerBasePath = $imageBasePath . 'controller/';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']->role !== 'admin') {
    header('Location: ../security/login.php');
    exit;
}

$pageTitle = 'Activity Logs - Admin Dashboard';

$currentSortBy = isset($currentSort['sortBy']) ? $currentSort['sortBy'] : 'created_at';
$currentSortOrder = isset($currentSort['sortOrder']) ? $currentSort['sortOrder'] : 'DESC';

// Initialize variables if not set
$logs = $logs ?? [];
$pagination = $pagination ?? [];
$actionTypes = $actionTypes ?? [];
$entityTypes = $entityTypes ?? [];
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
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>AllTables.css">
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
        .page-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #0f172a;
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
            text-decoration: none;
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
        .table-container {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        .activity-table {
            width: 100%;
            border-collapse: collapse;
        }
        .activity-table thead {
            background: #f9fafb;
        }
        .activity-table thead th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        .activity-table tbody td {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
            font-size: 0.875rem;
            color: #4b5563;
        }
        .activity-table tbody tr:hover {
            background: #f9fafb;
        }
        .action-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .action-badge.create { background: #d1fae5; color: #059669; }
        .action-badge.update { background: #dbeafe; color: #2563eb; }
        .action-badge.delete { background: #fee2e2; color: #dc2626; }
        .action-badge.status { background: #fef3c7; color: #d97706; }
        .view-details-btn {
            background: #f3f4f6;
            color: #374151;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .view-details-btn:hover {
            background: #e5e7eb;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            padding: 1.5rem;
            background: white;
            border-top: 1px solid #e5e7eb;
        }
        .pagination button {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .pagination button:hover:not(:disabled) {
            background: #f3f4f6;
            border-color: #FF523B;
        }
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            overflow: auto;
        }
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 0.75rem;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .close-modal {
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
        }
        .close-modal:hover {
            color: #0f172a;
        }
        .diff-view {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }
        .diff-section {
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }
        .diff-section h4 {
            margin-bottom: 0.5rem;
            color: #374151;
        }
        .diff-item {
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            border-radius: 0.25rem;
            background: #f9fafb;
        }
        .diff-item.removed {
            background: #fee2e2;
        }
        .diff-item.added {
            background: #d1fae5;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="header-actions">
            <h1 class="page-title">Activity Logs</h1>
            <div style="display: flex; gap: 0.5rem;">
                <a href="<?php echo $controllerBasePath; ?>ActivityLogController.php?action=export&format=csv<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Export CSV
                </a>
                <a href="<?php echo $controllerBasePath; ?>ActivityLogController.php?action=export&format=json<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Export JSON
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="<?php echo $controllerBasePath; ?>ActivityLogController.php" class="filters-form" id="filterForm">
                <input type="hidden" name="action" value="showAll">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Search logs..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>
                <div class="filter-group">
                    <label>Action Type</label>
                    <select name="action_type">
                        <option value="">All Actions</option>
                        <?php foreach ($actionTypes as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (isset($_GET['action_type']) && $_GET['action_type'] === $type) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Entity Type</label>
                    <select name="entity_type">
                        <option value="">All Entities</option>
                        <?php foreach ($entityTypes as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (isset($_GET['entity_type']) && $_GET['entity_type'] === $type) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($_GET['start_date'] ?? ''); ?>">
                </div>
                <div class="filter-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($_GET['end_date'] ?? ''); ?>">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="<?php echo $controllerBasePath; ?>ActivityLogController.php?action=showAll" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Activity Logs Table -->
        <div class="table-container">
            <table class="activity-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: #6b7280;">
                                No activity logs found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($log['admin_full_name'] ?? 'Unknown'); ?><br>
                                    <small style="color: #6b7280;"><?php echo htmlspecialchars($log['admin_username'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $actionClass = 'update';
                                    if (strpos($log['action_type'], 'create') !== false) $actionClass = 'create';
                                    elseif (strpos($log['action_type'], 'delete') !== false) $actionClass = 'delete';
                                    elseif (strpos($log['action_type'], 'status') !== false) $actionClass = 'status';
                                    ?>
                                    <span class="action-badge <?php echo $actionClass; ?>">
                                        <?php echo htmlspecialchars($log['action_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($log['entity_type']); ?>
                                    <?php if ($log['entity_id']): ?>
                                        <br><small style="color: #6b7280;">ID: <?php echo $log['entity_id']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['action_description']); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                                <td>
                                    <button class="view-details-btn" onclick="viewLogDetails(<?php echo $log['log_id']; ?>)">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (!empty($pagination) && $pagination['total_pages'] > 1): ?>
            <div class="pagination">
                <button onclick="changePage(<?php echo $pagination['current_page'] - 1; ?>)" <?php echo $pagination['current_page'] <= 1 ? 'disabled' : ''; ?>>
                    Previous
                </button>
                <span>
                    Page <?php echo $pagination['current_page']; ?> of <?php echo $pagination['total_pages']; ?>
                    (<?php echo $pagination['showing_from']; ?>-<?php echo $pagination['showing_to']; ?> of <?php echo $pagination['total_logs']; ?>)
                </span>
                <button onclick="changePage(<?php echo $pagination['current_page'] + 1; ?>)" <?php echo $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : ''; ?>>
                    Next
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Log Details Modal -->
    <div id="logDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Activity Log Details</h2>
                <span class="close-modal" onclick="closeModal()">&times;</span>
            </div>
            <div id="logDetailsContent">
                Loading...
            </div>
        </div>
    </div>

    <script>
        function changePage(page) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }

        function viewLogDetails(logId) {
            const modal = document.getElementById('logDetailsModal');
            const content = document.getElementById('logDetailsContent');
            
            content.innerHTML = 'Loading...';
            modal.style.display = 'block';

            fetch('<?php echo $controllerBasePath; ?>ActivityLogController.php?action=getDetails&log_id=' + logId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const log = data.log;
                        let html = `
                            <div style="margin-bottom: 1rem;">
                                <strong>Timestamp:</strong> ${new Date(log.created_at).toLocaleString()}<br>
                                <strong>Admin:</strong> ${log.admin_full_name} (${log.admin_username})<br>
                                <strong>Action Type:</strong> ${log.action_type}<br>
                                <strong>Entity:</strong> ${log.entity_type}${log.entity_id ? ' (ID: ' + log.entity_id + ')' : ''}<br>
                                <strong>Description:</strong> ${log.action_description}<br>
                                <strong>IP Address:</strong> ${log.ip_address || 'N/A'}<br>
                                <strong>User Agent:</strong> ${log.user_agent || 'N/A'}<br>
                            </div>
                        `;

                        if (log.old_values || log.new_values) {
                            html += '<div class="diff-view">';
                            if (log.old_values) {
                                html += `
                                    <div class="diff-section">
                                        <h4>Old Values</h4>
                                        ${formatValues(log.old_values)}
                                    </div>
                                `;
                            }
                            if (log.new_values) {
                                html += `
                                    <div class="diff-section">
                                        <h4>New Values</h4>
                                        ${formatValues(log.new_values)}
                                    </div>
                                `;
                            }
                            html += '</div>';
                        }

                        content.innerHTML = html;
                    } else {
                        content.innerHTML = '<p style="color: #dc2626;">Error loading log details: ' + data.error + '</p>';
                    }
                })
                .catch(error => {
                    content.innerHTML = '<p style="color: #dc2626;">Error loading log details.</p>';
                });
        }

        function formatValues(values) {
            if (typeof values === 'string') {
                try {
                    values = JSON.parse(values);
                } catch (e) {
                    return '<pre>' + values + '</pre>';
                }
            }
            if (typeof values !== 'object' || values === null) {
                return '<pre>' + JSON.stringify(values, null, 2) + '</pre>';
            }
            let html = '';
            for (const [key, value] of Object.entries(values)) {
                html += `<div class="diff-item"><strong>${key}:</strong> ${typeof value === 'object' ? JSON.stringify(value, null, 2) : value}</div>`;
            }
            return html;
        }

        function closeModal() {
            document.getElementById('logDetailsModal').style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('logDetailsModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>

