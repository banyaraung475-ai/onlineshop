-- OnlineShop Database Schema
-- Generated for this project (tables/columns aligned to current PHP code)

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS onlineshop
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE onlineshop;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS coupon_usage;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS delivery_methods;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS wishlist;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS product_reviews;
DROP TABLE IF EXISTS product_attributes;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS admin_logs;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS newsletter_subscribers;
DROP TABLE IF EXISTS contacts;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- Users
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    password VARCHAR(255) GENERATED ALWAYS AS (password_hash) VIRTUAL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    is_active TINYINT(1) DEFAULT 1,
    email_verified TINYINT(1) DEFAULT 0,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Categories
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(150) NULL,
    description TEXT,
    parent_id INT NULL,
    image_url VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_categories_slug (slug),
    FOREIGN KEY (parent_id) REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Products
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2),
    sku VARCHAR(50) UNIQUE,
    category_id INT,
    brand VARCHAR(100),
    stock_quantity INT DEFAULT 0,
    min_stock_level INT DEFAULT 5,
    weight DECIMAL(8,2),
    dimensions VARCHAR(50),
    image_url VARCHAR(255),
    additional_images JSON,
    is_active TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
) ENGINE=InnoDB;

-- Product attributes
CREATE TABLE product_attributes (
    attribute_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    attribute_name VARCHAR(50) NOT NULL,
    attribute_value VARCHAR(100) NOT NULL,
    price_modifier DECIMAL(10,2) DEFAULT 0,
    stock_quantity INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Product reviews
CREATE TABLE product_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL,
    review_text TEXT,
    is_verified TINYINT(1) DEFAULT 0,
    is_approved TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Product images
CREATE TABLE product_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_primary TINYINT(1) DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Orders
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled', 'completed') DEFAULT 'pending',
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    payment_id INT NULL,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    delivery_id INT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'MMK',
    payment_method VARCHAR(20) DEFAULT 'cod',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded', 'completed') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    billing_address TEXT,
    tracking_number VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_orders_payment_id (payment_id),
    INDEX idx_orders_delivery_id (delivery_id)
) ENGINE=InnoDB;

-- Order items
CREATE TABLE order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    price DECIMAL(10,2) GENERATED ALWAYS AS (unit_price) VIRTUAL,
    total_price DECIMAL(10,2) NOT NULL,
    product_attributes JSON,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
) ENGINE=InnoDB;

-- Cart items
CREATE TABLE cart_items (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    product_attributes JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
) ENGINE=InnoDB;

-- Wishlist
CREATE TABLE wishlist (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist_item (user_id, product_id)
) ENGINE=InnoDB;

