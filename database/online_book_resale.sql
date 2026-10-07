-- ============================================================
-- Online Book Resale & Purchase System
-- Database: online_book_resale
-- MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

CREATE DATABASE IF NOT EXISTS `online_book_resale`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `online_book_resale`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `messages`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `wishlist`;
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `book_images`;
DROP TABLE IF EXISTS `books`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `coupons`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `admins`;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Admins Table
-- ============================================================
CREATE TABLE `admins` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Users Table
-- ============================================================
CREATE TABLE `users` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`          VARCHAR(100) NOT NULL,
  `email`         VARCHAR(150) NOT NULL UNIQUE,
  `password`      VARCHAR(255) NOT NULL,
  `phone`         VARCHAR(20) DEFAULT NULL,
  `address`       TEXT DEFAULT NULL,
  `city`          VARCHAR(100) DEFAULT NULL,
  `state`         VARCHAR(100) DEFAULT NULL,
  `pincode`       VARCHAR(10) DEFAULT NULL,
  `profile_photo` VARCHAR(255) DEFAULT 'default_user.png',
  `is_blocked`    TINYINT(1) DEFAULT 0,
  `reset_token`   VARCHAR(100) DEFAULT NULL,
  `reset_expires` DATETIME DEFAULT NULL,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Categories Table
-- ============================================================
CREATE TABLE `categories` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `icon`        VARCHAR(50) DEFAULT 'bi-book',
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Books Table
-- ============================================================
CREATE TABLE `books` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `seller_id`        INT UNSIGNED NOT NULL,
  `category_id`      INT UNSIGNED NOT NULL,
  `title`            VARCHAR(300) NOT NULL,
  `author`           VARCHAR(200) NOT NULL,
  `isbn`             VARCHAR(20) DEFAULT NULL,
  `publisher`        VARCHAR(200) DEFAULT NULL,
  `edition`          VARCHAR(50) DEFAULT NULL,
  `language`         VARCHAR(50) DEFAULT 'English',
  `pages`            SMALLINT UNSIGNED DEFAULT NULL,
  `condition_type`   ENUM('New','Like New','Good','Acceptable','Old') DEFAULT 'Good',
  `description`      TEXT DEFAULT NULL,
  `original_price`   DECIMAL(10,2) DEFAULT 0.00,
  `selling_price`    DECIMAL(10,2) NOT NULL,
  `is_negotiable`    TINYINT(1) DEFAULT 0,
  `quantity`         SMALLINT UNSIGNED DEFAULT 1,
  `views`            INT UNSIGNED DEFAULT 0,
  `status`           ENUM('pending','approved','rejected','sold') DEFAULT 'pending',
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`seller_id`)   REFERENCES `users`(`id`)       ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)  ON DELETE RESTRICT,
  INDEX idx_status (`status`),
  INDEX idx_category (`category_id`),
  INDEX idx_seller (`seller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Book Images
-- ============================================================
CREATE TABLE `book_images` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `book_id`    INT UNSIGNED NOT NULL,
  `image_name` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Cart Table
-- ============================================================
CREATE TABLE `cart` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `book_id`    INT UNSIGNED NOT NULL,
  `quantity`   SMALLINT UNSIGNED DEFAULT 1,
  `added_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_cart (`user_id`,`book_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Wishlist Table
