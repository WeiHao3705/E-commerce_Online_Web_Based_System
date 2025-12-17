-- Add cart items for user_id = 1
-- First, ensure a shopping cart exists for user 1
INSERT IGNORE INTO shopping_cart (user_id) VALUES (1);

-- Get the cart_id for user 1 and add items
-- Note: Adjust product_id values based on your actual products in the database

-- Add item 1 (quantity: 2)
INSERT INTO cart_item (cart_id, product_id, quantity)
SELECT cart_id, 1, 2
FROM shopping_cart
WHERE user_id = 1
LIMIT 1;

-- Add item 2 (quantity: 1)
INSERT INTO cart_item (cart_id, product_id, quantity)
SELECT cart_id, 2, 1
FROM shopping_cart
WHERE user_id = 1
LIMIT 1;

-- Add item 3 (quantity: 3)
INSERT INTO cart_item (cart_id, product_id, quantity)
SELECT cart_id, 3, 3
FROM shopping_cart
WHERE user_id = 1
LIMIT 1;

-- Add item 4 (quantity: 1)
INSERT INTO cart_item (cart_id, product_id, quantity)
SELECT cart_id, 4, 1
FROM shopping_cart
WHERE user_id = 1
LIMIT 1;
