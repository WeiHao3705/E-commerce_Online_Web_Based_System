CREATE TABLE IF NOT EXISTS wishlist (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT NULL,
    size VARCHAR(20) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variant(variant_id) ON DELETE CASCADE,
    
    -- Prevent duplicate entries (same user, product, variant, and size combination)
    UNIQUE KEY unique_user_product_variant_size (user_id, product_id, variant_id, size),
    
    INDEX idx_user_id (user_id),
    INDEX idx_product_id (product_id),
    INDEX idx_variant_id (variant_id),
    INDEX idx_size (size),
    INDEX idx_created_at (created_at)
);
