<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/*
|--------------------------------------------------------------------------
| JSON / HATA YARDIMCISI
|--------------------------------------------------------------------------
*/

function pdfError(string $message, int $status = 400): never
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($status);

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        [
            'success' => false,
            'message' => $message,
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| AUTHORIZATION
|--------------------------------------------------------------------------
*/

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

/*
 * Apache bazı durumlarda Authorization header'ını HTTP_AUTHORIZATION
 * olarak aktarmayabilir. getallheaders() ile tekrar kontrol ediyoruz.
 */
if (!$authHeader && function_exists('getallheaders')) {

    $headers = getallheaders();

    foreach ($headers as $key => $value) {

        if (strtolower($key) === 'authorization') {
            $authHeader = $value;
            break;
        }
    }
}

$token = null;

/*
 * DOĞRU BEARER REGEX
 */
if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
    $token = trim($matches[1]);
}

if (!$token) {
    pdfError('Yetkilendirme gerekli.', 401);
}

/*
|--------------------------------------------------------------------------
| VERİTABANI
|--------------------------------------------------------------------------
*/

try {

    $pdo = getDbConnection();

} catch (Throwable $e) {

    error_log(
        'PDF DB HATASI: ' .
        $e->getMessage()
    );

    pdfError(
        'Veritabanı bağlantısı kurulamadı.',
        500
    );
}

/*
|--------------------------------------------------------------------------
| KULLANICI DOĞRULAMA
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT
            e.id,
            e.ad,
            e.soyad,
            e.kullanici_adi,
            e.rol,
            e.durum
        FROM auth_tokens t
        INNER JOIN employees e
            ON e.id = t.employee_id
        WHERE t.token = ?
          AND t.son_kullanim_tarihi > NOW()
          AND e.durum = 'AKTIF'
        LIMIT 1
    ");

    $stmt->execute([$token]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Throwable $e) {

    error_log(
        'PDF AUTH HATASI: ' .
        $e->getMessage()
    );

    pdfError(
        'Oturum doğrulanırken bir hata oluştu.',
        500
    );
}

if (!$user) {
    pdfError(
        'Oturum geçersiz veya süresi dolmuş.',
        401
    );
}

/*
|--------------------------------------------------------------------------
| SİPARİŞ ID
|--------------------------------------------------------------------------
*/

$orderId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$orderId || $orderId <= 0) {

    pdfError(
        'Geçerli bir sipariş ID belirtilmedi.',
        400
    );
}

/*
|--------------------------------------------------------------------------
| SİPARİŞ
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT
            o.*,
            c.cari_kodu,
            c.firma_adi,
            c.yetkili_kisi,
            c.telefon,
            c.email,
            c.adres,
            c.sehir,
            c.ilce,
            c.vergi_dairesi,
            c.vergi_no
        FROM orders o
        LEFT JOIN customers c
            ON c.id = o.customer_id
        WHERE o.id = ?
        LIMIT 1
    ");

    $stmt->execute([$orderId]);

    $order = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (Throwable $e) {

    error_log(
        'PDF ORDER HATASI: ' .
        $e->getMessage()
    );

    pdfError(
        'Sipariş bilgileri alınamadı.',
        500
    );
}

if (!$order) {

    pdfError(
        'Sipariş bulunamadı.',
        404
    );
}

/*
|--------------------------------------------------------------------------
| ÇALIŞAN YETKİSİ
|--------------------------------------------------------------------------
|
| Çalışan yalnızca kendi siparişini görebilir.
| Admin bütün siparişleri görebilir.
|
|--------------------------------------------------------------------------
*/

$isAdmin =
    strtoupper((string) $user['rol']) === 'ADMIN';

if (
    !$isAdmin &&
    (int) $order['employee_id'] !== (int) $user['id']
) {

    pdfError(
        'Bu siparişin PDF dosyasına erişim yetkiniz yok.',
        403
    );
}

