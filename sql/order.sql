CREATE TABLE order (
    order_id INT(20) AUTO_INCREMENT PRIMARY KEY,
    shipping_id INT(20) NOT NULL,
    user_id INT(20) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL CHECK(total_amount >= 0),
    order_status ENUM('pending', 'paid', 'shipped', 'delivered', 'canceled', 'refunded') DEFAULT 'pending',
    create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (shipping_id) REFERENCES shipping_address(shipping_id),
    FOREIGN KEY (user_id) REFERENCES user(user_id)
);