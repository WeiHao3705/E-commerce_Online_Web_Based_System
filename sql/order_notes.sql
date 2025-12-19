-- Order Notes Table
-- Stores internal admin notes for orders

CREATE TABLE IF NOT EXISTS order_notes (
    note_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    admin_id INT NOT NULL,
    note_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    INDEX idx_order_id (order_id),
    INDEX idx_admin_id (admin_id),
    INDEX idx_created_at (created_at)
);
