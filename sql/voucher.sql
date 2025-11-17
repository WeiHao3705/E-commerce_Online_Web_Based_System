CREATE TABLE voucher (
    voucher_id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255),
    code VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('percent', 'fixed', 'freeshipping') NOT NULL,
    diacount_value DECIMAL(10,2) NOT NULL,
    min_spend DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_discount DECIMAL(10,2),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    membership_required BOOLEAN NOT NULL,
    status ENUM('active', 'inactive', 'expired') DEFAULT 'active'
);
