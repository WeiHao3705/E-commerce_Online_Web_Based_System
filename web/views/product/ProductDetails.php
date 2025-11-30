<?php
session_start();

// DB connection
require __DIR__ . '/../../database/connection.php';
$db = new Database();
$conn = $db->getConnection();

// Validate product_id
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid product.");
}

$product_id = $_GET['id'];

// Include layout
$pageTitle = "Product Details";
require __DIR__ . '/../../general/_header.php';
require __DIR__ . '/../../general/_navbar.php';

// ------------------- FETCH PRODUCT INFO -------------------
$sql = "
    SELECT 
        p.product_id,
        p.product_name,
        p.category,
        p.description,
        pr.original_price,
        pi.image_path
    FROM product p
    LEFT JOIN product_price pr ON p.product_id = pr.product_id
    LEFT JOIN product_image pi ON p.product_id = pi.product_id
    WHERE p.product_id = :id
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<h2>Product not found.</h2>";
    require __DIR__ . '/../../general/_footer.php';
    exit;
}

// ------------------- FETCH VARIANTS -------------------
$sql2 = "
    SELECT size, color
    FROM product_variant
    WHERE product_id = :id
";

$stmt2 = $conn->prepare($sql2);
$stmt2->execute([':id' => $product_id]);
$variants = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>

<h2 style="margin-top: 20px;"><?= htmlspecialchars($product['product_name']) ?></h2>

<div style="display:flex; gap:40px; margin-top:20px;">

    <!-- Product Image -->
    <div style="width:35%;">
        <?php if ($product['image_path']): ?>
            <img src="/<?= htmlspecialchars($product['image_path']) ?>" width="100%" style="border-radius:5px;">
        <?php else: ?>
            <p>(No image available)</p>
        <?php endif; ?>
    </div>

    <!-- Product Details -->
    <div style="width:55%;">
        <p><strong>Price: </strong>RM <?= $product['original_price'] ?></p>
        <p><?= htmlspecialchars($product['description']) ?></p>

        <h3>Available Variants</h3>

        <?php if ($variants): ?>
            <ul>
                <?php foreach ($variants as $v): ?>
                    <li>
                        Size: <strong><?= htmlspecialchars($v['size']) ?></strong> 
                        &nbsp; | &nbsp; 
                        Color: <strong><?= htmlspecialchars($v['color'] ?? 'N/A') ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No variants available.</p>
        <?php endif; ?>
    </div>

</div>

<?php require __DIR__ . '/../../general/_footer.php'; ?>
