<?php
// Note: Session and authentication checks are handled in the controller
// Ensure session is started if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect admins to AdminDashboard - they should not access member pages
if (isset($_SESSION['user']) && isset($_SESSION['user']->role) && $_SESSION['user']->role === 'admin') {
    header('Location: ../../views/admin/AdminDashboard.php');
    exit;
}

$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_views = (strpos($current_dir, '/views') !== false);
$prefix = $is_in_views ? '../' : '';

$pageTitle = 'My Vouchers';

// Calculate base path for CSS
$currentFileDir = dirname(__FILE__);
$webRootDir = dirname(dirname($currentFileDir));
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$cssBasePath = str_replace('\\', '/', $relativePath) . '/css/';

// Get filter from URL
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$allowedFilters = ['all', 'active', 'used', 'expired'];
if (!in_array($filter, $allowedFilters)) {
    $filter = 'all';
}

// Check if user is logged in - use controller's variables if set, otherwise check session
if (!isset($isLoggedIn)) {
    $isLoggedIn = isset($_SESSION['user']) && !empty($_SESSION['user']);
}
if (!isset($isMember)) {
    $isMember = $isLoggedIn && isset($_SESSION['user']->role) && $_SESSION['user']->role === 'member';
}

// Helper function to format discount value
function formatDiscountValue($type, $discountValue, $maxDiscount = null)
{
    switch ($type) {
        case 'percent':
            $formatted = $discountValue . '%';
            if ($maxDiscount !== null && $maxDiscount > 0) {
                $formatted .= ' (max: $' . number_format($maxDiscount, 2) . ')';
            }
            return $formatted;
        case 'fixed':
            return '$' . number_format($discountValue, 2);
        case 'freeshipping':
            return 'Free Shipping';
        default:
            return number_format($discountValue, 2);
    }
}

// Helper function to format date
function formatDate($date)
{
    if (empty($date)) return '';
    $dateObj = new DateTime($date);
    return $dateObj->format('d M Y');
}

// Determine voucher status
function getVoucherStatus($voucher)
{
    if (!empty($voucher['used_at'])) {
        return 'used';
    }

    $currentDate = date('Y-m-d');
    $endDate = $voucher['end_date'];
    $startDate = $voucher['start_date'];

    if ($endDate < $currentDate) {
        return 'expired';
    }

    if ($voucher['status'] === 'inactive') {
        return 'inactive';
    }

    if ($startDate > $currentDate) {
        return 'pending';
    }

    return 'active';
}

include __DIR__ . '/../../general/_header.php';
include __DIR__ . '/../../general/_navbar.php';
?>

<link rel="stylesheet" href="<?php echo $cssBasePath; ?>MemberVoucherList.css?v=<?php echo time(); ?>">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

