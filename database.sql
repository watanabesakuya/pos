/**
 * YSEレジシステム - データベーススキーマ
 * 
 * ファイル構造:
 * /yse-pos/
 * └── database.sql
 */

-- データベース作成
CREATE DATABASE IF NOT EXISTS `yse_pos` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `yse_pos`;

-- ユーザーテーブル
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','manager','cashier') NOT NULL DEFAULT 'cashier',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 商品カテゴリテーブル（新規追加）
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 商品テーブル（新規追加）
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `tax_rate` decimal(4,2) NOT NULL DEFAULT 10.00,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 売上テーブル
CREATE TABLE IF NOT EXISTS `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amount` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `items_count` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `sale_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `payment_method` enum('cash','credit_card','other') NOT NULL DEFAULT 'cash',
  `status` enum('completed','refunded','voided') NOT NULL DEFAULT 'completed',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 売上明細テーブル
CREATE TABLE IF NOT EXISTS `sale_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `item_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `tax_rate` decimal(4,2) NOT NULL DEFAULT 10.00,
  `tax_amount` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 設定テーブル（新規追加）
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ログテーブル（新規追加）
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 初期データの挿入
INSERT INTO `users` (`username`, `password`, `display_name`, `role`) VALUES
('admin', '$2y$10$lx1AgJj6g5.ZW0t0y2OOROReQzgVLaVaBPJPCe0OJEDUUFx2HVkMq', '管理者', 'admin'); -- パスワード: admin123

-- カテゴリ初期データ
INSERT INTO `categories` (`name`, `description`) VALUES
('食品', '食料品カテゴリ'),
('飲料', '飲料カテゴリ'),
('雑貨', '日用雑貨カテゴリ');

-- 商品初期データ
INSERT INTO `products` (`name`, `category_id`, `price`, `tax_rate`, `description`) VALUES
('サンドイッチ', 1, 300, 8.00, '具だくさんサンドイッチ'),
('おにぎり', 1, 150, 8.00, '塩むすび'),
('ペットボトル飲料', 2, 150, 10.00, '500mlペットボトル飲料'),
('缶コーヒー', 2, 130, 10.00, '缶コーヒー（HOT/COLD）'),
('ボールペン', 3, 100, 10.00, '黒ボールペン');

-- 設定初期データ
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('site_name', 'YSEレジシステム', 'サイト名'),
('tax_rate', '10', '標準消費税率（%）'),
('reduced_tax_rate', '8', '軽減税率（%）'),
('receipt_header', 'YSEマート', 'レシートヘッダー'),
('receipt_footer', 'ご利用ありがとうございました', 'レシートフッター'),
('business_hours', '9:00-21:00', '営業時間');
