-- Migration: Add transaction_number to orders
-- Run this in your MySQL/MariaDB client (phpMyAdmin, mysql CLI, or via your deployment tool).
-- This adds a nullable transaction_number column and an index for faster lookups.

ALTER TABLE `orders`
  ADD COLUMN `transaction_number` VARCHAR(255) NULL AFTER `status_updated_at`;

-- Optional: add an index if you plan to search by transaction number often
CREATE INDEX `idx_orders_transaction_number` ON `orders` (`transaction_number`(100));

-- Rollback (manual): to remove the column
-- ALTER TABLE `orders` DROP INDEX `idx_orders_transaction_number`;
-- ALTER TABLE `orders` DROP COLUMN `transaction_number`;
