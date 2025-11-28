<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base path
$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_views = (strpos($current_dir, '/views') !== false);
$prefix = $is_in_views ? '../' : '';

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
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="<?php echo $prefix . 'views/VoucherRegisterForm.php' . ($returnTo ? '?return_to=' . urlencode($returnTo) : ''); ?>" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-text-light dark:text-text-dark bg-card-light dark:bg-card-dark border border-border-light dark:border-border-dark rounded-lg hover:bg-background-light dark:hover:bg-background-dark transition-colors">
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

            <header class="text-center mb-8">
                <h1 class="text-text-light dark:text-text-dark text-3xl md:text-4xl font-bold tracking-tight">Bulk Import Preview</h1>
                <p class="mt-2 text-subtle-light dark:text-subtle-dark">Review the vouchers below before importing them into the system.</p>
            </header>

            <!-- Summary -->
            <div class="bg-card-light dark:bg-card-dark rounded-xl border border-border-light dark:border-border-dark shadow-sm p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-primary"><?php echo count($vouchers); ?></p>
                        <p class="text-sm text-subtle-light dark:text-subtle-dark">Valid Vouchers</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-red-500"><?php echo count($errors); ?></p>
                        <p class="text-sm text-subtle-light dark:text-subtle-dark">Invalid Rows</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-text-light dark:text-text-dark"><?php echo count($vouchers) + count($errors); ?></p>
                        <p class="text-sm text-subtle-light dark:text-subtle-dark">Total Rows</p>
                    </div>
                </div>
            </div>

            <!-- Invalid Rows (if any) -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 shadow-sm p-6 mb-6">
                    <h2 class="text-lg font-semibold text-red-700 dark:text-red-300 mb-4">
                        <span class="material-symbols-outlined align-middle mr-2">error</span>
                        Invalid Rows (Will Not Be Imported)
                    </h2>
                    <div class="space-y-4">
                        <?php foreach ($errors as $error): ?>
                            <div class="bg-white dark:bg-card-dark rounded-lg border border-red-200 dark:border-red-800 p-4">
                                <p class="font-semibold text-red-700 dark:text-red-300 mb-2">Row <?php echo $error['row']; ?>: <?php echo htmlspecialchars($error['data']['code'] ?: 'No code'); ?></p>
                                <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400 space-y-1">
                                    <?php foreach ($error['errors'] as $err): ?>
                                        <li><?php echo htmlspecialchars($err); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Valid Vouchers Table -->
            <?php if (!empty($vouchers)): ?>
                <div class="bg-card-light dark:bg-card-dark rounded-xl border border-border-light dark:border-border-dark shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-border-light dark:border-border-dark">
                        <h2 class="text-lg font-semibold text-text-light dark:text-text-dark">
                            <span class="material-symbols-outlined align-middle mr-2">check_circle</span>
                            Valid Vouchers (Will Be Imported)
                        </h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-background-light dark:bg-background-dark">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-subtle-light dark:text-subtle-dark uppercase tracking-wider">Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-subtle-light dark:text-subtle-dark uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-subtle-light dark:text-subtle-dark uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-subtle-light dark:text-subtle-dark uppercase tracking-wider">Discount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-subtle-light dark:text-subtle-dark uppercase tracking-wider">Min Spend</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-subtle-light dark:text-subtle-dark uppercase tracking-wider">Max Discount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-subtle-light dark:text-subtle-dark uppercase tracking-wider">Start Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-subtle-light dark:text-subtle-dark uppercase tracking-wider">End Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-light dark:divide-border-dark">
                                <?php foreach ($vouchers as $voucher): ?>
                                    <tr class="hover:bg-background-light dark:hover:bg-background-dark">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-text-light dark:text-text-dark">
                                            <?php echo htmlspecialchars($voucher['code']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-text-light dark:text-text-dark">
                                            <?php echo htmlspecialchars($voucher['description'] ?: '-'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-text-light dark:text-text-dark">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                                <?php echo htmlspecialchars(ucfirst($voucher['type'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-text-light dark:text-text-dark">
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-text-light dark:text-text-dark">
                                            <?php echo !empty($voucher['min_spend']) ? '$' . htmlspecialchars($voucher['min_spend']) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-text-light dark:text-text-dark">
                                            <?php echo !empty($voucher['max_discount']) ? '$' . htmlspecialchars($voucher['max_discount']) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-text-light dark:text-text-dark">
                                            <?php echo htmlspecialchars($voucher['start_date']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-text-light dark:text-text-dark">
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
                    <div class="mt-6 bg-card-light dark:bg-card-dark rounded-xl border border-border-light dark:border-border-dark shadow-sm p-6">
                        <form action="<?php echo $prefix; ?>controller/VoucherController.php" method="POST">
                            <input type="hidden" name="action" value="executeBulkImport">
                            <?php if ($returnTo): ?>
                                <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($returnTo); ?>">
                            <?php endif; ?>
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-text-light dark:text-text-dark font-medium">Ready to import <?php echo count($vouchers); ?> voucher(s)?</p>
                                    <p class="text-sm text-subtle-light dark:text-subtle-dark mt-1">Click the button below to confirm and import all valid vouchers.</p>
                                </div>
                                <button type="submit" class="px-6 py-3 text-sm font-semibold text-white bg-primary rounded-lg hover:bg-primary/90 focus:outline-none focus:ring-4 focus:ring-primary/30 transition-colors">
                                    <span class="material-symbols-outlined align-middle mr-2">check</span>
                                    Confirm Import
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800 shadow-sm p-6 text-center">
                    <p class="text-yellow-700 dark:text-yellow-300">No valid vouchers to import. Please check your CSV file and try again.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Include Footer -->
    <?php include $prefix . 'general/_footer.php'; ?>
</body>
</html>

