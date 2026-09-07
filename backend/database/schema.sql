-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 07 Eyl 2026, 13:02:27
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `erp`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `auth_tokens`
--

CREATE TABLE `auth_tokens` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `son_kullanim_tarihi` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `ad` varchar(150) NOT NULL,
  `ust_kategori_id` int(11) DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `cari_kodu` varchar(50) NOT NULL,
  `firma_adi` varchar(255) NOT NULL,
  `yetkili_kisi` varchar(150) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `adres` text DEFAULT NULL,
  `sehir` varchar(100) DEFAULT NULL,
  `ilce` varchar(100) DEFAULT NULL,
  `vergi_dairesi` varchar(150) DEFAULT NULL,
  `vergi_no` varchar(50) DEFAULT NULL,
  `not_metni` text DEFAULT NULL,
  `durum` enum('AKTIF','PASIF') NOT NULL DEFAULT 'AKTIF',
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `ad` varchar(100) NOT NULL,
  `soyad` varchar(100) NOT NULL,
  `kullanici_adi` varchar(100) NOT NULL,
  `sifre_hash` varchar(255) NOT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `rol` enum('ADMIN','YONETICI','CALISAN') NOT NULL DEFAULT 'CALISAN',
  `durum` enum('AKTIF','PASIF') NOT NULL DEFAULT 'AKTIF',
  `son_giris` datetime DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `employee_customers`
--

CREATE TABLE `employee_customers` (
  `employee_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `siparis_no` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `evrak_aciklamasi` varchar(500) DEFAULT NULL,
  `teslim_tarihi` date DEFAULT NULL,
  `teslim_edilme_tarihi` datetime DEFAULT NULL,
  `ambar_bilgisi` varchar(255) DEFAULT NULL,
  `odeme_tipi` enum('NAKIT','VADELI') NOT NULL DEFAULT 'NAKIT',
  `vade_gun` int(11) DEFAULT NULL,
  `durum` enum('TASLAK','BEKLEMEDE','ONAYLANDI','TAMAMLANDI','IPTAL') NOT NULL DEFAULT 'TASLAK',
  `ara_toplam` decimal(14,2) NOT NULL DEFAULT 0.00,
  `kdv_toplam` decimal(14,2) NOT NULL DEFAULT 0.00,
  `genel_toplam` decimal(14,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `koli_adedi` int(11) NOT NULL DEFAULT 0,
  `adet` int(11) NOT NULL DEFAULT 0,
  `birim_fiyat` decimal(12,2) NOT NULL,
  `iskonto_1` decimal(5,2) NOT NULL DEFAULT 0.00,
  `iskonto_2` decimal(5,2) NOT NULL DEFAULT 0.00,
  `iskonto_3` decimal(5,2) NOT NULL DEFAULT 0.00,
  `net_fiyat` decimal(12,2) NOT NULL,
  `net_tutar` decimal(14,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `urun_kodu` varchar(50) NOT NULL,
  `urun_adi` varchar(255) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  `barkod` varchar(100) DEFAULT NULL,
  `liste_fiyati` decimal(12,2) NOT NULL DEFAULT 0.00,
  `koli_fiyati` decimal(12,2) NOT NULL DEFAULT 0.00,
  `dip_fiyat` decimal(12,2) NOT NULL DEFAULT 0.00,
  `koli_ici_adet` int(11) NOT NULL DEFAULT 1,
  `kdv_orani` decimal(5,2) NOT NULL DEFAULT 20.00,
  `hacim_m3` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `stok` int(11) NOT NULL DEFAULT 0,
  `birim` varchar(20) NOT NULL DEFAULT 'ADET',
  `gorsel_url` varchar(500) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `guncellenme_tarihi` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `hareket_tipi` enum('GIRIS','CIKIS','SIPARIS','IADE','DUZELTME') NOT NULL,
  `miktar` int(11) NOT NULL,
  `referans_order_id` int(11) DEFAULT NULL,
  `aciklama` varchar(255) DEFAULT NULL,
  `olusturulma_tarihi` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `idx_token` (`token`);

--
-- Tablo için indeksler `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ust_kategori_id` (`ust_kategori_id`);

--
-- Tablo için indeksler `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cari_kodu` (`cari_kodu`),
  ADD KEY `idx_firma_adi` (`firma_adi`),
  ADD KEY `idx_cari_kodu` (`cari_kodu`);

--
-- Tablo için indeksler `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kullanici_adi` (`kullanici_adi`);

--
-- Tablo için indeksler `employee_customers`
--
ALTER TABLE `employee_customers`
  ADD PRIMARY KEY (`employee_id`,`customer_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Tablo için indeksler `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `siparis_no` (`siparis_no`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_durum` (`durum`);

--
-- Tablo için indeksler `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Tablo için indeksler `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `urun_kodu` (`urun_kodu`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_urun_kodu` (`urun_kodu`),
  ADD KEY `idx_urun_adi` (`urun_adi`),
  ADD KEY `idx_barkod` (`barkod`);

--
-- Tablo için indeksler `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `referans_order_id` (`referans_order_id`),
  ADD KEY `idx_product_stock` (`product_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `auth_tokens`
--
ALTER TABLE `auth_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD CONSTRAINT `auth_tokens_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`ust_kategori_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `employee_customers`
--
ALTER TABLE `employee_customers`
  ADD CONSTRAINT `employee_customers_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_customers_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Tablo kısıtlamaları `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Tablo kısıtlamaları `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`referans_order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
