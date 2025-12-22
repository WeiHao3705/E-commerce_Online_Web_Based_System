<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']->role !== 'admin') {
    header('Location: ../security/login.php');
    exit;
}

require __DIR__ . '/../../database/connection.php';
$db = new Database();
$conn = $db->getConnection();

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$query = "
    SELECT o.*, 
           u.username, 
           u.email,
           p.payment_method,
           p.payment_status,
           COUNT(oi.order_item_id) as total_items
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    LEFT JOIN payment p ON o.order_id = p.order_id
    LEFT JOIN order_item oi ON o.order_id = oi.order_id
    WHERE 1=1
";

$params = [];

if ($status_filter !== 'all') {
    $query .= " AND o.order_status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $query .= " AND (u.username LIKE :search OR u.email LIKE :search OR o.order_id LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($date_from)) {
    $query .= " AND DATE(o.create_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(o.create_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

$query .= " GROUP BY o.order_id ORDER BY o.create_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$statsQuery = "
    SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN order_status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
    SUM(CASE WHEN order_status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
    SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
    SUM(CASE WHEN order_status = 'canceled' THEN 1 ELSE 0 END) as canceled_orders,
    SUM(CASE WHEN order_status = 'refunded' THEN 1 ELSE 0 END) as refunded_orders,
    SUM(CASE WHEN order_status NOT IN ('canceled', 'refunded') THEN total_amount ELSE 0 END) as total_revenue
FROM orders
";
$statsStmt = $conn->query($statsQuery);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Calculate base path
$currentFileDir = dirname(__FILE__);
$webRootDir = dirname(dirname($currentFileDir));
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$webBasePath = str_replace('\\', '/', $relativePath) . '/';
$cssBasePath = $webBasePath . 'css/';
$controllerBasePath = $webBasePath . 'controller/';
$viewsBasePath = $webBasePath . 'views/';

// Get admin user info
$adminName = isset($_SESSION['user']->full_name) ? $_SESSION['user']->full_name : 'Admin';
$adminAvatar = isset($_SESSION['user']->profile_photo) ? $_SESSION['user']->profile_photo : $webBasePath . 'images/defaultUserImage.jpg';

$pageTitle = "Manage Orders - Admin";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - NGEAR</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $cssBasePath; ?>AdminOrder.css">
</head>
<body>
    <div class="page-container">
        <!-- Header -->
        <header class="header-actions">
            <h1 style="font-size: 2rem; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-box" style="color: #FF523B;"></i> Orders Management
            </h1>
            <a href="OrderAnalytics.php" class="btn-analytics">
                <i class="fas fa-chart-line"></i> View Analytics
            </a>
        </header>

        <!-- Statistics Cards -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-shopping-cart"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($stats['total_orders']) ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
            <style>
                /* Modal General Fixes */
                
            </style>
                    <h3><?= number_format($stats['pending_orders']) ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h3><?= number_format($stats['delivered_orders']) ?></h3>
                    <p>Delivered</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-dollar-sign"></i></div>
                <div class="stat-info">
                    <h3>RM <?= number_format($stats['total_revenue'], 2) ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>
        </section>

        <!-- Filters -->
        <section class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label><i class="fas fa-filter"></i> Status</label>
                    <select name="status" onchange="this.form.submit()">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Orders</option>
                        <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="paid" <?= $status_filter === 'paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="shipped" <?= $status_filter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="refund_requested" <?= $status_filter === 'refund_requested' ? 'selected' : '' ?>>Refund Requested</option>
                        <option value="canceled" <?= $status_filter === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                        <option value="refunded" <?= $status_filter === 'refunded' ? 'selected' : '' ?>>Refunded</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-search"></i> Search</label>
                    <input type="text" name="search" placeholder="Order ID, Username, Email..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> From Date</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> To Date</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="AdminOrder.php" class="btn btn-secondary"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </form>
        </section>

        <!-- Orders Table -->
        <section class="table-container">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Order Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>No orders found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                                <td>
                                    <div class="customer-info">
                                        <strong><?= htmlspecialchars($order['username']) ?></strong>
                                        <small><?= htmlspecialchars($order['email']) ?></small>
                                    </div>
                                </td>
                                <td><?= $order['total_items'] ?> item(s)</td>
                                <td><strong>RM <?= number_format($order['total_amount'], 2) ?></strong></td>
                                <td>
                                    <span class="payment-badge payment-<?= strtolower($order['payment_status']) ?>">
                                        <?= ucfirst($order['payment_status']) ?>
                                    </span>
                                    <small><?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?></small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($order['order_status']) ?>">
                                        <?= ucfirst($order['order_status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y H:i', strtotime($order['create_at'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="OrderDetails.php?id=<?= $order['order_id'] ?>" class="btn-action btn-view" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($order['order_status'] === 'refund_requested'): ?>
                                            <button onclick="viewRefundReason(<?= $order['order_id'] ?>)" class="btn-action btn-info" title="View Refund Reason">
                                                <i class="fas fa-info-circle"></i>
                                            </button>
                                            <button onclick="approveRefund(<?= $order['order_id'] ?>)" class="btn-action btn-success" title="Approve Refund">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button onclick="rejectRefund(<?= $order['order_id'] ?>)" class="btn-action btn-danger" title="Reject Refund">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php else: ?>
                                            <button onclick="updateOrderStatus(<?= $order['order_id'] ?>)" class="btn-action btn-edit" title="Update Status">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>

    <!-- View Refund Reason Modal -->
    <div id="refundReasonModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-info-circle"></i> Refund Request Details</h2>
                <button class="close-btn" onclick="closeRefundReasonModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="refundReasonContent">
                    <div style="text-align: center; padding: 2rem;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary);"></i>
                        <p>Loading refund details...</p>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeRefundReasonModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Order Status Modal -->
    <div id="updateStatusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Update Order Status</h2>
                <button class="close-btn" onclick="closeStatusModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="updateStatusForm">
                    <input type="hidden" id="order_id" name="order_id">
                    
                    <div class="form-group">
                        <label for="order_status"><i class="fas fa-box"></i> Order Status</label>
                        <select id="order_status" name="order_status" required>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="refund_requested">Refund Requested</option>
                            <option value="canceled">Canceled</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="payment_status"><i class="fas fa-credit-card"></i> Payment Status</label>
                        <select id="payment_status" name="payment_status">
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tracking_number"><i class="fas fa-truck"></i> Tracking Number (Optional)</label>
                        <input type="text" id="tracking_number" name="tracking_number" placeholder="Enter tracking number">
                    </div>

                    <div class="form-group">
                        <label for="admin_notes"><i class="fas fa-sticky-note"></i> Admin Notes (Optional)</label>
                        <textarea id="admin_notes" name="admin_notes" rows="3" placeholder="Add internal notes..."></textarea>
                    </div>

                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" id="send_email" name="send_email" checked>
                            <span>Send email notification to customer</span>
                        </label>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeStatusModal()">Cancel</button>
                        <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Message Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-info-circle"></i> Message</h2>
                <button class="close-btn" onclick="closeMessageModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="messageModalText" style="font-size:1.1rem;"></p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeMessageModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="confirmModalTitle"><i class="fas fa-question-circle"></i> Confirm Action</h2>
                <button class="close-btn" onclick="closeConfirmModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="confirmModalText" style="font-size:1.1rem;"></p>
                <div id="confirmModalInputGroup" style="display:none; margin-top:1rem;">
                    <label for="confirmModalInput" style="font-size:0.95rem;">Reason (optional):</label>
                    <textarea id="confirmModalInput" rows="2" style="width:100%;"></textarea>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeConfirmModal()">Cancel</button>
                <button type="button" class="btn-primary" id="confirmModalOkBtn">OK</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function showMessageModal(message) {
        document.getElementById('messageModalText').textContent = message;
        document.getElementById('messageModal').style.display = 'block';
    }
    function closeMessageModal() {
        document.getElementById('messageModal').style.display = 'none';
    }
    function viewRefundReason(orderId) {
        document.getElementById('refundReasonModal').style.display = 'block';
        
        // Fetch refund reason from order notes
        $.ajax({
            url: '<?php echo $controllerBasePath; ?>AdminController.php?action=getRefundReason',
            method: 'GET',
            data: { order_id: orderId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    let content = `
                        <div class="refund-details">
                            <div class="form-group">
                                <label><i class="fas fa-hashtag"></i> Order ID</label>
                                <p>#${orderId}</p>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-calendar"></i> Request Date</label>
                                <p>${response.data.created_at || 'N/A'}</p>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-tag"></i> Reason</label>
                                <p><strong>${response.data.reason || 'Not specified'}</strong></p>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-comment-alt"></i> Additional Details</label>
                                <p style="white-space: pre-wrap;">${response.data.details || 'No additional details provided'}</p>
                            </div>
                        </div>
                    `;
                    document.getElementById('refundReasonContent').innerHTML = content;
                } else {
                    document.getElementById('refundReasonContent').innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: var(--danger);">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i>
                            <p>${response.message || 'Failed to load refund details'}</p>
                        </div>
                    `;
                }
            },
            error: function() {
                document.getElementById('refundReasonContent').innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: var(--danger);">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i>
                        <p>Error loading refund details. Please try again.</p>
                    </div>
                `;
            }
        });
    }

    function closeRefundReasonModal() {
        document.getElementById('refundReasonModal').style.display = 'none';
    }

    function updateOrderStatus(orderId) {
        // Get current order data
        const orderRow = event.target.closest('tr');
        const currentStatus = orderRow.querySelector('.status-badge').textContent.trim().toLowerCase();
        const currentPaymentStatus = orderRow.querySelector('.payment-badge').textContent.trim().toLowerCase();
        
        // Populate modal
        document.getElementById('order_id').value = orderId;
        document.getElementById('order_status').value = currentStatus;
        document.getElementById('payment_status').value = currentPaymentStatus;
        document.getElementById('tracking_number').value = '';
        document.getElementById('admin_notes').value = '';
        document.getElementById('send_email').checked = true;
        
        // Show modal
        document.getElementById('updateStatusModal').style.display = 'flex';
    }

    function closeStatusModal() {
        document.getElementById('updateStatusModal').style.display = 'none';
        document.getElementById('updateStatusForm').reset();
    }

    // Approve refund function
    function approveRefund(orderId) {
        showConfirmModal({
            title: 'Approve Refund',
            message: 'Are you sure you want to APPROVE this refund request? This will refund the payment to the customer.',
            input: false,
            onOk: function() {
                $.ajax({
                    url: '<?php echo $controllerBasePath; ?>AdminRefundController.php',
                    method: 'POST',
                    data: {
                        action: 'approve',
                        order_id: orderId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showMessageModal('✓ Refund approved successfully!');
                            setTimeout(function(){ location.reload(); }, 1500);
                        } else {
                            showMessageModal('✗ Error: ' + (response.message || 'Failed to approve refund'));
                        }
                    },
                    error: function() {
                        showMessageModal('✗ An error occurred while approving the refund');
                    }
                });
            }
        });
    }

    // Reject refund function
    function rejectRefund(orderId) {
        showConfirmModal({
            title: 'Reject Refund',
            message: 'Are you sure you want to REJECT this refund request?',
            input: true,
            onOk: function(note) {
                $.ajax({
                    url: '<?php echo $controllerBasePath; ?>AdminRefundController.php',
                    method: 'POST',
                    data: {
                        action: 'reject',
                        order_id: orderId,
                        admin_note: note
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showMessageModal('✓ Refund rejected successfully!');
                            setTimeout(function(){ location.reload(); }, 1500);
                        } else {
                            showMessageModal('✗ Error: ' + (response.message || 'Failed to reject refund'));
                        }
                    },
                    error: function() {
                        showMessageModal('✗ An error occurred while rejecting the refund');
                    }
                });
            }
        });
    }
    // Confirmation Modal Logic
    function showConfirmModal({title, message, input, onOk}) {
        document.getElementById('confirmModalTitle').innerHTML = '<i class="fas fa-question-circle"></i> ' + (title || 'Confirm Action');
        document.getElementById('confirmModalText').textContent = message || '';
        var inputGroup = document.getElementById('confirmModalInputGroup');
        var inputBox = document.getElementById('confirmModalInput');
        if (input) {
            inputGroup.style.display = '';
            inputBox.value = '';
        } else {
            inputGroup.style.display = 'none';
            inputBox.value = '';
        }
        document.getElementById('confirmModal').style.display = 'block';
        var okBtn = document.getElementById('confirmModalOkBtn');
        okBtn.onclick = function() {
            document.getElementById('confirmModal').style.display = 'none';
            if (input) {
                onOk(inputBox.value);
            } else {
                onOk();
            }
        };
    }
    function closeConfirmModal() {
        document.getElementById('confirmModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const statusModal = document.getElementById('updateStatusModal');
        const refundModal = document.getElementById('refundReasonModal');
        if (event.target === statusModal) {
            closeStatusModal();
        } else if (event.target === refundModal) {
            closeRefundReasonModal();
        }
    }

    // Handle form submission
    $(document).ready(function() {
        $('#updateStatusForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize();
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            
            // Disable button and show loading
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');
            
            $.ajax({
                url: '<?php echo $controllerBasePath; ?>AdminController.php?action=updateOrderStatus',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('✓ Order status updated successfully!');
                        location.reload();
                    } else {
                        alert('✗ Error: ' + (response.message || 'Failed to update order status'));
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    alert('✗ Error: Failed to update order status. Please try again.');
                    submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });
    });
    </script>
</body>
</html>