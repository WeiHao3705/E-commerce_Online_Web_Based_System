CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Product-level stock (for products without variants)
    product_id INT NULL,

    -- Variant-level stock (for products with color variants)
    variant_id INT NULL,

    -- Size-based stock (NULL = free size / no size)
    size VARCHAR(20) NULL,

    -- Current available stock
    stock_quantity INT NOT NULL DEFAULT 0,

    -- Last updated timestamp
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                 ON UPDATE CURRENT_TIMESTAMP,

    -- Foreign key constraints
    CONSTRAINT fk_inventory_product
        FOREIGN KEY (product_id)
        REFERENCES product(product_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_inventory_variant
        FOREIGN KEY (variant_id)
        REFERENCES product_variant(variant_id)
        ON DELETE CASCADE,

    -- Prevent duplicate stock rows
    UNIQUE KEY uniq_inventory (product_id, variant_id, size)
);



