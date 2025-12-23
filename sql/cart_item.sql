CREATE TABLE IF NOT EXISTS cart_item (
    cart_item_id INT(20) AUTO_INCREMENT PRIMARY KEY,
    cart_id INT(20) NOT NULL,
    product_id INT(20) NOT NULL,
    variant_id INT(20) DEFAULT NULL,
    size VARCHAR(20) DEFAULT NULL,
    quantity INT NOT NULL CHECK(quantity > 0),

    FOREIGN KEY (cart_id) REFERENCES shopping_cart(cart_id),
    FOREIGN KEY (product_id) REFERENCES product(product_id),
    FOREIGN KEY (variant_id) REFERENCES product_variant(variant_id)
);