

USE smartcart_db;

-- 1. New table to hold the color options a product can be sold in
CREATE TABLE IF NOT EXISTS product_colors (
    color_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    color_name VARCHAR(50) NOT NULL,
    color_hex VARCHAR(7) NOT NULL DEFAULT '#000000',
    sort_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 2. Track which color a customer picked, on both the cart and the final order
ALTER TABLE cart_items
    ADD COLUMN selected_color VARCHAR(50) DEFAULT NULL AFTER quantity;

ALTER TABLE order_items
    ADD COLUMN selected_color VARCHAR(50) DEFAULT NULL AFTER quantity;

-- 3. Allow the same product to sit in the cart twice with two different colors
ALTER TABLE cart_items
    DROP INDEX uniq_cart_product,
    ADD UNIQUE KEY uniq_cart_product_color (cart_id, product_id, selected_color);

-- Done! Optionally try it out immediately with a couple of demo colors:
-- INSERT INTO product_colors (product_id, color_name, color_hex, sort_order) VALUES
-- (1, 'Midnight Black', '#1a1a1a', 0),
-- (1, 'Champagne Gold', '#d4af6a', 1),
-- (1, 'Pearl White', '#f5f4f1', 2);
