-- SQL Script to Update Orders Table for Pickup-Only E-commerce
-- Run this script to update your database schema

-- 1. Update the orders table status enum to include pickup-specific statuses
ALTER TABLE `orders` 
MODIFY COLUMN `status` ENUM('pending','confirmed','preparing','ready_to_pickup','cancelled','refunded') 
DEFAULT 'pending';

-- 2. Ensure order_status_history table exists (if not already created)
CREATE TABLE IF NOT EXISTS `order_status_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `status` ENUM('pending','confirmed','preparing','ready_to_pickup','cancelled','refunded') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`),
  KEY `idx_order_history` (`order_id`,`created_at`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `order_status_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Update existing orders status if needed (optional - uncomment if you want to migrate existing data)
-- UPDATE `orders` SET `status` = 'ready_to_pickup' WHERE `status` = 'delivered';
-- UPDATE `orders` SET `status` = 'preparing' WHERE `status` = 'shipping';

