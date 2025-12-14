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

<style>
    .product-page {
        max-width: 1200px;
        margin: 40px auto 60px;
        padding: 0 20px;
    }

    .product-header {
        text-align: center;
        margin-bottom: 20px;
    }

    .product-header h2 {
        margin: 0 0 6px;
        font-size: 28px;
        letter-spacing: 0.5px;
    }

    .product-header p {
        margin: 0;
        color: #5c6470;
        font-size: 14px;
    }

    .category-section {
        margin-top: 32px;
    }

    .category-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
        padding-bottom: 8px;
        border-bottom: 1px solid #e2e6ea;
    }

    .category-heading h3 {
        margin: 0;
        font-size: 20px;
        color: #1f2a37;
    }

    .category-count {
        font-size: 13px;
        color: #6b7280;
        background: #f3f4f6;
        padding: 4px 10px;
        border-radius: 999px;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 18px;
    }

    .product-card {
        display: flex;
        flex-direction: column;
        text-decoration: none;
        color: inherit;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        transition: transform 140ms ease, box-shadow 140ms ease;
    }

    .product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
        border-color: #d1d5db;
    }

    .product-media {
        width: 100%;
        aspect-ratio: 4 / 3;
        border-radius: 10px;
        background: linear-gradient(135deg, #eef2ff, #e0f2fe);
        overflow: hidden;
        margin-bottom: 12px;
    }

    .product-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .product-body {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .product-title {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        color: #111827;
    }

    .product-price {
        font-size: 15px;
        font-weight: 600;
        color: #f97316;
    }

    .product-meta {
        font-size: 13px;
        color: #4b5563;
        line-height: 1.5;
    }

    @media (max-width: 640px) {
        .product-page {
            padding: 0 14px;
        }

        .product-header h2 {
            font-size: 24px;
        }
    }
</style>

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
