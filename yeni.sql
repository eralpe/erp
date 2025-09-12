-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1:3306
-- Üretim Zamanı: 12 Eyl 2025, 21:30:00
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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `assets`
--

DROP TABLE IF EXISTS `assets`;
CREATE TABLE IF NOT EXISTS `assets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `serial_number` varchar(50) DEFAULT NULL,
  `value` decimal(10,2) DEFAULT '0.00',
  `supplier_id` int DEFAULT NULL,
  `personnel_id` int DEFAULT NULL,
  `description` text,
  `status` enum('active','in_repair','deactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial_number` (`serial_number`),
  KEY `supplier_id` (`supplier_id`),
  KEY `personnel_id` (`personnel_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `cash_accounts`
--

INSERT INTO `cash_accounts` (`id`, `name`, `currency`, `balance`, `created_at`, `description`) VALUES
(1, 'ANA KASA', 'TRY', 0.00, '2025-09-12 21:18:15', '');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `cash_transactions`
--

DROP TABLE IF EXISTS `cash_transactions`;
CREATE TABLE IF NOT EXISTS `cash_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cash_id` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `amount_try` decimal(15,2) NOT NULL,
  `type` enum('in','out','debit') NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `category_id` int DEFAULT NULL,
  `account_id` int NOT NULL,
  `transaction_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_id` (`cash_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `description` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `address` text,
  `balance` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `credit_limit` decimal(10,2) DEFAULT '0.00',
  `due_days` int DEFAULT '15',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `customers`
--

INSERT INTO `customers` (`id`, `name`, `address`, `balance`, `created_at`, `email`, `phone`, `status`, `credit_limit`, `due_days`, `updated_at`) VALUES
(1, 'TEST', '', 0.00, '2025-09-12 21:18:41', 'test@test.com', '00000000000', 'active', 2000.00, 15, NULL);

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `invoices`
--

INSERT INTO `invoices` (`id`, `supplier_id`, `invoice_number`, `amount`, `currency`, `amount_try`, `issue_date`, `due_date`, `description`, `created_at`, `status`, `updated_at`) VALUES
(7, 3, '1', 560.00, '', 0.00, '2025-08-25', '2025-09-01', NULL, '2025-09-11 15:10:28', 'pending', '2025-09-12 16:25:30'),
(8, 2, '1', 2360.00, '', 0.00, '2025-09-07', '2025-09-14', NULL, '2025-09-11 15:34:53', 'pending', '2025-09-11 18:34:53'),
(9, 3, '2', 560.00, '', 0.00, '2025-09-01', '2025-09-08', NULL, '2025-09-11 15:36:55', 'pending', '2025-09-12 16:25:56'),
(10, 1, 'M012025000002230', 5250.00, '', 0.00, '2025-09-05', '2025-09-11', NULL, '2025-09-11 15:45:06', 'pending', '2025-09-11 18:45:06'),
(12, 1, 'm012025000002293', 1750.56, '', 0.00, '2025-09-12', '2025-09-19', NULL, '2025-09-12 13:48:20', 'pending', '2025-09-12 16:52:21');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `invoice_items`
--

DROP TABLE IF EXISTS `invoice_items`;
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `quantity`, `price`, `total`) VALUES
(4, 7, 3, 7, 15.00, 105.00),
(5, 7, 2, 14, 20.00, 280.00),
(6, 7, 1, 7, 25.00, 175.00),
(7, 9, 3, 7, 15.00, 105.00),
(8, 9, 2, 7, 20.00, 140.00),
(9, 9, 2, 7, 20.00, 140.00),
(10, 9, 1, 7, 25.00, 175.00),
(11, 12, 5, 48, 10.42, 500.16),
(12, 12, 5, 48, 10.42, 500.16),
(13, 12, 5, 24, 10.42, 250.08),
(14, 12, 4, 48, 10.42, 500.16);

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=253 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `logs`
--

