CREATE TABLE product (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    category VARCHAR(255) NOT NULL,
    description TEXT,
    main_image_id INT NULL,
    FOREIGN KEY (main_image_id) REFERENCES product_image(id)
);

-- test

-- INSERT INTO product (product_name, category, description)
-- VALUES ('Apple iPhone 14', 'Electronics', 'Latest model smartphone');

