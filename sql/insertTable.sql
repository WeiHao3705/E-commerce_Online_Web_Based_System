CREATE DATABASE IF NOT EXISTS ecommerce_db;
USE ecommerce_db;

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    gender ENUM('Male','Female','Other') DEFAULT 'Other',
    contact_no VARCHAR(20),
    email VARCHAR(100) NOT NULL UNIQUE,
    profile_photo MEDIUMBLOB,
    DateOfBirth DATE NOT NULL,
    password VARCHAR(255) NOT NULL,
    security_question VARCHAR(255),
    security_answer VARCHAR(255),
    role ENUM('member','admin') DEFAULT 'member',
    status ENUM('active','inactive','banned') DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Product table
CREATE TABLE product (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    category VARCHAR(255) NOT NULL,
    description TEXT
);

-- Voucher table
CREATE TABLE voucher (
    voucher_id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255),
    code VARCHAR(50) NOT NULL UNIQUE,
    type ENUM('percent', 'fixed', 'freeshipping') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_spend DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_discount DECIMAL(10,2),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'inactive', 'expired') DEFAULT 'active'
);

-- Address table (depends on users)
CREATE TABLE address (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address1 VARCHAR(30) NOT NULL,
    address2 VARCHAR(30) NOT NULL,
    city VARCHAR(20) NOT NULL,
    postcode VARCHAR(5) NOT NULL,
    state VARCHAR(20) NOT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Membership table (depends on users)
CREATE TABLE membership (
    membership_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Shopping cart table (depends on users)
CREATE TABLE shopping_cart (
    cart_id INT(20) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Voucher usage table (depends on users and voucher)
CREATE TABLE voucher_usage (
    user_id INT NOT NULL,
    voucher_id INT NOT NULL,
    used_at DATETIME NOT NULL,
    
    PRIMARY KEY (user_id, voucher_id), 
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (voucher_id) REFERENCES voucher(voucher_id)
);

-- Product price table (depends on product)
CREATE TABLE product_price (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    cost DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2) NOT NULL,
    selling_price DECIMAL(10,2) NOT NULL,

    -- 1-to-1 relationship (each product has only one price)
    CONSTRAINT fk_price_product
        FOREIGN KEY (product_id)
        REFERENCES product(product_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Enforce 1 product = 1 price row
    UNIQUE (product_id)
);

-- Product image table (depends on product)
CREATE TABLE product_image (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    type ENUM('main', 'gallery') DEFAULT 'gallery',
    FOREIGN KEY (product_id) REFERENCES product(product_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- Product variant table (depends on product)
CREATE TABLE product_variant (
    variant_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    size VARCHAR(50) NOT NULL,
    color VARCHAR(50) DEFAULT NULL,
    
    FOREIGN KEY (product_id) REFERENCES product(product_id)
);

-- Orders table (depends on users and voucher)
CREATE TABLE orders (
    order_id INT(20) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(20) NOT NULL,
    voucher_id INT(20),
    total_amount DECIMAL(10, 2) NOT NULL CHECK(total_amount >= 0),
    order_status ENUM('pending', 'paid', 'shipped', 'delivered', 'canceled', 'refunded') DEFAULT 'pending',
    create_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (voucher_id) REFERENCES voucher(voucher_id)
);

-- Cart item table (depends on shopping_cart and product)
CREATE TABLE cart_item (
    cart_item_id INT(20) AUTO_INCREMENT PRIMARY KEY,
    cart_id INT(20) NOT NULL,
    product_id INT(20) NOT NULL,
    quantity INT NOT NULL CHECK(quantity > 0),

    FOREIGN KEY (cart_id) REFERENCES shopping_cart(cart_id),
    FOREIGN KEY (product_id) REFERENCES product(product_id)
);

-- Inventory table (depends on product_variant)
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    variant_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    reorder_level INT NOT NULL DEFAULT 10,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
                 ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (variant_id) REFERENCES product_variant(variant_id)
);

-- Order item table (depends on orders and product)
CREATE TABLE order_item (
    order_item_id INT(20) AUTO_INCREMENT PRIMARY KEY,
    order_id INT(20) NOT NULL,
    product_id INT(20) NOT NULL,
    product_name_snapshot VARCHAR(255) NOT NULL,
    product_price_snapshot DECIMAL(10, 2) NOT NULL,
    quantity INT NOT NULL CHECK(quantity > 0),
    subtotal DECIMAL(10, 2) NOT NULL CHECK(subtotal >= 0),

    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES product(product_id)
);

-- Payment table (depends on orders)
CREATE TABLE payment (
    payment_id INT(20) AUTO_INCREMENT PRIMARY KEY,
    order_id INT(20) NOT NULL,
    payment_method ENUM('credit_card', 'fpx', 'e-wallet', 'COD') NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100) UNIQUE,
    paid_amount DECIMAL(10, 2) NOT NULL CHECK(paid_amount >= 0),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);