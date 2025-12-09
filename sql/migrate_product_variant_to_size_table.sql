-- ============================================
-- Migration Script: Convert product_variant.size to product_size reference
-- ============================================
-- This script helps migrate existing data from VARCHAR size to size_id reference
-- Run this ONLY if you have existing data in the OLD product_variant table schema
-- (where size is VARCHAR(50) and not yet converted to size_id)
-- 
-- IMPORTANT: Before running this script:
-- 1. Ensure product_size table exists and is populated
-- 2. Back up your database
-- 3. Verify your product_variant table has a 'size' column (VARCHAR)

-- Step 1: Verify the old schema exists (check if 'size' column exists)
-- If this query fails, your database is already using the new schema
-- SELECT size FROM product_variant LIMIT 1;

-- Step 2: Create temporary column for the old size data
ALTER TABLE product_variant ADD COLUMN size_old VARCHAR(50) NULL;

-- Step 3: Copy existing size data from 'size' column to temporary 'size_old' column
UPDATE product_variant SET size_old = size WHERE size IS NOT NULL;

-- Step 4: Drop the old 'size' VARCHAR column (data is safely stored in size_old)
ALTER TABLE product_variant DROP COLUMN size;

-- Step 5: Add the new 'size_id' column as nullable first
ALTER TABLE product_variant 
    ADD COLUMN size_id INT NULL;

-- Step 6: Map old sizes from 'size_old' to new 'size_id' values
-- Uses case-insensitive and whitespace-trimmed matching to handle variations
-- like 'small' vs 'Small' or ' M ' vs 'M'
-- NOTE: For large datasets (>100k rows), consider normalizing size_old values first
-- to improve performance, or create an explicit mapping table
UPDATE product_variant pv
INNER JOIN product_size ps ON TRIM(LOWER(ps.size_name)) = TRIM(LOWER(pv.size_old))
SET pv.size_id = ps.size_id
WHERE pv.size_old IS NOT NULL AND TRIM(pv.size_old) != '';

-- Step 7: Verify all variants have been mapped successfully
-- This query should return 0 rows if migration is successful
-- If it returns rows, those sizes need to be added to product_size first
SELECT 
    variant_id, 
    size_old,
    CASE 
        WHEN size_old IS NULL OR TRIM(size_old) = '' THEN 'EMPTY/NULL SIZE - Set a valid size_id manually'
        ELSE 'NOT MAPPED - Add this size to product_size table first'
    END AS error_message
FROM product_variant 
WHERE size_id IS NULL;

-- Step 8: Make size_id NOT NULL and add foreign key constraint
-- Only run this after Step 7 returns 0 rows
ALTER TABLE product_variant 
    MODIFY COLUMN size_id INT NOT NULL,
    ADD CONSTRAINT fk_variant_size 
        FOREIGN KEY (size_id) 
        REFERENCES product_size(size_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE;

-- Step 9: Drop the temporary 'size_old' column once migration is verified
-- Uncomment this line after verifying everything works correctly
-- ALTER TABLE product_variant DROP COLUMN size_old;

-- Note: If you have sizes that don't exist in product_size table,
-- you'll need to add them first before running Step 5:
-- INSERT INTO product_size (size_name, size_order) VALUES ('YourSize', 50);