/*
|--------------------------------------------------------------------------
| PDF CACHE KLASÖRÜ
|--------------------------------------------------------------------------
|
| B/
| └── storage/
|     └── order-pdfs/
|         ├── order-1.pdf
|         ├── order-2.pdf
|         └── ...
|
|--------------------------------------------------------------------------
*/

$storageDir =
    __DIR__ . '/../../storage/order-pdfs';

/*
 * Klasör yoksa oluştur.
 */
if (!is_dir($storageDir)) {

    error_log(
        'PDF CACHE: Klasör bulunamadı, oluşturuluyor: ' .
        $storageDir
    );

    if (
        !mkdir($storageDir, 0775, true) &&
        !is_dir($storageDir)
    ) {

        error_log(
            'PDF CACHE: Klasör oluşturulamadı: ' .
            $storageDir
        );

        pdfError(
            'PDF depolama klasörü oluşturulamadı.',
            500
        );
    }
}

/*
|--------------------------------------------------------------------------
| CACHE DOSYA YOLU
|--------------------------------------------------------------------------
*/

$pdfPath =
    $storageDir .
    '/order-' .
    (int) $order['id'] .
    '.pdf';

/*
|--------------------------------------------------------------------------
| PDF DOSYA ADI
|--------------------------------------------------------------------------
*/

$siparisNo = (string) $order['siparis_no'];

$safeSiparisNo = preg_replace(
    '/[^A-Za-z0-9_-]/',
    '_',
    $siparisNo
);

if (!$safeSiparisNo) {
    $safeSiparisNo = 'Siparis-' . (int) $order['id'];
}

$pdfFileName =
    'Siparis-' .
    $safeSiparisNo .
    '.pdf';

/*
|--------------------------------------------------------------------------
| CACHE KONTROLÜ
|--------------------------------------------------------------------------
|
| PDF daha önce oluşturulmuşsa Dompdf çalıştırılmaz.
|
|--------------------------------------------------------------------------
*/

clearstatcache(true, $pdfPath);

if (is_file($pdfPath)) {

    $cachedSize = filesize($pdfPath);

    if (
        $cachedSize !== false &&
        $cachedSize > 0
    ) {

        error_log(
            'PDF CACHE HIT: Order ID=' .
            $orderId .
            ' | Size=' .
            $cachedSize .
            ' bytes'
        );

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code(200);

        header(
            'Content-Type: application/pdf'
        );

        header(
            'Content-Disposition: inline; filename="' .
            $pdfFileName .
            '"'
        );

        header(
            'Content-Length: ' .
            $cachedSize
        );

        header(
            'Cache-Control: private, max-age=3600'
        );

        header(
            'X-PDF-Cache: HIT'
        );

        readfile($pdfPath);

        exit;
    }

    /*
     * Dosya var ama boşsa yeniden oluştur.
     */
    error_log(
        'PDF CACHE: Boş/geçersiz dosya bulundu: ' .
        $pdfPath
    );
}

/*
|--------------------------------------------------------------------------
| ÜRÜNLER
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        SELECT
            oi.*,
            p.urun_kodu,
            p.urun_adi,
            p.barkod
        FROM order_items oi
        LEFT JOIN products p
            ON p.id = oi.product_id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");

    $stmt->execute([$orderId]);

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {

    error_log(
        'PDF ITEMS HATASI: ' .
        $e->getMessage()
    );

    pdfError(
        'Sipariş ürünleri alınamadı.',
        500
    );
}

/*
|--------------------------------------------------------------------------
| DOMPDF AYARLARI
|--------------------------------------------------------------------------
*/

$options = new Options();

$options->set([
    'isRemoteEnabled' => true,
    'isHtml5ParserEnabled' => true,
    'defaultFont' => 'DejaVu Sans',
]);

$dompdf = new Dompdf($options);

/*
|--------------------------------------------------------------------------
| HTML
|--------------------------------------------------------------------------
*/

