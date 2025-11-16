CREATE TABLE order (
    order_id INT(20) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(20) NOT NULL,
    voucher_id INT(20),
    total_amount DECIMAL(10, 2) NOT NULL CHECK(total_amount >= 0),
    order_status ENUM('pending', 'paid', 'shipped', 'delivered', 'canceled', 'refunded') DEFAULT 'pending',
    create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES user(user_id),
    FOREIGN KEY (voucher_id) REFERENCES voucher(voucher_id)
);