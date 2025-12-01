CREATE TABLE product_image (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_id INT NULL,   
    image_path VARCHAR(255) NOT NULL,
    type ENUM('main', 'gallery') DEFAULT 'gallery',

    FOREIGN KEY (product_id) REFERENCES product(product_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (variant_id) REFERENCES product_variant(variant_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);