<div class="voucher-list-container">
    <div class="voucher-list-content">
        <main class="voucher-main">
            <div class="voucher-title-section">
                <h1 class="voucher-title">My Vouchers</h1>
                <div class="sort-section">
                    <span class="sort-label">Sort by:</span>
                    <div class="sort-dropdown">
                        <button class="sort-button" id="sortButton">
                            <?php
                            $currentSortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'end_date';
                            $currentSortOrder = isset($_GET['sortOrder']) ? $_GET['sortOrder'] : 'ASC';

                            $sortLabels = [
                                'end_date' => 'Expiry Date',
                                'start_date' => 'Start Date',
                                'assigned_at' => 'Assigned Date',
                                'discount_value' => 'Discount Value',
                                'code' => 'Voucher Code'
                            ];
                            echo $sortLabels[$currentSortBy] ?? 'Expiry Date';
                            ?>
                            <span class="material-symbols-outlined">expand_more</span>
                        </button>
                        <div class="sort-dropdown-menu" id="sortDropdown">
                            <?php
                            $sortOptions = [
                                'end_date' => 'Expiry Date',
                                'start_date' => 'Start Date',
                                'assigned_at' => 'Assigned Date',
                                'discount_value' => 'Discount Value',
                                'code' => 'Voucher Code'
                            ];

                            foreach ($sortOptions as $sortKey => $sortLabel):
                                $newSortOrder = ($currentSortBy === $sortKey && $currentSortOrder === 'ASC') ? 'DESC' : 'ASC';
                                $sortUrl = $prefix . 'controller/VoucherController.php?action=showMemberVouchers&filter=' . $filter . '&sortBy=' . $sortKey . '&sortOrder=' . $newSortOrder;
                                $isActive = ($currentSortBy === $sortKey);
                            ?>
                                <a href="<?php echo $sortUrl; ?>" class="sort-option <?php echo $isActive ? 'active' : ''; ?>">
                                    <?php echo $sortLabel; ?>
                                    <?php if ($isActive): ?>
                                        <span class="material-symbols-outlined">
                                            <?php echo $currentSortOrder === 'ASC' ? 'arrow_upward' : 'arrow_downward'; ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            // Show success/error messages
            if (isset($_SESSION['success_message'])): ?>
                <div class="message-alert success-message">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span><?php echo htmlspecialchars($_SESSION['success_message']); ?></span>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="message-alert error-message">
                    <span class="material-symbols-outlined">error</span>
                    <span><?php echo htmlspecialchars($_SESSION['error_message']); ?></span>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <?php
            // Show voucher redemption form if user is logged in and is a member
            if ($isLoggedIn && $isMember): ?>
                <div class="redeem-voucher-section">
                    <div class="redeem-voucher-card">
                        <div class="redeem-voucher-header">
                            <span class="material-symbols-outlined">redeem</span>
                            <h2 class="redeem-voucher-title">Redeem Voucher Code</h2>
                        </div>
                        <form method="POST" action="<?php echo $prefix; ?>controller/VoucherController.php" class="redeem-voucher-form" id="redeemVoucherForm">
                            <input type="hidden" name="action" value="redeemVoucher">
                            <div class="redeem-voucher-input-group">
                                <input 
                                    type="text" 
                                    name="voucher_code" 
                                    id="voucher_code" 
                                    class="redeem-voucher-input" 
                                    placeholder="Enter voucher code or scan QR"
                                    required
                                    autocomplete="off"
                                >
                                <button type="submit" class="btn-primary btn-redeem">
                                    <span>Redeem</span>
                                    <span class="material-symbols-outlined">arrow_forward</span>
                                </button>
                            </div>
                            <div class="redeem-voucher-qr-tools">
                                <div class="qr-dropdown">
                                    <button type="button" class="btn-secondary qr-main-btn" id="btnQrOptions">
                                        <span class="material-symbols-outlined">qr_code_2</span>
                                        <span>Use QR</span>
                                        <span class="material-symbols-outlined qr-caret">expand_more</span>
                                    </button>
                                    <div class="qr-dropdown-menu" id="qrOptionsMenu">
                                        <button type="button" class="qr-dropdown-item" id="btnScanQrCamera">
                                            <span class="material-symbols-outlined">qr_code_scanner</span>
                                            <span>Scan with Camera</span>
                                        </button>
                                        <button type="button" class="qr-dropdown-item" id="btnScanQrImage">
                                            <span class="material-symbols-outlined">image</span>
                                            <span>Upload QR Image</span>
                                        </button>
                                    </div>
                                </div>
                                <input type="file" id="qrImageInput" accept="image/*" style="display:none">
                            </div>
                        </form>
                    </div>
                </div>

                <!-- QR Scan Modal -->
                <div id="qrScanModal" class="qr-scan-modal hidden">
                    <div class="qr-scan-backdrop"></div>
                    <div class="qr-scan-dialog">
                        <div class="qr-scan-header">
                            <h3>Scan Voucher QR Code</h3>
                            <button type="button" class="qr-scan-close" id="btnCloseQrModal">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                        <div class="qr-scan-body">
                            <p class="qr-scan-instructions">
                                Align the QR code within the frame. Once detected, the voucher code will be filled in automatically.
                            </p>
                            <div id="qr-reader" class="qr-reader-container"></div>
                        </div>
                    </div>
                </div>

                <!-- Redeem Confirmation Modal -->
                <div id="redeemConfirmModal" class="qr-scan-modal hidden">
                    <div class="qr-scan-backdrop"></div>
                    <div class="qr-scan-dialog">
                        <div class="qr-scan-header">
                            <h3>Confirm Redeem</h3>
                            <button type="button" class="qr-scan-close" id="btnCloseRedeemModal">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                        <div class="qr-scan-body">
                            <p class="qr-scan-instructions">
                                Are you sure you want to redeem this voucher code?
                                <br>
                                <strong id="redeemVoucherCodeLabel"></strong>
                            </p>
                            <div class="redeem-confirm-actions">
                                <button type="button" class="btn-secondary" id="btnRedeemCancel">
                                    <span class="material-symbols-outlined">close</span>
                                    <span>Cancel</span>
                                </button>
                                <button type="button" class="btn-primary" id="btnRedeemConfirm">
                                    <span class="material-symbols-outlined">check</span>
                                    <span>Confirm</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            // Only show filter tabs if user is logged in and is a member
            if ($isLoggedIn && $isMember): ?>
                <div class="filter-tabs">
                    <a href="<?php echo $prefix; ?>controller/VoucherController.php?action=showMemberVouchers&filter=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
                    <a href="<?php echo $prefix; ?>controller/VoucherController.php?action=showMemberVouchers&filter=active" class="filter-tab <?php echo $filter === 'active' ? 'active' : ''; ?>">Active</a>
                    <a href="<?php echo $prefix; ?>controller/VoucherController.php?action=showMemberVouchers&filter=used" class="filter-tab <?php echo $filter === 'used' ? 'active' : ''; ?>">Used</a>
                    <a href="<?php echo $prefix; ?>controller/VoucherController.php?action=showMemberVouchers&filter=expired" class="filter-tab <?php echo $filter === 'expired' ? 'active' : ''; ?>">Expired</a>
                </div>
            <?php endif; ?>

            <div class="voucher-grid">
                <?php
                if (!$isLoggedIn || !$isMember): ?>
                    <div class="no-vouchers login-required">
                        <div class="login-message-icon">
                            <span class="material-symbols-outlined">lock</span>
                        </div>
                        <p class="no-vouchers-message">
                            Please login to get more vouchers
                        </p>
                        <a href="<?php echo $prefix; ?>account.php" class="view-all-link login-link">Login Now</a>
                    </div>
                <?php elseif (!empty($vouchers)): ?>
                    <?php foreach ($vouchers as $voucher):
                        $status = getVoucherStatus($voucher);
                        $isUsed = $status === 'used';
                        $isExpired = $status === 'expired';
                        $isActive = $status === 'active';
                    ?>
                        <div class="voucher-card <?php echo $isUsed || $isExpired ? 'disabled' : ''; ?>">
                            <div class="voucher-card-content">
                                <div class="voucher-info">
                                    <p class="voucher-status-badge <?php echo $isActive ? 'active' : ($isUsed ? 'used' : 'expired'); ?>">
                                        <?php echo ucfirst($status); ?>
                                    </p>
                                    <p class="voucher-title-text">
                                        <?php echo formatDiscountValue($voucher['type'], $voucher['discount_value'], $voucher['max_discount'] ?? null); ?>
                                    </p>
                                    <p class="voucher-description">
                                        <?php if ($isUsed): ?>
                                            Used on <?php echo formatDate($voucher['used_at']); ?>.
                                        <?php elseif ($isExpired): ?>
                                            Expired on <?php echo formatDate($voucher['end_date']); ?>.
                                        <?php else: ?>
                                            Valid until <?php echo formatDate($voucher['end_date']); ?>.
                                        <?php endif; ?>
                                        <?php if (!empty($voucher['description'])): ?>
                                            <?php echo htmlspecialchars($voucher['description']); ?>
                                        <?php else: ?>
                                            <?php if (!empty($voucher['min_spend']) && $voucher['min_spend'] > 0): ?>
                                                On all orders over $<?php echo number_format($voucher['min_spend'], 2); ?>.
                                            <?php else: ?>
                                                On all orders.
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="voucher-actions">
                                    <?php if ($isActive): ?>
                                        <button class="btn-primary btn-shop-now" data-shop-url="../../index.php?page=product">
                                            Shop Now
                                        </button>
                                        <button class="btn-secondary btn-copy-code" data-voucher-code="<?php echo htmlspecialchars($voucher['code'], ENT_QUOTES); ?>">
                                            <span class="material-symbols-outlined">content_copy</span>
                                            <span>Copy Code</span>
                                        </button>
                                    <?php elseif ($isUsed): ?>
                                        <button class="btn-disabled" disabled>
                                            <span class="material-symbols-outlined">history</span>
                                            <span>View Order</span>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-disabled" disabled>
                                            Expired
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-vouchers">
                        <p class="no-vouchers-message">
                            <?php if ($filter === 'all'): ?>
                                You don't have any vouchers yet.
                            <?php else: ?>
                                No <?php echo $filter; ?> vouchers found.
                            <?php endif; ?>
                        </p>
                        <?php if ($filter !== 'all'): ?>
                            <a href="<?php echo $prefix; ?>controller/VoucherController.php?action=showMemberVouchers&filter=all" class="view-all-link">View All Vouchers</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination (if needed in future) -->
            <?php if (!empty($vouchers) && count($vouchers) > 0): ?>
                <div class="pagination-container">
                    <!-- Pagination can be added here if needed -->
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- QR scanning library (supports camera and image files) -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script src="<?php echo $prefix; ?>js/memberVoucherList.js?v=<?php echo filemtime(__DIR__ . '/../../js/memberVoucherList.js'); ?>"></script>

<?php include __DIR__ . '/../../general/_footer.php'; ?>