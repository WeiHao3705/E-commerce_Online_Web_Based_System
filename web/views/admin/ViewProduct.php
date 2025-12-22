<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only admins can access
if (!isset($_SESSION['user']) || $_SESSION['user']->role !== 'admin') {
    header('Location: ../../security/login.php');
    exit;
}

require_once __DIR__ . '/../../../helpers.php';
require_once __DIR__ . '/../../database/connection.php';
require_once __DIR__ . '/../../service/ProductService.php';

// Initialize
$db = new Database();
$conn = $db->getConnection();
$productService = new ProductService($conn);

// Get product ID from query string
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    die('Invalid product ID');
}

// Fetch product details
$data = $productService->getProductDetailsForAdmin($product_id);

if (!$data || !$data['product']) {
    die('Product not found');
}

// Extract data
$product = $data['product'];
$pricing = $data['pricing'];
$variants = $data['variants'];
$productImages = $data['productImages'];
$inventory = $data['inventory'];
$variantSizes = $data['variantSizes'];
$totalStock = $data['total_stock'];
$variantStock = $data['variant_stock'];

// Get base paths
$currentFileDir = __DIR__;
$webRootDir = dirname(dirname($currentFileDir));
$docRoot = $_SERVER['DOCUMENT_ROOT'];
$relativePath = str_replace($docRoot, '', $webRootDir);
$webBasePath = str_replace('\\', '/', $relativePath) . '/';
$cssBasePath = $webBasePath . 'css/';
$jsBasePath = $webBasePath . 'js/';

$pageTitle = html_escape($product->product_name) . ' - View Product';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= $cssBasePath ?>ViewProduct.css?v=<?= filemtime(__DIR__ . '/../../css/ViewProduct.css'); ?>">
</head>
<body>
    <div class="view-product-container">
        <!-- Header Section -->
        <div class="product-header">
            <div class="header-left">
                <a href="AdminProduct.php" class="back-btn">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Back to Products
                </a>
                <h1 class="product-title"><?= html_escape($product->product_name) ?></h1>
                <span class="product-category"><?= html_escape($product->category) ?></span>
            </div>
            <div class="header-right">
                <span class="stock-badge <?= $totalStock > 20 ? 'stock-high' : ($totalStock > 0 ? 'stock-low' : 'stock-out') ?>">
                    <?= $totalStock > 0 ? 'In Stock: ' . $totalStock : 'Out of Stock' ?>
                </span>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Left Column: Images & Pricing -->
            <div class="left-column">
                <!-- Product Images Section -->
                <div class="section-card images-section">
                    <h2 class="section-title">
                        <span class="material-symbols-outlined">image</span>
                        Product Images
                    </h2>
                    <?php if (!empty($productImages)): ?>
                        <div class="images-grid">
                            <?php foreach ($productImages as $img): ?>
                                <div class="image-item">
                                    <img src="/<?= html_escape($img['image_path']) ?>" alt="Product Image">
                                    <span class="image-type"><?= ucfirst($img['type']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No product-level images</p>
                    <?php endif; ?>
                </div>

                <!-- Pricing Section -->
                <div class="section-card pricing-section">
                    <h2 class="section-title">
                        <span class="material-symbols-outlined">payments</span>
                        Pricing Information
                    </h2>
                    <div class="pricing-grid">
                        <div class="price-item">
                            <label>Cost</label>
                            <span class="price">RM <?= number_format($pricing['cost'] ?? 0, 2) ?></span>
                        </div>
                        <div class="price-item">
                            <label>Original Price</label>
                            <span class="price">RM <?= number_format($pricing['original_price'] ?? 0, 2) ?></span>
                        </div>
                        <div class="price-item">
                            <label>Selling Price</label>
                            <span class="price highlight">RM <?= number_format($pricing['selling_price'] ?? 0, 2) ?></span>
                        </div>
                        <div class="price-item">
                            <label>Margin</label>
                            <span class="price">
                                <?php
                                    $margin = ($pricing['selling_price'] ?? 0) - ($pricing['cost'] ?? 0);
                                    $marginPercent = ($pricing['cost'] > 0) ? (($margin / $pricing['cost']) * 100) : 0;
                                    echo 'RM ' . number_format($margin, 2) . ' (' . number_format($marginPercent, 1) . '%)';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <?php if (!empty($product->description)): ?>
                <div class="section-card description-section">
                    <h2 class="section-title">
                        <span class="material-symbols-outlined">description</span>
                        Description
                    </h2>
                    <p class="description-text"><?= nl2br(html_escape($product->description)) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Variants & Inventory -->
            <div class="right-column">
                <!-- Variants Section -->
                <div class="section-card variants-section">
                    <h2 class="section-title">
                        <span class="material-symbols-outlined">palette</span>
                        Product Variants
                        <span class="count-badge"><?= count($variants) ?></span>
                    </h2>

                    <?php if (!empty($variants)): ?>
                        <?php foreach ($variants as $vData): ?>
                            <?php 
                                $variant = $vData['variant'];
                                $images = $vData['images'];
                                $stock = $variantStock[(int)$variant->variant_id] ?? 0;
                            ?>
                            <div class="variant-card">
                                <div class="variant-header">
                                    <div class="variant-info">
                                        <h3 class="variant-color"><?= html_escape($variant->color) ?></h3>
                                        <span class="variant-id">ID: <?= $variant->variant_id ?></span>
                                    </div>
                                    <span class="variant-stock <?= $stock > 0 ? 'in-stock' : 'out-stock' ?>">
                                        Stock: <?= $stock ?>
                                    </span>
                                </div>

                                <?php if (!empty($images)): ?>
                                    <div class="variant-images">
                                        <?php foreach ($images as $img): ?>
                                            <div class="variant-image-item">
                                                <img src="/<?= html_escape($img['image_path']) ?>" alt="<?= html_escape($variant->color) ?>">
                                                <?php if ($img['type'] === 'main'): ?>
                                                    <span class="main-badge">Main</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (isset($variantSizes[$variant->variant_id])): ?>
                                    <div class="variant-sizes">
                                        <strong>Available Sizes:</strong>
                                        <?php foreach ($variantSizes[$variant->variant_id] as $size): ?>
                                            <span class="size-badge"><?= html_escape($size) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data">This product has no variants</p>
                    <?php endif; ?>
                </div>

                <!-- Inventory Section -->
                <div class="section-card inventory-section">
                    <h2 class="section-title">
                        <span class="material-symbols-outlined">inventory</span>
                        Inventory Details
                    </h2>

                    <?php if (!empty($inventory)): ?>
                        <div class="inventory-table-wrapper">
                            <table class="inventory-table">
                                <thead>
                                    <tr>
                                        <th>Variant/Color</th>
                                        <th>Size</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventory as $inv): ?>
                                        <tr>
                                            <td><?= html_escape($inv['color'] ?? 'No variant') ?></td>
                                            <td><span class="size-badge"><?= html_escape($inv['size'] ?? 'N/A') ?></span></td>
                                            <td><strong><?= (int)$inv['stock_quantity'] ?></strong></td>
                                            <td>
                                                <?php 
                                                    $qty = (int)$inv['stock_quantity'];
                                                    $statusClass = $qty > 20 ? 'status-high' : ($qty > 0 ? 'status-low' : 'status-out');
                                                    $statusText = $qty > 20 ? 'Healthy' : ($qty > 0 ? 'Low' : 'Empty');
                                                ?>
                                                <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="no-data">No inventory records found</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= $jsBasePath ?>viewProduct.js?v=<?= filemtime(__DIR__ . '/../../js/viewProduct.js'); ?>"></script>
</body>
</html>
