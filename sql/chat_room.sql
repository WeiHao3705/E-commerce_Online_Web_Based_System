CREATE TABLE IF NOT EXISTS chat_room (
    chat_room_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    admin_id INT NULL,
    status ENUM('open','closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,

    FOREIGN KEY (member_id) REFERENCES users(user_id),
    FOREIGN KEY (admin_id) REFERENCES users(user_id)
);

