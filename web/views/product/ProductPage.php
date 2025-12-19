<?php
session_start();

// Include DB connection
require __DIR__ . '/../../database/connection.php';
$db = new Database();
$conn = $db->getConnection();

// Page title for header
$pageTitle = "Products";

// Include layout
require __DIR__ . '/../../general/_header.php';
require __DIR__ . '/../../general/_navbar.php';

$assetPrefix = '../../';

// ------------------- Fetch product data -------------------

$sql = "
    SELECT 
        p.product_id, 
        p.product_name, 
        p.category, 
        p.description,
        pi.image_path,
        pr.original_price,
        GROUP_CONCAT(DISTINCT pv.color SEPARATOR ', ') AS colors
    FROM product p
    LEFT JOIN product_image pi ON p.product_id = pi.product_id
    LEFT JOIN product_price pr ON p.product_id = pr.product_id
    LEFT JOIN product_variant pv ON p.product_id = pv.product_id
    GROUP BY p.product_id
    ORDER BY p.category, p.product_name
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by category
$grouped = [];
foreach ($rows as $product) {
    $grouped[$product['category']][] = $product;
}
?>

<link rel="stylesheet" href="<?= $assetPrefix ?>css/ProductPage.css?v=<?= filemtime(__DIR__ . '/../../css/ProductPage.css'); ?>">

<div class="product-page">
    <div class="product-header">
        <h2>Products</h2>
        <p>Explore categories and find your next pick.</p>
    </div>

    <?php foreach ($grouped as $category => $products): ?>
        <section class="category-section">
            <div class="category-heading">
                <h3><?= htmlspecialchars($category) ?></h3>
                <span class="category-count"><?= count($products) ?> item(s)</span>
            </div>

            <div class="product-grid">
                <?php foreach ($products as $row): ?>
                    <a class="product-card" href="ProductDetails.php?id=<?= $row['product_id'] ?>">
                        <div class="product-media">
                            <?php if ($row['image_path']): ?>
                                <img src="/<?= htmlspecialchars($row['image_path']) ?>" alt="<?= htmlspecialchars($row['product_name']) ?>">
                            <?php endif; ?>
                        </div>

                        <div class="product-body">
                            <h4 class="product-title"><?= htmlspecialchars($row['product_name']) ?></h4>
                            <div class="product-price">
                                <?= $row['original_price'] ? "RM " . $row['original_price'] : "Price unavailable" ?>
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
</div>

<?php require __DIR__ . '/../../general/_footer.php'; ?>
