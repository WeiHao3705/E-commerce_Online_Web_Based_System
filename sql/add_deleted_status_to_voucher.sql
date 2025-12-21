-- Add 'deleted' status to voucher table
ALTER TABLE voucher MODIFY COLUMN status ENUM('active','inactive','expired','deleted') DEFAULT 'active';

