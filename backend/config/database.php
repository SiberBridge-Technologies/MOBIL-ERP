<?php
/**
 * Veritabanı bağlantısı
 * cPanel'de bu bilgileri kendi hosting bilgilerinizle değiştirin.
 * Bu dosya API veya admin klasörlerinin DIŞINDA tutulmalı, mümkünse
 * .env veya public_html dışı bir konumdan okunmalıdır.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'erp_app');
define('DB_USER', 'CPANEL_KULLANICI_ADI');
define('DB_PASS', 'CPANEL_SIFRE');
define('DB_CHARSET', 'utf8mb4');

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // gerçek prepared statement kullan
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantı hatası.']);
            exit;
        }
    }

    return $pdo;
}