ob_start();

?>

<!DOCTYPE html>

<html lang="tr">

<head>

<meta charset="UTF-8">

<style>

@page {
    margin: 35px 35px 45px 35px;
}

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 10px;
    color: #222;
}

.header {
    border-bottom: 2px solid #222;
    padding-bottom: 12px;
    margin-bottom: 18px;
}

.company {
    font-size: 21px;
    font-weight: bold;
}

.title {
    font-size: 18px;
    font-weight: bold;
    margin-top: 5px;
}

.order-number {
    font-size: 12px;
    margin-top: 5px;
}

.info-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 18px;
}

.info-table td {
    border: 1px solid #ddd;
    padding: 7px;
}

.label {
    width: 18%;
    background: #f3f3f3;
    font-weight: bold;
}

.products {
    width: 100%;
    border-collapse: collapse;
}

.products th {
    background: #222;
    color: #fff;
    padding: 7px 4px;
    font-size: 8px;
}

.products td {
    border: 1px solid #ddd;
    padding: 6px 4px;
    font-size: 8px;
}

.text-right {
    text-align: right;
}

.text-center {
    text-align: center;
}

.totals {
    width: 45%;
    margin-left: auto;
    margin-top: 15px;
    border-collapse: collapse;
}

.totals td {
    border: 1px solid #ddd;
    padding: 7px;
}

.total-label {
    background: #f3f3f3;
    font-weight: bold;
}

.grand-total {
    font-size: 12px;
    font-weight: bold;
    background: #222;
    color: #fff;
}

.note {
    margin-top: 20px;
    border: 1px solid #ddd;
    padding: 10px;
}

.footer {
    position: fixed;
    bottom: -25px;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 8px;
    color: #777;
}

</style>

</head>

<body>

