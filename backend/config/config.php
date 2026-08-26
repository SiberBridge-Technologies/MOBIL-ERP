<?php
/**
 * Genel uygulama ayarları
 */

// Hata gösterimi (canlıda mutlaka 0 yapın)
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// Token geçerlilik süresi (saniye) — 8 saat
define('TOKEN_LIFETIME', 8 * 60 * 60);

// CORS - mobil uygulamanın API'ye erişebilmesi için
define('ALLOWED_ORIGIN', '*'); // İstenirse belirli bir origin ile sınırlandırılabilir

require_once __DIR__ . '/database.php';
