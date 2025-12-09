CREATE TABLE product_size (
    size_id INT AUTO_INCREMENT PRIMARY KEY,
    size_name VARCHAR(50) NOT NULL UNIQUE,
    size_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert common clothing sizes
INSERT INTO product_size (size_name, size_order) VALUES
('XS', 1),
('S', 2),
('M', 3),
('L', 4),
('XL', 5),
('XXL', 6),
('XXXL', 7);

-- Insert common shoe sizes (can be extended as needed)
INSERT INTO product_size (size_name, size_order) VALUES
('6', 10),
('6.5', 11),
('7', 12),
('7.5', 13),
('8', 14),
('8.5', 15),
('9', 16),
('9.5', 17),
('10', 18),
('10.5', 19),
('11', 20),
('11.5', 21),
('12', 22);

-- Insert numeric sizes (for pants, etc.)
INSERT INTO product_size (size_name, size_order) VALUES
('28', 30),
('30', 31),
('32', 32),
('34', 33),
('36', 34),
('38', 35),
('40', 36);

-- Insert one-size option
INSERT INTO product_size (size_name, size_order) VALUES
('One Size', 100);
