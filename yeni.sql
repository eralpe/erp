-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1:3306
-- Üretim Zamanı: 09 Eyl 2025, 20:41:14
-- Sunucu sürümü: 8.2.0
-- PHP Sürümü: 8.2.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `yeni`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `accounting_transactions`
--

DROP TABLE IF EXISTS `accounting_transactions`;
CREATE TABLE IF NOT EXISTS `accounting_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `account_code` varchar(10) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_type` enum('debit','credit') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `related_id` int DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `accounts`
--

DROP TABLE IF EXISTS `accounts`;
CREATE TABLE IF NOT EXISTS `accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `account_code` varchar(10) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `advances`
--

DROP TABLE IF EXISTS `advances`;
CREATE TABLE IF NOT EXISTS `advances` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personel_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'TRY',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `amount_try` decimal(10,2) NOT NULL DEFAULT '0.00',
  `category_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `personel_id` (`personel_id`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cash_accounts`
--

DROP TABLE IF EXISTS `cash_accounts`;
CREATE TABLE IF NOT EXISTS `cash_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `description` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `cash_accounts`
--

INSERT INTO `cash_accounts` (`id`, `name`, `currency`, `balance`, `created_at`, `description`) VALUES
(13, 'Ana Kasa', 'TRY', 110900.00, '2025-09-04 06:49:18', ''),
(14, 'Dolar Hesabı', 'USD', 5000.00, '2025-09-04 06:49:18', ''),
(15, 'Euro Hesabı', 'EUR', 250.00, '2025-09-04 06:49:18', '');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cash_transactions`
--

DROP TABLE IF EXISTS `cash_transactions`;
CREATE TABLE IF NOT EXISTS `cash_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cash_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `transaction_type` enum('income','expense') NOT NULL,
  `currency` varchar(3) NOT NULL,
  `amount_try` decimal(15,2) NOT NULL,
  `type` enum('in','out') NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `category_id` int DEFAULT NULL,
  `account_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_id` (`cash_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `cash_transactions`
--

INSERT INTO `cash_transactions` (`id`, `cash_id`, `amount`, `transaction_type`, `currency`, `amount_try`, `type`, `description`, `created_at`, `category_id`, `account_id`) VALUES
(15, 13, 155.00, 'income', 'TRY', 155.00, 'in', '', '2025-09-03 18:09:55', 2, 0),
(18, 0, 500.00, 'income', 'TRY', 500.00, '', 'Fatura Ödemesi: INV', '2025-09-04 11:45:28', NULL, 15),
(19, 0, 500.00, 'income', 'TRY', 500.00, '', 'Fatura Ödemesi: INV', '2025-09-04 11:46:04', NULL, 13),
(20, 0, 10000.00, 'income', 'TRY', 10000.00, '', 'Fatura Ödemesi: INV', '2025-09-04 11:46:32', NULL, 13),
(21, 0, 10.00, 'income', 'USD', 340.00, '', 'Fatura Ödemesi: INVINV001', '2025-09-04 13:56:39', NULL, 15),
(22, 0, 10.00, 'income', 'EUR', 380.00, '', 'Fatura Ödemesi: INVINV001', '2025-09-04 13:56:48', NULL, 15),
(23, 0, 25.00, 'income', 'USD', 850.00, '', 'Fatura Ödemesi: INVINV002', '2025-09-04 19:07:45', NULL, 13),
(24, 0, 1000.00, 'income', 'TRY', 1000.00, '', 'Fatura Ödemesi: INVINV002', '2025-09-04 19:07:58', NULL, 13),
(25, 0, 250.00, 'income', 'TRY', 250.00, '', 'Fatura Ödemesi: INVINV002', '2025-09-04 19:08:06', NULL, 13),
(26, 0, 2500.00, 'income', '', 0.00, '', 'Kredi taksit ödemesi (ID: 13)', '2025-09-03 21:00:00', NULL, 13),
(27, 0, 2000.00, 'income', '', 0.00, '', 'Kredi taksit ödemesi (ID: 1)', '2025-09-03 21:00:00', NULL, 15),
(28, 0, 2500.00, 'income', '', 0.00, '', 'Kredi taksit ödemesi (ID: 7)', '2025-09-03 21:00:00', NULL, 13),
(29, 0, 2500.00, 'income', '', 0.00, '', 'Kredi taksit ödemesi (ID: 14)', '2025-09-03 21:00:00', NULL, 13),
(30, 0, 2000.00, 'income', '', 0.00, '', 'Kredi taksit ödemesi (ID: 8)', '2025-09-03 21:00:00', NULL, 13),
(31, 0, 15000.00, 'income', '', 0.00, '', 'Personel maaşı (ID: 1)', '2025-09-01 07:00:00', NULL, 1),
(32, 0, 5000.00, 'income', '', 0.00, '', 'Personel avansı (ID: 1)', '2025-09-03 11:00:00', NULL, 1),
(33, 0, 4500.00, 'income', '', 0.00, '', 'Personel advance ödemesi (ID: 3)', '2025-09-03 21:00:00', NULL, 16),
(34, 0, 2000.00, 'income', '', 0.00, '', 'Kredi taksit ödemesi (ID: 2)', '2025-09-03 21:00:00', NULL, 13),
(35, 0, 7000.00, 'income', '', 0.00, '', 'Personel advance ödemesi (ID: 5)', '2025-09-06 21:00:00', NULL, 13),
(36, 0, 7000.00, 'income', '', 0.00, '', 'Personel salary ödemesi (ID: 5)', '2025-09-08 21:00:00', NULL, 13),
(37, 0, 6000.00, 'income', '', 0.00, '', 'Personel advance ödemesi (ID: 6)', '2025-09-06 21:00:00', NULL, 13),
(38, 0, 7000.00, 'income', '', 0.00, '', 'Personel advance ödemesi (ID: 7)', '2025-09-06 21:00:00', NULL, 13);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('income','expense','both') NOT NULL DEFAULT 'both',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `categories`
--

INSERT INTO `categories` (`id`, `name`, `type`, `created_at`) VALUES
(1, 'Maaş', 'expense', '2025-09-02 09:21:03'),
(2, 'Satış Geliri', 'income', '2025-09-02 09:21:03'),
(3, 'Kira', 'expense', '2025-09-02 09:21:03'),
(4, 'Diğer', 'both', '2025-09-02 09:21:03'),
(5, 'Maaş Avansı', 'expense', '2025-09-02 11:42:39'),
(6, 'Fatura Ödemesi', 'expense', '2025-09-02 12:06:06'),
(7, 'Satış Ödemesi', 'income', '2025-09-02 14:58:38'),
(8, 'Transfer Gideri', 'expense', '2025-09-03 19:39:09'),
(9, 'Transfer Geliri', 'income', '2025-09-03 19:39:09'),
(19, 'Elektronik', 'both', '2025-09-06 13:40:07'),
(20, 'Gıda', 'both', '2025-09-06 13:40:07'),
(21, 'eeetdfs', 'both', '2025-09-09 13:22:14');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sender_id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `sender_id`, `receiver_id`, `message`, `is_read`, `created_at`) VALUES
(1, 1, 2, 'Merhaba, nasılsın?', 1, '2025-09-07 08:18:00'),
(2, 2, 1, 'İyiyim, sen nasılsın?', 1, '2025-09-07 08:18:00'),
(3, 1, 3, 'Toplantı ne zaman?', 1, '2025-09-07 08:18:00'),
(7, 2, 1, 'göt', 1, '2025-09-08 07:26:37'),
(6, 2, 1, 'test', 1, '2025-09-08 07:26:30'),
(8, 2, 4, 'merhaba', 0, '2025-09-08 07:29:54'),
(9, 2, 1, 'zd', 1, '2025-09-08 08:40:16'),
(10, 2, 1, 'd', 1, '2025-09-08 08:40:19'),
(11, 2, 1, 'm', 1, '2025-09-08 08:40:23'),
(12, 2, 1, 'test', 1, '2025-09-08 08:47:14'),
(13, 2, 1, 'test', 1, '2025-09-08 08:47:15'),
(14, 2, 1, 'tets', 1, '2025-09-08 08:47:16'),
(15, 2, 1, 'test', 1, '2025-09-08 09:26:31'),
(16, 2, 1, 'test', 1, '2025-09-08 17:27:57');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `credits`
--

DROP TABLE IF EXISTS `credits`;
CREATE TABLE IF NOT EXISTS `credits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `interest_rate` decimal(5,2) DEFAULT NULL,
  `due_date` date NOT NULL,
  `installment_count` int NOT NULL,
  `currency` varchar(3) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `credits`
--

INSERT INTO `credits` (`id`, `bank_name`, `amount`, `interest_rate`, `due_date`, `installment_count`, `currency`, `description`, `created_at`) VALUES
(1, '', 10000.00, NULL, '0000-00-00', 0, '', 'Test Credit 1', '2025-09-01 07:00:00'),
(2, '', 5000.00, NULL, '0000-00-00', 0, '', 'Test Credit 2', '2025-09-02 09:00:00');

--
-- Tetikleyiciler `credits`
--
DROP TRIGGER IF EXISTS `after_credit_insert`;
DELIMITER $$
CREATE TRIGGER `after_credit_insert` AFTER INSERT ON `credits` FOR EACH ROW BEGIN
    IF NEW.due_date IS NOT NULL AND NEW.due_date >= CURDATE() THEN
        INSERT INTO reminders (type, related_id, due_date, status)
        VALUES ('credit', NEW.id, NEW.due_date, 'pending');
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `credit_installments`
--

DROP TABLE IF EXISTS `credit_installments`;
CREATE TABLE IF NOT EXISTS `credit_installments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `credit_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `paid_amount` decimal(10,2) DEFAULT '0.00',
  `paid_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_id` (`credit_id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `credit_installments`
--

INSERT INTO `credit_installments` (`id`, `credit_id`, `amount`, `due_date`, `status`, `paid_amount`, `paid_date`) VALUES
(1, 1, 2000.00, '2025-09-10', 'paid', 2000.00, '2025-09-04 00:00:00'),
(2, 1, 2000.00, '2025-10-10', 'paid', 2000.00, '2025-09-04 00:00:00'),
(3, 1, 2000.00, '2025-11-10', 'pending', 0.00, NULL),
(4, 1, 2000.00, '2025-12-10', 'pending', 0.00, NULL),
(5, 1, 2000.00, '2026-01-10', 'pending', 0.00, NULL),
(6, 2, 2500.00, '2025-09-05', 'paid', 2500.00, '2025-09-04 00:00:00'),
(7, 2, 2500.00, '2025-10-05', 'paid', 2500.00, '2025-09-04 00:00:00'),
(8, 1, 2000.00, '2025-09-10', 'paid', 2000.00, '2025-09-04 00:00:00'),
(9, 1, 2000.00, '2025-10-10', 'pending', 0.00, NULL),
(10, 1, 2000.00, '2025-11-10', 'pending', 0.00, NULL),
(11, 1, 2000.00, '2025-12-10', 'pending', 0.00, NULL),
(12, 1, 2000.00, '2026-01-10', 'pending', 0.00, NULL),
(13, 2, 2500.00, '2025-09-05', 'paid', 2500.00, '2025-09-04 00:00:00'),
(14, 2, 2500.00, '2025-10-05', 'paid', 2500.00, '2025-09-04 00:00:00');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `credit_payments`
--

DROP TABLE IF EXISTS `credit_payments`;
CREATE TABLE IF NOT EXISTS `credit_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `credit_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `payment_date` date NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `credit_id` (`credit_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `credit_payments`
--

INSERT INTO `credit_payments` (`id`, `credit_id`, `amount`, `currency`, `payment_date`, `description`, `created_at`) VALUES
(1, 1, 1000.00, 'USD', '2025-09-02', '', '2025-09-02 08:56:41'),
(2, 1, 100.00, 'TRY', '2025-09-02', '', '2025-09-02 18:23:09'),
(3, 2, 5000.00, 'TRY', '2025-09-02', '', '2025-09-03 13:15:34');

--
-- Tetikleyiciler `credit_payments`
--
DROP TRIGGER IF EXISTS `after_credit_payment_insert`;
DELIMITER $$
CREATE TRIGGER `after_credit_payment_insert` AFTER INSERT ON `credit_payments` FOR EACH ROW BEGIN
    UPDATE reminders 
    SET status = 'dismissed'
    WHERE type = 'credit' AND related_id = NEW.credit_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `address` text,
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `customers`
--

INSERT INTO `customers` (`id`, `name`, `contact`, `address`, `balance`, `created_at`, `email`, `phone`, `status`) VALUES
(1, 'eralp ekemen', '05310281767', 'ALÇITEPE', -300.00, '2025-09-06 06:18:02', 'eralp.ekemen@sabl.com.tr', '05310281767', 'inactive'),
(2, 'test', NULL, 'gfhlğşfögöş yturkey', 150.00, '2025-09-06 06:54:28', 'test@test.com', '05378502174', 'active');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `customer_transactions`
--

DROP TABLE IF EXISTS `customer_transactions`;
CREATE TABLE IF NOT EXISTS `customer_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('credit','debit') NOT NULL,
  `description` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `customer_transactions`
--

INSERT INTO `customer_transactions` (`id`, `customer_id`, `amount`, `type`, `description`, `created_at`) VALUES
(1, 1, 150.00, 'debit', 'Manuel bakiye güncelleme', '2025-09-06 09:22:16'),
(2, 1, 50.00, 'credit', 'Manuel bakiye güncelleme', '2025-09-06 09:54:03'),
(3, 1, 200.00, 'debit', 'Manuel bakiye güncelleme', '2025-09-06 09:54:11'),
(4, 2, 150.00, 'credit', 'Manuel bakiye güncelleme', '2025-09-06 13:45:41');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `documents`
--

DROP TABLE IF EXISTS `documents`;
CREATE TABLE IF NOT EXISTS `documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `related_table` enum('invoices','sales_invoices','purchase_orders') NOT NULL,
  `related_id` int NOT NULL,
  `description` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `documents`
--

INSERT INTO `documents` (`id`, `file_name`, `file_path`, `related_table`, `related_id`, `description`, `created_at`, `updated_at`) VALUES
(2, 'invoice1.pdf', 'uploads/invoice1.pdf', 'invoices', 1, 'Fatura 1', '2025-09-01 10:00:00', NULL),
(3, 'sales_invoice1.pdf', 'uploads/sales_invoice1.pdf', 'sales_invoices', 1, 'Satış faturası 1', '2025-09-01 11:00:00', NULL),
(4, 'po1.pdf', 'uploads/po1.pdf', 'purchase_orders', 1, 'Satın alma siparişi 1', '2025-09-01 13:00:00', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `exchange_rates`
--

DROP TABLE IF EXISTS `exchange_rates`;
CREATE TABLE IF NOT EXISTS `exchange_rates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `currency_code` varchar(3) NOT NULL,
  `rate` decimal(10,4) NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `exchange_rates`
--

INSERT INTO `exchange_rates` (`id`, `currency_code`, `rate`, `updated_at`) VALUES
(1, 'TRY', 1.0000, '2025-09-02 08:39:58'),
(2, 'USD', 34.0000, '2025-09-02 08:39:58'),
(3, 'EUR', 37.0000, '2025-09-02 08:39:58'),
(10, 'EUR', 38.0000, '2025-09-04 06:49:37'),
(9, 'USD', 34.0000, '2025-09-04 06:49:37');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `inventory`
--

DROP TABLE IF EXISTS `inventory`;
CREATE TABLE IF NOT EXISTS `inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `category_id` int DEFAULT NULL,
  `unit` varchar(50) NOT NULL,
  `stock_quantity` decimal(10,2) DEFAULT '0.00',
  `min_stock_level` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`),
  KEY `category_id` (`category_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `inventory`
--

INSERT INTO `inventory` (`id`, `product_code`, `product_name`, `category_id`, `unit`, `stock_quantity`, `min_stock_level`, `created_at`, `updated_at`) VALUES
(1, '12345', 'test', NULL, '1', 6.00, 1.00, '2025-09-06 13:36:26', '2025-09-08 18:56:17'),
(2, 'P001', 'Laptop', 19, 'Adet', 20.00, 5.00, '2025-09-06 13:40:07', '2025-09-08 18:49:48'),
(3, 'P002', 'Ekmek', 20, 'Adet', 100.00, 20.00, '2025-09-06 13:40:07', '2025-09-06 13:40:38'),
(4, 'test', 'test', 20, 'adet', 2.00, 2.00, '2025-09-06 14:29:28', '2025-09-06 14:29:28');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `inventory_transactions`
--

DROP TABLE IF EXISTS `inventory_transactions`;
CREATE TABLE IF NOT EXISTS `inventory_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `type` enum('in','out') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `description` text,
  `related_id` int DEFAULT NULL,
  `related_type` enum('sale','purchase') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`id`, `product_id`, `type`, `quantity`, `description`, `related_id`, `related_type`, `created_at`) VALUES
(1, 2, 'in', 10.00, '', NULL, '', '2025-09-08 18:49:48'),
(2, 1, 'out', 9.00, '', NULL, '', '2025-09-08 18:51:19'),
(3, 1, 'in', 5.00, '', NULL, '', '2025-09-08 18:56:17');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `invoices`
--

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplier_id` int NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `amount_try` decimal(15,2) NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(25) NOT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_supplier_invoice` (`supplier_id`,`invoice_number`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `invoices`
--

INSERT INTO `invoices` (`id`, `supplier_id`, `invoice_number`, `amount`, `currency`, `amount_try`, `issue_date`, `due_date`, `description`, `created_at`, `status`, `updated_at`) VALUES
(5, 8, 'ELF0001', 7095.00, '', 0.00, '2025-08-31', '2025-09-07', NULL, '2025-09-05 19:47:17', 'pending', '2025-09-05 22:47:17');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `invoice_payments`
--

DROP TABLE IF EXISTS `invoice_payments`;
CREATE TABLE IF NOT EXISTS `invoice_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` int NOT NULL,
  `account_id` int NOT NULL,
  `transaction_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `amount_try` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `fk_invoice_payments_account_id` (`account_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `invoice_payments`
--

INSERT INTO `invoice_payments` (`id`, `invoice_id`, `account_id`, `transaction_id`, `amount`, `currency`, `description`, `created_at`, `amount_try`, `payment_date`) VALUES
(1, 3, 15, 0, 500.00, 'TRY', NULL, '2025-09-04 11:45:28', 500.00, '2025-09-04'),
(2, 6, 13, 0, 500.00, 'TRY', NULL, '2025-09-04 11:46:04', 500.00, '2025-09-04'),
(3, 5, 13, 0, 10000.00, 'TRY', NULL, '2025-09-04 11:46:32', 10000.00, '2025-09-04'),
(4, 3, 15, 0, 10.00, 'USD', NULL, '2025-09-04 13:56:39', 340.00, '2025-09-04'),
(5, 3, 15, 0, 10.00, 'EUR', NULL, '2025-09-04 13:56:48', 380.00, '2025-09-04'),
(6, 7, 13, 0, 25.00, 'USD', NULL, '2025-09-04 19:07:45', 850.00, '2025-09-04'),
(7, 7, 13, 0, 1000.00, 'TRY', NULL, '2025-09-04 19:07:58', 1000.00, '2025-09-04'),
(8, 7, 13, 0, 250.00, 'TRY', NULL, '2025-09-04 19:08:06', 250.00, '2025-09-04');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `logs`
--

DROP TABLE IF EXISTS `logs`;
CREATE TABLE IF NOT EXISTS `logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `action` varchar(50) NOT NULL,
  `details` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=198 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `logs`
--

INSERT INTO `logs` (`id`, `action`, `details`, `created_at`) VALUES
(1, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 19:42:21'),
(2, 'error', 'Tedarikçi güncelleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'updated_at\' in \'field list\', POST: {\"csrf_token\":\"105938fd0044b99f57243ef2925b47c7358453db12073f2ee68f857f3e1ead7e\",\"id\":\"7\",\"update_supplier\":\"1\",\"name\":\"RE\\u0130S GIDA\",\"balance\":\"20245.00\",\"contact_name\":\"MUSTAFA\",\"email\":\"\",\"phone\":\"\",\"address\":\"\",\"contact\":\"\"}', '2025-09-05 19:42:34'),
(3, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 19:42:34'),
(4, 'error', 'Tedarikçi güncelleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"105938fd0044b99f57243ef2925b47c7358453db12073f2ee68f857f3e1ead7e\",\"id\":\"7\",\"update_supplier\":\"1\",\"name\":\"RE\\u0130S GIDA\",\"balance\":\"20245.00\",\"contact_name\":\"MUSTAFA\",\"email\":\"\",\"phone\":\"\",\"address\":\"\",\"contact\":\"\"}', '2025-09-05 19:43:08'),
(5, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 19:43:08'),
(6, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 19:43:09'),
(7, 'error', 'Tedarikçi güncelleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'updated_at\' in \'field list\', POST: {\"csrf_token\":\"28edff962bb52f14e00adfc24e0149aa4a2c894e09fcb2ad89d7278c3e013ab9\",\"id\":\"7\",\"update_supplier\":\"1\",\"name\":\"RE\\u0130S GIDA\",\"balance\":\"20245.00\",\"contact_name\":\"MUSTAFA\",\"email\":\"\",\"phone\":\"\",\"address\":\"\",\"contact\":\"\"}', '2025-09-05 19:43:17'),
(8, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 19:43:17'),
(9, 'error', 'Fatura ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'updated_at\' in \'field list\', POST: {\"csrf_token\":\"4a27aac9f4dda76eaf3dd3542ad7de5b0b5576f11b2cf381c2e5b2622ce28a84\",\"supplier_id\":\"7\",\"add_invoice\":\"1\",\"invoice_number\":\"1\",\"amount\":\"7095\",\"issue_date\":\"2025-08-31\",\"due_date\":\"2025-09-07\"}', '2025-09-05 19:43:40'),
(10, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 19:43:40'),
(11, 'error', 'Fatura ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"4a27aac9f4dda76eaf3dd3542ad7de5b0b5576f11b2cf381c2e5b2622ce28a84\",\"supplier_id\":\"7\",\"add_invoice\":\"1\",\"invoice_number\":\"1\",\"amount\":\"7095\",\"issue_date\":\"2025-08-31\",\"due_date\":\"2025-09-07\"}', '2025-09-05 19:45:44'),
(12, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 19:45:44'),
(13, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 19:45:45'),
(14, 'info', 'Fatura #1 tedarikçi ID 7 için eklendi.', '2025-09-05 19:45:58'),
(15, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 19:45:58'),
(16, 'info', 'get_invoices.php: 7 için 1 fatura döndürüldü', '2025-09-05 19:46:01'),
(17, 'info', 'get_invoices.php: 7 için 1 fatura döndürüldü', '2025-09-05 19:46:01'),
(18, 'info', 'get_invoices.php: 7 için 1 fatura döndürüldü', '2025-09-05 19:46:06'),
(19, 'info', 'get_invoices.php: 7 için 1 fatura döndürüldü', '2025-09-05 19:46:06'),
(20, 'info', 'Fetched 0 suppliers and 3 cash accounts', '2025-09-05 19:46:34'),
(21, 'info', 'Tedarikçi REİS GIDA eklendi.', '2025-09-05 19:46:55'),
(22, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 19:46:55'),
(23, 'info', 'Fatura #ELF0001 tedarikçi ID 8 için eklendi.', '2025-09-05 19:47:17'),
(24, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 19:47:17'),
(25, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 19:47:20'),
(26, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 19:47:20'),
(27, 'info', 'Tedarikçi ID 8 güncellendi: REİS GIDA', '2025-09-05 19:47:49'),
(28, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 19:47:49'),
(29, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 19:48:02'),
(30, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 19:48:02'),
(31, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 19:49:06'),
(32, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 19:49:17'),
(33, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 19:49:22'),
(34, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 19:49:25'),
(35, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 19:54:02'),
(36, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 19:54:04'),
(37, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 19:54:04'),
(38, 'error', 'Ödeme ekleme hatası: SQLSTATE[23000]: Integrity constraint violation: 1048 Column \'invoice_id\' cannot be null, POST: {\"csrf_token\":\"b0c83de2354eb75af7687faf4f3e900f8aecd6d295d0a1fa86c31e1c67822811\",\"supplier_id\":\"8\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"8000\",\"account_id\":\"13\",\"payment_date\":\"2025-09-05\"}', '2025-09-05 19:54:19'),
(39, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 19:54:19'),
(40, 'error', 'Ödeme ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"b0c83de2354eb75af7687faf4f3e900f8aecd6d295d0a1fa86c31e1c67822811\",\"supplier_id\":\"8\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"8000\",\"account_id\":\"13\",\"payment_date\":\"2025-09-05\"}', '2025-09-05 20:19:18'),
(41, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 20:19:18'),
(42, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 20:19:19'),
(43, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 20:19:20'),
(44, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 20:19:20'),
(45, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 20:19:35'),
(46, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 20:19:38'),
(47, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 20:19:38'),
(48, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"fa13956655c30b48e060cbd4ba27084a44da5b12c869c0bee667c848a020f75b\",\"supplier_id\":\"8\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"8000\",\"account_id\":\"13\",\"payment_date\":\"2025-08-31\"}', '2025-09-05 20:19:51'),
(49, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 20:19:51'),
(50, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 20:22:33'),
(51, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 20:22:34'),
(52, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 20:22:34'),
(53, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"f0e65092c63515c7a1fef45fa035e255ce71e04b5ac6e69966829cf025d4120b\",\"supplier_id\":\"8\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"8000\",\"account_id\":\"13\",\"payment_date\":\"2025-08-31\"}', '2025-09-05 20:22:43'),
(54, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 20:22:43'),
(55, 'error', 'Ödeme ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"f0e65092c63515c7a1fef45fa035e255ce71e04b5ac6e69966829cf025d4120b\",\"supplier_id\":\"8\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"8000\",\"account_id\":\"13\",\"payment_date\":\"2025-08-31\"}', '2025-09-05 20:23:25'),
(56, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 20:23:25'),
(57, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 20:23:26'),
(58, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 20:23:29'),
(59, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 20:23:29'),
(60, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"9cc3a24933a72e9c9b856621ecc5549cea1449cdd213a3ee07d325317d1e60b1\",\"supplier_id\":\"8\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"8000\",\"account_id\":\"13\",\"payment_date\":\"2025-08-31\"}', '2025-09-05 20:23:38'),
(61, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 20:23:38'),
(62, 'error', 'Ödeme ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"9cc3a24933a72e9c9b856621ecc5549cea1449cdd213a3ee07d325317d1e60b1\",\"supplier_id\":\"8\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"8000\",\"account_id\":\"13\",\"payment_date\":\"2025-08-31\"}', '2025-09-05 20:24:52'),
(63, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 20:24:52'),
(64, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 20:24:52'),
(65, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 20:24:53'),
(66, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 20:24:53'),
(67, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"8114333eb6d45314e26c6ac89688aa9deee3af1cbbe7b65879e98f95d63b60fd\",\"supplier_id\":\"8\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"8000\",\"account_id\":\"13\",\"payment_date\":\"2025-08-31\"}', '2025-09-05 20:25:04'),
(68, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 20:25:04'),
(69, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 20:25:45'),
(70, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 20:25:46'),
(71, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 20:25:46'),
(72, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"1574a8dace4e0c4747d6ee34760971419b1f845fda442179b355561a845b29c8\",\"supplier_id\":\"8\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"8000\",\"account_id\":\"13\",\"payment_date\":\"2025-08-31\"}', '2025-09-05 20:25:57'),
(73, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 20:25:57'),
(74, 'error', 'Ödeme ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"1574a8dace4e0c4747d6ee34760971419b1f845fda442179b355561a845b29c8\",\"supplier_id\":\"8\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"8000\",\"account_id\":\"13\",\"payment_date\":\"2025-08-31\"}', '2025-09-05 20:28:21'),
(75, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 20:28:21'),
(76, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 20:28:23'),
(77, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 20:28:24'),
(78, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 20:28:26'),
(79, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-05 20:28:26'),
(80, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"6e5df041a269dd0a85fa885d18b2153089d95bb094c9ef5885feaf84807cef43\",\"supplier_id\":\"8\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"8000\",\"account_id\":\"13\",\"payment_date\":\"2025-09-05\"}', '2025-09-05 20:28:33'),
(81, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-05 20:28:33'),
(82, 'info', 'Fetched 5 inventory items', '2025-09-06 06:17:10'),
(83, 'info', 'Fetched 5 inventory items', '2025-09-06 06:17:13'),
(84, 'info', 'Fetched 5 inventory items', '2025-09-06 06:17:17'),
(85, 'info', 'Müşteri bakiyesi güncellendi: debit 150 TRY for customer ID 1', '2025-09-06 06:22:16'),
(86, 'info', 'Müşteri bakiyesi güncellendi: credit 50 TRY for customer ID 1', '2025-09-06 06:54:03'),
(87, 'info', 'Müşteri bakiyesi güncellendi: debit 200 TRY for customer ID 1', '2025-09-06 06:54:11'),
(88, 'info', 'Müşteri bakiyesi güncellendi: credit 150 TRY for customer ID 2', '2025-09-06 10:45:41'),
(89, 'info', 'Fetched 5 inventory items', '2025-09-06 10:54:25'),
(90, 'info', 'Fetched 5 inventory items', '2025-09-06 10:54:26'),
(91, 'info', 'Fetched 5 inventory items', '2025-09-06 10:54:28'),
(92, 'info', 'Fetched 5 inventory items', '2025-09-06 11:04:28'),
(93, 'info', 'Fetched 5 inventory items', '2025-09-06 11:04:35'),
(94, 'info', 'Fetched 5 inventory items', '2025-09-06 11:04:35'),
(95, 'info', 'Fetched 5 inventory items', '2025-09-06 11:04:36'),
(96, 'info', 'Fetched 5 inventory items', '2025-09-06 11:04:36'),
(97, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-08 18:45:00'),
(98, 'info', 'Total payments for personnel ID 3: 15000', '2025-09-08 20:34:14'),
(99, 'info', 'Total payments for personnel ID 4: 12000', '2025-09-08 20:34:14'),
(100, 'info', 'Fetched 2 personnel, 2 payments, 0 assets, and 1 leaves', '2025-09-08 20:34:14'),
(101, 'info', 'Total payments for personnel ID 3: 15000', '2025-09-09 07:02:42'),
(102, 'info', 'Total payments for personnel ID 4: 12000', '2025-09-09 07:02:42'),
(103, 'info', 'Fetched 2 personnel, 2 payments, 0 assets, and 1 leaves', '2025-09-09 07:02:42'),
(104, 'info', 'Personnel ID 3 deleted', '2025-09-09 07:02:45'),
(105, 'info', 'Total payments for personnel ID 4: 12000', '2025-09-09 07:02:45'),
(106, 'info', 'Fetched 1 personnel, 1 payments, 0 assets, and 0 leaves', '2025-09-09 07:02:45'),
(107, 'info', 'Personnel ID 4 deleted', '2025-09-09 07:02:48'),
(108, 'info', 'Fetched 0 personnel, 0 payments, 0 assets, and 0 leaves', '2025-09-09 07:02:48'),
(109, 'info', 'Personnel İSMAİL SEVİNÇ added with email: ismailsevinc@sabl.com.tr', '2025-09-09 07:03:53'),
(110, 'info', 'Total payments for personnel ID 5: 28000', '2025-09-09 07:03:53'),
(111, 'info', 'Fetched 1 personnel, 1 payments, 0 assets, and 0 leaves', '2025-09-09 07:03:53'),
(112, 'info', 'Total payments for personnel ID 5: 28000', '2025-09-09 07:03:56'),
(113, 'info', 'Fetched 1 personnel, 1 payments, 0 assets, and 0 leaves', '2025-09-09 07:03:56'),
(114, 'info', 'Payment advance of 7000 TRY for personnel ID 5', '2025-09-09 07:04:22'),
(115, 'info', 'Total payments for personnel ID 5: 35000', '2025-09-09 07:04:22'),
(116, 'info', 'Fetched 1 personnel, 1 payments, 0 assets, and 0 leaves', '2025-09-09 07:04:22'),
(117, 'error', 'Geçersiz CSRF token (personnel), Sent: 9bec2d8c0ac0c2e19e274896aa6f1fbfeaa422559b271e7128437f27745e2894, Expected: db39622942b9c043d6a9713c7e8c0f797779aabc8bb1cbb5f7f7a2e0b17414bf', '2025-09-09 07:45:22'),
(118, 'error', 'Hata: Geçersiz CSRF token., POST: {\"csrf_token\":\"9bec2d8c0ac0c2e19e274896aa6f1fbfeaa422559b271e7128437f27745e2894\",\"type\":\"add_payment\",\"personnel_id\":\"5\",\"payment_type\":\"advance\",\"amount\":\"7000\",\"account_id\":\"13\",\"payment_date\":\"2025-09-07\",\"currency\":\"TRY\",\"description\":\"\"}', '2025-09-09 07:45:22'),
(119, 'error', 'Geçersiz CSRF token (personnel), Sent: 9bec2d8c0ac0c2e19e274896aa6f1fbfeaa422559b271e7128437f27745e2894, Expected: db39622942b9c043d6a9713c7e8c0f797779aabc8bb1cbb5f7f7a2e0b17414bf', '2025-09-09 07:50:50'),
(120, 'error', 'Hata: Geçersiz CSRF token., POST: {\"csrf_token\":\"9bec2d8c0ac0c2e19e274896aa6f1fbfeaa422559b271e7128437f27745e2894\",\"type\":\"add_payment\",\"personnel_id\":\"5\",\"payment_type\":\"advance\",\"amount\":\"7000\",\"account_id\":\"13\",\"payment_date\":\"2025-09-07\",\"currency\":\"TRY\",\"description\":\"\"}', '2025-09-09 07:50:50'),
(121, 'info', 'Total payments for personnel ID 5: 35000', '2025-09-09 07:50:50'),
(122, 'info', 'Fetched 1 personnel, 1 payments, 0 assets, and 0 leaves', '2025-09-09 07:50:50'),
(123, 'info', 'Total payments for personnel ID 5: 35000', '2025-09-09 07:50:52'),
(124, 'info', 'Fetched 1 personnel, 1 payments, 0 assets, and 0 leaves', '2025-09-09 07:50:52'),
(125, 'info', 'Leave added for personnel ID 5 from 2025-09-08 to 2025-09-08', '2025-09-09 07:51:04'),
(126, 'info', 'Total payments for personnel ID 5: 35000', '2025-09-09 07:51:04'),
(127, 'info', 'Fetched 1 personnel, 1 payments, 0 assets, and 1 leaves', '2025-09-09 07:51:04'),
(128, 'info', 'Total payments for personnel ID 5: 28000', '2025-09-09 07:55:20'),
(129, 'info', 'Fetched 1 personnel, 1 payments, 0 assets, and 0 leaves', '2025-09-09 07:55:20'),
(130, 'info', 'Payment salary of 7000 TRY for personnel ID 5', '2025-09-09 07:55:34'),
(131, 'info', 'Total payments for personnel ID 5: 7000', '2025-09-09 07:55:34'),
(132, 'info', 'Fetched 1 personnel, 2 payments, 0 assets, and 0 leaves', '2025-09-09 07:55:34'),
(133, 'info', 'Leave added for personnel ID 5 from 2025-09-08 to 2025-09-08', '2025-09-09 07:56:43'),
(134, 'info', 'Total payments for personnel ID 5: 7000', '2025-09-09 07:56:43'),
(135, 'info', 'Fetched 1 personnel, 2 payments, 0 assets, and 1 leaves', '2025-09-09 07:56:43'),
(136, 'error', 'Geçersiz CSRF token (personnel), Sent: fba0cecdbaf08c22f96571b7a4bec75e3e687538ddabcc41f092667006feef06, Expected: 7777e4341af9a60444ef2158b5cd936fcfe8f49eb0d355dc6db6ab398ae1c3df', '2025-09-09 08:21:22'),
(137, 'error', 'Hata: Geçersiz CSRF token., POST: {\"csrf_token\":\"fba0cecdbaf08c22f96571b7a4bec75e3e687538ddabcc41f092667006feef06\",\"type\":\"add_leave\",\"personnel_id\":\"5\",\"start_date\":\"2025-09-08\",\"end_date\":\"2025-09-08\",\"reason\":\"\"}', '2025-09-09 08:21:22'),
(138, 'info', 'Remaining balance for personnel ID 5: 7000', '2025-09-09 08:21:22'),
(139, 'info', 'Fetched 1 personnel, 2 payments, 0 assets, and 0 leaves', '2025-09-09 08:21:22'),
(140, 'info', 'Remaining balance for personnel ID 5: 7000', '2025-09-09 08:21:25'),
(141, 'info', 'Fetched 1 personnel, 2 payments, 0 assets, and 0 leaves', '2025-09-09 08:21:25'),
(142, 'info', 'Fetched 0 personnel, 0 payments, 0 assets, and 0 leaves', '2025-09-09 08:21:43'),
(143, 'info', 'Personnel İSMAİL SEVİNÇ added with email: ismailsevinc@sabl.com.tr', '2025-09-09 08:22:19'),
(144, 'info', 'Remaining balance for personnel ID 6: 28000', '2025-09-09 08:22:19'),
(145, 'info', 'Fetched 1 personnel, 1 payments, 0 assets, and 0 leaves', '2025-09-09 08:22:19'),
(146, 'info', 'Payment advance of 6000 TRY for personnel ID 6', '2025-09-09 08:23:26'),
(147, 'info', 'Remaining balance for personnel ID 6: 22000', '2025-09-09 08:23:26'),
(148, 'info', 'Fetched 1 personnel, 1 payments, 0 assets, and 0 leaves', '2025-09-09 08:23:26'),
(149, 'info', 'Leave added for personnel ID 6 from 2025-09-03 to 2025-09-03', '2025-09-09 08:23:41'),
(150, 'info', 'Remaining balance for personnel ID 6: 22000', '2025-09-09 08:23:41'),
(151, 'info', 'Fetched 1 personnel, 1 payments, 0 assets, and 1 leaves', '2025-09-09 08:23:41'),
(152, 'info', 'Leave added for personnel ID 6 from 2025-09-08 to 2025-09-08', '2025-09-09 08:23:52'),
(153, 'info', 'Remaining balance for personnel ID 6: 22000', '2025-09-09 08:23:52'),
(154, 'info', 'Fetched 1 personnel, 1 payments, 0 assets, and 2 leaves', '2025-09-09 08:23:52'),
(155, 'info', 'Personnel BULUT BENEK added with email: bulutbenek@sabl.com.tr', '2025-09-09 08:25:07'),
(156, 'info', 'Remaining balance for personnel ID 7: 28000', '2025-09-09 08:25:07'),
(157, 'info', 'Remaining balance for personnel ID 6: 22000', '2025-09-09 08:25:07'),
(158, 'info', 'Fetched 2 personnel, 2 payments, 0 assets, and 2 leaves', '2025-09-09 08:25:07'),
(159, 'info', 'Payment advance of 7000 TRY for personnel ID 7', '2025-09-09 08:25:28'),
(160, 'info', 'Remaining balance for personnel ID 7: 21000', '2025-09-09 08:25:28'),
(161, 'info', 'Remaining balance for personnel ID 6: 22000', '2025-09-09 08:25:28'),
(162, 'info', 'Fetched 2 personnel, 2 payments, 0 assets, and 2 leaves', '2025-09-09 08:25:28'),
(163, 'error', 'Geçersiz CSRF token (personnel), Sent: e955aa1193bef621e024e9f56bb97b1f9588bc8f89b0161035f12b9db0d95da4, Expected: d0f3148196e5a4b5b9d0809ddd0d3878f74be66ad33ca057ca55b8c6b2915346', '2025-09-09 08:33:47'),
(164, 'error', 'Hata: Geçersiz CSRF token., POST: {\"csrf_token\":\"e955aa1193bef621e024e9f56bb97b1f9588bc8f89b0161035f12b9db0d95da4\",\"type\":\"add_payment\",\"personnel_id\":\"7\",\"payment_type\":\"advance\",\"amount\":\"7000\",\"account_id\":\"13\",\"payment_date\":\"2025-09-07\",\"currency\":\"TRY\",\"description\":\"\"}', '2025-09-09 08:33:47'),
(165, 'info', 'Remaining balance for personnel ID 7: 21000', '2025-09-09 08:33:47'),
(166, 'info', 'Remaining balance for personnel ID 6: 22000', '2025-09-09 08:33:47'),
(167, 'info', 'Fetched 2 personnel, 2 payments, 0 assets, 2 leaves, 0 overtime records', '2025-09-09 08:33:47'),
(168, 'info', 'Overtime added for personnel ID 7: 14.983333333333 hours, earning 1748.0555555556 TRY', '2025-09-09 08:34:09'),
(169, 'info', 'Remaining balance for personnel ID 7: 21000', '2025-09-09 08:34:09'),
(170, 'info', 'Remaining balance for personnel ID 6: 22000', '2025-09-09 08:34:09'),
(171, 'info', 'Fetched 2 personnel, 2 payments, 0 assets, 2 leaves, 1 overtime records', '2025-09-09 08:34:09'),
(172, 'error', 'Invalid CSRF token: Sent: d0f3148196e5a4b5b9d0809ddd0d3878f74be66ad33ca057ca55b8c6b2915346, Expected: 9b16707267e1614575f46d1735c23bc488c66b3697ffa1bec9f689ac6b22b829', '2025-09-09 09:37:04'),
(173, 'error', 'Hata: Geçersiz CSRF token., POST: {\"csrf_token\":\"d0f3148196e5a4b5b9d0809ddd0d3878f74be66ad33ca057ca55b8c6b2915346\",\"type\":\"add_overtime\",\"personnel_id\":\"7\",\"work_date\":\"2025-09-08\",\"start_time\":\"09:00\",\"end_time\":\"23:59\",\"description\":\"\"}', '2025-09-09 09:37:04'),
(174, 'error', 'Invalid CSRF token: Sent: d0f3148196e5a4b5b9d0809ddd0d3878f74be66ad33ca057ca55b8c6b2915346, Expected: 9b16707267e1614575f46d1735c23bc488c66b3697ffa1bec9f689ac6b22b829', '2025-09-09 09:39:35'),
(175, 'error', 'Hata: Geçersiz CSRF token., POST: {\"csrf_token\":\"d0f3148196e5a4b5b9d0809ddd0d3878f74be66ad33ca057ca55b8c6b2915346\",\"type\":\"add_overtime\",\"personnel_id\":\"7\",\"work_date\":\"2025-09-08\",\"start_time\":\"09:00\",\"end_time\":\"23:59\",\"description\":\"\"}', '2025-09-09 09:39:35'),
(176, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-09 11:09:07'),
(177, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-09 11:09:08'),
(178, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-09 11:17:40'),
(179, 'error', 'Hata: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'WHERE issue_date BETWEEN \'2025-09-01\' AND \'2025-09-07\' ORDER BY issue_date DESC\' at line 1, POST: {\"csrf_token\":\"9b16707267e1614575f46d1735c23bc488c66b3697ffa1bec9f689ac6b22b829\",\"type\":\"export_pdf\",\"export_type\":\"details\",\"personnel_id\":\"7\",\"period\":\"weekly\",\"start_date\":\"2025-09-01\",\"end_date\":\"2025-09-07\"}', '2025-09-09 13:15:29'),
(180, 'error', 'Hata: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'WHERE issue_date BETWEEN \'2025-09-01\' AND \'2025-09-07\' ORDER BY issue_date DESC\' at line 1, POST: {\"csrf_token\":\"e1884a2c352e4c8b7d8cede638a69f3729a362df3e7be1bb2d89887da006bb65\",\"type\":\"export_pdf\",\"export_type\":\"details\",\"personnel_id\":\"6\",\"period\":\"weekly\",\"start_date\":\"2025-09-01\",\"end_date\":\"2025-09-07\"}', '2025-09-09 13:15:41'),
(181, 'error', 'Invalid CSRF token: Sent: e1884a2c352e4c8b7d8cede638a69f3729a362df3e7be1bb2d89887da006bb65, Expected: dec4fb2ac1e7acef7150b6b90ffd8ef396d9e4c58cb64f428d95ab816e205bee', '2025-09-09 13:17:03'),
(182, 'error', 'Hata: Geçersiz CSRF token., POST: {\"csrf_token\":\"e1884a2c352e4c8b7d8cede638a69f3729a362df3e7be1bb2d89887da006bb65\",\"type\":\"export_pdf\",\"export_type\":\"details\",\"personnel_id\":\"6\",\"period\":\"weekly\",\"start_date\":\"2025-09-01\",\"end_date\":\"2025-09-07\"}', '2025-09-09 13:17:03'),
(183, 'info', 'Personnel test added with email: test@test.com', '2025-09-09 13:17:55'),
(184, 'info', 'Personnel ID 8 deleted', '2025-09-09 13:18:02'),
(185, 'error', 'Hata: SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MySQL server version for the right syntax to use near \'WHERE issue_date BETWEEN \'2025-09-01\' AND \'2025-09-30\' ORDER BY issue_date DESC\' at line 1, POST: {\"csrf_token\":\"8a6db0fa69bff74535047418b7b7a52553444532c9407501f15d3504ca0e0ed4\",\"type\":\"export_pdf\",\"export_type\":\"details\",\"personnel_id\":\"7\",\"period\":\"monthly\",\"start_date\":\"2025-09-01\",\"end_date\":\"2025-09-30\"}', '2025-09-09 13:18:26'),
(186, 'error', 'Invalid CSRF token: Sent: 8a6db0fa69bff74535047418b7b7a52553444532c9407501f15d3504ca0e0ed4, Expected: 97715198a7daf32ebe16a049a78541509ac54bd3d0eba631f491d63ec20bf12c', '2025-09-09 13:20:36'),
(187, 'error', 'Hata: Geçersiz CSRF token., POST: {\"csrf_token\":\"8a6db0fa69bff74535047418b7b7a52553444532c9407501f15d3504ca0e0ed4\",\"type\":\"export_pdf\",\"export_type\":\"details\",\"personnel_id\":\"7\",\"period\":\"monthly\",\"start_date\":\"2025-09-01\",\"end_date\":\"2025-09-30\"}', '2025-09-09 13:20:36'),
(188, 'error', 'Invalid CSRF token: Sent: 97715198a7daf32ebe16a049a78541509ac54bd3d0eba631f491d63ec20bf12c, Expected: dbae9556abb48c44ae1f0d4921eb7ab6ffb2284a5a4b2afa71a80fdbaff570e7', '2025-09-09 13:21:28'),
(189, 'error', 'Hata: Geçersiz CSRF token., POST: {\"csrf_token\":\"97715198a7daf32ebe16a049a78541509ac54bd3d0eba631f491d63ec20bf12c\",\"type\":\"export_pdf\",\"export_type\":\"details\",\"personnel_id\":\"6\",\"period\":\"weekly\",\"start_date\":\"2025-09-01\",\"end_date\":\"2025-09-07\"}', '2025-09-09 13:21:28'),
(190, 'info', 'Fetched 3 documents', '2025-09-09 13:23:10'),
(191, 'info', 'Fetched 3 documents', '2025-09-09 13:35:16'),
(192, 'info', 'Fetched 3 documents', '2025-09-09 14:14:42'),
(193, 'info', 'Fetched 3 documents', '2025-09-09 15:38:29'),
(194, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-09 15:38:42'),
(195, 'info', 'get_invoices.php: 8 için 1 fatura döndürüldü', '2025-09-09 15:38:46'),
(196, 'info', 'Fetched 1 suppliers and 3 cash accounts', '2025-09-09 15:40:37'),
(197, 'info', 'Fetched 3 documents', '2025-09-09 20:26:03');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `type` enum('stock','chat','other') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_archived` tinyint(1) DEFAULT '0',
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_id` (`user_id`,`created_at`,`is_read`,`is_archived`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `is_read`, `created_at`, `is_archived`, `priority`) VALUES
(1, 1, 'stock', 'Ürün #123 stok seviyesi kritik (5 adet kaldı).', 0, '2025-09-06 16:52:31', 0, 'medium'),
(2, 1, 'chat', 'Ayşe Demir’den yeni mesaj: \"Sipariş durumu nedir?\"', 0, '2025-09-06 16:52:31', 0, 'medium'),
(3, 1, 'other', 'Sistem güncellemesi: Yeni özellikler eklendi.', 0, '2025-09-06 16:52:31', 0, 'medium'),
(4, 1, 'other', 'Test bildirimi: Sistem güncellendi.', 0, '2025-09-07 08:14:49', 0, 'medium'),
(5, 1, 'stock', 'Ürün #123 stok seviyesi kritik.', 0, '2025-09-07 08:14:49', 0, 'medium'),
(6, 1, 'chat', 'Yeni mesaj from Kate: test', 0, '2025-09-08 07:26:30', 0, 'medium'),
(7, 1, 'chat', 'Yeni mesaj from Kate: göt', 0, '2025-09-08 07:26:37', 0, 'medium'),
(8, 4, 'chat', 'Yeni mesaj from Kate: merhaba', 0, '2025-09-08 07:29:54', 0, 'medium'),
(9, 1, 'chat', 'Yeni mesaj from Kate: zd', 0, '2025-09-08 08:40:16', 0, 'medium'),
(10, 1, 'chat', 'Yeni mesaj from Kate: d', 0, '2025-09-08 08:40:19', 0, 'medium'),
(11, 1, 'chat', 'Yeni mesaj from Kate: m', 0, '2025-09-08 08:40:23', 0, 'medium'),
(12, 1, 'chat', 'Yeni mesaj from Kate: test', 0, '2025-09-08 08:47:14', 0, 'medium'),
(13, 1, 'chat', 'Yeni mesaj from Kate: test', 0, '2025-09-08 08:47:15', 0, 'medium'),
(14, 1, 'chat', 'Yeni mesaj from Kate: tets', 0, '2025-09-08 08:47:16', 0, 'medium'),
(15, 1, 'chat', 'Yeni mesaj from Kate: test', 0, '2025-09-08 09:26:31', 0, 'medium'),
(16, 1, 'chat', 'Yeni mesaj from Kate: test', 0, '2025-09-08 17:27:57', 0, 'medium'),
(17, 2, 'stock', 'Ürün bilgisi güncellendi: 1', 0, '2025-09-08 18:51:19', 0, 'low'),
(18, 2, 'stock', 'Ürün bilgisi güncellendi: 1', 0, '2025-09-08 18:56:17', 0, 'low');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_date` datetime NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_date`, `status`, `created_at`) VALUES
(1, 2, '2025-09-05 15:29:27', 'pending', '2025-09-05 15:29:27');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `order_details`
--

DROP TABLE IF EXISTS `order_details`;
CREATE TABLE IF NOT EXISTS `order_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `quantity`, `created_at`) VALUES
(1, 1, 1, 20, '2025-09-05 15:29:27'),
(2, 1, 2, 20, '2025-09-05 15:29:27');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplier_id` int NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_type` enum('invoice','balance','customer_balance') NOT NULL,
  `currency` varchar(3) NOT NULL,
  `payment_date` date NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `payments`
--

INSERT INTO `payments` (`id`, `supplier_id`, `invoice_id`, `amount`, `payment_type`, `currency`, `payment_date`, `description`, `created_at`, `customer_id`) VALUES
(1, 1, 2, 5000.00, '', 'TRY', '2025-09-02', '', '2025-09-02 11:58:18', NULL);

--
-- Tetikleyiciler `payments`
--
DROP TRIGGER IF EXISTS `after_payment_insert`;
DELIMITER $$
CREATE TRIGGER `after_payment_insert` AFTER INSERT ON `payments` FOR EACH ROW BEGIN
    DECLARE total_paid DECIMAL(15,2);
    DECLARE invoice_amount DECIMAL(15,2);
    
    -- Ödenen toplam tutarı hesapla
    SELECT SUM(amount * (SELECT rate FROM exchange_rates WHERE currency_code = p.currency ORDER BY updated_at DESC LIMIT 1))
    INTO total_paid
    FROM payments p
    WHERE p.invoice_id = NEW.invoice_id;
    
    -- Fatura tutarını al
    SELECT total_amount * (SELECT rate FROM exchange_rates WHERE currency_code = i.currency ORDER BY updated_at DESC LIMIT 1)
    INTO invoice_amount
    FROM invoices i
    WHERE i.id = NEW.invoice_id;
    
    -- Eğer fatura tamamen ödendiyse hatırlatıcıyı kapat
    IF total_paid >= invoice_amount THEN
        UPDATE reminders 
        SET status = 'dismissed'
        WHERE type = 'invoice' AND related_id = NEW.invoice_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `personnel`
--

DROP TABLE IF EXISTS `personnel`;
CREATE TABLE IF NOT EXISTS `personnel` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) DEFAULT NULL,
  `salary` decimal(15,2) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `personnel`
--

INSERT INTO `personnel` (`id`, `name`, `position`, `salary`, `email`, `phone`, `created_at`) VALUES
(6, 'İSMAİL SEVİNÇ', 'GARSON', 28000.00, 'ismailsevinc@sabl.com.tr', '05467713730', '2025-09-09 08:22:19'),
(7, 'BULUT BENEK', 'GARSON', 28000.00, 'bulutbenek@sabl.com.tr', '05518584689', '2025-09-09 08:25:07');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `personnel_advances`
--

DROP TABLE IF EXISTS `personnel_advances`;
CREATE TABLE IF NOT EXISTS `personnel_advances` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnel_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `issue_date` date NOT NULL,
  `currency` varchar(3) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `personnel_id` (`personnel_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `personnel_advances`
--

INSERT INTO `personnel_advances` (`id`, `personnel_id`, `amount`, `issue_date`, `currency`, `description`, `created_at`) VALUES
(4, 6, 6000.00, '2025-09-07', 'TRY', NULL, '2025-09-09 08:23:26'),
(5, 7, 7000.00, '2025-09-07', 'TRY', NULL, '2025-09-09 08:25:28');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `personnel_assets`
--

DROP TABLE IF EXISTS `personnel_assets`;
CREATE TABLE IF NOT EXISTS `personnel_assets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnel_id` int NOT NULL,
  `asset_name` varchar(100) NOT NULL,
  `assigned_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `personnel_id` (`personnel_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `personnel_leaves`
--

DROP TABLE IF EXISTS `personnel_leaves`;
CREATE TABLE IF NOT EXISTS `personnel_leaves` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnel_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `personnel_id` (`personnel_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `personnel_leaves`
--

INSERT INTO `personnel_leaves` (`id`, `personnel_id`, `start_date`, `end_date`, `reason`, `created_at`) VALUES
(4, 6, '2025-09-03', '2025-09-03', NULL, '2025-09-09 08:23:41'),
(5, 6, '2025-09-08', '2025-09-08', NULL, '2025-09-09 08:23:52');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `personnel_overtime`
--

DROP TABLE IF EXISTS `personnel_overtime`;
CREATE TABLE IF NOT EXISTS `personnel_overtime` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnel_id` int NOT NULL,
  `work_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `hours_worked` decimal(5,2) NOT NULL,
  `overtime_earning` decimal(15,2) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `personnel_id` (`personnel_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `personnel_overtime`
--

INSERT INTO `personnel_overtime` (`id`, `personnel_id`, `work_date`, `start_time`, `end_time`, `hours_worked`, `overtime_earning`, `description`, `created_at`) VALUES
(1, 7, '2025-09-08', '09:00:00', '23:59:00', 14.98, 1748.06, NULL, '2025-09-09 08:34:09');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `production`
--

DROP TABLE IF EXISTS `production`;
CREATE TABLE IF NOT EXISTS `production` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `raw_material_id` int NOT NULL,
  `raw_quantity` int NOT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `raw_material_id` (`raw_material_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `category` varchar(100) DEFAULT NULL,
  `production_status` enum('raw','finished','semi-finished') DEFAULT 'raw',
  `unit_type` enum('kg','ml','adet','gram','litre','metre','paket') DEFAULT 'adet',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `products`
--

INSERT INTO `products` (`id`, `name`, `sku`, `unit_price`, `cost_price`, `created_at`, `category`, `production_status`, `unit_type`) VALUES
(1, 'Ürün 1', 'PRD001', 100.00, 80.00, '2025-09-02 08:40:14', NULL, 'raw', 'adet'),
(2, 'Ürün 2', 'PRD002', 100.00, 80.00, '2025-09-02 08:40:14', NULL, 'raw', 'adet'),
(3, 'Un', 'UN001', 10.50, 8.00, '2025-09-09 16:41:48', 'Gıda', 'raw', 'kg'),
(4, 'Ekmek', 'EK001', 5.00, 4.00, '2025-09-09 16:41:48', 'Gıda', 'finished', 'adet'),
(5, 'Hamur', 'HM001', 3.00, 2.50, '2025-09-09 16:41:48', 'Gıda', 'semi-finished', 'kg'),
(6, 'Süt', 'ST001', 12.00, 9.50, '2025-09-09 16:41:48', 'İçecek', 'raw', 'litre'),
(7, 'Peynir', 'PY001', 25.00, 20.00, '2025-09-09 16:41:48', 'Süt Ürünleri', 'finished', 'paket');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `supplier_id` int NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'TRY',
  `amount_try` decimal(15,2) NOT NULL,
  `delivery_date` date DEFAULT NULL,
  `delivery_status` enum('pending','delivered') DEFAULT 'pending',
  `order_date` date NOT NULL,
  `expected_delivery_date` date NOT NULL,
  `status` enum('pending','delivered','cancelled') DEFAULT 'pending',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `supplier_id` (`supplier_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_id` (`purchase_order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `recipes`
--

DROP TABLE IF EXISTS `recipes`;
CREATE TABLE IF NOT EXISTS `recipes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `product_id` int NOT NULL,
  `total_cost` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `recipes`
--

INSERT INTO `recipes` (`id`, `name`, `product_id`, `total_cost`, `created_at`) VALUES
(1, 'ekmek reçete', 4, 99.00, '2025-09-09 16:47:46');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `recipe_details`
--

DROP TABLE IF EXISTS `recipe_details`;
CREATE TABLE IF NOT EXISTS `recipe_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `recipe_id` int NOT NULL,
  `raw_material_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_type` enum('kg','ml','adet','gram','litre','metre','paket') NOT NULL,
  `cost_per_unit` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `recipe_id` (`recipe_id`),
  KEY `raw_material_id` (`raw_material_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `recipe_details`
--

INSERT INTO `recipe_details` (`id`, `recipe_id`, `raw_material_id`, `quantity`, `unit_type`, `cost_per_unit`) VALUES
(5, 1, 6, 2, 'kg', 9.50),
(4, 1, 3, 10, 'kg', 8.00);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `reminders`
--

DROP TABLE IF EXISTS `reminders`;
CREATE TABLE IF NOT EXISTS `reminders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` enum('credit','invoice','salary') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `related_id` int NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','sent','dismissed') NOT NULL DEFAULT 'pending',
  `notification_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `title` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `reminders`
--

INSERT INTO `reminders` (`id`, `type`, `related_id`, `due_date`, `status`, `notification_date`, `created_at`, `title`) VALUES
(1, 'credit', 1, '2026-09-01', 'dismissed', NULL, '2025-09-02 08:51:51', NULL),
(2, 'invoice', 1, '2025-09-30', 'pending', '2025-09-02 14:57:11', '2025-09-02 09:01:08', NULL),
(3, 'salary', 1, '2025-09-02', 'dismissed', '2025-09-02 14:52:45', '2025-09-02 11:52:59', NULL),
(4, 'invoice', 2, '2025-09-10', 'pending', NULL, '2025-09-02 11:57:44', NULL),
(5, 'credit', 2, '2025-11-30', 'dismissed', NULL, '2025-09-03 13:15:22', NULL),
(6, 'invoice', 5, '2025-10-20', 'pending', NULL, '2025-09-04 09:09:24', 0);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'personel'),
(2, 'müdür'),
(3, 'muhasebeci'),
(4, 'yönetici'),
(5, 'admin');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `salary_advances`
--

DROP TABLE IF EXISTS `salary_advances`;
CREATE TABLE IF NOT EXISTS `salary_advances` (
  `id` int NOT NULL AUTO_INCREMENT,
  `personnel_id` int NOT NULL,
  `transaction_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `personnel_id` (`personnel_id`),
  KEY `transaction_id` (`transaction_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `salary_advances`
--

INSERT INTO `salary_advances` (`id`, `personnel_id`, `transaction_id`, `amount`, `currency`, `description`, `created_at`) VALUES
(1, 1, 0, 5000.00, 'TRY', '', '2025-09-02 18:22:07'),
(2, 1, 0, 2000.00, 'TRY', '', '2025-09-02 18:22:41'),
(3, 1, 0, 100.00, 'TRY', 'test', '2025-09-02 19:38:51');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sales`
--

DROP TABLE IF EXISTS `sales`;
CREATE TABLE IF NOT EXISTS `sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `customer_id` int NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'TRY',
  `created_by` int NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sales_invoices`
--

DROP TABLE IF EXISTS `sales_invoices`;
CREATE TABLE IF NOT EXISTS `sales_invoices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `inventory_id` int NOT NULL,
  `quantity` int NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'TRY',
  `amount_try` decimal(15,2) NOT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `inventory_id` (`inventory_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `sales_invoices`
--

INSERT INTO `sales_invoices` (`id`, `customer_id`, `inventory_id`, `quantity`, `invoice_number`, `amount`, `currency`, `amount_try`, `issue_date`, `due_date`, `status`, `description`, `created_at`) VALUES
(1, 1, 1, 10, 'INV-001', 0.00, 'TRY', 500.00, '2025-09-02', '2025-09-09', 'pending', NULL, '2025-09-02 19:15:11');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sales_payments`
--

DROP TABLE IF EXISTS `sales_payments`;
CREATE TABLE IF NOT EXISTS `sales_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sales_invoice_id` int NOT NULL,
  `transaction_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sales_invoice_id` (`sales_invoice_id`),
  KEY `transaction_id` (`transaction_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `warehouse_id` int NOT NULL,
  `quantity` int NOT NULL,
  `type` enum('in','out') NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `warehouse_id` (`warehouse_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `warehouse_id`, `quantity`, `type`, `description`, `created_at`) VALUES
(1, 1, 1, 100, 'in', NULL, '2025-09-09 16:41:48'),
(2, 2, 1, 50, 'in', NULL, '2025-09-09 16:41:48'),
(3, 3, 1, 30, 'in', NULL, '2025-09-09 16:41:48'),
(4, 4, 1, 200, 'in', NULL, '2025-09-09 16:41:48'),
(5, 5, 1, 20, 'in', NULL, '2025-09-09 16:41:48');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `stock_transactions`
--

DROP TABLE IF EXISTS `stock_transactions`;
CREATE TABLE IF NOT EXISTS `stock_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `inventory_id` int NOT NULL,
  `transaction_type` enum('entry','exit') NOT NULL,
  `quantity` int NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inventory_id` (`inventory_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `stock_transactions`
--

INSERT INTO `stock_transactions` (`id`, `inventory_id`, `transaction_type`, `quantity`, `description`, `created_at`) VALUES
(1, 1, 'exit', 5, '', '2025-09-03 13:09:58'),
(2, 1, 'exit', 45, '', '2025-09-04 22:16:41'),
(3, 1, 'exit', 45, '', '2025-09-04 22:16:46');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `balance` decimal(15,2) DEFAULT '0.00',
  `contact_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `contact` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `balance`, `contact_name`, `email`, `phone`, `address`, `created_at`, `contact`, `updated_at`) VALUES
(8, 'REİS GIDA', 28245.00, 'MUSTAFA', NULL, NULL, 'GELİBOLU', '2025-09-05 19:46:55', NULL, '2025-09-05 22:47:49');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `avatar` varchar(255) NOT NULL DEFAULT 'https://bootdey.com/img/Content/user_1.jpg',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `email`, `role`, `created_at`, `avatar`) VALUES
(1, 'admin', '$2y$10$KY7A2hyPj2EshTlfcTZ0he8kfsH0O2NLnxUMYwhrFJLU1yZLUyY6e', 'Yönetici', 'admin@example.com', 'admin', '2025-09-02 15:41:08', 'https://bootdey.com/img/Content/user_1.jpg'),
(2, 'Kate', '', '', 'kate@example.com', 'user', '2025-09-07 08:17:14', 'https://bootdey.com/img/Content/user_3.jpg'),
(4, 'take', '', '', 'kate@example.com', 'user', '2025-09-07 08:17:14', 'https://bootdey.com/img/Content/user_2.jpg');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_notification_preferences`
--

DROP TABLE IF EXISTS `user_notification_preferences`;
CREATE TABLE IF NOT EXISTS `user_notification_preferences` (
  `user_id` int NOT NULL,
  `notification_type` enum('stock','finance','chat','general') NOT NULL,
  `is_enabled` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`user_id`,`notification_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_id` (`role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `warehouses`
--

DROP TABLE IF EXISTS `warehouses`;
CREATE TABLE IF NOT EXISTS `warehouses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `location` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `warehouses`
--

INSERT INTO `warehouses` (`id`, `name`, `location`, `created_at`) VALUES
(1, 'Merkez Depo', 'İstanbul', '2025-09-02 08:40:14'),
(2, 'Ana Depo', 'İstanbul', '2025-09-09 16:41:48');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