-- Coupons
CREATE TABLE coupons (
    coupon_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    minimum_amount DECIMAL(10,2) DEFAULT 0,
    usage_limit INT,
    usage_count INT DEFAULT 0,
    starts_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Coupon usage
CREATE TABLE coupon_usage (
    usage_id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(coupon_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Settings
CREATE TABLE settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Admin logs
CREATE TABLE admin_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- Newsletter
CREATE TABLE newsletter_subscribers (
    subscriber_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Notifications
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Payments
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NULL,
    amount DECIMAL(10,2) DEFAULT 0,
    payment_method VARCHAR(20) NULL,
    method VARCHAR(20) NULL,
    status ENUM('pending', 'paid', 'failed', 'refunded', 'completed') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    payment_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    INDEX idx_payments_order (order_id),
    INDEX idx_payments_status (status)
) ENGINE=InnoDB;

-- Delivery methods
CREATE TABLE delivery_methods (
    delivery_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NULL,
    method ENUM('Standard', 'Express') NOT NULL,
    address TEXT NOT NULL,
    estimated_delivery DATETIME NOT NULL,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending', 'preparing', 'shipped', 'in_transit', 'delivered', 'cancelled') DEFAULT 'preparing',
    tracking_number VARCHAR(100),
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    INDEX idx_delivery_order (order_id),
    INDEX idx_delivery_status (status)
) ENGINE=InnoDB;

-- Contacts
CREATE TABLE contacts (
    contact_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    newsletter TINYINT(1) DEFAULT 0,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    replied_at TIMESTAMP NULL,
    INDEX idx_contacts_email (email),
    INDEX idx_contacts_created (created_at),
    INDEX idx_contacts_read (is_read)
) ENGINE=InnoDB;

-- Performance indexes
CREATE INDEX idx_categories_parent ON categories(parent_id);
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_active ON products(is_active);
CREATE INDEX idx_products_featured ON products(is_featured);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_date ON orders(created_at);
CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_order_items_product ON order_items(product_id);
CREATE INDEX idx_cart_user ON cart_items(user_id);
CREATE INDEX idx_cart_product ON cart_items(product_id);
CREATE INDEX idx_wishlist_user ON wishlist(user_id);
CREATE INDEX idx_wishlist_product ON wishlist(product_id);
CREATE INDEX idx_coupon_usage_coupon ON coupon_usage(coupon_id);
CREATE INDEX idx_coupon_usage_user ON coupon_usage(user_id);
CREATE INDEX idx_coupon_usage_order ON coupon_usage(order_id);
CREATE INDEX idx_admin_logs_user ON admin_logs(user_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_reviews_product ON product_reviews(product_id);
CREATE INDEX idx_reviews_user ON product_reviews(user_id);
CREATE INDEX idx_reviews_rating ON product_reviews(rating);

-- Add diagram relationships after dependent tables exist
ALTER TABLE orders
    ADD CONSTRAINT fk_orders_payment_id
    FOREIGN KEY (payment_id) REFERENCES payments(payment_id) ON DELETE SET NULL ON UPDATE CASCADE,
    ADD CONSTRAINT fk_orders_delivery_id
    FOREIGN KEY (delivery_id) REFERENCES delivery_methods(delivery_id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Keep payments.method and payments.payment_method in sync
DELIMITER //
CREATE TRIGGER payments_bi BEFORE INSERT ON payments FOR EACH ROW
BEGIN
  IF NEW.payment_method IS NULL AND NEW.method IS NOT NULL THEN
    SET NEW.payment_method = NEW.method;
  END IF;
  IF NEW.method IS NULL AND NEW.payment_method IS NOT NULL THEN
    SET NEW.method = NEW.payment_method;
  END IF;
END//
CREATE TRIGGER payments_bu BEFORE UPDATE ON payments FOR EACH ROW
BEGIN
  IF NEW.payment_method IS NULL AND NEW.method IS NOT NULL THEN
    SET NEW.payment_method = NEW.method;
  END IF;
  IF NEW.method IS NULL AND NEW.payment_method IS NOT NULL THEN
    SET NEW.method = NEW.payment_method;
  END IF;
END//
DELIMITER ;

-- Seed data
INSERT INTO users (username, email, password_hash, full_name, role)
VALUES ('admin', 'admin@onlineshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin')
ON DUPLICATE KEY UPDATE username = username;

INSERT INTO categories (name, description, sort_order) VALUES
('Electronics', 'Gadgets, devices, and technology products', 1),
('Clothing', 'Fashion, apparel, and accessories', 2),
('Home & Garden', 'Furniture, decor, and household items', 3),
('Books', 'Literature, educational, and reading materials', 4),
('Sports & Outdoors', 'Athletic equipment and outdoor gear', 5),
('Toys & Games', 'Children\'s toys and entertainment', 6),
('Grocery & Kitchen', 'Food items and kitchen essentials', 7),
('Health & Beauty', 'Personal care and beauty products', 8)
ON DUPLICATE KEY UPDATE name = name;



-- Seed products (10 per category)
INSERT INTO products (name, description, price, original_price, sku, category_id, brand, stock_quantity, is_featured, image_url) VALUES
('Electronics Product 01', 'Electronics item 01', 29999.00, 39999.00, 'EL-001', 1, 'ElectroMax', 50, 1, 'https://picsum.photos/seed/electronics01/600/600'),
('Electronics Product 02', 'Electronics item 02', 44999.00, 54999.00, 'EL-002', 1, 'ElectroMax', 40, 0, 'https://picsum.photos/seed/electronics02/600/600'),
('Electronics Product 03', 'Electronics item 03', 59999.00, 69999.00, 'EL-003', 1, 'Voltix', 35, 0, 'https://picsum.photos/seed/electronics03/600/600'),
('Electronics Product 04', 'Electronics item 04', 79999.00, 89999.00, 'EL-004', 1, 'Voltix', 25, 1, 'https://picsum.photos/seed/electronics04/600/600'),
('Electronics Product 05', 'Electronics item 05', 99999.00, 119999.00, 'EL-005', 1, 'NovaTech', 20, 0, 'https://picsum.photos/seed/electronics05/600/600'),
('Electronics Product 06', 'Electronics item 06', 129999.00, 149999.00, 'EL-006', 1, 'NovaTech', 18, 0, 'https://picsum.photos/seed/electronics06/600/600'),
('Electronics Product 07', 'Electronics item 07', 159999.00, 179999.00, 'EL-007', 1, 'CircuitPro', 15, 0, 'https://picsum.photos/seed/electronics07/600/600'),
('Electronics Product 08', 'Electronics item 08', 189999.00, 209999.00, 'EL-008', 1, 'CircuitPro', 12, 1, 'https://picsum.photos/seed/electronics08/600/600'),
('Electronics Product 09', 'Electronics item 09', 219999.00, 239999.00, 'EL-009', 1, 'TechLine', 10, 0, 'https://picsum.photos/seed/electronics09/600/600'),
('Electronics Product 10', 'Electronics item 10', 249999.00, 279999.00, 'EL-010', 1, 'TechLine', 8, 0, 'https://picsum.photos/seed/electronics10/600/600'),

('Clothing Product 01', 'Clothing item 01', 14999.00, 19999.00, 'CL-001', 2, 'StyleCo', 120, 1, 'https://picsum.photos/seed/clothing01/600/600'),
('Clothing Product 02', 'Clothing item 02', 17999.00, 22999.00, 'CL-002', 2, 'StyleCo', 110, 0, 'https://picsum.photos/seed/clothing02/600/600'),
('Clothing Product 03', 'Clothing item 03', 19999.00, 24999.00, 'CL-003', 2, 'UrbanWear', 100, 0, 'https://picsum.photos/seed/clothing03/600/600'),
('Clothing Product 04', 'Clothing item 04', 21999.00, 27999.00, 'CL-004', 2, 'UrbanWear', 95, 1, 'https://picsum.photos/seed/clothing04/600/600'),
('Clothing Product 05', 'Clothing item 05', 23999.00, 29999.00, 'CL-005', 2, 'CottonCore', 90, 0, 'https://picsum.photos/seed/clothing05/600/600'),
('Clothing Product 06', 'Clothing item 06', 25999.00, 32999.00, 'CL-006', 2, 'CottonCore', 85, 0, 'https://picsum.photos/seed/clothing06/600/600'),
('Clothing Product 07', 'Clothing item 07', 28999.00, 35999.00, 'CL-007', 2, 'DenimWorks', 80, 0, 'https://picsum.photos/seed/clothing07/600/600'),
('Clothing Product 08', 'Clothing item 08', 31999.00, 38999.00, 'CL-008', 2, 'DenimWorks', 75, 1, 'https://picsum.photos/seed/clothing08/600/600'),
('Clothing Product 09', 'Clothing item 09', 34999.00, 41999.00, 'CL-009', 2, 'TrendLab', 70, 0, 'https://picsum.photos/seed/clothing09/600/600'),
('Clothing Product 10', 'Clothing item 10', 37999.00, 44999.00, 'CL-010', 2, 'TrendLab', 65, 0, 'https://picsum.photos/seed/clothing10/600/600'),

('Home & Garden Product 01', 'Home & garden item 01', 24999.00, 29999.00, 'HG-001', 3, 'HomeEase', 60, 1, 'https://picsum.photos/seed/homegarden01/600/600'),
('Home & Garden Product 02', 'Home & garden item 02', 29999.00, 35999.00, 'HG-002', 3, 'HomeEase', 55, 0, 'https://picsum.photos/seed/homegarden02/600/600'),
('Home & Garden Product 03', 'Home & garden item 03', 34999.00, 41999.00, 'HG-003', 3, 'GardenPro', 50, 0, 'https://picsum.photos/seed/homegarden03/600/600'),
('Home & Garden Product 04', 'Home & garden item 04', 39999.00, 46999.00, 'HG-004', 3, 'GardenPro', 45, 1, 'https://picsum.photos/seed/homegarden04/600/600'),
('Home & Garden Product 05', 'Home & garden item 05', 44999.00, 51999.00, 'HG-005', 3, 'NestLine', 40, 0, 'https://picsum.photos/seed/homegarden05/600/600'),
('Home & Garden Product 06', 'Home & garden item 06', 49999.00, 57999.00, 'HG-006', 3, 'NestLine', 35, 0, 'https://picsum.photos/seed/homegarden06/600/600'),
('Home & Garden Product 07', 'Home & garden item 07', 54999.00, 62999.00, 'HG-007', 3, 'CasaCraft', 30, 0, 'https://picsum.photos/seed/homegarden07/600/600'),
('Home & Garden Product 08', 'Home & garden item 08', 59999.00, 68999.00, 'HG-008', 3, 'CasaCraft', 28, 1, 'https://picsum.photos/seed/homegarden08/600/600'),
('Home & Garden Product 09', 'Home & garden item 09', 64999.00, 72999.00, 'HG-009', 3, 'UrbanNest', 25, 0, 'https://picsum.photos/seed/homegarden09/600/600'),
('Home & Garden Product 10', 'Home & garden item 10', 69999.00, 78999.00, 'HG-010', 3, 'UrbanNest', 22, 0, 'https://picsum.photos/seed/homegarden10/600/600'),

('Books Product 01', 'Book item 01', 5999.00, 7999.00, 'BK-001', 4, 'ReadMore', 200, 1, 'https://picsum.photos/seed/books01/600/600'),
('Books Product 02', 'Book item 02', 6999.00, 8999.00, 'BK-002', 4, 'ReadMore', 190, 0, 'https://picsum.photos/seed/books02/600/600'),
('Books Product 03', 'Book item 03', 7999.00, 9999.00, 'BK-003', 4, 'BookHive', 180, 0, 'https://picsum.photos/seed/books03/600/600'),
('Books Product 04', 'Book item 04', 8999.00, 10999.00, 'BK-004', 4, 'BookHive', 170, 1, 'https://picsum.photos/seed/books04/600/600'),
('Books Product 05', 'Book item 05', 9999.00, 11999.00, 'BK-005', 4, 'PageTurn', 160, 0, 'https://picsum.photos/seed/books05/600/600'),
('Books Product 06', 'Book item 06', 10999.00, 12999.00, 'BK-006', 4, 'PageTurn', 150, 0, 'https://picsum.photos/seed/books06/600/600'),
('Books Product 07', 'Book item 07', 11999.00, 13999.00, 'BK-007', 4, 'LeafLine', 140, 0, 'https://picsum.photos/seed/books07/600/600'),
('Books Product 08', 'Book item 08', 12999.00, 14999.00, 'BK-008', 4, 'LeafLine', 130, 1, 'https://picsum.photos/seed/books08/600/600'),
('Books Product 09', 'Book item 09', 13999.00, 15999.00, 'BK-009', 4, 'StoryCraft', 120, 0, 'https://picsum.photos/seed/books09/600/600'),
('Books Product 10', 'Book item 10', 14999.00, 16999.00, 'BK-010', 4, 'StoryCraft', 110, 0, 'https://picsum.photos/seed/books10/600/600'),

('Sports & Outdoors Product 01', 'Sports item 01', 19999.00, 24999.00, 'SP-001', 5, 'SportGear', 70, 1, 'https://picsum.photos/seed/sports01/600/600'),
('Sports & Outdoors Product 02', 'Sports item 02', 22999.00, 27999.00, 'SP-002', 5, 'SportGear', 65, 0, 'https://picsum.photos/seed/sports02/600/600'),
('Sports & Outdoors Product 03', 'Sports item 03', 25999.00, 30999.00, 'SP-003', 5, 'TrailX', 60, 0, 'https://picsum.photos/seed/sports03/600/600'),
('Sports & Outdoors Product 04', 'Sports item 04', 28999.00, 33999.00, 'SP-004', 5, 'TrailX', 55, 1, 'https://picsum.photos/seed/sports04/600/600'),
('Sports & Outdoors Product 05', 'Sports item 05', 31999.00, 36999.00, 'SP-005', 5, 'ActivePeak', 50, 0, 'https://picsum.photos/seed/sports05/600/600'),
('Sports & Outdoors Product 06', 'Sports item 06', 34999.00, 39999.00, 'SP-006', 5, 'ActivePeak', 45, 0, 'https://picsum.photos/seed/sports06/600/600'),
('Sports & Outdoors Product 07', 'Sports item 07', 37999.00, 42999.00, 'SP-007', 5, 'OutdoorPro', 40, 0, 'https://picsum.photos/seed/sports07/600/600'),
('Sports & Outdoors Product 08', 'Sports item 08', 40999.00, 45999.00, 'SP-008', 5, 'OutdoorPro', 35, 1, 'https://picsum.photos/seed/sports08/600/600'),
('Sports & Outdoors Product 09', 'Sports item 09', 43999.00, 48999.00, 'SP-009', 5, 'SummitCo', 30, 0, 'https://picsum.photos/seed/sports09/600/600'),
('Sports & Outdoors Product 10', 'Sports item 10', 46999.00, 51999.00, 'SP-010', 5, 'SummitCo', 28, 0, 'https://picsum.photos/seed/sports10/600/600'),

('Toys & Games Product 01', 'Toy item 01', 9999.00, 12999.00, 'TG-001', 6, 'PlayBox', 90, 1, 'https://picsum.photos/seed/toys01/600/600'),
('Toys & Games Product 02', 'Toy item 02', 10999.00, 13999.00, 'TG-002', 6, 'PlayBox', 85, 0, 'https://picsum.photos/seed/toys02/600/600'),
('Toys & Games Product 03', 'Toy item 03', 11999.00, 14999.00, 'TG-003', 6, 'ToySphere', 80, 0, 'https://picsum.photos/seed/toys03/600/600'),
('Toys & Games Product 04', 'Toy item 04', 12999.00, 15999.00, 'TG-004', 6, 'ToySphere', 75, 1, 'https://picsum.photos/seed/toys04/600/600'),
('Toys & Games Product 05', 'Toy item 05', 13999.00, 16999.00, 'TG-005', 6, 'FunZone', 70, 0, 'https://picsum.photos/seed/toys05/600/600'),
('Toys & Games Product 06', 'Toy item 06', 14999.00, 17999.00, 'TG-006', 6, 'FunZone', 65, 0, 'https://picsum.photos/seed/toys06/600/600'),
('Toys & Games Product 07', 'Toy item 07', 15999.00, 18999.00, 'TG-007', 6, 'PlayWorks', 60, 0, 'https://picsum.photos/seed/toys07/600/600'),
('Toys & Games Product 08', 'Toy item 08', 16999.00, 19999.00, 'TG-008', 6, 'PlayWorks', 55, 1, 'https://picsum.photos/seed/toys08/600/600'),
('Toys & Games Product 09', 'Toy item 09', 17999.00, 20999.00, 'TG-009', 6, 'KidJoy', 50, 0, 'https://picsum.photos/seed/toys09/600/600'),
('Toys & Games Product 10', 'Toy item 10', 18999.00, 21999.00, 'TG-010', 6, 'KidJoy', 45, 0, 'https://picsum.photos/seed/toys10/600/600'),

('Grocery & Kitchen Product 01', 'Grocery item 01', 4999.00, 6499.00, 'GK-001', 7, 'FreshMart', 140, 1, 'https://picsum.photos/seed/grocery01/600/600'),
('Grocery & Kitchen Product 02', 'Grocery item 02', 5999.00, 7499.00, 'GK-002', 7, 'FreshMart', 135, 0, 'https://picsum.photos/seed/grocery02/600/600'),
('Grocery & Kitchen Product 03', 'Grocery item 03', 6999.00, 8499.00, 'GK-003', 7, 'KitchenPro', 130, 0, 'https://picsum.photos/seed/grocery03/600/600'),
('Grocery & Kitchen Product 04', 'Grocery item 04', 7999.00, 9499.00, 'GK-004', 7, 'KitchenPro', 125, 1, 'https://picsum.photos/seed/grocery04/600/600'),
('Grocery & Kitchen Product 05', 'Grocery item 05', 8999.00, 10499.00, 'GK-005', 7, 'PantryPlus', 120, 0, 'https://picsum.photos/seed/grocery05/600/600'),
('Grocery & Kitchen Product 06', 'Grocery item 06', 9999.00, 11499.00, 'GK-006', 7, 'PantryPlus', 115, 0, 'https://picsum.photos/seed/grocery06/600/600'),
('Grocery & Kitchen Product 07', 'Grocery item 07', 10999.00, 12499.00, 'GK-007', 7, 'ChefLine', 110, 0, 'https://picsum.photos/seed/grocery07/600/600'),
('Grocery & Kitchen Product 08', 'Grocery item 08', 11999.00, 13499.00, 'GK-008', 7, 'ChefLine', 105, 1, 'https://picsum.photos/seed/grocery08/600/600'),
('Grocery & Kitchen Product 09', 'Grocery item 09', 12999.00, 14499.00, 'GK-009', 7, 'DailyFresh', 100, 0, 'https://picsum.photos/seed/grocery09/600/600'),
('Grocery & Kitchen Product 10', 'Grocery item 10', 13999.00, 15499.00, 'GK-010', 7, 'DailyFresh', 95, 0, 'https://picsum.photos/seed/grocery10/600/600'),

('Health & Beauty Product 01', 'Health & beauty item 01', 8999.00, 10999.00, 'HB-001', 8, 'GlowCare', 100, 1, 'https://picsum.photos/seed/healthbeauty01/600/600'),
('Health & Beauty Product 02', 'Health & beauty item 02', 9999.00, 11999.00, 'HB-002', 8, 'GlowCare', 95, 0, 'https://picsum.photos/seed/healthbeauty02/600/600'),
('Health & Beauty Product 03', 'Health & beauty item 03', 10999.00, 12999.00, 'HB-003', 8, 'PureSkin', 90, 0, 'https://picsum.photos/seed/healthbeauty03/600/600'),
('Health & Beauty Product 04', 'Health & beauty item 04', 11999.00, 13999.00, 'HB-004', 8, 'PureSkin', 85, 1, 'https://picsum.photos/seed/healthbeauty04/600/600'),
('Health & Beauty Product 05', 'Health & beauty item 05', 12999.00, 14999.00, 'HB-005', 8, 'WellnessCo', 80, 0, 'https://picsum.photos/seed/healthbeauty05/600/600'),
('Health & Beauty Product 06', 'Health & beauty item 06', 13999.00, 15999.00, 'HB-006', 8, 'WellnessCo', 75, 0, 'https://picsum.photos/seed/healthbeauty06/600/600'),
('Health & Beauty Product 07', 'Health & beauty item 07', 14999.00, 16999.00, 'HB-007', 8, 'CarePlus', 70, 0, 'https://picsum.photos/seed/healthbeauty07/600/600'),
('Health & Beauty Product 08', 'Health & beauty item 08', 15999.00, 17999.00, 'HB-008', 8, 'CarePlus', 65, 1, 'https://picsum.photos/seed/healthbeauty08/600/600'),
('Health & Beauty Product 09', 'Health & beauty item 09', 16999.00, 18999.00, 'HB-009', 8, 'VitalGlow', 60, 0, 'https://picsum.photos/seed/healthbeauty09/600/600'),
('Health & Beauty Product 10', 'Health & beauty item 10', 17999.00, 19999.00, 'HB-010', 8, 'VitalGlow', 55, 0, 'https://picsum.photos/seed/healthbeauty10/600/600')
ON DUPLICATE KEY UPDATE name = name;
