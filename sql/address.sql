CREATE TABLE address (
    id INT AUTO_INCREMENT PRIMARY KEY,
    address1 VARCHAR(30) NOT NULL,
    address2 VARCHAR(30) NOT NULL,
    city VARCHAR(20) NOT NULL,
    postcode VARCHAR(5) NOT NULL,
    state VARCHAR(20) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user(user_id)
)