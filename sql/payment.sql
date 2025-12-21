CREATE TABLE payment (
    payment_id INT(20) AUTO_INCREMENT PRIMARY KEY,
    order_id INT(20) NOT NULL,
    payment_method ENUM('credit_card', 'online-banking', 'e-wallet') NOT NULL,
    payment_status ENUM('pending', 'paid') DEFAULT 'paid',
    transaction_id VARCHAR(100) UNIQUE,
    paid_amount DECIMAL(10, 2) NOT NULL CHECK(paid_amount >= 0),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);