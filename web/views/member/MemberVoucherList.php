<?php
// Note: Session and authentication checks are handled in the controller
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

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user']) && !empty($_SESSION['user']);
$isMember = $isLoggedIn && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'member';

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
                                        <button class="btn-primary btn-shop-now" data-shop-url="<?php echo $prefix; ?>index.php?page=product">
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
<script>
    function copyVoucherCode(code) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(code).then(function() {
                alert('Voucher code copied: ' + code);
            }, function() {
                // Fallback for older browsers
                fallbackCopyToClipboard(code);
            });
        } else {
            // Fallback for older browsers
            fallbackCopyToClipboard(code);
        }
    }

    function fallbackCopyToClipboard(code) {
        const $textarea = $('<textarea>').val(code).css({
            position: 'fixed',
            left: '-9999px',
            top: '-9999px'
        });
        $('body').append($textarea);
        $textarea[0].select();
        try {
            document.execCommand('copy');
            alert('Voucher code copied: ' + code);
        } catch (err) {
            alert('Failed to copy voucher code. Please copy manually: ' + code);
        }
        $textarea.remove();
    }

    $(document).ready(function() {
        const $sortButton = $('#sortButton');
        const $sortDropdown = $('#sortDropdown');

        if ($sortButton.length && $sortDropdown.length) {
            $sortButton.on('click', function(e) {
                e.stopPropagation();
                const isVisible = $sortDropdown.is(':visible');
                $sortDropdown.toggle();
            });

            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$sortButton.is(e.target) && !$sortDropdown.is(e.target) && !$sortButton.find(e.target).length && !$sortDropdown.find(e.target).length) {
                    $sortDropdown.hide();
                }
            });
        }

        // Shop Now button handler
        $(document).on('click', '.btn-shop-now', function() {
            const shopUrl = $(this).data('shop-url');
            if (shopUrl) {
                window.location.href = shopUrl;
            }
        });

        // Copy code button handler
        $(document).on('click', '.btn-copy-code', function() {
            const code = $(this).data('voucher-code');
            if (code) {
                copyVoucherCode(code);
            }
        });
    });
</script>

<?php include __DIR__ . '/../../general/_footer.php'; ?>