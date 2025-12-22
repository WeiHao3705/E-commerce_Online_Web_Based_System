CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Allow stock for products WITHOUT variants
    product_id INT NULL,

    -- Allow stock for products WITH variants (color)
    variant_id INT NULL,

    size VARCHAR(20) NOT NULL,

    stock_quantity INT NOT NULL DEFAULT 0,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
                 ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES product(product_id)
        ON DELETE CASCADE,

    FOREIGN KEY (variant_id) REFERENCES product_variant(variant_id)
        ON DELETE CASCADE
);


