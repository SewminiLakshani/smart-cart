-- =====================================================================
-- SMARTCART - ADVANCED E-COMMERCE MANAGEMENT SYSTEM
-- Database: smartcart_db
-- Engine: InnoDB | Charset: utf8mb4
-- =====================================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

CREATE DATABASE IF NOT EXISTS smartcart_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartcart_db;

-- =====================================================================
-- 1. ROLES
-- =====================================================================
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO roles (role_id, role_name) VALUES (1,'customer'),(2,'admin'),(3,'staff');

-- =====================================================================
-- 2. USERS
-- =====================================================================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL DEFAULT 1,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    status ENUM('active','inactive','banned') NOT NULL DEFAULT 'active',
    reset_token VARCHAR(191) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id),
    INDEX idx_email (email),
    INDEX idx_role (role_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 3. CATEGORIES
-- =====================================================================
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- 4. BRANDS
-- =====================================================================
CREATE TABLE brands (
    brand_id INT AUTO_INCREMENT PRIMARY KEY,
    brand_name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    logo VARCHAR(255) DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- 5. PRODUCTS
-- =====================================================================
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    brand_id INT DEFAULT NULL,
    product_name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    description TEXT,
    specifications TEXT,
    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    sku VARCHAR(60) DEFAULT NULL UNIQUE,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_flash_sale TINYINT(1) NOT NULL DEFAULT 0,
    flash_sale_price DECIMAL(10,2) DEFAULT NULL,
    flash_sale_start DATETIME DEFAULT NULL,
    flash_sale_end DATETIME DEFAULT NULL,
    total_sold INT NOT NULL DEFAULT 0,
    avg_rating DECIMAL(3,2) NOT NULL DEFAULT 0,
    review_count INT NOT NULL DEFAULT 0,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT,
    FOREIGN KEY (brand_id) REFERENCES brands(brand_id) ON DELETE SET NULL,
    INDEX idx_pname (product_name),
    INDEX idx_category (category_id),
    INDEX idx_brand (brand_id),
    INDEX idx_created (created_at),
    FULLTEXT INDEX ft_search (product_name, description)
) ENGINE=InnoDB;

-- =====================================================================
-- 6. PRODUCT IMAGES
-- =====================================================================
CREATE TABLE product_images (
    image_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_main TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 7. INVENTORY
-- =====================================================================
CREATE TABLE inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL UNIQUE,
    quantity INT NOT NULL DEFAULT 0,
    low_stock_threshold INT NOT NULL DEFAULT 10,
    stock_status ENUM('available','low_stock','out_of_stock') NOT NULL DEFAULT 'available',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 8. STOCK HISTORY
-- =====================================================================
CREATE TABLE stock_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    previous_quantity INT NOT NULL,
    change_quantity INT NOT NULL,
    new_quantity INT NOT NULL,
    change_type ENUM('add','reduce','order','return','adjustment') NOT NULL,
    note VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- 9. ADDRESSES
-- =====================================================================
CREATE TABLE addresses (
    address_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address_line VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    province VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    address_type ENUM('home','office','other') NOT NULL DEFAULT 'home',
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 10 & 11. WISHLIST / WISHLIST_ITEMS
-- =====================================================================
CREATE TABLE wishlist (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE wishlist_items (
    wishlist_item_id INT AUTO_INCREMENT PRIMARY KEY,
    wishlist_id INT NOT NULL,
    product_id INT NOT NULL,
    added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_wish_product (wishlist_id, product_id),
    FOREIGN KEY (wishlist_id) REFERENCES wishlist(wishlist_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 12 & 13. CART / CART_ITEMS
-- =====================================================================
CREATE TABLE cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_items (
    cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_cart_product (cart_id, product_id),
    FOREIGN KEY (cart_id) REFERENCES cart(cart_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 17 & 18. COUPONS / COUPON_USAGE  (created before orders so orders can FK)
-- =====================================================================
CREATE TABLE coupons (
    coupon_id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_code VARCHAR(50) NOT NULL UNIQUE,
    discount_type ENUM('percentage','fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_discount_amount DECIMAL(10,2) DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT NOT NULL DEFAULT 0,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- 14. ORDERS
-- =====================================================================
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(30) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    address_id INT DEFAULT NULL,
    coupon_id INT DEFAULT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    delivery_method ENUM('standard','express') NOT NULL DEFAULT 'standard',
    payment_method ENUM('cod','card') NOT NULL DEFAULT 'cod',
    order_status ENUM('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    assigned_staff_id INT DEFAULT NULL,
    shipping_full_name VARCHAR(120) NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    shipping_address VARCHAR(255) NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_province VARCHAR(100) NOT NULL,
    shipping_postal_code VARCHAR(20) DEFAULT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (address_id) REFERENCES addresses(address_id) ON DELETE SET NULL,
    FOREIGN KEY (coupon_id) REFERENCES coupons(coupon_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_staff_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_status (order_status),
    INDEX idx_created (created_at),
    INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 15. ORDER ITEMS
-- =====================================================================
CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================================
-- 16. PAYMENTS
-- =====================================================================
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method ENUM('cod','card') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
    transaction_ref VARCHAR(100) DEFAULT NULL,
    card_last_four VARCHAR(4) DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 18. COUPON USAGE
-- =====================================================================
CREATE TABLE coupon_usage (
    usage_id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    discount_applied DECIMAL(10,2) NOT NULL,
    used_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(coupon_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 19. REVIEWS
-- =====================================================================
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    rating TINYINT NOT NULL,
    review_text TEXT,
    status ENUM('pending','approved','hidden') NOT NULL DEFAULT 'approved',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL,
    CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- =====================================================================
-- 20. NOTIFICATIONS
-- =====================================================================
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    audience ENUM('customer','admin','staff','all') NOT NULL DEFAULT 'customer',
    title VARCHAR(150) NOT NULL,
    message VARCHAR(255) NOT NULL,
    type VARCHAR(40) NOT NULL DEFAULT 'general',
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB;

-- =====================================================================
-- 21. ORDER STATUS HISTORY
-- =====================================================================
CREATE TABLE order_status_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    status ENUM('pending','confirmed','processing','shipped','delivered','cancelled') NOT NULL,
    note VARCHAR(255) DEFAULT NULL,
    changed_by INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- SUPPORTING TABLES
-- =====================================================================
CREATE TABLE recently_viewed (
    view_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_user_viewed (user_id, viewed_at)
) ENGINE=InnoDB;

CREATE TABLE newsletter_subscribers (
    subscriber_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    subscribed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new','read','replied') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS=1;

-- =====================================================================
-- DEMO / SAMPLE DATA
-- =====================================================================

-- Passwords below are all bcrypt hashes of: Password@123
-- (verified compatible with PHP password_verify())
INSERT INTO users (role_id, full_name, email, phone, password_hash, status) VALUES
(2, 'Admin User', 'admin@smartcart.com', '0771234567', '$2b$12$kFI7w.mL8r7valnX3hacYuIxV6IStE/L8vHJU6uRwJiWQgTJj2nNK', 'active'),
(3, 'Staff Member', 'staff@smartcart.com', '0771234568', '$2b$12$kFI7w.mL8r7valnX3hacYuIxV6IStE/L8vHJU6uRwJiWQgTJj2nNK', 'active'),
(1, 'Nimal Perera', 'nimal@example.com', '0771234569', '$2b$12$kFI7w.mL8r7valnX3hacYuIxV6IStE/L8vHJU6uRwJiWQgTJj2nNK', 'active'),
(1, 'Kamala Silva', 'kamala@example.com', '0771234570', '$2b$12$kFI7w.mL8r7valnX3hacYuIxV6IStE/L8vHJU6uRwJiWQgTJj2nNK', 'active'),
(1, 'Sunil Fernando', 'sunil@example.com', '0771234571', '$2b$12$kFI7w.mL8r7valnX3hacYuIxV6IStE/L8vHJU6uRwJiWQgTJj2nNK', 'active');

INSERT INTO categories (category_name, slug, description, status) VALUES
('Electronics','electronics','Gadgets, audio, wearables and smart devices','active'),
('Fashion','fashion','Apparel, footwear and style essentials','active'),
('Beauty','beauty','Skincare, fragrance and cosmetics','active'),
('Home & Living','home-living','Furniture, decor and home essentials','active'),
('Sports','sports','Fitness gear and sports equipment','active'),
('Accessories','accessories','Bags, watches and everyday carry','active');

INSERT INTO brands (brand_name, slug, status) VALUES
('Aurora','aurora','active'),
('Nordic','nordic','active'),
('Velora','velora','active'),
('Zenith','zenith','active'),
('Lumen','lumen','active'),
('Crestline','crestline','active');

INSERT INTO products (category_id, brand_id, product_name, slug, description, specifications, price, discount_price, sku, is_featured, is_flash_sale, flash_sale_price, flash_sale_start, flash_sale_end, total_sold, avg_rating, review_count, status) VALUES
(1,1,'Aurora Wireless Headphones','aurora-wireless-headphones','Premium over-ear wireless headphones with active noise cancellation and 40-hour battery life.','Bluetooth 5.3 | 40h battery | ANC | Memory foam ear cushions',24900.00,19900.00,'SKU-EL-001',1,1,17900.00,'2026-08-01 00:00:00','2026-12-31 23:59:59',142,4.7,58,'active'),
(1,4,'Zenith Smart Watch Pro','zenith-smart-watch-pro','A refined smartwatch with AMOLED display, health tracking and 10-day battery.','AMOLED 1.4in | Heart-rate & SpO2 | 5ATM water resistant',32900.00,27900.00,'SKU-EL-002',1,0,NULL,NULL,NULL,98,4.6,41,'active'),
(1,5,'Lumen Portable Speaker','lumen-portable-speaker','Compact speaker with rich bass and 12-hour playtime, perfect for travel.','360 degree sound | IPX7 waterproof | USB-C fast charge',8900.00,NULL,'SKU-EL-003',0,0,NULL,NULL,NULL,64,4.3,22,'active'),
(1,1,'Aurora 4K Action Camera','aurora-4k-action-camera','Rugged 4K action camera with stabilization for every adventure.','4K/60fps | Waterproof to 30m | Built-in stabilization',45900.00,39900.00,'SKU-EL-004',1,0,NULL,NULL,NULL,37,4.5,19,'active'),
(2,3,'Velora Tailored Blazer','velora-tailored-blazer','Elegant tailored blazer crafted from premium wool blend.','Wool blend | Slim fit | Dry clean only',15900.00,12900.00,'SKU-FA-001',1,0,NULL,NULL,NULL,52,4.4,27,'active'),
(2,2,'Nordic Minimalist Sneakers','nordic-minimalist-sneakers','Clean, minimalist sneakers built for everyday comfort.','Genuine leather | Cushioned sole | Breathable lining',13500.00,NULL,'SKU-FA-002',0,1,9900.00,'2026-08-15 00:00:00','2026-12-31 23:59:59',88,4.6,35,'active'),
(2,6,'Crestline Cashmere Scarf','crestline-cashmere-scarf','Soft cashmere scarf that adds a refined finishing touch.','100% cashmere | 180x30cm | Hand wash',7900.00,6200.00,'SKU-FA-003',0,0,NULL,NULL,NULL,29,4.8,14,'active'),
(3,3,'Velora Radiance Serum','velora-radiance-serum','Brightening face serum with vitamin C and hyaluronic acid.','30ml | Vitamin C 15% | Suitable for all skin types',5900.00,4900.00,'SKU-BE-001',1,0,NULL,NULL,NULL,205,4.7,96,'active'),
(3,6,'Crestline Signature Perfume','crestline-signature-perfume','A warm, elegant fragrance with amber and vanilla notes.','50ml EDP | Long lasting | Gift boxed',11900.00,NULL,'SKU-BE-002',0,0,NULL,NULL,NULL,73,4.5,38,'active'),
(4,2,'Nordic Ceramic Dinner Set','nordic-ceramic-dinner-set','16-piece ceramic dinnerware set in a minimalist Nordic design.','16 pieces | Microwave & dishwasher safe | Matte finish',18900.00,15900.00,'SKU-HL-001',1,0,NULL,NULL,NULL,44,4.6,21,'active'),
(4,4,'Zenith Aroma Diffuser','zenith-aroma-diffuser','Ultrasonic aroma diffuser with ambient LED lighting.','300ml tank | 7 LED colors | Auto shut-off',6500.00,NULL,'SKU-HL-002',0,1,4900.00,'2026-08-10 00:00:00','2026-12-31 23:59:59',61,4.4,25,'active'),
(5,5,'Lumen Yoga Mat Pro','lumen-yoga-mat-pro','Extra thick non-slip yoga mat with carry strap.','6mm thick | Eco TPE material | Includes carry strap',5400.00,4200.00,'SKU-SP-001',0,0,NULL,NULL,NULL,97,4.6,44,'active'),
(5,4,'Zenith Fitness Resistance Bands','zenith-fitness-resistance-bands','5-level resistance band set for full-body training.','5 resistance levels | Includes door anchor | Carry bag',3900.00,NULL,'SKU-SP-002',0,0,NULL,NULL,NULL,120,4.3,52,'active'),
(6,1,'Aurora Leather Backpack','aurora-leather-backpack','Handcrafted leather backpack with padded laptop compartment.','Fits 15in laptop | Genuine leather | Water resistant lining',21900.00,17900.00,'SKU-AC-001',1,0,NULL,NULL,NULL,66,4.7,30,'active'),
(6,6,'Crestline Classic Watch','crestline-classic-watch','Timeless analog watch with a sapphire crystal face.','Stainless steel | Sapphire crystal | 5ATM water resistant',28900.00,23900.00,'SKU-AC-002',1,1,19900.00,'2026-08-20 00:00:00','2026-12-31 23:59:59',54,4.8,33,'active');

INSERT INTO inventory (product_id, quantity, low_stock_threshold, stock_status) VALUES
(1,45,10,'available'),(2,32,10,'available'),(3,8,10,'low_stock'),(4,15,10,'available'),
(5,22,10,'available'),(6,0,10,'out_of_stock'),(7,18,10,'available'),(8,60,10,'available'),
(9,26,10,'available'),(10,12,10,'available'),(11,5,10,'low_stock'),(12,40,10,'available'),
(13,33,10,'available'),(14,19,10,'available'),(15,6,10,'low_stock');

INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES
(1,'assets/images/products/placeholder.svg',1,0),
(2,'assets/images/products/placeholder.svg',1,0),
(3,'assets/images/products/placeholder.svg',1,0),
(4,'assets/images/products/placeholder.svg',1,0),
(5,'assets/images/products/placeholder.svg',1,0),
(6,'assets/images/products/placeholder.svg',1,0),
(7,'assets/images/products/placeholder.svg',1,0),
(8,'assets/images/products/placeholder.svg',1,0),
(9,'assets/images/products/placeholder.svg',1,0),
(10,'assets/images/products/placeholder.svg',1,0),
(11,'assets/images/products/placeholder.svg',1,0),
(12,'assets/images/products/placeholder.svg',1,0),
(13,'assets/images/products/placeholder.svg',1,0),
(14,'assets/images/products/placeholder.svg',1,0),
(15,'assets/images/products/placeholder.svg',1,0);

INSERT INTO addresses (user_id, full_name, phone, address_line, city, province, postal_code, address_type, is_default) VALUES
(3,'Nimal Perera','0771234569','123 Galle Road','Colombo','Western','00300','home',1),
(4,'Kamala Silva','0771234570','45 Kandy Road','Kandy','Central','20000','home',1);

INSERT INTO coupons (coupon_code, discount_type, discount_value, min_order_amount, max_discount_amount, usage_limit, used_count, start_date, end_date, status) VALUES
('WELCOME10','percentage',10.00,5000.00,3000.00,500,12,'2026-01-01 00:00:00','2026-12-31 23:59:59','active'),
('SAVE1500','fixed',1500.00,10000.00,NULL,200,5,'2026-01-01 00:00:00','2026-12-31 23:59:59','active'),
('FLASH20','percentage',20.00,3000.00,5000.00,100,3,'2026-08-01 00:00:00','2026-12-31 23:59:59','active');

INSERT INTO reviews (product_id, user_id, rating, review_text, status) VALUES
(1,3,5,'Sound quality is outstanding and the noise cancellation actually works well on flights.','approved'),
(1,4,4,'Very comfortable for long listening sessions. Battery life is as advertised.','approved'),
(8,3,5,'My skin looks noticeably brighter after two weeks of use. Will repurchase.','approved'),
(6,4,5,'Super comfortable sneakers, true to size and great for daily wear.','approved'),
(14,3,4,'Beautiful craftsmanship, the leather smells amazing and fits my laptop perfectly.','approved');

INSERT INTO newsletter_subscribers (email) VALUES
('subscriber1@example.com'), ('subscriber2@example.com');

-- Sample orders
INSERT INTO orders (order_number, user_id, address_id, coupon_id, subtotal, discount_amount, delivery_fee, total_amount, delivery_method, payment_method, order_status, assigned_staff_id, shipping_full_name, shipping_phone, shipping_address, shipping_city, shipping_province, shipping_postal_code, created_at) VALUES
('SC202608001', 3, 1, 1, 19900.00, 1990.00, 350.00, 18260.00, 'standard', 'cod', 'delivered', 2, 'Nimal Perera', '0771234569', '123 Galle Road', 'Colombo', 'Western', '00300', '2026-08-05 10:15:00'),
('SC202608002', 4, 2, NULL, 15900.00, 0.00, 350.00, 16250.00, 'express', 'card', 'shipped', 2, 'Kamala Silva', '0771234570', '45 Kandy Road', 'Kandy', 'Central', '20000', '2026-08-20 14:30:00'),
('SC202608003', 3, 1, NULL, 4900.00, 0.00, 350.00, 5250.00, 'standard', 'cod', 'processing', 2, 'Nimal Perera', '0771234569', '123 Galle Road', 'Colombo', 'Western', '00300', '2026-08-28 09:00:00'),
('SC202609001', 4, 2, NULL, 27900.00, 0.00, 0.00, 27900.00, 'standard', 'cod', 'pending', NULL, 'Kamala Silva', '0771234570', '45 Kandy Road', 'Kandy', 'Central', '20000', '2026-09-01 16:45:00');

INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total) VALUES
(1,1,'Aurora Wireless Headphones',19900.00,1,19900.00),
(2,5,'Velora Tailored Blazer',12900.00,1,12900.00),
(2,7,'Crestline Cashmere Scarf',6200.00,1,6200.00);

INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, line_total) VALUES
(3,8,'Velora Radiance Serum',4900.00,1,4900.00),
(4,2,'Zenith Smart Watch Pro',27900.00,1,27900.00);

INSERT INTO payments (order_id, payment_method, amount, payment_status, transaction_ref, paid_at) VALUES
(1,'cod',18260.00,'paid','COD-SC202608001','2026-08-08 11:00:00'),
(2,'card',16250.00,'paid','TXN-9F82AC13','2026-08-20 14:31:00'),
(3,'cod',5250.00,'pending',NULL,NULL),
(4,'cod',27900.00,'pending',NULL,NULL);

INSERT INTO order_status_history (order_id, status, note, changed_by, created_at) VALUES
(1,'pending','Order placed',3,'2026-08-05 10:15:00'),
(1,'confirmed','Order confirmed',1,'2026-08-05 12:00:00'),
(1,'processing','Preparing your order',2,'2026-08-06 09:00:00'),
(1,'shipped','Order shipped via courier',2,'2026-08-07 10:00:00'),
(1,'delivered','Delivered to customer',2,'2026-08-08 11:00:00'),
(2,'pending','Order placed',4,'2026-08-20 14:30:00'),
(2,'confirmed','Order confirmed',1,'2026-08-20 15:00:00'),
(2,'processing','Preparing your order',2,'2026-08-21 09:00:00'),
(2,'shipped','Order shipped via courier',2,'2026-08-22 10:00:00'),
(3,'pending','Order placed',3,'2026-08-28 09:00:00'),
(3,'confirmed','Order confirmed',1,'2026-08-28 10:00:00'),
(3,'processing','Preparing your order',2,'2026-08-28 13:00:00'),
(4,'pending','Order placed',4,'2026-09-01 16:45:00');

-- Bump product total_sold / stock reflect above orders roughly already set in products table

INSERT INTO wishlist (user_id) VALUES (3), (4);
INSERT INTO wishlist_items (wishlist_id, product_id) VALUES (1,2),(1,9),(2,1),(2,14);

INSERT INTO cart (user_id) VALUES (3), (4);
INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (1,3,2),(2,12,1);

INSERT INTO notifications (user_id, audience, title, message, type, link, is_read, created_at) VALUES
(3,'customer','Order Delivered','Your order SC202608001 has been delivered. Enjoy!','order_delivered','/customer/order-details.php?id=1',0,'2026-08-08 11:00:00'),
(4,'customer','Order Shipped','Your order SC202608002 is on its way.','order_shipped','/customer/order-details.php?id=2',0,'2026-08-22 10:00:00'),
(3,'customer','Special Offer','Use code WELCOME10 for 10% off your next order.','offer',NULL,1,'2026-08-10 08:00:00'),
(NULL,'admin','New Order Received','A new order SC202609001 has been placed.','new_order','/admin/orders/details.php?id=4',0,'2026-09-01 16:45:00'),
(NULL,'admin','Low Stock Alert','Nordic Minimalist Sneakers is out of stock.','low_stock','/admin/inventory/index.php',0,'2026-08-25 07:00:00'),
(NULL,'staff','Order Assigned','You have been assigned order SC202608003.','order_assigned','/staff/orders.php?id=3',0,'2026-08-28 09:05:00');
