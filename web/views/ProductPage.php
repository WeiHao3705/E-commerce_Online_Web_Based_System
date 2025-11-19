<?php
// Fetch product & image data
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
";


$stmt = $conn->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2 style="margin-top: 20px;">Products</h2>

<?php
if (!$rows) {
    echo "<p>No products found.</p>";
    return;
}

foreach ($rows as $row): ?>
    <div style='border:1px solid #ccc; padding:10px; margin:10px; width:300px;'>
        
        <strong>ID:</strong> <?= $row['product_id'] ?><br>
        <strong>Name:</strong> <?= $row['product_name'] ?><br>
        <strong>Category:</strong> <?= $row['category'] ?><br>
        <strong>Description:</strong> <?= $row['description'] ?><br>

        <strong>Original Price:</strong> 
        <?= $row['original_price'] ? "RM " . $row['original_price'] : "N/A" ?><br>

        <strong>Colors:</strong> 
        <?= $row['colors'] ? $row['colors'] : "No variants" ?><br><br>

        <?php if ($row['image_path']): ?>
            <img src="/<?= $row['image_path'] ?>" width="150">
        <?php else: ?>
            (No image)
        <?php endif; ?>

    </div>
<?php endforeach; ?>

