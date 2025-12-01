<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path
$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_views = (strpos($current_dir, '/views') !== false);
$prefix = $is_in_views ? '../../' : '';

$pageTitle = 'Admin - Bulk Import Vouchers Preview';

// Check if vouchers are in session
if (!isset($_SESSION['bulk_import_vouchers'])) {
    $_SESSION['error_message'] = 'No vouchers to preview. Please upload a CSV file first.';
    $returnTo = isset($_GET['return_to']) ? $_GET['return_to'] : '';
    $redirectUrl = 'VoucherRegisterForm.php';
    if ($returnTo) {
        $redirectUrl .= '?return_to=' . urlencode($returnTo);
    }
    header('Location: ' . $redirectUrl);
    exit;
}

$vouchers = $_SESSION['bulk_import_vouchers'];
$errors = isset($_SESSION['bulk_import_errors']) ? $_SESSION['bulk_import_errors'] : [];
$returnTo = isset($_GET['return_to']) ? $_GET['return_to'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - NGEAR' : 'NGEAR - Sports & Fitness Store'; ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $prefix; ?>css/VoucherForm.css">
</head>
<body>
    <!-- Include Navbar -->
    <?php include __DIR__ . '/../../general/_navbar.php'; ?>

    <main class="voucher-main">
        <div class="voucher-container" style="max-width: 72rem;">
            <div class="mb-6">
                <a href="<?php echo $prefix . 'views/voucher_management/VoucherRegisterForm.php' . ($returnTo ? '?return_to=' . urlencode($returnTo) : ''); ?>" class="back-link">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Back
                </a>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="message-box message-success mb-4">
                    <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="message-box message-error mb-4">
                    <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <header class="voucher-header">
                <h1>Bulk Import Preview</h1>
                <p>Review the vouchers below before importing them into the system.</p>
            </header>

            <!-- Summary -->
            <div class="voucher-card mb-6">
                <div class="summary-grid">
                    <div class="summary-item">
                        <p class="summary-value" style="color: #D92121;"><?php echo count($vouchers); ?></p>
                        <p class="summary-label">Valid Vouchers</p>
                    </div>
                    <div class="summary-item">
                        <p class="summary-value" style="color: #ef4444;"><?php echo count($errors); ?></p>
                        <p class="summary-label">Invalid Rows</p>
                    </div>
                    <div class="summary-item">
                        <p class="summary-value"><?php echo count($vouchers) + count($errors); ?></p>
                        <p class="summary-label">Total Rows</p>
                    </div>
                </div>
            </div>

            <!-- Invalid Rows (if any) -->
            <?php if (!empty($errors)): ?>
                <div class="voucher-card mb-6" style="background-color: #fef2f2; border-color: #fecaca;">
                    <div class="voucher-card-section">
                        <h3 style="color: #991b1b; display: flex; align-items: center; gap: 0.5rem;">
                            <span class="material-symbols-outlined">error</span>
                            Invalid Rows (Will Not Be Imported)
                        </h3>
                        <div style="margin-top: 1rem;">
                            <?php foreach ($errors as $error): ?>
                                <div class="voucher-card" style="margin-bottom: 1rem; background-color: #ffffff;">
                                    <div class="voucher-card-section">
                                        <p style="font-weight: 600; color: #991b1b; margin-bottom: 0.5rem;">
                                            Row <?php echo $error['row']; ?>: <?php echo htmlspecialchars($error['data']['code'] ?: 'No code'); ?>
                                        </p>
                                        <ul class="error-list">
                                            <?php foreach ($error['errors'] as $err): ?>
                                                <li><?php echo htmlspecialchars($err); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Valid Vouchers Table -->
            <?php if (!empty($vouchers)): ?>
                <div class="voucher-card">
                    <div class="voucher-card-section" style="border-bottom: 1px solid #e5e7eb;">
                        <h3 style="display: flex; align-items: center; gap: 0.5rem;">
                            <span class="material-symbols-outlined">check_circle</span>
                            Valid Vouchers (Will Be Imported)
                        </h3>
                    </div>
                    <div class="preview-table-wrapper">
                        <table class="preview-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th>Discount</th>
                                    <th>Min Spend</th>
                                    <th>Max Discount</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vouchers as $voucher): ?>
                                    <tr>
                                        <td style="font-weight: 500;">
                                            <?php echo htmlspecialchars($voucher['code']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($voucher['description'] ?: '-'); ?>
                                        </td>
                                        <td>
                                            <span class="badge">
                                                <?php echo htmlspecialchars(ucfirst($voucher['type'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($voucher['type'] === 'percent') {
                                                echo htmlspecialchars($voucher['discount_value']) . '%';
                                            } elseif ($voucher['type'] === 'fixed') {
                                                echo '$' . htmlspecialchars($voucher['discount_value']);
                                            } else {
                                                echo 'Free Shipping';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo !empty($voucher['min_spend']) ? '$' . htmlspecialchars($voucher['min_spend']) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php echo !empty($voucher['max_discount']) ? '$' . htmlspecialchars($voucher['max_discount']) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($voucher['start_date']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($voucher['end_date']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Import Action -->
                <?php if (!empty($vouchers)): ?>
                    <div class="voucher-card mt-6">
                        <div class="voucher-card-section">
                            <form action="<?php echo $prefix; ?>controller/VoucherController.php" method="POST">
                                <input type="hidden" name="action" value="executeBulkImport">
                                <?php if ($returnTo): ?>
                                    <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($returnTo); ?>">
                                <?php endif; ?>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <p style="font-weight: 500; margin-bottom: 0.25rem;">Ready to import <?php echo count($vouchers); ?> voucher(s)?</p>
                                        <p style="font-size: 0.875rem; color: #6b7280;">Click the button below to confirm and import all valid vouchers.</p>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <span class="material-symbols-outlined btn-icon">check</span>
                                        Confirm Import
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="voucher-card" style="background-color: #fefce8; border-color: #fde047; text-align: center;">
                    <div class="voucher-card-section">
                        <p style="color: #854d0e;">No valid vouchers to import. Please check your CSV file and try again.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Include Footer -->
    <?php include __DIR__ . '/../../general/_footer.php'; ?>
</body>
</html>
