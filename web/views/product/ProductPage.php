<?php
// If this view is accessed directly (not through controller), initialize via controller
if (!isset($allCategories)) {
    session_start();
    require __DIR__ . '/../../database/connection.php';
    require __DIR__ . '/../../service/ProductService.php';
    require __DIR__ . '/../../controller/ProductController.php';
    
    // Initialize and handle the request through controller
    $controller = new ProductController();
    $controller->handleRequest();
    exit;
}

// This view file is responsible for displaying filtered products
// All business logic is handled by ProductController and ProductService
// Data is passed from controller via extract($data)

$assetPrefix = '../../';
?>

<link rel="stylesheet" href="<?= $assetPrefix ?>css/ProductPage.css?v=<?= filemtime(__DIR__ . '/../../css/ProductPage.css'); ?>">
<link rel="stylesheet" href="<?= $assetPrefix ?>css/reviews.css?v=<?= filemtime(__DIR__ . '/../../css/reviews.css'); ?>">

<div class="product-page">
    <div class="product-header">
        <h2>Products</h2>
        <p>Explore categories and find your next pick.</p>
    </div>

    <div class="product-container">
        <!-- Filters Sidebar -->
        <aside class="filters-sidebar">
            <div class="filters-header">
                <h3>Filters</h3>
                <a href="ProductPage.php" class="clear-filters">Clear All</a>
            </div>

            <form method="GET" id="filterForm" class="filters-form">
                <!-- Category Filter -->
                <div class="filter-group">
                    <h4 class="filter-title">Category</h4>
                    <div class="filter-options">
                        <?php foreach ($allCategories as $cat): ?>
                            <label class="filter-checkbox">
                                <input type="checkbox" name="category" value="<?= htmlspecialchars($cat) ?>" class="filter-input"
                                    <?= ($selectedCategory === $cat) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($cat) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Price Range Filter -->
                <div class="filter-group">
                    <h4 class="filter-title">Price Range</h4>
                    <div class="price-filter">
                        <div class="price-input">
                            <label>Min</label>
                            <input type="number" name="min_price" placeholder="0" class="filter-input"
                                value="<?= htmlspecialchars($minPrice ?? '') ?>" step="1">
                        </div>
                        <div class="price-input">
                            <label>Max</label>
                            <input type="number" name="max_price" placeholder="9999" class="filter-input"
                                value="<?= htmlspecialchars($maxPrice ?? '') ?>" step="1">
                        </div>
                    </div>
                </div>

                <!-- Color Filter -->
                <div class="filter-group">
                    <h4 class="filter-title">Color</h4>
                    <div class="filter-options">
                        <?php foreach ($allColors as $color): ?>
                            <label class="filter-checkbox">
                                <input type="checkbox" name="colors[]" value="<?= htmlspecialchars($color) ?>" class="filter-input"
                                    <?= in_array($color, $selectedColors) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($color) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>
        </aside>

        <!-- Products Section -->
        <div class="products-section">
            <?php if (empty($grouped)): ?>
                <div style="text-align: center; padding: 40px 20px; color: #6b7280;">
                    <p style="font-size: 16px; margin: 0;">No products found matching your filters.</p>
                </div>
            <?php else: ?>
                <?php foreach ($grouped as $category => $categoryProducts): ?>
                    <section class="category-section">
                        <div class="category-heading">
                            <h3><?= htmlspecialchars($category) ?></h3>
                            <span class="category-count"><?= count($categoryProducts) ?> item(s)</span>
                        </div>

                        <div class="product-grid">
                            <?php foreach ($categoryProducts as $row): ?>
                                <a class="product-card" href="ProductDetails.php?id=<?= $row['product_id'] ?>">
                                    <div class="product-media">
                                        <?php if ($row['image_path']): ?>
                                            <img src="/<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['product_name']) ?>">
                                        <?php endif; ?>
                                    </div>

                                    <div class="product-body">
                                        <h4 class="product-title"><?= htmlspecialchars($row['product_name']) ?></h4>
                                        <div class="product-price">
                                            <?= $row['original_price'] ? "RM " . number_format($row['original_price'], 2) : "Price unavailable" ?>
                                        </div>
                                        <?php 
                                            $avgRating = isset($row['average_rating']) ? (float)$row['average_rating'] : 0;
                                            $reviewCount = isset($row['review_count']) ? (int)$row['review_count'] : 0;
                                        ?>
                                        <div class="product-rating">
                                            <div class="star-rating" data-rating="<?= $avgRating ?>"></div>
                                            <span class="rating-text">
                                                <?php if ($reviewCount > 0): ?>
                                                    <?= number_format($avgRating, 1) ?> (<?= $reviewCount ?> review<?= $reviewCount !== 1 ? 's' : '' ?>)
                                                <?php else: ?>
                                                    No reviews yet
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="product-meta">
                                            <strong>Colors:</strong> <?= $row['colors'] ? htmlspecialchars($row['colors']) : "No variants" ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="<?= $assetPrefix ?>js/productPage.js"></script>
<script src="<?= $assetPrefix ?>js/reviews.js"></script>
