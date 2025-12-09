CREATE TABLE product_variant (
    variant_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size_id INT NOT NULL,
    color VARCHAR(50) DEFAULT NULL,
    
    FOREIGN KEY (product_id) REFERENCES product(product_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    
    FOREIGN KEY (size_id) REFERENCES product_size(size_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);
