<?php

declare(strict_types=1);

/**
 * ERP ORDER PDF CACHE TEMİZLEYİCİ
 *
 * 120 günden eski PDF cache dosyalarını siler.
 *
 * ÖNEMLİ:
 * orders tablosuna dokunmaz.
 * Sadece storage/order-pdfs içindeki PDF'leri siler.
 */

$storageDir =
    __DIR__ . '/storage/order-pdfs';

$maxAge =
    120 * 24 * 60 * 60;

$now = time();

$total = 0;
$deleted = 0;
$failed = 0;

if (!is_dir($storageDir)) {
    echo "PDF klasörü bulunamadı." . PHP_EOL;
    exit(1);
}

$files =
    glob($storageDir . '/order-*.pdf');

if ($files === false) {
    echo "PDF dosyaları okunamadı." . PHP_EOL;
    exit(1);
}

foreach ($files as $file) {

    if (!is_file($file)) {
        continue;
    }

    $total++;

    $modified =
        filemtime($file);

    if ($modified === false) {
        $failed++;
        continue;
    }

    $age =
        $now - $modified;

    if ($age <= $maxAge) {
        continue;
    }

    if (unlink($file)) {

        $deleted++;

        echo
            '[SILINDI] ' .
            basename($file) .
            PHP_EOL;

    } else {

        $failed++;

        echo
            '[HATA] ' .
            basename($file) .
            PHP_EOL;
    }
}

echo PHP_EOL;
echo "==============================" . PHP_EOL;
echo "ERP PDF CACHE TEMIZLENDI" . PHP_EOL;
echo "Toplam PDF : {$total}" . PHP_EOL;
echo "Silinen    : {$deleted}" . PHP_EOL;
echo "Hatalı     : {$failed}" . PHP_EOL;
echo "==============================" . PHP_EOL;