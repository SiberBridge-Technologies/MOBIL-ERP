<?php
/**
 * Veritabanı bağlantısı — bilgiler artık .env dosyasından okunur.
 * Değerleri değiştirmek için bu dosyayı DEĞİL, config/.env dosyasını düzenleyin.
 */

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'erp_app'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

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
            // APP_DEBUG açıksa gerçek hatayı göster (yerel test), kapalıysa gizle (canlı ortam)
            $message = (defined('APP_DEBUG') && APP_DEBUG)
                ? 'Veritabanı bağlantı hatası: ' . $e->getMessage()
                : 'Veritabanı bağlantı hatası.';
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }
    }

    return $pdo;
}
