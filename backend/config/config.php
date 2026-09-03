<?php
/**
 * Genel uygulama ayarları — artık .env dosyasından okunur.
 */

// Dosya başında gizli BOM/boşluk karakteri olsa bile header()/session_start()
// çağrılarının "headers already sent" hatası vermesini engellemek için
// çıktı arabelleğe alınır.
if (ob_get_level() === 0) {
    ob_start();
}

require_once __DIR__ . '/../includes/env_loader.php';
loadEnv(__DIR__ . '/.env');

// Hata gösterimi — .env'deki APP_DEBUG'a göre otomatik ayarlanır.
// APP_DEBUG=true  -> hatalar ekranda gösterilir (SADECE yerel/test için)
// APP_DEBUG=false -> hatalar gizlenir (canlı ortam için doğru ayar)
$appDebug = env('APP_DEBUG', false);
ini_set('display_errors', $appDebug ? '1' : '0');
error_reporting(E_ALL);
define('APP_DEBUG', $appDebug);

// Zaman dilimi
date_default_timezone_set(env('APP_TIMEZONE', 'Europe/Istanbul'));

// Token geçerlilik süresi (saniye) — varsayılan 8 saat
define('TOKEN_LIFETIME', (int) env('TOKEN_LIFETIME', 28800));

// CORS - mobil uygulamanın API'ye erişebilmesi için
define('ALLOWED_ORIGIN', env('ALLOWED_ORIGIN', '*'));

require_once __DIR__ . '/database.php';
