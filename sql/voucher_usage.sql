CREATE TABLE voucher_usage (
    membership_id INT NOT NULL,
    voucher_id INT NOT NULL,
    used_at DATETIME NOT NULL,
    status ENUM('used', 'cancelled') NOT NULL DEFAULT 'used',
    
    PRIMARY KEY (membership_id, voucher_id), 
    FOREIGN KEY (membership_id) REFERENCES membership(membership_id),
    FOREIGN KEY (voucher_id) REFERENCES voucher(voucher_id)
);
