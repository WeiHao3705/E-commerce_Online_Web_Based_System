CREATE TABLE product_price (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    cost DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2) NOT NULL,
    selling_price DECIMAL(10,2) NOT NULL,

    -- 1-to-1 relationship (each product has only one price)
    CONSTRAINT fk_price_product
        FOREIGN KEY (product_id)
        REFERENCES product(product_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Enforce 1 product = 1 price row
    UNIQUE (product_id)
);
