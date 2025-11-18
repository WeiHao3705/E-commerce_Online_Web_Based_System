<?php
session_start();
require __DIR__ . '/../database/connection.php';  // go up one folder
$db = new Database();
$conn = $db->getConnection();

echo "<h2>Testing Product + Image Fetch</h2>";

// Fetch product & image data
$sql = "
    SELECT p.product_id, p.product_name, p.category, p.description,
           pi.image_path
    FROM product p
    LEFT JOIN product_image pi ON p.product_id = pi.product_id
    GROUP BY p.product_id
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "<p>No products found.</p>";
    exit;
}

foreach ($rows as $row) {
    echo "<div style='border:1px solid #ccc; margin:10px; padding:10px;'>";
    echo "<strong>ID:</strong> " . $row['product_id'] . "<br>";
    echo "<strong>Name:</strong> " . $row['product_name'] . "<br>";
    echo "<strong>Category:</strong> " . $row['category'] . "<br>";
    echo "<strong>Description:</strong> " . $row['description'] . "<br>";

    if ($row['image_path']) {
        echo "<img src='/" . $row['image_path'] . "' width='150'>";
    } else {
        echo "(No image)";
    }

    echo "</div>";
}
