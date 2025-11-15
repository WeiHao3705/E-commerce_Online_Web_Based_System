CREATE TABLE cart_item (
    cart_item_id INT(20) AUTO_INCREMENT PRIMARY KEY,
    cart_id INT(20) NOT NULL,
    product_id INT(20) NOT NULL,
    quantity INT NOT NULL CHECK(quantity > 0),

    FOREIGN KEY (cart_id) REFERENCES shopping_cart(cart_id),
    FOREIGN KEY (product_id) REFERENCES product(product_id)
);