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

<h2 style="margin-top: 20px;">Products</h2>

<?php foreach ($grouped as $category => $products): ?>
    <h3 style="margin-top: 30px;"><?= htmlspecialchars($category) ?></h3>

    <div style="display: flex; flex-wrap: wrap; gap: 20px;">
        <?php foreach ($products as $row): ?>
           <a href="ProductDetails.php?id=<?= $row['product_id'] ?>" 
   style="text-decoration:none; color:inherit;">
            <div style="
                border:1px solid #ccc; 
                padding:10px; 
                width:23%; 
                box-sizing:border-box;
            ">
                <strong><?= htmlspecialchars($row['product_name']) ?></strong><br>

                <strong>Price:</strong>
                <?= $row['original_price'] ? "RM " . $row['original_price'] : "N/A" ?><br>

                <strong>Colors:</strong>
                <?= $row['colors'] ? htmlspecialchars($row['colors']) : "No variants" ?><br><br>

                <?php if ($row['image_path']): ?>
                    <img src="/<?= htmlspecialchars($row['image_path']) ?>" width="100%" style="border-radius: 5px;">
                <?php else: ?>
                    (No image)
                <?php endif; ?>
                </a>
            </div>
            
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../../general/_footer.php'; ?>
