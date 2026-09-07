-- =====================================================================
-- Pharmacy Management System Database Export
-- IBL12307: Web Development Laboratory Practical Final Project
-- =====================================================================

-- Create Database (Commented out for cloud/shared hosting compatibility where the DB is pre-selected)
-- CREATE DATABASE IF NOT EXISTS `pharmacy_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- USE `pharmacy_db`;

-- Disable foreign key checks temporarily to avoid drop conflicts
SET FOREIGN_KEY_CHECKS = 0;

-- Drop Tables if they exist
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `sales`;
DROP TABLE IF EXISTS `medicines`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table (Stores administrator and normal staff credentials)
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('Admin', 'Normal') NOT NULL DEFAULT 'Normal',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Categories Table (Medicines categorization)
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Medicines Table (Inventory records, links to Categories)
CREATE TABLE `medicines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL UNIQUE,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `stock_quantity` INT NOT NULL DEFAULT 0,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_medicines_category` 
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Sales Table (Transaction records, links Medicines and Users who registered the sale)
CREATE TABLE `sales` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `medicine_id` INT DEFAULT NULL,
  `user_id` INT DEFAULT NULL,
  `quantity` INT NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  `sale_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_sales_medicine` 
    FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_sales_user` 
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Activity Logs Table (System operations audit logging)
CREATE TABLE `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `activity` ENUM('Login', 'Logout', 'Add', 'Edit', 'Delete') NOT NULL,
  `description` TEXT NOT NULL,
  `log_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_logs_user` 
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
-- Seed Data Insertion
-- =====================================================================

-- Insert Default Users
-- admin / admin123
-- staff / staff123
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`) VALUES
('admin', 'admin@pharmacy.com', '$2y$10$CKmjUcsotJpGFjlzkyTj1empmeoj2z9t1fwyRYTeTwQjDQwDR9TLC', 'Admin'),
('staff', 'staff@pharmacy.com', '$2y$10$D57kVFrl6elcV/GUww0xEeHdPWCLOcFT5MK/6NrwOdM5fxQCKMQo6', 'Normal');

-- Insert Initial Categories
INSERT INTO `categories` (`name`, `description`) VALUES
('Analgesics', 'Pain relievers such as Paracetamol, Ibuprofen, and Aspirin.'),
('Antibiotics', 'Medicines that inhibit the growth of or destroy microorganisms.'),
('Antihistamines', 'Used to treat allergic reactions and nasal congestion.'),
('Cardiovascular', 'Drugs related to heart and blood vessels.'),
('Vitamins', 'Nutritional supplements and multi-vitamins.');

-- Insert Initial Medicines (Inventory items)
-- Image paths are relative to the project assets folder
INSERT INTO `medicines` (`category_id`, `name`, `description`, `price`, `stock_quantity`, `image_path`) VALUES
(1, 'Paracetamol 500mg', 'Standard painkiller and fever reducer.', 150.00, 200, 'paracetamol.jpg'),
(1, 'Ibuprofen 400mg', 'Nonsteroidal anti-inflammatory drug (NSAID) used to treat fever and mild-to-moderate pain.', 300.00, 150, 'ibuprofen.jpg'),
(2, 'Amoxicillin 500mg', 'Broad-spectrum antibiotic used for bacterial infections.', 1200.00, 80, 'amoxicillin.jpg'),
(3, 'Cetirizine 10mg', 'Antihistamine used to relieve allergy symptoms.', 250.00, 120, 'cetirizine.jpg'),
(5, 'Vitamin C 1000mg', 'Immune-boosting dietary supplement.', 500.00, 300, 'vitaminc.jpg');

-- Insert Initial Sales
INSERT INTO `sales` (`medicine_id`, `user_id`, `quantity`, `total_price`, `sale_date`) VALUES
(1, 2, 2, 300.00, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 2 DAY)),
(3, 2, 1, 1200.00, DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY)),
(5, 1, 5, 2500.00, CURRENT_TIMESTAMP);

-- Insert Initial Activity Logs
INSERT INTO `activity_logs` (`user_id`, `activity`, `description`) VALUES
(1, 'Login', 'Admin logged into the system successfully.'),
(2, 'Login', 'Staff logged into the system successfully.'),
(2, 'Add', 'Recorded a sale of Paracetamol 500mg (Qty: 2).'),
(1, 'Add', 'Recorded a sale of Vitamin C 1000mg (Qty: 5).');
