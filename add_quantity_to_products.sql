-- SQL Script to Add Quantity Column to Products Table
-- Run this script to add quantity functionality to your products table

-- Add quantity column to products table
ALTER TABLE `products` 
ADD COLUMN IF NOT EXISTS `quantity` INT(11) NOT NULL DEFAULT 0 AFTER `base_price`;

-- Update existing products to have quantity from inventory_lots if needed
UPDATE `products` p
SET p.quantity = (
    SELECT COALESCE(SUM(il.available_quantity), 0)
    FROM inventory_lots il
    WHERE il.product_id = p.product_id 
      AND il.status = 'available'
)
WHERE p.quantity = 0 OR p.quantity IS NULL;

