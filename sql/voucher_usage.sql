CREATE TABLE voucher_usage (
    user_id INT NOT NULL,
    voucher_id INT NOT NULL,
    used_at DATETIME NOT NULL,
    status ENUM('used', 'cancelled') NOT NULL DEFAULT 'used',
    
    PRIMARY KEY (user_id, voucher_id), 
    FOREIGN KEY (user_id) REFERENCES user(user_id),
    FOREIGN KEY (voucher_id) REFERENCES voucher(voucher_id)
);
