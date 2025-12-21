-- Migration: Add variant_id support to wishlist table
-- This allows wishlist to store specific product variants (colors) that are out of stock

-- Step 1: Add variant_id column
ALTER TABLE wishlist ADD COLUMN variant_id INT NULL AFTER product_id;

-- Step 2: Add foreign key constraint for variant_id
ALTER TABLE wishlist ADD FOREIGN KEY (variant_id) REFERENCES product_variant(variant_id) ON DELETE CASCADE;

-- Step 3: Drop old unique constraint (if exists)
-- Note: Run this only if the index exists, otherwise it will error
-- ALTER TABLE wishlist DROP INDEX unique_user_product;

-- Step 4: Add new unique constraint that includes variant_id
ALTER TABLE wishlist ADD UNIQUE KEY unique_user_product_variant (user_id, product_id, variant_id);

-- Step 5: Add index for variant_id
ALTER TABLE wishlist ADD INDEX idx_variant_id (variant_id);

