<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path
$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_views = (strpos($current_dir, '/views') !== false);
$prefix = $is_in_views ? '../' : '';

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
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - REDSTORE' : 'REDSTORE - Sports & Fitness Store'; ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script>
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            colors: {
              "primary": "#D92121",
              "background-light": "#f8f8f8",
              "background-dark": "#121212",
              "text-light": "#111111",
              "text-dark": "#eeeeee",
              "subtle-light": "#6b7280",
              "subtle-dark": "#9ca3af",
              "border-light": "#e5e7eb",
              "border-dark": "#374151",
              "card-light": "#ffffff",
              "card-dark": "#1f2937"
            },
            fontFamily: {
              "display": ["Inter", "sans-serif"]
            },
            borderRadius: {
              "DEFAULT": "0.25rem",
              "lg": "0.5rem",
              "xl": "0.75rem",
              "full": "9999px"
            },
          },
        },
      }
    </script>
    <style>
      .material-symbols-outlined {
        font-variation-settings:
        'FILL' 0,
        'wght' 400,
        'GRAD' 0,
        'opsz' 24
      }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-text-light dark:text-text-dark">
    <!-- Include Navbar -->
    <?php include $prefix . 'general/_navbar.php'; ?>

    <main class="flex-1 w-full py-8 sm:py-12 lg:py-16">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <a href="<?php echo isset($_GET['return_to']) && $_GET['return_to'] === 'admin' ? $prefix . 'controller/VoucherController.php?action=showAll' : $prefix . 'index.php'; ?>" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-text-light dark:text-text-dark bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg hover:bg-background-light dark:hover:bg-background-dark transition-colors">
                        <span class="material-symbols-outlined text-lg">arrow_back</span>
                        Back
                    </a>
                </div>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 rounded-lg">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg">
                        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="mb-4 p-4 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg">
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <header class="text-center mb-8">
                    <h1 class="text-text-light dark:text-text-dark text-3xl md:text-4xl font-bold tracking-tight">Create New Voucher</h1>
                    <p class="mt-2 text-subtle-light dark:text-subtle-dark">Fill in the details below to create a new voucher for your store.</p>
                </header>

                <div class="bg-card-light dark:bg-card-dark rounded-xl border border-border-light dark:border-border-dark shadow-sm">
                    <form action="<?php echo $prefix; ?>controller/VoucherController.php" class="flex flex-col divide-y divide-border-light dark:divide-border-dark" method="POST">
                        <input type="hidden" name="action" value="register">
                        <?php if (isset($_GET['return_to'])): ?>
                            <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($_GET['return_to']); ?>">
                        <?php endif; ?>

                        <div class="p-6">
                            <h3 class="text-text-light dark:text-text-dark text-lg font-semibold leading-tight">Voucher Identification</h3>
                            <p class="text-subtle-light dark:text-subtle-dark text-sm mt-1">Basic details to identify the voucher.</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <label class="flex flex-col col-span-1">
                                    <p class="text-text-light dark:text-text-dark text-sm font-medium leading-normal pb-2">Voucher Code <span class="text-red-500">*</span></p>
                                    <div class="flex w-full items-stretch rounded-lg">
                                        <input 
                                            class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-l-lg text-text-light dark:text-text-dark focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary h-11 placeholder:text-subtle-light dark:placeholder:text-subtle-dark p-2.5 text-sm" 
                                            placeholder="e.g. SUMMER25" 
                                            name="code"
                                            id="code"
                                            value="<?php echo isset($formData['code']) ? htmlspecialchars($formData['code']) : ''; ?>"
                                            required
                                        />
                                        <button class="text-text-light dark:text-text-dark flex border border-l-0 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark items-center justify-center px-3 rounded-r-lg hover:bg-primary/10 transition-colors" type="button" id="generate-code">
                                            <span class="material-symbols-outlined text-lg">auto_awesome</span>
                                        </button>
                                    </div>
                                </label>
                                <label class="flex flex-col col-span-2">
                                    <p class="text-text-light dark:text-text-dark text-sm font-medium leading-normal pb-2">Description</p>
                                    <textarea 
                                        class="form-textarea flex w-full min-w-0 flex-1 resize-y overflow-hidden rounded-lg text-text-light dark:text-text-dark focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary placeholder:text-subtle-light dark:placeholder:text-subtle-dark p-2.5 text-sm" 
                                        placeholder="Internal note, e.g., Summer sale campaign for new members"
                                        name="description"
                                        id="description"
                                    ><?php echo isset($formData['description']) ? htmlspecialchars($formData['description']) : ''; ?></textarea>
                                </label>
                            </div>
                        </div>

                        <div class="p-6">
                            <h3 class="text-text-light dark:text-text-dark text-lg font-semibold leading-tight">Discount Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                                <label class="flex flex-col col-span-1">
                                    <p class="text-text-light dark:text-text-dark text-sm font-medium leading-normal pb-2">Voucher Type <span class="text-red-500">*</span></p>
                                    <select 
                                        class="form-select flex w-full min-w-0 rounded-lg text-text-light dark:text-text-dark focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary h-11 p-2.5 text-sm"
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
                                <label class="flex flex-col col-span-1">
                                    <p class="text-text-light dark:text-text-dark text-sm font-medium leading-normal pb-2">Discount Value <span class="text-red-500">*</span></p>
                                    <div class="flex w-full items-stretch rounded-lg">
                                        <span class="flex items-center justify-center px-3 border border-r-0 border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark text-subtle-light dark:text-subtle-dark rounded-l-lg text-sm" id="discount-prefix">%</span>
                                        <input 
                                            class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-r-lg text-text-light dark:text-text-dark focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary h-11 placeholder:text-subtle-light dark:placeholder:text-subtle-dark p-2.5 text-sm" 
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
                                <label class="flex flex-col col-span-1" id="max-discount-group" style="display: none;">
                                    <p class="text-text-light dark:text-text-dark text-sm font-medium leading-normal pb-2">Maximum Discount ($)</p>
                                    <input 
                                        class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-text-light dark:text-text-dark focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary h-11 placeholder:text-subtle-light dark:placeholder:text-subtle-dark p-2.5 text-sm" 
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

                        <div class="p-6">
                            <h3 class="text-text-light dark:text-text-dark text-lg font-semibold leading-tight">Usage Rules & Conditions</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <label class="flex flex-col col-span-1">
                                    <p class="text-text-light dark:text-text-dark text-sm font-medium leading-normal pb-2">Minimum Spend ($)</p>
                                    <input 
                                        class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-text-light dark:text-text-dark focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary h-11 placeholder:text-subtle-light dark:placeholder:text-subtle-dark p-2.5 text-sm" 
                                        placeholder="e.g. 100" 
                                        type="number"
                                        name="min_spend"
                                        id="min-spend"
                                        step="0.01"
                                        min="0"
                                        value="<?php echo isset($formData['min_spend']) ? htmlspecialchars($formData['min_spend']) : '0'; ?>"
                                    />
                                </label>
                                <div class="flex flex-col col-span-2">
                                    <p class="text-text-light dark:text-text-dark text-sm font-medium leading-normal pb-2">Validity Period <span class="text-red-500">*</span></p>
                                    <div class="flex items-center gap-4">
                                        <input 
                                            class="form-input flex-1 w-full rounded-lg text-text-light dark:text-text-dark focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary h-11 p-2.5 text-sm" 
                                            type="date"
                                            name="start_date"
                                            id="start-date"
                                            value="<?php echo isset($formData['start_date']) ? htmlspecialchars($formData['start_date']) : ''; ?>"
                                            required
                                        />
                                        <span class="text-subtle-light dark:text-subtle-dark">to</span>
                                        <input 
                                            class="form-input flex-1 w-full rounded-lg text-text-light dark:text-text-dark focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary h-11 p-2.5 text-sm" 
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

                        <div class="p-6">
                            <h3 class="text-text-light dark:text-text-dark text-lg font-semibold leading-tight">Initial Status</h3>
                            <div class="flex items-center justify-between mt-4">
                                <div>
                                    <p class="text-text-light dark:text-text-dark text-sm font-medium">Set as Active</p>
                                    <p class="text-subtle-light dark:text-subtle-dark text-xs">If active, the voucher will be usable immediately.</p>
                                </div>
                                <button 
                                    aria-checked="true"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent bg-primary transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-background-dark"
                                    role="switch" 
                                    type="button"
                                    id="status-toggle"
                                >
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out translate-x-5"></span>
                                </button>
                            </div>
                        </div>

                        <div class="p-6 flex justify-end items-center gap-4 bg-background-light/50 dark:bg-background-dark/50 rounded-b-xl">
                            <a href="<?php echo isset($_GET['return_to']) && $_GET['return_to'] === 'admin' ? $prefix . 'controller/VoucherController.php?action=showAll' : $prefix . 'index.php'; ?>" class="px-4 py-2 text-sm font-semibold text-text-light dark:text-text-dark bg-transparent rounded-lg hover:bg-black/5 dark:hover:bg-white/5 transition-colors">Cancel</a>
                            <button class="px-5 py-2.5 text-sm font-semibold text-white bg-primary rounded-lg hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-primary/30 transition-colors" type="submit">Create Voucher</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

    <!-- Include Footer -->
    <?php include $prefix . 'general/_footer.php'; ?>

    <script>
        // Generate random voucher code
        document.getElementById('generate-code').addEventListener('click', function() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = '';
            for (let i = 0; i < 8; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('code').value = code;
        });

        // Update discount prefix and show/hide max discount based on type
        document.getElementById('type').addEventListener('change', function() {
            const type = this.value;
            const discountPrefix = document.getElementById('discount-prefix');
            const discountValueInput = document.getElementById('discount-value');
            const maxDiscountGroup = document.getElementById('max-discount-group');

            if (type === 'percent') {
                discountPrefix.textContent = '%';
                discountValueInput.setAttribute('max', '100');
                maxDiscountGroup.style.display = 'flex';
            } else if (type === 'fixed') {
                discountPrefix.textContent = '$';
                discountValueInput.removeAttribute('max');
                maxDiscountGroup.style.display = 'none';
            } else if (type === 'freeshipping') {
                discountPrefix.textContent = '';
                discountValueInput.value = '0';
                discountValueInput.setAttribute('readonly', 'readonly');
                maxDiscountGroup.style.display = 'none';
            } else {
                discountValueInput.removeAttribute('readonly');
                maxDiscountGroup.style.display = 'none';
            }
        });

        // Validate end date is after start date
        document.getElementById('start-date').addEventListener('change', function() {
            const startDate = new Date(this.value);
            const endDateInput = document.getElementById('end-date');
            const endDate = new Date(endDateInput.value);

            if (endDateInput.value && endDate < startDate) {
                alert('End date must be after start date!');
                endDateInput.value = '';
            }
        });

        document.getElementById('end-date').addEventListener('change', function() {
            const startDateInput = document.getElementById('start-date');
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(this.value);

            if (startDateInput.value && endDate < startDate) {
                alert('End date must be after start date!');
                this.value = '';
            }
        });

        // Validate discount value based on type
        document.getElementById('discount-value').addEventListener('input', function() {
            const type = document.getElementById('type').value;
            const value = parseFloat(this.value);

            if (type === 'percent' && (value < 0 || value > 100)) {
                this.classList.add('border-red-500');
            } else {
                this.classList.remove('border-red-500');
            }
        });

        // Trigger type change on page load if type is already selected
        if (document.getElementById('type').value) {
            document.getElementById('type').dispatchEvent(new Event('change'));
        }

        // Scroll to error field if exists
        <?php if ($errorField): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const errorField = document.getElementById('<?php echo $errorField; ?>');
                if (errorField) {
                    errorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    errorField.focus();
                }
            });
        <?php endif; ?>
    </script>