-- ============================================================
CREATE TABLE `wishlist` (
  `id`       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`  INT UNSIGNED NOT NULL,
  `book_id`  INT UNSIGNED NOT NULL,
  `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_wish (`user_id`,`book_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Coupons Table
-- ============================================================
CREATE TABLE `coupons` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code`           VARCHAR(30) NOT NULL UNIQUE,
  `discount_type`  ENUM('percent','fixed') DEFAULT 'percent',
  `discount_value` DECIMAL(8,2) NOT NULL,
  `min_order`      DECIMAL(10,2) DEFAULT 0.00,
  `max_discount`   DECIMAL(10,2) DEFAULT 0.00,
  `uses_limit`     INT UNSIGNED DEFAULT 999,
  `used_count`     INT UNSIGNED DEFAULT 0,
  `is_active`      TINYINT(1) DEFAULT 1,
  `expires_at`     DATE DEFAULT NULL,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Orders Table
-- ============================================================
CREATE TABLE `orders` (
  `id`                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_number`           VARCHAR(50) NOT NULL UNIQUE,
  `user_id`                INT UNSIGNED NOT NULL,
  `shipping_name`          VARCHAR(100) NOT NULL,
  `shipping_email`         VARCHAR(150) NOT NULL,
  `shipping_phone`         VARCHAR(20) NOT NULL,
  `shipping_address`       TEXT NOT NULL,
  `shipping_city`          VARCHAR(100) NOT NULL,
  `shipping_state`         VARCHAR(100) NOT NULL,
  `shipping_pincode`       VARCHAR(10) NOT NULL,
  `delivery_instructions`  TEXT DEFAULT NULL,
  `subtotal`               DECIMAL(12,2) NOT NULL,
  `discount`               DECIMAL(10,2) DEFAULT 0.00,
  `coupon_code`            VARCHAR(30) DEFAULT NULL,
  `total`                  DECIMAL(12,2) NOT NULL,
  `payment_method`         ENUM('cod','upi','credit_card','debit_card','net_banking') DEFAULT 'cod',
  `status`                 ENUM('pending','confirmed','packed','shipped','delivered','cancelled') DEFAULT 'pending',
  `cancel_reason`          TEXT DEFAULT NULL,
  `created_at`             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX idx_user (`user_id`),
  INDEX idx_status (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Order Items Table
-- ============================================================
CREATE TABLE `order_items` (
  `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id`  INT UNSIGNED NOT NULL,
  `book_id`   INT UNSIGNED NOT NULL,
  `quantity`  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `price`     DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`)  REFERENCES `books`(`id`)  ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Payments Table
-- ============================================================
CREATE TABLE `payments` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `order_id`       INT UNSIGNED NOT NULL,
  `user_id`        INT UNSIGNED NOT NULL,
  `amount`         DECIMAL(12,2) NOT NULL,
  `payment_method` VARCHAR(30) NOT NULL,
  `transaction_id` VARCHAR(100) DEFAULT NULL,
  `status`         ENUM('pending','success','failed','refunded') DEFAULT 'pending',
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Reviews Table
-- ============================================================
CREATE TABLE `reviews` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `book_id`     INT UNSIGNED NOT NULL,
  `user_id`     INT UNSIGNED NOT NULL,
  `order_id`    INT UNSIGNED NOT NULL,
  `rating`      TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `review_text` TEXT DEFAULT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_review (`book_id`,`user_id`),
  FOREIGN KEY (`book_id`)  REFERENCES `books`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Messages Table (Buyer-Seller Chat)
-- ============================================================
CREATE TABLE `messages` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `book_id`     INT UNSIGNED NOT NULL,
  `sender_id`   INT UNSIGNED NOT NULL,
  `receiver_id` INT UNSIGNED NOT NULL,
  `message`     TEXT NOT NULL,
  `is_read`     TINYINT(1) DEFAULT 0,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`book_id`)     REFERENCES `books`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_id`)   REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Notifications Table
-- ============================================================
CREATE TABLE `notifications` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `title`      VARCHAR(150) NOT NULL,
  `message`    TEXT NOT NULL,
  `type`       ENUM('info','success','warning','danger') DEFAULT 'info',
  `link`       VARCHAR(300) DEFAULT NULL,
  `is_read`    TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX idx_user_unread (`user_id`,`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Admin (password: Admin@123)
INSERT INTO `admins` (`name`,`email`,`password`) VALUES
('Admin User', 'admin@bookresale.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Categories
INSERT INTO `categories` (`name`,`description`,`icon`) VALUES
('Engineering',           'Civil, Mechanical, Electrical, Chemical Engineering books', 'bi-gear'),
('Computer Science',      'Programming, Algorithms, Databases, AI/ML, Web Development', 'bi-laptop'),
('Medical & Pharmacy',    'MBBS, BDS, Pharmacy, Nursing textbooks', 'bi-hospital'),
('Commerce & Management', 'Accounts, Finance, MBA, BBA, Economics', 'bi-briefcase'),
('Arts & Humanities',     'Literature, History, Philosophy, Political Science', 'bi-palette'),
('School Books',          'CBSE, ICSE, State Board books for classes 1-12', 'bi-backpack'),
('Competitive Exams',     'UPSC, SSC, GATE, CAT, NEET, JEE preparation', 'bi-award'),
('Fiction & Literature',  'Novels, Short Stories, Poetry, Fantasy, Thriller', 'bi-book-open'),
('Science',               'Physics, Chemistry, Biology, Mathematics textbooks', 'bi-flask'),
('Law',                   'LLB, LLM, Constitution, Criminal, Civil Law books', 'bi-journal-bookmark');

-- Sample Users (password: Test@1234 for all)
INSERT INTO `users` (`name`,`email`,`password`,`phone`,`city`,`state`,`pincode`) VALUES
('Aarav Sharma',  'aarav@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876543210', 'Mumbai',    'Maharashtra', '400001'),
('Priya Singh',   'priya@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876543211', 'Delhi',     'Delhi',       '110001'),
('Rohit Kumar',   'rohit@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876543212', 'Bangalore', 'Karnataka',   '560001'),
('Sneha Patel',   'sneha@example.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876543213', 'Ahmedabad', 'Gujarat',     '380001'),
('Vikram Nair',   'vikram@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '9876543214', 'Kochi',     'Kerala',      '682001');

-- Sample Books
INSERT INTO `books` (`seller_id`,`category_id`,`title`,`author`,`isbn`,`publisher`,`edition`,`language`,`pages`,`condition_type`,`description`,`original_price`,`selling_price`,`is_negotiable`,`quantity`,`views`,`status`) VALUES
(1, 2, 'Data Structures and Algorithms in Java', 'Robert Lafore', '978-0672324536', 'Sams Publishing', '2nd', 'English', 800, 'Good', 'Comprehensive guide to DSA with Java. Minor notes in margins. Great condition overall.', 850.00, 350.00, 1, 1, 245, 'approved'),
(2, 2, 'Introduction to Algorithms (CLRS)', 'Cormen, Leiserson, Rivest, Stein', '978-0262033848', 'MIT Press', '4th', 'English', 1312, 'Like New', 'Barely used. Perfect for GATE preparation and algorithmic understanding.', 4999.00, 1800.00, 0, 1, 521, 'approved'),
(3, 1, 'Strength of Materials', 'R.K. Bansal', '978-8180141416', 'Laxmi Publications', '5th', 'English', 720, 'Acceptable', 'Used for 2 semesters. Some highlighted text. All content readable.', 650.00, 200.00, 1, 2, 178, 'approved'),
(4, 7, 'Objective General Knowledge', 'S.B. Singh', '978-9326194044', 'Arihant', '2024', 'English', 1100, 'New', 'Brand new, unopened. Latest edition 2024.', 499.00, 399.00, 0, 3, 340, 'approved'),
(5, 8, 'The Alchemist', 'Paulo Coelho', '978-0062315007', 'HarperOne', '25th Anniversary', 'English', 208, 'Like New', 'Read once. Excellent condition. A timeless masterpiece.', 350.00, 150.00, 0, 1, 412, 'approved'),
(1, 2, 'Operating System Concepts (Dinosaur Book)', 'Silberschatz, Galvin, Gagne', '978-1119800644', 'Wiley', '10th', 'English', 976, 'Good', 'Good condition. Few pages folded. All text clear.', 1200.00, 450.00, 1, 1, 289, 'approved'),
(2, 9, 'H.C. Verma Concepts of Physics Part 1', 'H.C. Verma', '978-8177091878', 'Bharati Bhawan', '1st', 'English', 468, 'New', 'Brand new copy. Best for JEE preparation.', 280.00, 250.00, 0, 2, 567, 'approved'),
(3, 4, 'Financial Management', 'I.M. Pandey', '978-9386042163', 'Vikas Publishing', '12th', 'English', 960, 'Good', 'MBA student selling this after use. Good condition.', 950.00, 350.00, 1, 1, 134, 'approved');

-- Sample Book Images
INSERT INTO `book_images` (`book_id`,`image_name`,`is_primary`) VALUES
(1, 'books/dsa_java.jpg', 1),
(2, 'books/81ExrhgthGL._SY425_.jpg', 1),
(3, 'books/81I3S2dxNJL._SY425_.jpg', 1),
(4, 'books/81JDBNRhnKL._SY466_.jpg', 1),
(5, 'books/71E5-XTVqtL._SY385_.jpg', 1),
(6, 'books/81VfAlE0SNL._SL1500_.jpg', 1),
(7, 'books/eng.jpg', 1),
(8, 'books/quntitative.jpg', 1);

-- Sample Coupons
INSERT INTO `coupons` (`code`,`discount_type`,`discount_value`,`min_order`,`max_discount`,`uses_limit`,`expires_at`) VALUES
('BOOK10',    'percent', 10.00, 100.00,  500.00, 999, '2027-12-31'),
('FIRST50',   'fixed',   50.00,  99.00,  100.00, 999, '2027-12-31'),
('STUDENT20', 'percent', 20.00, 200.00, 1000.00, 500, '2027-06-30');
