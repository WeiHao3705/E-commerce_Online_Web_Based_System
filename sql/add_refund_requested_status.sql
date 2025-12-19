-- Add 'refund_requested' status to orders table
-- Run this SQL query in your database to add the new status

ALTER TABLE orders 
MODIFY COLUMN order_status ENUM('pending', 'paid', 'processing', 'shipped', 'delivered', 'refund_requested', 'canceled', 'refunded') DEFAULT 'pending';
