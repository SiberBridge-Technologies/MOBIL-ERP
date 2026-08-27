-- ERP-APP Veritabanı Şeması
-- Karakter seti: utf8mb4 (Türkçe karakter desteği için)

-- ==========================================================
-- ÇALIŞANLAR (employees)
-- ==========================================================
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(100) NOT NULL,
    soyad VARCHAR(100) NOT NULL,
    kullanici_adi VARCHAR(100) NOT NULL UNIQUE,
    sifre_hash VARCHAR(255) NOT NULL,
    telefon VARCHAR(20),
    email VARCHAR(150),
    rol ENUM('ADMIN', 'YONETICI', 'CALISAN') NOT NULL DEFAULT 'CALISAN',
    durum ENUM('AKTIF', 'PASIF') NOT NULL DEFAULT 'AKTIF',
    son_giris DATETIME NULL,
    olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    guncellenme_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ==========================================================
-- CARİLER (customers)
-- ==========================================================
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cari_kodu VARCHAR(50) NOT NULL UNIQUE,
    firma_adi VARCHAR(255) NOT NULL,
    yetkili_kisi VARCHAR(150),
    telefon VARCHAR(20),
    email VARCHAR(150),
    adres TEXT,
    sehir VARCHAR(100),
    ilce VARCHAR(100),
    vergi_dairesi VARCHAR(150),
    vergi_no VARCHAR(50),
    not_metni TEXT,
    durum ENUM('AKTIF', 'PASIF') NOT NULL DEFAULT 'AKTIF',
    olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    guncellenme_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_firma_adi (firma_adi),
    INDEX idx_cari_kodu (cari_kodu)
) ENGINE=InnoDB;

-- ==========================================================
-- ÇALIŞAN - CARİ İLİŞKİSİ (employee_customers)
-- ==========================================================
CREATE TABLE employee_customers (
    employee_id INT NOT NULL,
    customer_id INT NOT NULL,
    PRIMARY KEY (employee_id, customer_id),
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ==========================================================
-- KATEGORİLER (categories)
-- ==========================================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(150) NOT NULL,
    ust_kategori_id INT NULL,
    olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ust_kategori_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ==========================================================
-- ÜRÜNLER (products)
-- Figma "Ürün Detay" ekranındaki alanlar dahil edildi:
-- liste fiyatı, koli fiyatı, koli içi adet, kdv, hacim, barkod, iskonto oranları vb.
-- ==========================================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    urun_kodu VARCHAR(50) NOT NULL UNIQUE,
    urun_adi VARCHAR(255) NOT NULL,
    category_id INT NULL,
    aciklama TEXT,
    barkod VARCHAR(100),
    liste_fiyati DECIMAL(12,2) NOT NULL DEFAULT 0,
    koli_fiyati DECIMAL(12,2) NOT NULL DEFAULT 0,
    koli_ici_adet INT NOT NULL DEFAULT 1,
    kdv_orani DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    hacim_m3 DECIMAL(10,4) NOT NULL DEFAULT 0,
    stok INT NOT NULL DEFAULT 0,
    birim VARCHAR(20) NOT NULL DEFAULT 'ADET',
    gorsel_url VARCHAR(500),
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    guncellenme_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_urun_kodu (urun_kodu),
    INDEX idx_urun_adi (urun_adi),
    INDEX idx_barkod (barkod)
) ENGINE=InnoDB;

-- ==========================================================
-- SİPARİŞLER (orders)
-- Figma "Sipariş Formu" ekranındaki alanlar: evrak açıklaması,
-- teslim tarihi, ambar bilgisi, ödeme tipi (vadeli/nakit)
-- ==========================================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siparis_no VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    employee_id INT NOT NULL,
    evrak_aciklamasi VARCHAR(500),
    teslim_tarihi DATE,
    ambar_bilgisi VARCHAR(255),
    odeme_tipi ENUM('NAKIT', 'VADELI') NOT NULL DEFAULT 'NAKIT',
    vade_gun INT NULL,
    durum ENUM('TASLAK', 'BEKLEMEDE', 'ONAYLANDI', 'TAMAMLANDI', 'IPTAL') NOT NULL DEFAULT 'TASLAK',
    ara_toplam DECIMAL(14,2) NOT NULL DEFAULT 0,
    kdv_toplam DECIMAL(14,2) NOT NULL DEFAULT 0,
    genel_toplam DECIMAL(14,2) NOT NULL DEFAULT 0,
    note TEXT,
    olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    guncellenme_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    INDEX idx_customer (customer_id),
    INDEX idx_employee (employee_id),
    INDEX idx_durum (durum)
) ENGINE=InnoDB;

-- ==========================================================
-- SİPARİŞ KALEMLERİ (order_items)
-- Figma "Ürün Detay" ekranındaki 3 iskonto kademesi dahil edildi
-- ==========================================================
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    koli_adedi INT NOT NULL DEFAULT 0,
    adet INT NOT NULL DEFAULT 0,
    birim_fiyat DECIMAL(12,2) NOT NULL,
    iskonto_1 DECIMAL(5,2) NOT NULL DEFAULT 0,
    iskonto_2 DECIMAL(5,2) NOT NULL DEFAULT 0,
    iskonto_3 DECIMAL(5,2) NOT NULL DEFAULT 0,
    net_fiyat DECIMAL(12,2) NOT NULL,
    net_tutar DECIMAL(14,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_order (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB;

-- ==========================================================
-- STOK HAREKETLERİ (stock_movements)
-- ==========================================================
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    hareket_tipi ENUM('GIRIS', 'CIKIS', 'SIPARIS', 'IADE', 'DUZELTME') NOT NULL,
    miktar INT NOT NULL,
    referans_order_id INT NULL,
    aciklama VARCHAR(255),
    olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (referans_order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_product_stock (product_id)
) ENGINE=InnoDB;

-- ==========================================================
-- API TOKENLARI (auth_tokens) — mobil oturum yönetimi için
-- ==========================================================
CREATE TABLE auth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    olusturulma_tarihi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    son_kullanim_tarihi DATETIME NOT NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_token (token)
) ENGINE=InnoDB;

-- Örnek admin kullanıcı (şifre: "admin123" -> gerçek kurulumda password_hash ile üretilmeli)
-- INSERT INTO employees (ad, soyad, kullanici_adi, sifre_hash, rol) VALUES
-- ('Sistem', 'Yöneticisi', 'admin', '$2y$10$REPLACE_WITH_REAL_HASH', 'ADMIN');
