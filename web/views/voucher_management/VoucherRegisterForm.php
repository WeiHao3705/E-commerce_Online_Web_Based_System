<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path
$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_views = (strpos($current_dir, '/views') !== false);
$prefix = $is_in_views ? '../../' : '';

$pageTitle = 'Admin - Create New Voucher';

// Initialize errors array
$errors = [];

// Check for validation errors from controller
if (isset($_SESSION['validation_errors'])) {
    $errors = array_merge($errors, $_SESSION['validation_errors']);
    unset($_SESSION['validation_errors']);
}

// Preserve form data from session if available (for error repopulation)
$formData = [];
if (isset($_SESSION['form_data'])) {
    $formData = $_SESSION['form_data'];
    unset($_SESSION['form_data']);
}

// Get error field for scrolling and highlighting
$errorField = isset($_SESSION['error_field']) ? $_SESSION['error_field'] : null;
if ($errorField) {
    unset($_SESSION['error_field']);
}

// Merge form data with POST data (POST takes priority for current submission)
if (!empty($_POST)) {
    $formData = array_merge($formData, $_POST);
}
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
    <main class="voucher-main">
        <div class="voucher-container">
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

            <?php if (!empty($errors)): ?>
                <div class="message-box message-error mb-4">
                    <ul class="error-list">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <header class="voucher-header">
                <h1>Create New Voucher</h1>
                <p>Fill in the details below to create a new voucher for your store.</p>
            </header>

            <div class="voucher-card">
                <form action="<?php echo $prefix; ?>controller/VoucherController.php" method="POST">
                    <input type="hidden" name="action" value="register">
                    <?php if (isset($_GET['return_to'])): ?>
                        <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($_GET['return_to']); ?>">
                    <?php endif; ?>

                    <div class="voucher-card-section">
                        <h3>Voucher Identification</h3>
                        <p>Basic details to identify the voucher.</p>
                        <div class="form-grid">
                            <label class="form-label">
                                <span class="form-label">Voucher Code <span class="required">*</span></span>
                                <div class="form-input-group">
                                    <input 
                                        class="form-input" 
                                        placeholder="e.g. SUMMER25" 
                                        name="code"
                                        id="code"
                                        value="<?php echo isset($formData['code']) ? htmlspecialchars($formData['code']) : ''; ?>"
                                        required
                                    />
                                    <button class="form-input-suffix" type="button" id="generate-code">
                                        <span class="material-symbols-outlined">auto_awesome</span>
                                    </button>
                                </div>
                            </label>
                            <label class="form-label form-grid-full">
                                <span class="form-label">Description</span>
                                <textarea 
                                    class="form-textarea" 
                                    placeholder="Internal note, e.g., Summer sale campaign for new members"
                                    name="description"
                                    id="description"
                                ><?php echo isset($formData['description']) ? htmlspecialchars($formData['description']) : ''; ?></textarea>
                            </label>
                        </div>
                    </div>

                    <div class="voucher-card-section">
                        <h3>Discount Details</h3>
                        <div class="form-grid">
                            <label class="form-label">
                                <span class="form-label">Voucher Type <span class="required">*</span></span>
                                <select 
                                    class="form-select"
                                    name="type"
                                    id="type"
                                    required
                                >
                                    <option value="">Select Type</option>
                                    <option value="percent" <?php echo (isset($formData['type']) && $formData['type'] == 'percent') ? 'selected' : ''; ?>>Percentage</option>
                                    <option value="fixed" <?php echo (isset($formData['type']) && $formData['type'] == 'fixed') ? 'selected' : ''; ?>>Fixed Amount</option>
                                    <option value="freeshipping" <?php echo (isset($formData['type']) && $formData['type'] == 'freeshipping') ? 'selected' : ''; ?>>Free Shipping</option>
                                </select>
                            </label>
                            <label class="form-label">
                                <span class="form-label">Discount Value <span class="required">*</span></span>
                                <div class="form-input-group">
                                    <span class="form-input-prefix" id="discount-prefix">%</span>
                                    <input 
                                        class="form-input" 
                                        placeholder="25" 
                                        type="number"
                                        name="discount_value"
                                        id="discount-value"
                                        step="0.01"
                                        min="0"
                                        value="<?php echo isset($formData['discount_value']) ? htmlspecialchars($formData['discount_value']) : ''; ?>"
                                        required
                                    />
                                </div>
                            </label>
                            <label class="form-label" id="max-discount-group" style="display: none;">
                                <span class="form-label">Maximum Discount ($)</span>
                                <input 
                                    class="form-input" 
                                    placeholder="e.g. 50" 
                                    type="number"
                                    name="max_discount"
                                    id="max-discount"
                                    step="0.01"
                                    min="0"
                                    value="<?php echo isset($formData['max_discount']) ? htmlspecialchars($formData['max_discount']) : ''; ?>"
                                />
                            </label>
                        </div>
                    </div>

                    <div class="voucher-card-section">
                        <h3>Usage Rules & Conditions</h3>
                        <div class="form-grid">
                            <label class="form-label">
                                <span class="form-label">Minimum Spend ($)</span>
                                <input 
                                    class="form-input" 
                                    placeholder="e.g. 100" 
                                    type="number"
                                    name="min_spend"
                                    id="min-spend"
                                    step="0.01"
                                    min="0"
                                    value="<?php echo isset($formData['min_spend']) ? htmlspecialchars($formData['min_spend']) : '0'; ?>"
                                />
                            </label>
                            <div class="form-label form-grid-full">
                                <span class="form-label">Validity Period <span class="required">*</span></span>
                                <div class="date-input-group">
                                    <input 
                                        class="form-input" 
                                        type="date"
                                        name="start_date"
                                        id="start-date"
                                        value="<?php echo isset($formData['start_date']) ? htmlspecialchars($formData['start_date']) : ''; ?>"
                                        required
                                    />
                                    <span class="date-separator">to</span>
                                    <input 
                                        class="form-input" 
                                        type="date"
                                        name="end_date"
                                        id="end-date"
                                        value="<?php echo isset($formData['end_date']) ? htmlspecialchars($formData['end_date']) : ''; ?>"
                                        required
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="voucher-card-section">
                        <h3>Redemption Settings</h3>
                        <div class="form-grid">
                            <label class="form-label form-grid-full">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <input 
                                        type="checkbox" 
                                        name="is_redeemable" 
                                        id="is-redeemable" 
                                        value="1" 
                                        checked
                                        style="width: 20px; height: 20px; cursor: pointer;"
                                    />
                                    <div>
                                        <span class="form-label">Allow members to redeem this voucher</span>
                                        <small style="display: block; margin-top: 0.25rem; color: #6b7280;">If unchecked, only admins can assign this voucher to members. Members won't be able to redeem it using the voucher code.</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="voucher-card-section">
                        <h3>Initial Status</h3>
                        <div class="toggle-wrapper">
                            <div class="toggle-label">
                                <p>Set as Active</p>
                                <small>If active, the voucher will be usable immediately.</small>
                            </div>
                            <button 
                                aria-checked="true"
                                class="toggle-switch"
                                role="switch" 
                                type="button"
                                id="status-toggle"
                            >
                                <span></span>
                            </button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <div class="form-actions-left">
                            <button type="button" id="bulk-import-btn" class="btn btn-outline">
                                <span class="material-symbols-outlined btn-icon">upload_file</span>
                                Bulk Import
                            </button>
                        </div>
                        <div class="form-actions-right">
                            <a href="<?php echo isset($_GET['return_to']) && $_GET['return_to'] === 'admin' ? $prefix . 'controller/VoucherController.php?action=showAll' : $prefix . 'index.php'; ?>" class="btn btn-secondary">Cancel</a>
                            <button class="btn btn-primary" type="submit">Create Voucher</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Bulk Import Modal -->
    <div id="bulk-import-modal" class="modal-overlay hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Bulk Import Vouchers</h2>
                <button type="button" id="close-modal" class="modal-close">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-6">
                    <p style="margin-bottom: 1rem;">Import multiple vouchers at once using a CSV file. Download the template to see the required format.</p>
                    <div style="display: flex; gap: 0.75rem;">
                        <a href="<?php echo $prefix; ?>controller/VoucherController.php?action=downloadTemplate<?php echo isset($_GET['return_to']) ? '&return_to=' . htmlspecialchars($_GET['return_to']) : ''; ?>" class="btn btn-outline">
                            <span class="material-symbols-outlined btn-icon">download</span>
                            Download CSV Template
                        </a>
                    </div>
                </div>
                <form id="bulk-import-form" action="<?php echo $prefix; ?>controller/VoucherController.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="previewBulkImport">
                    <?php if (isset($_GET['return_to'])): ?>
                        <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($_GET['return_to']); ?>">
                    <?php endif; ?>
                    <div class="mb-4">
                        <label class="form-label">
                            <span class="form-label">Select CSV File <span class="required">*</span></span>
                            <input 
                                type="file" 
                                name="csv_file" 
                                id="csv-file"
                                accept=".csv"
                                required
                                class="form-input"
                            />
                        </label>
                    </div>
                    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                        <button type="button" id="cancel-import" class="btn btn-secondary">Cancel</button>
                        <button type="submit" class="btn btn-primary">Preview Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // jQuery event handlers - following conventions (use jQuery instead of plain JavaScript)
        $(document).ready(function() {
            // Bulk Import Modal handlers
            $('#bulk-import-btn').on('click', function() {
                $('#bulk-import-modal').removeClass('hidden');
            });

            $('#close-modal, #cancel-import').on('click', function() {
                $('#bulk-import-modal').addClass('hidden');
            });

            $('#bulk-import-modal').on('click', function(e) {
                if ($(e.target).is('#bulk-import-modal')) {
                    $(this).addClass('hidden');
                }
            });

            // Generate random voucher code
            $('#generate-code').on('click', function() {
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                let code = '';
                for (let i = 0; i < 8; i++) {
                    code += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                $('#code').val(code);
            });

            // Update discount prefix and show/hide max discount based on type
            $('#type').on('change', function() {
                const type = $(this).val();
                const $discountPrefix = $('#discount-prefix');
                const $discountValueInput = $('#discount-value');
                const $maxDiscountGroup = $('#max-discount-group');

                if (type === 'percent') {
                    $discountPrefix.text('%');
                    $discountValueInput.attr('max', '100');
                    $maxDiscountGroup.show();
                } else if (type === 'fixed') {
                    $discountPrefix.text('$');
                    $discountValueInput.removeAttr('max');
                    $maxDiscountGroup.hide();
                } else if (type === 'freeshipping') {
                    $discountPrefix.text('');
                    $discountValueInput.val('0').attr('readonly', 'readonly');
                    $maxDiscountGroup.hide();
                } else {
                    $discountValueInput.removeAttr('readonly');
                    $maxDiscountGroup.hide();
                }
            });

            // Validate end date is after start date
            $('#start-date').on('change', function() {
                const startDate = new Date($(this).val());
                const $endDateInput = $('#end-date');
                const endDate = new Date($endDateInput.val());

                if ($endDateInput.val() && endDate < startDate) {
                    alert('End date must be after start date!');
                    $endDateInput.val('');
                }
            });

            $('#end-date').on('change', function() {
                const $startDateInput = $('#start-date');
                const startDate = new Date($startDateInput.val());
                const endDate = new Date($(this).val());

                if ($startDateInput.val() && endDate < startDate) {
                    alert('End date must be after start date!');
                    $(this).val('');
                }
            });

            // Validate discount value based on type
            $('#discount-value').on('input', function() {
                const type = $('#type').val();
                const value = parseFloat($(this).val());

                if (type === 'percent' && (value < 0 || value > 100)) {
                    $(this).css('border-color', '#ef4444');
                } else {
                    $(this).css('border-color', '');
                }
            });

            // Toggle switch functionality
            $('#status-toggle').on('click', function() {
                const isChecked = $(this).attr('aria-checked') === 'true';
                $(this).attr('aria-checked', isChecked ? 'false' : 'true');
            });

            // Trigger type change on page load if type is already selected
            if ($('#type').val()) {
                $('#type').trigger('change');
            }

            // Scroll to error field if exists
            <?php if ($errorField): ?>
                var $errorField = $('#<?php echo html_escape($errorField); ?>');
                if ($errorField.length) {
                    $('html, body').animate({
                        scrollTop: $errorField.offset().top - 100
                    }, 500);
                    $errorField.focus();
                }
            <?php endif; ?>
        });
    </script>
</body>
</html>
