CREATE TABLE product_variant (
    variant_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size VARCHAR(50) NOT NULL,
    color VARCHAR(50) DEFAULT NULL,
    
    FOREIGN KEY (product_id) REFERENCES product(product_id)
);
