CREATE TABLE order_item (
    order_item_id INT(20) AUTO_INCREMENT PRIMARY KEY,
    order_id INT(20) NOT NULL,
    product_id INT(20) NOT NULL,
    product_name_snapshot VARCHAR(255) NOT NULL,
    product_price_snapshot DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL CHECK(quantity > 0),
    subtotal INT(10, 2) NOT NULL CHECK(subtotal >= 0),
    price DECIMAL(10, 2) NOT NULL CHECK(price >= 0),

    FOREIGN KEY (order_id) REFERENCES order(order_id),
    FOREIGN KEY (product_id) REFERENCES product(product_id)
);