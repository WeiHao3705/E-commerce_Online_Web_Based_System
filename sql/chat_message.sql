CREATE TABLE chat_message (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    chat_room_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (chat_room_id) REFERENCES chat_room(chat_room_id)
        ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(user_id)
);
