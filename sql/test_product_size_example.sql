-- ============================================
-- Example Usage: Product Size Management
-- ============================================
-- This file shows how to use the new product_size structure

-- Example 1: View all available sizes
SELECT size_id, size_name, size_order 
FROM product_size 
ORDER BY size_order;

-- Example 2: Add a custom size
-- INSERT INTO product_size (size_name, size_order) 
-- VALUES ('3XL', 8);

-- Example 3: Create a product with variants using the new structure
-- Assuming product_id 1 exists

-- Step 1: Check which sizes you want to use
-- SELECT size_id, size_name FROM product_size WHERE size_name IN ('S', 'M', 'L', 'XL');

-- Step 2: Create variants for the product
-- Example: T-shirt in multiple sizes and colors
/*
INSERT INTO product_variant (product_id, size_id, color) VALUES
(1, 2, 'Red'),      -- Size S, Red
(1, 3, 'Red'),      -- Size M, Red  
(1, 4, 'Red'),      -- Size L, Red
(1, 5, 'Red'),      -- Size XL, Red
(1, 2, 'Blue'),     -- Size S, Blue
(1, 3, 'Blue'),     -- Size M, Blue
(1, 4, 'Blue'),     -- Size L, Blue
(1, 5, 'Blue');     -- Size XL, Blue
*/

-- Example 4: Query product variants with size names
/*
SELECT 
    p.product_name,
    pv.variant_id,
    ps.size_name,
    pv.color,
    i.quantity AS stock
FROM product_variant pv
INNER JOIN product p ON pv.product_id = p.product_id
INNER JOIN product_size ps ON pv.size_id = ps.size_id
LEFT JOIN inventory i ON pv.variant_id = i.variant_id
WHERE p.product_id = 1
ORDER BY pv.color, ps.size_order;
*/

-- Example 5: Find all variants for a specific size
/*
SELECT 
    p.product_name,
    ps.size_name,
    pv.color,
    pr.original_price
FROM product_variant pv
INNER JOIN product p ON pv.product_id = p.product_id
INNER JOIN product_size ps ON pv.size_id = ps.size_id
LEFT JOIN product_price pr ON p.product_id = pr.product_id
WHERE ps.size_name = 'M';
*/

-- Example 6: Check which products are available in a specific size
/*
SELECT DISTINCT
    p.product_id,
    p.product_name,
    ps.size_name
FROM product p
INNER JOIN product_variant pv ON p.product_id = pv.product_id
INNER JOIN product_size ps ON pv.size_id = ps.size_id
WHERE ps.size_name = 'L';
*/

-- Example 7: Get size distribution for a category
/*
SELECT 
    ps.size_name,
    COUNT(DISTINCT pv.product_id) AS product_count,
    COUNT(pv.variant_id) AS variant_count
FROM product_variant pv
INNER JOIN product_size ps ON pv.size_id = ps.size_id
INNER JOIN product p ON pv.product_id = p.product_id
WHERE p.category = 'Clothing'
GROUP BY ps.size_name, ps.size_order
ORDER BY ps.size_order;
*/
