-- ============================================
-- Migration Script: Convert product_variant.size to product_size reference
-- ============================================
-- This script helps migrate existing data from VARCHAR size to size_id reference
-- Run this ONLY if you have existing data in product_variant table

-- Step 1: Create temporary column for the old size data
ALTER TABLE product_variant ADD COLUMN size_old VARCHAR(50) NULL;

-- Step 2: Copy existing size data to the temporary column
UPDATE product_variant SET size_old = size WHERE size IS NOT NULL;

-- Step 3: Drop the old size column
ALTER TABLE product_variant DROP COLUMN size;

-- Step 4: Add the new size_id column with foreign key
ALTER TABLE product_variant 
    ADD COLUMN size_id INT NOT NULL,
    ADD CONSTRAINT fk_variant_size 
        FOREIGN KEY (size_id) 
        REFERENCES product_size(size_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE;

-- Step 5: Map old sizes to new size_id values
-- Update this mapping based on your existing size values
UPDATE product_variant pv
INNER JOIN product_size ps ON ps.size_name = pv.size_old
SET pv.size_id = ps.size_id
WHERE pv.size_old IS NOT NULL;

-- Step 6: Verify all variants have been mapped
-- This query should return 0 rows if migration is successful
SELECT variant_id, size_old 
FROM product_variant 
WHERE size_id IS NULL OR size_id = 0;

-- Step 7: Drop the temporary column once verified
-- ALTER TABLE product_variant DROP COLUMN size_old;

-- Note: If you have sizes that don't exist in product_size table,
-- you'll need to add them first before running Step 5:
-- INSERT INTO product_size (size_name, size_order) VALUES ('YourSize', 50);
