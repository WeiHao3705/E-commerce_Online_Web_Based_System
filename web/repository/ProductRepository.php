<?php

require_once __DIR__ . '/../DTO/ProductDTO.php';
require_once __DIR__ . '/../DTO/ProductVariantDTO.php';

class ProductRepository {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getProductById($product_id) {
        $sql = "
            SELECT 
                p.product_id,
                p.product_name,
                p.category,
                p.description,
                pr.original_price
            FROM product p
            LEFT JOIN product_price pr ON p.product_id = pr.product_id
            WHERE p.product_id = :id
            LIMIT 1
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $product_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? new ProductDTO($data) : null;
    }
    
    public function getMainImage($product_id) {
        $sql = "
            SELECT image_path, variant_id
            FROM product_image
            WHERE product_id = :id
            ORDER BY CASE WHEN variant_id = 1 THEN 0 ELSE 1 END
            LIMIT 1
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $product_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getVariantsByProductId($product_id) {
        $sql = "
            SELECT 
                pv.variant_id,
                pv.product_id,
                pv.color,
                (
                    SELECT pi.image_path 
                    FROM product_image pi 
                    WHERE pi.variant_id = pv.variant_id
                    ORDER BY pi.id ASC
                    LIMIT 1
                ) AS image_path
            FROM product_variant pv
            WHERE pv.product_id = :id
            ORDER BY pv.variant_id
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $product_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $variants = [];
        foreach ($rows as $row) {
            if (isset($row['image_path'])) {
                $row['image_path'] = trim($row['image_path']);
            }
            $variants[] = new ProductVariantDTO($row);
        }
        
        return $variants;
    }
    
    public function getSizesByProductId($product_id) {
        $sql = "
            SELECT 
                i.variant_id,
                i.size,
                i.stock_quantity
            FROM inventory i
            WHERE i.variant_id IN (
                SELECT variant_id FROM product_variant WHERE product_id = :id
            )
            ORDER BY i.variant_id, i.size
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $product_id]);
        $sizesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Build sizes map: variant_id => array of sizes
        $variantSizes = [];
        foreach ($sizesData as $s) {
            $vid = (int)$s['variant_id'];
            if (!isset($variantSizes[$vid])) {
                $variantSizes[$vid] = [];
            }
            $variantSizes[$vid][] = $s['size'];
        }
        
        return $variantSizes;
    }
    
    /**
     * Get all products with basic info and first image
     * @return array Array of products with category, price, and image
     */
    public function getAllProducts() {
        $sql = "
            SELECT 
                p.product_id,
                p.product_name,
                p.category,
                p.description,
                pr.original_price,
                pr.selling_price,
                COALESCE((
                    SELECT pi.image_path 
                    FROM product_image pi 
                    WHERE pi.product_id = p.product_id
                    LIMIT 1
                ), '') AS image_path,
                COALESCE((
                    SELECT COUNT(*) FROM product_variant pv 
                    WHERE pv.product_id = p.product_id
                ), 0) AS variant_count
            FROM product p
            LEFT JOIN product_price pr ON p.product_id = pr.product_id
            ORDER BY p.category, p.product_name
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $products;
    }
}