<div class="header">

    <div class="company">
        ERP SİPARİŞ SİSTEMİ
    </div>

    <div class="title">
        SİPARİŞ FORMU
    </div>

    <div class="order-number">

        Sipariş No:

        <strong>
            <?= htmlspecialchars(
                $order['siparis_no'] ?? '-',
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </strong>

    </div>

</div>


<table class="info-table">

<tr>

    <td class="label">
        Cari Kodu
    </td>

    <td>
        <?= htmlspecialchars(
            $order['cari_kodu'] ?? '-',
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </td>

    <td class="label">
        Firma
    </td>

    <td>
        <?= htmlspecialchars(
            $order['firma_adi'] ?? '-',
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </td>

</tr>


<tr>

    <td class="label">
        Yetkili
    </td>

    <td>
        <?= htmlspecialchars(
            $order['yetkili_kisi'] ?? '-',
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </td>

    <td class="label">
        Telefon
    </td>

    <td>
        <?= htmlspecialchars(
            $order['telefon'] ?? '-',
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </td>

</tr>


<tr>

    <td class="label">
        Adres
    </td>

    <td colspan="3">

        <?= htmlspecialchars(
            $order['adres'] ?? '-',
            ENT_QUOTES,
            'UTF-8'
        ) ?>

        <?= htmlspecialchars(
            $order['ilce'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        ) ?>

        <?= htmlspecialchars(
            $order['sehir'] ?? '',
            ENT_QUOTES,
            'UTF-8'
        ) ?>

    </td>

</tr>


<tr>

    <td class="label">
        Sisteme Giriş
    </td>

    <td>

        <?= !empty($order['olusturulma_tarihi'])
            ? date(
                'd.m.Y H:i',
                strtotime(
                    $order['olusturulma_tarihi']
                )
            )
            : '-' ?>

    </td>

    <td class="label">
        Son Teslim
    </td>

    <td>

        <?= !empty($order['teslim_tarihi'])
            ? date(
                'd.m.Y',
                strtotime(
                    $order['teslim_tarihi']
                )
            )
            : '-' ?>

    </td>

</tr>


<tr>

    <td class="label">
        Teslim Edilen
    </td>

    <td>

        <?= !empty($order['teslim_edilme_tarihi'])
            ? date(
                'd.m.Y H:i',
                strtotime(
                    $order['teslim_edilme_tarihi']
                )
            )
            : '-' ?>

    </td>

    <td class="label">
        Durum
    </td>

    <td>

        <?= htmlspecialchars(
            $order['durum'] ?? '-',
            ENT_QUOTES,
            'UTF-8'
        ) ?>

    </td>

</tr>

</table>


<table class="products">

<thead>

<tr>

    <th>KOD</th>

    <th>BARKOD</th>

    <th>ÜRÜN ADI</th>

    <th>ADET</th>

    <th>İSK 1</th>

    <th>İSK 2</th>

    <th>İSK 3</th>

    <th>BİRİM FİYAT</th>

    <th>NET TUTAR</th>

</tr>

</thead>


<tbody>

<?php foreach ($items as $item): ?>

<tr>

<td>

    <?= htmlspecialchars(
        $item['urun_kodu'] ?? '-',
        ENT_QUOTES,
        'UTF-8'
    ) ?>

</td>


<td>

    <?= htmlspecialchars(
        $item['barkod'] ?? '-',
        ENT_QUOTES,
        'UTF-8'
    ) ?>

</td>


<td>

    <?= htmlspecialchars(
        $item['urun_adi'] ?? '-',
        ENT_QUOTES,
        'UTF-8'
    ) ?>

</td>


<td class="text-center">

    <?= htmlspecialchars(
        (string)($item['koli_adedi'] ?? 0),
        ENT_QUOTES,
        'UTF-8'
    ) ?>

</td>


<td class="text-center">

    %<?= number_format(
        (float)($item['iskonto_1'] ?? 0),
        2,
        ',',
        '.'
    ) ?>

</td>


<td class="text-center">

    %<?= number_format(
        (float)($item['iskonto_2'] ?? 0),
        2,
        ',',
        '.'
    ) ?>

</td>


<td class="text-center">

    %<?= number_format(
        (float)($item['iskonto_3'] ?? 0),
        2,
        ',',
        '.'
    ) ?>

</td>


<td class="text-right">

    <?= number_format(
        (float)($item['birim_fiyat'] ?? 0),
        2,
        ',',
        '.'
    ) ?> ₺

</td>


<td class="text-right">

    <?= number_format(
        (float)($item['net_tutar'] ?? 0),
        2,
        ',',
        '.'
    ) ?> ₺

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>


<table class="totals">

<tr>

    <td class="total-label">
        Brüt Toplam
    </td>

    <td class="text-right">

        <?= number_format(
            (float)($order['ara_toplam'] ?? 0),
            2,
            ',',
            '.'
        ) ?> ₺

    </td>

</tr>


<tr>

    <td class="total-label">
        Ara Toplam Net
    </td>

    <td class="text-right">

        <?= number_format(
            (float)($order['ara_toplam'] ?? 0),
            2,
            ',',
            '.'
        ) ?> ₺

    </td>

</tr>


<tr>

    <td class="total-label">
        KDV
    </td>

    <td class="text-right">

        <?= number_format(
            (float)($order['kdv_toplam'] ?? 0),
            2,
            ',',
            '.'
        ) ?> ₺

    </td>

</tr>


<tr>

    <td class="grand-total">
        GENEL TOPLAM
    </td>

    <td class="grand-total text-right">

        <?= number_format(
            (float)($order['genel_toplam'] ?? 0),
            2,
            ',',
            '.'
        ) ?> ₺

    </td>

</tr>

</table>


<?php if (!empty($order['note'])): ?>

<div class="note">

    <strong>
        Açıklama / Not:
    </strong>

    <br>
    <br>

    <?= nl2br(
        htmlspecialchars(
            $order['note'],
            ENT_QUOTES,
            'UTF-8'
        )
    ) ?>

</div>

<?php endif; ?>


<div class="footer">

    ERP Sipariş Sistemi —
    <?= date('d.m.Y H:i') ?>

</div>


</body>

</html>

<?php

$html = ob_get_clean();

/*
|--------------------------------------------------------------------------
| DOMPDF OLUŞTUR
|--------------------------------------------------------------------------
*/

try {

    error_log(
        'PDF OLUŞTURULUYOR: Order ID=' .
        $orderId
    );

    $dompdf->loadHtml(
        $html,
        'UTF-8'
    );

    $dompdf->setPaper(
        'A4',
        'portrait'
    );

    $dompdf->render();

    $pdfOutput = $dompdf->output();

    if (
        !is_string($pdfOutput) ||
        strlen($pdfOutput) === 0
    ) {

        error_log(
            'PDF HATASI: Dompdf boş çıktı üretti.'
        );

        pdfError(
            'PDF oluşturuldu ancak boş çıktı alındı.',
            500
        );
    }

    error_log(
        'PDF OLUŞTURULDU: ' .
        strlen($pdfOutput) .
        ' bytes'
    );

} catch (Throwable $e) {

    error_log(
        'PDF OLUŞTURMA HATASI: ' .
        $e->getMessage()
    );

    error_log(
        'PDF HATA DOSYASI: ' .
        $e->getFile() .
        ':' .
        $e->getLine()
    );

    pdfError(
        'PDF oluşturulurken bir hata oluştu.',
        500
    );
}

/*
|--------------------------------------------------------------------------
| CACHE'E KAYDET
|--------------------------------------------------------------------------
*/

try {

    /*
     * Önce geçici dosyaya yazıyoruz.
     * Böylece yarım/bozuk PDF cache'e girmiyor.
     */
    $tempPath =
        $pdfPath .
        '.tmp-' .
        bin2hex(random_bytes(6));

    $bytesWritten = file_put_contents(
        $tempPath,
        $pdfOutput,
        LOCK_EX
    );

    if (
        $bytesWritten === false ||
        $bytesWritten !== strlen($pdfOutput)
    ) {

        error_log(
            'PDF CACHE YAZMA HATASI: ' .
            $pdfPath
        );

        if (is_file($tempPath)) {
            @unlink($tempPath);
        }

    } else {

        /*
         * Geçici dosya başarılıysa gerçek cache dosyasına taşı.
         */
        if (!@rename($tempPath, $pdfPath)) {

            /*
             * Windows/Linux farkları için fallback.
             */
            if (
                !@copy(
                    $tempPath,
                    $pdfPath
                )
            ) {

                error_log(
                    'PDF CACHE TAŞIMA HATASI: ' .
                    $pdfPath
                );

            } else {

                @unlink($tempPath);

                error_log(
                    'PDF CACHE: Başarıyla kaydedildi: ' .
                    $pdfPath
                );
            }

        } else {

            error_log(
                'PDF CACHE: Başarıyla kaydedildi: ' .
                $pdfPath .
                ' | ' .
                $bytesWritten .
                ' bytes'
            );
        }
    }

} catch (Throwable $e) {

    error_log(
        'PDF CACHE EXCEPTION: ' .
        $e->getMessage()
    );

    /*
     * Cache yazılamasa bile PDF'i kullanıcıya gönderiyoruz.
     */
}

/*
|--------------------------------------------------------------------------
| PDF GÖNDER
|--------------------------------------------------------------------------
*/

while (ob_get_level() > 0) {
    ob_end_clean();
}

http_response_code(200);

header(
    'Content-Type: application/pdf'
);

header(
    'Content-Disposition: inline; filename="' .
    $pdfFileName .
    '"'
);

header(
    'Content-Length: ' .
    strlen($pdfOutput)
);

header(
    'Cache-Control: private, max-age=3600'
);

header(
    'X-PDF-Cache: MISS'
);

echo $pdfOutput;

exit;