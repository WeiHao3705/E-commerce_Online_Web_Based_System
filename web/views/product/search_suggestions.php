<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../database/connection.php';

try {
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    // Allow suggestions from a single character
    if (strlen($query) < 1) {
        echo json_encode(['suggestions' => []]);
        exit;
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Search for products, colors, and categories
    $searchPattern = '%' . $query . '%';
    
    $sql = "
        SELECT DISTINCT 
            p.product_id,
            p.product_name,
            p.category,
            pv.color,
            pi.image_path
        FROM product p
        LEFT JOIN product_variant pv ON p.product_id = pv.product_id
        LEFT JOIN product_image pi ON p.product_id = pi.product_id AND pi.type = 'main'
        WHERE p.product_name LIKE :pattern
           OR p.category LIKE :pattern
           OR pv.color LIKE :pattern
        GROUP BY p.product_id, pv.color
        ORDER BY 
            CASE 
                WHEN p.product_name LIKE :exact THEN 1
                WHEN p.category LIKE :exact THEN 2
                WHEN pv.color LIKE :exact THEN 3
                ELSE 4
            END,
            p.product_name
        LIMIT 10
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':pattern' => $searchPattern,
        ':exact' => $query . '%'
    ]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $suggestions = [];
    foreach ($results as $row) {
        $suggestions[] = [
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'category' => $row['category'],
            'color' => $row['color'],
            'image_path' => $row['image_path'] ?: ''
        ];
    }
    
    echo json_encode(['suggestions' => $suggestions]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Search failed', 'suggestions' => []]);
}