INSERT INTO `logs` (`id`, `action`, `details`, `created_at`) VALUES
(1, 'info', 'Fetched 0 suppliers and 0 cash accounts', '2025-09-09 20:42:24'),
(2, 'info', 'Fetched 0 suppliers and 0 cash accounts', '2025-09-09 20:55:52'),
(3, 'info', 'Tedarikçi KEMAL ATEŞ eklendi (Bakiye: 5325.5 TRY).', '2025-09-09 20:58:05'),
(4, 'error', 'Tedarikçi ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"b9031092a671a735febdbc0a563756b9ca7006cabf0bf258115beda740327418\",\"add_supplier\":\"1\",\"name\":\"KEMAL ATE\\u015e\",\"balance\":\"5325.50\",\"currency\":\"TRY\",\"contact_name\":\"KEMAL ATE\\u015e\",\"email\":\"alaskan_17@hotmail.com\",\"phone\":\"00000000000\",\"city\":\"\\u00c7ANAKKALE\",\"district\":\"ECEABAT\"}', '2025-09-09 20:59:17'),
(5, 'info', 'Fetched 1 suppliers and 0 cash accounts', '2025-09-09 20:59:17'),
(6, 'info', 'Fetched 1 suppliers and 0 cash accounts', '2025-09-09 20:59:21'),
(7, 'info', 'get_invoices.php: 1 için 0 fatura döndürüldü', '2025-09-09 20:59:31'),
(8, 'info', 'Fatura #M012025000002230 tedarikçi ID 1 için eklendi.', '2025-09-09 21:00:13'),
(9, 'info', 'Fetched 1 suppliers and 0 cash accounts', '2025-09-09 21:00:13'),
(10, 'info', 'get_invoices.php: 1 için 1 fatura döndürüldü', '2025-09-09 21:00:23'),
(11, 'info', 'Fetched 1 suppliers and 0 cash accounts', '2025-09-09 21:01:25'),
(12, 'info', 'Fatura #M012025000002230 tedarikçi ID 1 için eklendi.', '2025-09-09 21:01:46'),
(13, 'info', 'Fetched 1 suppliers and 0 cash accounts', '2025-09-09 21:01:46'),
(14, 'info', 'get_invoices.php: 1 için 1 fatura döndürüldü', '2025-09-09 21:01:50'),
(15, 'info', 'Tedarikçi ID 1 güncellendi: KEMAL ATEŞ (Bakiye: 10602.5 TRY).', '2025-09-09 21:02:05'),
(16, 'info', 'Fetched 1 suppliers and 0 cash accounts', '2025-09-09 21:02:06'),
(17, 'info', 'Fetched 1 suppliers and 0 cash accounts', '2025-09-09 21:02:26'),
(18, 'info', 'Tedarikçi REİS GIDA eklendi (Bakiye: 20245 TRY).', '2025-09-09 21:03:21'),
(19, 'info', 'Fetched 2 suppliers and 0 cash accounts', '2025-09-09 21:03:23'),
(20, 'info', 'Fetched 2 suppliers and 0 cash accounts', '2025-09-09 21:03:24'),
(21, 'info', 'Fatura #2 tedarikçi ID 1 için eklendi.', '2025-09-09 21:03:42'),
(22, 'info', 'Fetched 2 suppliers and 0 cash accounts', '2025-09-09 21:03:42'),
(23, 'info', 'Fetched 2 suppliers and 0 cash accounts', '2025-09-09 21:03:44'),
(24, 'info', 'get_invoices.php: 1 için 2 fatura döndürüldü', '2025-09-09 21:03:45'),
(25, 'info', 'Fetched 0 active credits, 0 installments, and 0 payment history records', '2025-09-09 21:04:36'),
(26, 'transfer', 'From: 3, To: 1, Amount: 7675 TRY, Amount TRY: 7675', '2025-09-09 21:07:14'),
(27, 'transfer', 'From: 3, To: 1, Amount: 300 TRY, Amount TRY: 300', '2025-09-09 21:10:17'),
(28, 'info', 'Personnel KADİR BEHRAMLI added with email: test@test.com', '2025-09-09 21:59:49'),
(29, 'error', 'Hata: Bu e-posta zaten kayıtlı., POST: {\"csrf_token\":\"5b8db36c359b198af1f92387ce917fd4267460dbf559e357211132013751f6da\",\"type\":\"add_personnel\",\"name\":\"\\u0130SMA\\u0130L SEV\\u0130N\\u00c7\",\"position\":\"GARSON\",\"salary\":\"28000\",\"email\":\"test@test.com\",\"phone\":\"00000000000\"}', '2025-09-09 22:01:14'),
(30, 'error', 'Invalid CSRF token: Sent: 5b8db36c359b198af1f92387ce917fd4267460dbf559e357211132013751f6da, Expected: 8040cd443ba600a048121daf9c1e49b05a15f1cf01bb4dc4741546ff775874de', '2025-09-09 22:01:17'),
(31, 'error', 'Hata: Geçersiz CSRF token., POST: {\"csrf_token\":\"5b8db36c359b198af1f92387ce917fd4267460dbf559e357211132013751f6da\",\"type\":\"add_personnel\",\"name\":\"\\u0130SMA\\u0130L SEV\\u0130N\\u00c7\",\"position\":\"GARSON\",\"salary\":\"28000\",\"email\":\"test@test.com\",\"phone\":\"00000000000\"}', '2025-09-09 22:01:17'),
(32, 'error', 'Hata: Bu e-posta zaten kayıtlı., POST: {\"csrf_token\":\"8040cd443ba600a048121daf9c1e49b05a15f1cf01bb4dc4741546ff775874de\",\"type\":\"add_personnel\",\"name\":\"\\u0130SMA\\u0130L SEV\\u0130N\\u00c7\",\"position\":\"GARSON\",\"salary\":\"28000\",\"email\":\"test@test.com\",\"phone\":\"00000000000\"}', '2025-09-09 22:01:55'),
(33, 'info', 'Personnel İSMAİL SEVİNÇ added with email: test@gmail.com', '2025-09-09 22:02:33'),
(34, 'info', 'Personnel BULUT BENEK added with email: test@sabl.com.tr', '2025-09-09 22:02:50'),
(35, 'info', 'Personnel ROZELİN added with email: test@test.com.tr', '2025-09-09 22:03:12'),
(36, 'error', 'Invalid CSRF token: Sent: 3907085f78c0c1664903dfc5b6511e0938b06e42fc0097073aa6bb2314fd98a5, Expected: 87f5b9d643a34ca94ae5890bf2e9718cc4ad58a13d33fc78880c6411b38572f5', '2025-09-09 22:03:12'),
(37, 'error', 'Hata: Geçersiz CSRF token., POST: {\"csrf_token\":\"3907085f78c0c1664903dfc5b6511e0938b06e42fc0097073aa6bb2314fd98a5\",\"type\":\"add_personnel\",\"name\":\"ROZEL\\u0130N\",\"position\":\"KANO K\\u0130RALAMA\",\"salary\":\"30000\",\"email\":\"test@test.com.tr\",\"phone\":\"00000000000\"}', '2025-09-09 22:03:12'),
(38, 'error', 'Invalid CSRF token: Sent: none, Expected: a34487b5920c8df8563dde80ad01d7f1c1151c59c9f11920dc44beac94a7154b', '2025-09-10 17:15:27'),
(39, 'error', 'Hata: Geçersiz CSRF token., POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-10 17:15:27'),
(40, 'error', 'Invalid CSRF token: Sent: none, Expected: a34487b5920c8df8563dde80ad01d7f1c1151c59c9f11920dc44beac94a7154b', '2025-09-10 17:17:05'),
(41, 'error', 'Hata: Geçersiz CSRF token., POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-10 17:17:05'),
(42, 'error', 'Invalid CSRF token: Sent: none, Expected: a34487b5920c8df8563dde80ad01d7f1c1151c59c9f11920dc44beac94a7154b', '2025-09-10 17:17:14'),
(43, 'error', 'Hata: Geçersiz CSRF token., POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-10\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-10 17:17:14'),
(44, 'error', 'Geçersiz CSRF token (credits), Sent: none, Expected: ', '2025-09-10 17:18:40'),
(45, 'error', 'Hata: Geçersiz CSRF token., POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-10 17:18:40'),
(46, 'error', 'Invalid CSRF token: Sent: none, Expected: a34487b5920c8df8563dde80ad01d7f1c1151c59c9f11920dc44beac94a7154b', '2025-09-10 17:19:00'),
(47, 'error', 'Hata: Geçersiz CSRF token., POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-10 17:19:00'),
(48, 'error', 'Invalid CSRF token: Sent: none, Expected: a34487b5920c8df8563dde80ad01d7f1c1151c59c9f11920dc44beac94a7154b', '2025-09-10 17:19:11'),
(49, 'error', 'Hata: Geçersiz CSRF token., POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-10 17:19:11'),
(50, 'error', 'Invalid CSRF token: Sent: none, Expected: a34487b5920c8df8563dde80ad01d7f1c1151c59c9f11920dc44beac94a7154b', '2025-09-10 17:36:55'),
(51, 'error', 'Hata: Geçersiz CSRF token. Lütfen sayfayı yenileyin ve tekrar deneyin., POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-10 17:36:55'),
(52, 'error', 'Invalid CSRF token: Sent: none, Expected: a34487b5920c8df8563dde80ad01d7f1c1151c59c9f11920dc44beac94a7154b', '2025-09-10 17:37:06'),
(53, 'error', 'Hata: Geçersiz CSRF token. Lütfen sayfayı yenileyin ve tekrar deneyin., POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-10 17:37:06'),
(54, 'error', 'Invalid CSRF token: Sent: none, Expected: a34487b5920c8df8563dde80ad01d7f1c1151c59c9f11920dc44beac94a7154b', '2025-09-10 17:38:58'),
(55, 'error', 'Hata: Geçersiz CSRF token. Lütfen sayfayı yenileyin ve tekrar deneyin., POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-10 17:38:58'),
(56, 'error', 'Invalid CSRF token at 2025-09-10 17:47:52: Sent: none, Expected: a34487b5920c8df8563dde80ad01d7f1c1151c59c9f11920dc44beac94a7154b, POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-10 17:47:52'),
(57, 'error', 'Hata at 2025-09-10 17:47:52: Geçersiz CSRF token. Lütfen sayfayı yenileyin ve tekrar deneyin., POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-10 17:47:52'),
(58, 'error', 'Invalid CSRF token at 2025-09-10 17:48:03: Sent: none, Expected: a34487b5920c8df8563dde80ad01d7f1c1151c59c9f11920dc44beac94a7154b, POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-10 17:48:03'),
(59, 'error', 'Hata at 2025-09-10 17:48:03: Geçersiz CSRF token. Lütfen sayfayı yenileyin ve tekrar deneyin., POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-10 17:48:03'),
(60, 'error', 'Invalid CSRF token at 2025-09-10 17:48:52: Sent: none, Expected: a34487b5920c8df8563dde80ad01d7f1c1151c59c9f11920dc44beac94a7154b, POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-10 17:48:52'),
(61, 'error', 'Hata at 2025-09-10 17:48:52: Geçersiz CSRF token. Lütfen sayfayı yenileyin ve tekrar deneyin., POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-10 17:48:52'),
(62, 'error', 'Invalid CSRF token at 2025-09-10 18:01:55: Sent: none, Expected: a34487b5920c8df8563dde80ad01d7f1c1151c59c9f11920dc44beac94a7154b, POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-10 18:01:55'),
(63, 'error', 'Hata at 2025-09-10 18:01:55: Geçersiz CSRF token. Lütfen sayfayı yenileyin ve tekrar deneyin., POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-10 18:01:55'),
(64, 'error', 'Invalid CSRF token at 2025-09-11 05:26:41: Sent: none, Expected: a34487b5920c8df8563dde80ad01d7f1c1151c59c9f11920dc44beac94a7154b, POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-11 05:26:41'),
(65, 'error', 'Hata at 2025-09-11 05:26:41: Geçersiz CSRF token. Lütfen sayfayı yenileyin ve tekrar deneyin., POST: {\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-11 05:26:41'),
(66, 'error', 'Undefined type in POST: {\"csrf_token\":\"231515b9d17c26e3e9d68d01c8144584886d33159f3c6fc7505797eddc6df3a1\",\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-11 05:46:22'),
(67, 'error', 'Hata at 2025-09-11 05:46:22: Geçersiz işlem tipi. Lütfen tekrar deneyin., POST: {\"csrf_token\":\"231515b9d17c26e3e9d68d01c8144584886d33159f3c6fc7505797eddc6df3a1\",\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-11 05:46:22'),
(68, 'error', 'Undefined type in POST: {\"csrf_token\":\"231515b9d17c26e3e9d68d01c8144584886d33159f3c6fc7505797eddc6df3a1\",\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-11 05:48:52'),
(69, 'error', 'Hata at 2025-09-11 05:48:52: Geçersiz işlem tipi. Lütfen tekrar deneyin., POST: {\"csrf_token\":\"231515b9d17c26e3e9d68d01c8144584886d33159f3c6fc7505797eddc6df3a1\",\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-11 05:48:52'),
(70, 'error', 'Undefined type in POST: {\"csrf_token\":\"231515b9d17c26e3e9d68d01c8144584886d33159f3c6fc7505797eddc6df3a1\",\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-11 05:53:50'),
(71, 'error', 'Hata at 2025-09-11 05:53:50: Geçersiz işlem tipi. Lütfen tekrar deneyin., POST: {\"csrf_token\":\"231515b9d17c26e3e9d68d01c8144584886d33159f3c6fc7505797eddc6df3a1\",\"personnel_id\":\"1\",\"account_id\":\"1\",\"payment_type\":\"salary\",\"amount\":\"2800\",\"currency\":\"TRY\",\"payment_date\":\"2025-09-03\",\"description\":\"\",\"add_payment\":\"\"}', '2025-09-11 05:53:50'),
(72, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:19:50'),
(73, 'info', 'get_invoices.php: 2 için 0 fatura döndürüldü', '2025-09-11 09:20:07'),
(74, 'info', 'Fatura #1 tedarikçi ID 2 için eklendi.', '2025-09-11 09:20:29'),
(75, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:20:29'),
(76, 'info', 'get_invoices.php: 2 için 1 fatura döndürüldü', '2025-09-11 09:20:36'),
(77, 'info', 'get_invoices.php: 2 için 1 fatura döndürüldü', '2025-09-11 09:20:36'),
(78, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"12265120b7211cac0a1cb5a61757ac7000819c4a7840479a48f09535cd97e518\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 09:20:47'),
(79, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:20:47'),
(80, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:21:13'),
(81, 'info', 'get_invoices.php: 2 için 1 fatura döndürüldü', '2025-09-11 09:21:16'),
(82, 'info', 'get_invoices.php: 2 için 1 fatura döndürüldü', '2025-09-11 09:21:16'),
(83, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"676657d80b7ea8af5c5a076b48dbe7730ccc6e1f547d85d1a215a7bacab44492\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 09:21:34'),
(84, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:21:34'),
(85, 'error', 'Ödeme ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"676657d80b7ea8af5c5a076b48dbe7730ccc6e1f547d85d1a215a7bacab44492\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 09:28:17'),
(86, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:28:17'),
(87, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:28:19'),
(88, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:31:15'),
(89, 'info', 'get_invoices.php: 2 için 1 fatura döndürüldü', '2025-09-11 09:31:17'),
(90, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"0dbd462bc628bf950149ea0423916682f8f2cf0bc499b6c848b0911e6ccdc2a6\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 09:31:25'),
(91, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:31:25'),
(92, 'error', 'Ödeme ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"0dbd462bc628bf950149ea0423916682f8f2cf0bc499b6c848b0911e6ccdc2a6\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 09:32:39'),
(93, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:32:39'),
(94, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:32:41'),
(95, 'info', 'get_invoices.php: 2 için 1 fatura döndürüldü', '2025-09-11 09:32:42'),
(96, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"c8b261b81abe5637446a96f3d6c3a751aaedb6cff41cd1470b34a8a1726249ca\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 09:32:50'),
(97, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:32:50'),
(98, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:35:51'),
(99, 'info', 'get_invoices.php: 2 için 1 fatura döndürüldü', '2025-09-11 09:35:54'),
(100, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"5204dd1cdd6c055d8b946b1b0617c36b9ad09cd492e1f2325ace679ba359a3bf\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 09:36:03'),
(101, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:36:03'),
(102, 'error', 'Ödeme ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"5204dd1cdd6c055d8b946b1b0617c36b9ad09cd492e1f2325ace679ba359a3bf\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 09:36:52'),
(103, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:36:52'),
(104, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:36:53'),
(105, 'info', 'get_invoices.php: 2 için 1 fatura döndürüldü', '2025-09-11 09:36:55'),
(106, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"7e21e75633eedb3abc0f96a4f5d5d81c6c369e7c8e0f784807565a692b48997d\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 09:37:03'),
(107, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:37:03'),
(108, 'error', 'Ödeme ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"7e21e75633eedb3abc0f96a4f5d5d81c6c369e7c8e0f784807565a692b48997d\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 09:38:46'),
(109, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:38:46'),
(110, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:38:46'),
(111, 'info', 'get_invoices.php: 2 için 1 fatura döndürüldü', '2025-09-11 09:38:49'),
(112, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:38:58'),
(113, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:39:01'),
(114, 'info', 'get_invoices.php: 2 için 1 fatura döndürüldü', '2025-09-11 09:39:02'),
(115, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"adce878b06a9436e3b3f989dc15c7fffcc8afa262881bec3de61a18a8795c9f7\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 09:39:10'),
(116, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 09:39:10'),
(117, 'error', 'Ödeme ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"adce878b06a9436e3b3f989dc15c7fffcc8afa262881bec3de61a18a8795c9f7\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 10:00:41'),
(118, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:00:41'),
(119, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:00:42'),
(120, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"961b373d75d2b62bb4360d3814ad82a7e19d7af93e5b9b41c9204791efaf47bd\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 10:00:59'),
(121, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:00:59'),
(122, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:06:39'),
(123, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:06:39'),
(124, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"c5e84def9a6c265adcbd4304d007e5430e394ddfdd21db0493f912b61eeacb80\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 10:06:51'),
(125, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:06:51'),
(126, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:08:31'),
(127, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"443c9ee10e4ab6ea3f7bcac2fe9a9410e35efc83ae17603f0eb131c7a2d6ae1d\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 10:08:41'),
(128, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:08:41'),
(129, 'error', 'Ödeme ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"443c9ee10e4ab6ea3f7bcac2fe9a9410e35efc83ae17603f0eb131c7a2d6ae1d\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 10:09:10'),
(130, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:09:10'),
(131, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:09:11'),
(132, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"12b15b75d6123638b5f6a86eef4fe5703bf6579e1473af1dd79c6db4225ae619\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 10:09:20'),
(133, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:09:20'),
(134, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:15:10'),
(135, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"3ed3ecde907cf6910c97b5ba341c77b6f38b37b8cb9d16f744fd0f8a94364d74\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 10:15:19'),
(136, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:15:19'),
(137, 'error', 'Ödeme ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"3ed3ecde907cf6910c97b5ba341c77b6f38b37b8cb9d16f744fd0f8a94364d74\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 10:17:00'),
(138, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:17:00'),
(139, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:17:02'),
(140, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"dda8083dc9e619f5302313c8e1bcbfc0672c5039cf57e08b1962a99e5fb9049f\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 10:17:10'),
(141, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:17:10'),
(142, 'error', 'Ödeme ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"dda8083dc9e619f5302313c8e1bcbfc0672c5039cf57e08b1962a99e5fb9049f\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 10:17:36'),
(143, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:17:36'),
(144, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:17:37'),
(145, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"8ee5219605c65ad10770c903bfdaceeca8743f9e782488164dba1905b4cb0c0c\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 10:17:46'),
(146, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:17:46'),
(147, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:25:35'),
(148, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:28:23'),
(149, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"e47395e301d4d11b219b2c99d0f8a42032d1cc71ba41afcb861387815b35732d\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 10:28:33'),
(150, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:28:33'),
(151, 'error', 'Ödeme ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"e47395e301d4d11b219b2c99d0f8a42032d1cc71ba41afcb861387815b35732d\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 10:31:35'),
(152, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:31:35'),
(153, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:31:36'),
(154, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:31:48'),
(155, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"3da0dd00a9ba29c5021f0961aecf757553650f7b86aea6a78d5e946b69b45cb9\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 10:32:00'),
(156, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:32:00'),
(157, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 10:33:19'),
(158, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 11:03:27'),
(159, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 11:03:28'),
(160, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"62d250e286d3bd850695bfb3bc9295c603fc81275a959127c59d10e6f8587fed\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 11:03:37'),
(161, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 11:03:37'),
(162, 'error', 'Ödeme ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"62d250e286d3bd850695bfb3bc9295c603fc81275a959127c59d10e6f8587fed\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 11:19:18'),
(163, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 11:19:18'),
(164, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 11:19:19'),
(165, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"1e6f41f475d81f794d0e4da16414bc29f1307556b903530d853fd648e58228a6\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 11:19:28'),
(166, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 11:19:28'),
(167, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 12:43:48'),
(168, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"a546b6c8423d956966b4bb2357eaf59b0e017b8faa1d4726c14457d02692b16d\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 12:43:57'),
(169, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 12:43:57'),
(170, 'error', 'Ödeme ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"a546b6c8423d956966b4bb2357eaf59b0e017b8faa1d4726c14457d02692b16d\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 12:44:25'),
(171, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 12:44:25'),
(172, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 12:44:43'),
(173, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"37ea093cc853c42db6fc423a6d03f5479e897e2e791863e4706dbd6fc036912a\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 12:44:54'),
(174, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 12:44:54'),
(175, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 12:49:04'),
(176, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 12:49:31'),
(177, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 12:49:32'),
(178, 'error', 'Ödeme ekleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'total_amount\' in \'field list\', POST: {\"csrf_token\":\"3f089967786ec6bbf6d691431de44d5195ab4d1f5b24b9da62ead7cb2b2e4449\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 12:49:40'),
(179, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 12:49:40'),
(180, 'error', 'Ödeme ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"3f089967786ec6bbf6d691431de44d5195ab4d1f5b24b9da62ead7cb2b2e4449\",\"supplier_id\":\"2\",\"add_payment\":\"1\",\"payment_type\":\"balance\",\"invoice_id\":\"\",\"amount\":\"7000\",\"account_id\":\"1\",\"payment_date\":\"2025-09-07\"}', '2025-09-11 13:04:24'),
(181, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 13:04:24'),
(182, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 13:04:26'),
(183, 'info', 'Ödeme balance of 7000 TRY for supplier ID 2', '2025-09-11 13:04:35'),
(184, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 13:04:35'),
(185, 'info', 'Fetched 2 suppliers and 5 cash accounts', '2025-09-11 13:26:09'),
(186, 'info', 'Tedarikçi GAZETECİ eklendi (Bakiye: 560 TRY).', '2025-09-11 13:26:49'),
(187, 'error', 'Tedarikçi ekleme hatası: Geçersiz CSRF token., POST: {\"csrf_token\":\"e4393a0559ef52660f0febb8e0748ef02a798d6aede2209bae9316eb3f98e97d\",\"add_supplier\":\"1\",\"name\":\"GAZETEC\\u0130\",\"balance\":\"560\",\"currency\":\"TRY\",\"contact_name\":\"CAH\\u0130T\",\"email\":\"gazete@test.com\",\"phone\":\"00000000000\",\"city\":\"\\u00c7ANAKKALE\",\"district\":\"ECEABAT\"}', '2025-09-11 13:26:52'),
(188, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 13:26:52'),
(189, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 13:26:54'),
(190, 'info', 'Ödeme balance of 560 TRY for supplier ID 3', '2025-09-11 13:27:07'),
(191, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 13:27:07'),
(192, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 13:47:58'),
(193, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 14:31:04'),
(194, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 14:31:50'),
(195, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 14:31:52'),
(196, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 14:32:10'),
(197, 'info', 'Fatura #1 tedarikçi ID 2 için eklendi.', '2025-09-11 14:32:23'),
(198, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 14:32:23'),
(199, 'info', 'Ödeme balance of 7000 TRY for supplier ID 2', '2025-09-11 14:32:35'),
(200, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 14:32:35'),
(201, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 14:42:15'),
(202, 'info', 'Fatura #1 tedarikçi ID 3 için eklendi.', '2025-09-11 14:43:03'),
(203, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 14:43:03'),
(204, 'info', 'Ödeme balance of 560 TRY for supplier ID 3', '2025-09-11 14:43:56'),
(205, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 14:43:56'),
(206, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 15:07:14'),
(207, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 15:08:20'),
(208, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 15:10:09'),
(209, 'info', 'Fatura #1 tedarikçi ID 3 için eklendi.', '2025-09-11 15:10:28'),
(210, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 15:10:28'),
(211, 'info', 'Ödeme balance of 560 TRY for supplier ID 3', '2025-09-11 15:10:46'),
(212, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 15:10:46'),
(213, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 15:34:25'),
(214, 'info', 'Fatura #1 tedarikçi ID 2 için eklendi.', '2025-09-11 15:34:53'),
(215, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 15:34:53'),
(216, 'info', 'Ödeme balance of 7000 TRY for supplier ID 2', '2025-09-11 15:35:03'),
(217, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 15:35:03'),
(218, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 15:36:42'),
(219, 'info', 'Fatura #2 tedarikçi ID 3 için eklendi.', '2025-09-11 15:36:55'),
(220, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 15:36:55'),
(221, 'info', 'Ödeme balance of 560 TRY for supplier ID 3', '2025-09-11 15:37:07'),
(222, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 15:37:07'),
(223, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 15:44:20'),
(224, 'info', 'Fatura #M012025000002230 tedarikçi ID 1 için eklendi.', '2025-09-11 15:45:06'),
(225, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 15:45:06'),
(226, 'info', 'Ödeme balance of 5000 TRY for supplier ID 1', '2025-09-11 15:45:19'),
(227, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 15:45:19'),
(228, 'error', 'Bakiye güncelleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'due_date\' in \'field list\', POST: {\"csrf_token\":\"818acc44acdc4fedec509b6860f6434a4a8412a5afc48a69172f29b74b535773\",\"customer_id\":\"1\",\"update_balance\":\"1\",\"type\":\"debit\",\"amount\":\"2370\",\"description\":\"DEV\\u0130R\"}', '2025-09-11 17:56:31'),
(229, 'error', 'Bakiye güncelleme hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'due_date\' in \'field list\', POST: {\"csrf_token\":\"c73332e01e62a48fce41bd545d7c9324833ce8fe5b388026ceeb616609da50f2\",\"customer_id\":\"2\",\"update_balance\":\"1\",\"type\":\"credit\",\"amount\":\"2135\",\"description\":\"\"}', '2025-09-11 19:19:26'),
(230, 'info', 'Fetched 0 active credits, 0 installments, and 0 payment history records', '2025-09-11 19:38:01'),
(231, 'info', 'Fetched 0 active credits, 0 installments, and 0 payment history records', '2025-09-11 19:42:14'),
(232, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-11 21:39:29'),
(233, 'info', 'Fetched 0 active credits, 0 installments, and 0 payment history records', '2025-09-12 06:44:08'),
(234, 'info', 'Fetched 0 documents', '2025-09-12 09:00:30'),
(235, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-12 12:22:45'),
(236, 'info', 'Fatura #M012025000002293 tedarikçi ID 1 için eklendi.', '2025-09-12 12:23:22'),
(237, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-12 12:23:22'),
(238, 'info', 'Fetched 0 documents', '2025-09-12 12:36:58'),
(239, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-12 13:27:01'),
(240, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-12 13:45:37'),
(241, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-12 13:46:57'),
(242, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-12 13:47:43'),
(243, 'info', 'Fatura #m012025000002293 tedarikçi ID 1 için eklendi.', '2025-09-12 13:48:20'),
(244, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-12 13:48:20'),
(245, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-12 13:48:40'),
(246, 'info', 'Fetched 3 suppliers and 5 cash accounts', '2025-09-12 13:52:26'),
(247, 'info', 'Fetched 0 documents', '2025-09-12 16:13:25'),
(248, 'info', 'Fetched 0 documents', '2025-09-12 16:13:28'),
(249, 'info', 'Fetched 0 documents', '2025-09-12 16:13:29'),
(250, 'info', 'Fetched 0 documents', '2025-09-12 16:13:39'),
(251, 'error', 'Arama hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'quantity\' in \'field list\', POST: {\"csrf_token\":\"5362fdbca644bf93004332569f491f772f2e1f685787a556ffcf8b13b3f63546\",\"search_query\":\"test\",\"search_assets\":\"\"}', '2025-09-12 21:16:36'),
(252, 'info', 'Fetched 3 suppliers and 0 cash accounts', '2025-09-12 21:16:42');

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
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `is_read`, `created_at`, `is_archived`, `priority`) VALUES
(1, 2, '', 'Yeni müşteri eklendi: TOLGA ABİ', 0, '2025-09-11 19:18:17', 0, 'medium'),
(2, 2, '', 'Yeni kategori eklendi: TEST', 0, '2025-09-11 19:36:22', 0, 'low'),
(3, 2, '', 'Kategori silindi: ID 15', 0, '2025-09-11 19:36:26', 0, 'low'),
(4, 2, '', 'Yeni kategori eklendi: TEST', 0, '2025-09-11 19:36:32', 0, 'low'),
(5, 2, '', 'Kategori güncellendi: TEST', 0, '2025-09-11 19:36:36', 0, 'low'),
(6, 2, '', 'Kategori silindi: ID 16', 0, '2025-09-11 19:36:40', 0, 'low'),
(7, 2, '', 'Kilit ekranı açıldı: Kullanıcı ID 2', 0, '2025-09-11 19:55:36', 0, 'low'),
(8, 2, '', 'Kilit ekranı açıldı: Kullanıcı ID 2', 0, '2025-09-11 19:56:07', 0, 'low'),
(9, 2, '', 'Kilit ekranı açıldı: Kullanıcı ID 2', 0, '2025-09-11 20:26:32', 0, 'low'),
(10, 2, '', 'Kullanıcı silindi: ID 4', 0, '2025-09-11 21:16:22', 0, 'low'),
(11, 2, '', 'Yeni kullanıcı eklendi: test', 0, '2025-09-11 21:16:34', 0, 'low'),
(12, 1, '', 'Yeni ürün eklendi: SÖZCÜ GAZETESİ', 0, '2025-09-12 08:35:01', 0, 'low'),
(13, 1, '', 'Yeni ürün eklendi: POSTA GAZETESİ', 0, '2025-09-12 08:45:21', 0, 'low'),
(14, 1, '', 'Yeni ürün eklendi: AKŞAM GAZETESİ', 0, '2025-09-12 09:00:23', 0, 'low'),
(15, 1, '', 'Yeni ürün eklendi: U.200 ML CAM FRUTTİ ELMA', 0, '2025-09-12 12:36:34', 0, 'low'),
(16, 1, '', 'Yeni ürün eklendi: U.200 ML CAM FRUTTİ LİMON', 0, '2025-09-12 12:36:56', 0, 'low'),
(17, 1, '', 'Fatura içeriği eklendi: Fatura ID 7', 0, '2025-09-12 13:25:19', 0, 'low'),
(18, 1, '', 'Fatura içeriği eklendi: Fatura ID 9', 0, '2025-09-12 13:25:56', 0, 'low'),
(19, 1, '', 'Fatura içeriği eklendi: Fatura ID 12', 0, '2025-09-12 13:52:21', 0, 'low'),
(20, 1, '', 'Yeni depo eklendi: ANA DEPO', 0, '2025-09-12 16:10:39', 0, 'medium'),
(21, 1, '', 'Yeni depo eklendi: KÜÇÜK DÜKKAN', 0, '2025-09-12 16:11:09', 0, 'medium'),
(22, 1, '', 'Yeni ürün eklendi: CAN PEPSİ 330ML', 0, '2025-09-12 21:10:14', 0, 'low'),
(23, 1, '', 'Yeni ürün eklendi: KINIK MADEN SUYU', 0, '2025-09-12 21:10:41', 0, 'low'),
(24, 1, '', 'Yeni ürün eklendi: BUZDAĞI 0.5LT PET SU', 0, '2025-09-12 21:11:05', 0, 'low'),
(25, 1, '', 'Yeni ürün eklendi: CAN LİPTON İCE TEA ŞEFTALİ', 0, '2025-09-12 21:11:25', 0, 'low'),
(26, 1, '', 'Yeni müşteri eklendi: TEST', 0, '2025-09-12 21:18:41', 0, 'medium');

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `payments`
--

INSERT INTO `payments` (`id`, `supplier_id`, `invoice_id`, `amount`, `payment_type`, `currency`, `payment_date`, `description`, `created_at`, `customer_id`) VALUES
(26, 3, NULL, 560.00, 'balance', '', '2025-09-01', NULL, '2025-09-11 15:10:46', NULL),
(27, 2, NULL, 7000.00, 'balance', '', '2025-09-07', NULL, '2025-09-11 15:35:03', NULL),
(28, 3, NULL, 560.00, 'balance', '', '2025-09-08', NULL, '2025-09-11 15:37:07', NULL),
(29, 1, NULL, 5000.00, 'balance', '', '2025-09-11', NULL, '2025-09-11 15:45:19', NULL);

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
    SELECT amount * (SELECT rate FROM exchange_rates WHERE currency_code = i.currency ORDER BY updated_at DESC LIMIT 1)
    INTO invoice_amount
    FROM invoices i
    WHERE i.id = NEW.invoice_id;
    
    -- Eğer fatura tamamen ödendiyse hatırlatıcıyı kapat
    IF total_paid IS NOT NULL AND invoice_amount IS NOT NULL AND total_paid >= invoice_amount THEN
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
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `personnel`
--

INSERT INTO `personnel` (`id`, `name`, `position`, `salary`, `email`, `phone`, `created_at`) VALUES
(1, 'KADİR BEHRAMLI', 'GARSON', 2800.00, 'test@test.com', '00000000000', '2025-09-09 21:59:49'),
(2, 'İSMAİL SEVİNÇ', 'GARSON', 28000.00, 'test@gmail.com', '00000000000', '2025-09-09 22:02:33'),
(3, 'BULUT BENEK', 'GARSON', 28000.00, 'test@sabl.com.tr', '00000000000', '2025-09-09 22:02:50'),
(4, 'ROZELİN', 'KANO KİRALAMA', 0.00, 'test@test.com.tr', '00000000000', '2025-09-09 22:03:12');

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `personnel_leaves`
--

INSERT INTO `personnel_leaves` (`id`, `personnel_id`, `start_date`, `end_date`, `reason`, `created_at`) VALUES
(1, 2, '2025-09-03', '2025-09-03', '', '2025-09-11 09:17:56'),
(2, 2, '2025-09-08', '2025-09-08', '', '2025-09-11 09:18:05');

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `stock_quantity`, `created_at`) VALUES
(1, 'SÖZCÜ GAZETESİ', 25.00, 14, '2025-09-12 11:35:01'),
(2, 'POSTA GAZETESİ', 20.00, 28, '2025-09-12 11:45:21'),
(3, 'AKŞAM GAZETESİ', 15.00, 14, '2025-09-12 12:00:23'),
(4, 'U.200 ML CAM FRUTTİ ELMA', 10.42, 48, '2025-09-12 15:36:34'),
(5, 'U.200 ML CAM FRUTTİ LİMON', 10.42, 120, '2025-09-12 15:36:56'),
(6, 'CAN PEPSİ 330ML', 36.45, 0, '2025-09-13 00:10:14'),
(7, 'KINIK MADEN SUYU', 8.33, 0, '2025-09-13 00:10:41'),
(8, 'BUZDAĞI 0.5LT PET SU', 5.00, 0, '2025-09-13 00:11:05'),
(9, 'CAN LİPTON İCE TEA ŞEFTALİ', 34.44, 0, '2025-09-13 00:11:25');

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `salary_advances`
--

INSERT INTO `salary_advances` (`id`, `personnel_id`, `transaction_id`, `amount`, `currency`, `description`, `created_at`) VALUES
(24, 2, 0, 6000.00, 'TRY', '', '2025-09-06 21:00:00'),
(23, 3, 0, 7000.00, 'TRY', '', '2025-09-06 21:00:00'),
(22, 4, 0, 3000.00, 'TRY', '', '2025-09-02 21:00:00'),
(21, 1, 0, 2800.00, 'TRY', 'ÇIKIŞ', '2025-09-02 21:00:00');

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
-- Tablo için tablo yapısı `settings`
--

DROP TABLE IF EXISTS `settings`;
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`) VALUES
(1, 'sms_enabled', '1'),
(2, 'email_enabled', '1'),
(3, 'global_credit_limit', '100000.00'),
(4, 'default_cash_account_id', '3'),
(5, 'theme', 'light'),
(6, 'primary_color', '#003087'),
(7, 'font_family', 'Roboto'),
(8, 'logo_url', 'assets/images/logo.png'),
(9, 'favicon_url', 'assets/images/favicon.ico'),
(10, 'sidebar_layout', 'fixed'),
(11, 'records_per_page', '10'),
(12, 'sms_enabled', '1'),
(13, 'email_enabled', '1'),
(14, 'global_credit_limit', '100000'),
(15, 'default_cash_account_id', '3'),
(16, 'sms_enabled', '1'),
(17, 'email_enabled', '1'),
(18, 'global_credit_limit', '100000'),
(19, 'default_cash_account_id', '3');

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `balance` decimal(15,2) DEFAULT '0.00',
  `currency` varchar(3) DEFAULT 'TRY',
  `contact_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `contact` varchar(100) DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `balance`, `currency`, `contact_name`, `email`, `phone`, `address`, `created_at`, `contact`, `updated_at`) VALUES
(1, 'KEMAL ATEŞ', 7353.06, 'TRY', 'KEMAL ATEŞ', 'alaskan_17@hotmail.com', '00000000000', 'ÇANAKKALE, ECEABAT', '2025-09-09 20:58:05', NULL, '2025-09-12 16:52:21'),
(2, 'REİS GIDA', 15605.00, 'TRY', 'MUSTAFA', 'test@test.com', '00000000000', 'ÇANAKKALE, GELİBOLU', '2025-09-09 21:03:21', NULL, '2025-09-11 18:35:03'),
(3, 'GAZETECİ', 1120.00, 'TRY', 'CAHİT', 'gazete@test.com', '00000000000', 'ÇANAKKALE, ECEABAT', '2025-09-11 13:26:49', NULL, '2025-09-12 16:25:56');

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
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `email`, `role`, `created_at`, `avatar`) VALUES
(1, 'admin', '$2y$10$KY7A2hyPj2EshTlfcTZ0he8kfsH0O2NLnxUMYwhrFJLU1yZLUyY6e', 'Yönetici', 'admin@example.com', 'admin', '2025-09-02 15:41:08', 'https://bootdey.com/img/Content/user_1.jpg'),
(2, 'serapekemen', '$2y$10$KY7A2hyPj2EshTlfcTZ0he8kfsH0O2NLnxUMYwhrFJLU1yZLUyY6e', 'Yönetici', 'kate@example.com', 'admin', '2025-09-07 08:17:14', 'https://bootdey.com/img/Content/user_3.jpg'),
(5, 'test', '$2y$10$TahUBtOW1ImkJsCf7n3sI.NsoNYlYe9q6LT9RLfrHJpQsZLkig/6G', '', 'test@test.com', 'user', '2025-09-11 21:16:34', 'https://bootdey.com/img/Content/user_1.jpg');

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
(1, 'ANA DEPO', 'ALÇITEPE', '2025-09-12 16:10:39'),
(2, 'KÜÇÜK DÜKKAN', 'ALÇITEPE', '2025-09-12 16:11:09');

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
