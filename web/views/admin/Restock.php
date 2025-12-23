<?php
session_start();

// Only admins can access
if (!isset($_SESSION['user'])) {
    header('Location: ../../security/login.php');
    exit;
}
if ($_SESSION['user']->role !== 'admin') {
    $_SESSION['error_message'] = 'Access denied. Admin privileges required.';
    header('Location: ../../index.php');
    exit;
}

require_once __DIR__ . '/../../database/connection.php';
require_once __DIR__ . '/../../service/InventoryService.php';

$db = new Database();
$conn = $db->getConnection();
$service = new InventoryService($conn);

$assetPrefix = '../../';
$pageTitle = 'Restock Inventory';
$success = '';
$error = '';

// Fetch data up front so POST handlers have product metadata (has_size)
$data = $service->getRestockData();
$products = $data['products'];
$variantsMap = $data['variantsMap'];
$sizesByProduct = $data['sizesByProduct'];
$sizesByVariant = $data['sizesByVariant'];
$hasSizeMap = [];
foreach ($products as $p) {
    $hasSizeMap[(int)$p['product_id']] = isset($p['has_size']) ? (int)$p['has_size'] : 0;
}

// Prepare JSON for client-side
$variantsJson = htmlspecialchars(json_encode($variantsMap), ENT_QUOTES, 'UTF-8');
$sizesProductJson = htmlspecialchars(json_encode($sizesByProduct), ENT_QUOTES, 'UTF-8');
$sizesVariantJson = htmlspecialchars(json_encode($sizesByVariant), ENT_QUOTES, 'UTF-8');
$hasSizeJson = htmlspecialchars(json_encode($hasSizeMap), ENT_QUOTES, 'UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
        $variantId = isset($_POST['variant_id']) && $_POST['variant_id'] !== '' ? (int)$_POST['variant_id'] : null;
        $sizeOption = $_POST['size_option'] ?? 'existing';
        $size = $sizeOption === 'new' ? trim($_POST['size_new'] ?? '') : trim($_POST['size'] ?? '');
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
        // Determine if this product requires size
        $requiresSize = isset($hasSizeMap[$productId]) ? ((int)$hasSizeMap[$productId] === 1) : false;

        if ($requiresSize && $size === '') {
            throw new Exception('Size is required for this product.');
        }

        $service->restock($productId, $variantId, $size, $quantity, $requiresSize);
        $success = 'Stock updated successfully.';
        
        // Check if notifications were sent
        if (isset($_SESSION['restock_notification'])) {
            $notif = $_SESSION['restock_notification'];
            unset($_SESSION['restock_notification']);
            
            // Show notification status if there are wishlist members
            if (isset($notif['total']) && $notif['total'] > 0) {
                // Check if product, variant, or specific size was out of stock
                $wasOutOfStock = (
                    (isset($notif['was_out_of_stock']) && $notif['was_out_of_stock']) || 
                    (isset($notif['variant_was_out_of_stock']) && $notif['variant_was_out_of_stock']) ||
                    (isset($notif['size_was_out_of_stock']) && $notif['size_was_out_of_stock'])
                );
                
                // Only show notification message when item was actually out of stock and got restocked
                if ($wasOutOfStock && $notif['success'] && $notif['notified'] > 0) {
                    $success .= " Notified {$notif['notified']} wishlist member(s) that this item is back in stock.";
                }
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$data = $service->getRestockData();
$products = $data['products'];
$variantsMap = $data['variantsMap'];
$sizesByProduct = $data['sizesByProduct'];
$sizesByVariant = $data['sizesByVariant'];
$hasSizeMap = [];
foreach ($products as $p) {
    $hasSizeMap[(int)$p['product_id']] = isset($p['has_size']) ? (int)$p['has_size'] : 0;
}

// Prepare JSON for client-side
$variantsJson = htmlspecialchars(json_encode($variantsMap), ENT_QUOTES, 'UTF-8');
$sizesProductJson = htmlspecialchars(json_encode($sizesByProduct), ENT_QUOTES, 'UTF-8');
$sizesVariantJson = htmlspecialchars(json_encode($sizesByVariant), ENT_QUOTES, 'UTF-8');
$hasSizeJson = htmlspecialchars(json_encode($hasSizeMap), ENT_QUOTES, 'UTF-8');

require __DIR__ . '/../../general/_header.php';
?>
<link rel="stylesheet" href="<?= $assetPrefix ?>css/Restock.css?v=<?= filemtime(__DIR__ . '/../../css/Restock.css'); ?>">
<div class="page-container">
    <div class="page-header">
        <a class="back-button" href="AdminProduct.php">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Products</span>
        </a>
        <h1 class="page-title">Restock Inventory</h1>
    </div>

    <?php if ($success): ?><div class="message message-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message message-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="content-card">
        <form method="POST" id="restockForm" class="restock-form"
              data-variants='<?= $variantsJson ?>'
              data-sizes-product='<?= $sizesProductJson ?>'
              data-sizes-variant='<?= $sizesVariantJson ?>'
              data-has-size='<?= $hasSizeJson ?>'>
            <div class="form-grid">
                <div class="form-group">
                    <label for="productSelect">Product</label>
                    <select id="productSelect" name="product_id" required>
                        <option value="">-- Select Product --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= (int)$p['product_id'] ?>"><?= htmlspecialchars($p['product_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="variantSelect">Variant (optional)</label>
                    <select id="variantSelect" name="variant_id" disabled>
                        <option value="">-- No variant --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="sizeSelect">Size</label>
                    <select id="sizeSelect" name="size">
                        <option value="">-- Select Size --</option>
                    </select>
                    <div class="inline">
                        <label class="checkbox">
                            <input type="checkbox" id="newSizeToggle"> Add new size
                        </label>
                        <input type="hidden" name="size_option" id="sizeOption" value="existing">
                    </div>
                    <input type="text" id="sizeNew" name="size_new" placeholder="e.g., UK-10, L" style="display:none;" />
                </div>

                <div class="form-group">
                    <label for="quantityInput">Quantity</label>
                    <input type="number" id="quantityInput" name="quantity" min="1" value="1" required>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Stock</button>
            </div>
        </form>
    </div>
</div>
<script src="<?= $assetPrefix ?>js/restock.js?v=<?= filemtime(__DIR__ . '/../../js/restock.js'); ?>"></script>

