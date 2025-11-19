CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    gender ENUM('Male','Female','Other') DEFAULT 'Other',
    contact_no VARCHAR(20),
    email VARCHAR(100) NOT NULL UNIQUE,
    profile_photo MEDIUMBLOB,
    password VARCHAR(255) NOT NULL,
    security_question VARCHAR(255),
    security_answer VARCHAR(255),
    role ENUM('member','staff','admin') DEFAULT 'member',
    status ENUM('active','inactive','banned') DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
