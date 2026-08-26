<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Sadece POST isteklerine izin verilir.', 405);
}

$data = getRequestBody();
requireFields($data, ['customer_id', 'items']);

$customerId = (int) $data['customer_id'];
$items = $data['items']; // [{ product_id, koli_adedi, iskonto_1, iskonto_2, iskonto_3 }, ...]

if (!is_array($items) || count($items) === 0) {
    jsonError('Sipariş en az bir ürün içermelidir.', 422);
}

if (!employeeCanAccessCustomer((int) $currentUser['employee_id'], $customerId, $currentUser['rol'])) {
    jsonError('Bu cariye sipariş oluşturma yetkiniz yok.', 403);
}

$pdo = getDbConnection();

try {
    $pdo->beginTransaction();

    $araToplam = 0;
    $kdvToplam = 0;
    $kalemler = [];

    foreach ($items as $item) {
        $productId = (int) ($item['product_id'] ?? 0);
        $koliAdedi = (int) ($item['koli_adedi'] ?? 0);

        if ($productId <= 0 || $koliAdedi <= 0) {
            throw new InvalidArgumentException('Geçersiz ürün veya koli adedi.');
        }

        // GÜVENLİK: fiyat ve stok bilgisi APK'dan DEĞİL, doğrudan veritabanından alınır.
        // Böylece istemci tarafında fiyat manipülasyonu (örn. 100 TL -> 1 TL) engellenir.
        $prodStmt = $pdo->prepare(
            'SELECT id, urun_kodu, urun_adi, koli_fiyati, koli_ici_adet, kdv_orani, stok
             FROM products WHERE id = :id AND aktif = 1 FOR UPDATE'
        );
        $prodStmt->execute(['id' => $productId]);
        $product = $prodStmt->fetch();

        if (!$product) {
            throw new RuntimeException("Ürün bulunamadı (id: $productId).");
        }

        $adet = $koliAdedi * (int) $product['koli_ici_adet'];

        if ($adet > (int) $product['stok']) {
            throw new RuntimeException("Yetersiz stok: {$product['urun_adi']} (mevcut: {$product['stok']}, istenen: $adet)");
        }

        // İskonto oranları da 0-100 aralığına sıkıştırılır (manipülasyona karşı ekstra doğrulama)
        $isk1 = max(0, min(100, (float) ($item['iskonto_1'] ?? 0)));
        $isk2 = max(0, min(100, (float) ($item['iskonto_2'] ?? 0)));
        $isk3 = max(0, min(100, (float) ($item['iskonto_3'] ?? 0)));

        $birimFiyat = (float) $product['koli_fiyati'];
        $netFiyat = $birimFiyat
            * (1 - $isk1 / 100)
            * (1 - $isk2 / 100)
            * (1 - $isk3 / 100);
        $netTutar = $netFiyat * $koliAdedi;
        $kdvTutari = $netTutar * ((float) $product['kdv_orani'] / 100);

        $araToplam += $netTutar;
        $kdvToplam += $kdvTutari;

        $kalemler[] = [
            'product_id'  => $productId,
            'koli_adedi'  => $koliAdedi,
            'adet'        => $adet,
            'birim_fiyat' => $birimFiyat,
            'iskonto_1'   => $isk1,
            'iskonto_2'   => $isk2,
            'iskonto_3'   => $isk3,
            'net_fiyat'   => $netFiyat,
            'net_tutar'   => $netTutar,
        ];
    }

    $genelToplam = $araToplam + $kdvToplam;
    $siparisNo = generateOrderNumber();

    $orderStmt = $pdo->prepare(
        'INSERT INTO orders
            (siparis_no, customer_id, employee_id, evrak_aciklamasi, teslim_tarihi,
             ambar_bilgisi, odeme_tipi, vade_gun, durum, ara_toplam, kdv_toplam, genel_toplam, note)
         VALUES
            (:siparis_no, :customer_id, :employee_id, :evrak_aciklamasi, :teslim_tarihi,
             :ambar_bilgisi, :odeme_tipi, :vade_gun, "BEKLEMEDE", :ara_toplam, :kdv_toplam, :genel_toplam, :note)'
    );
    $orderStmt->execute([
        'siparis_no'        => $siparisNo,
        'customer_id'       => $customerId,
        'employee_id'       => $currentUser['employee_id'],
        'evrak_aciklamasi'  => $data['evrak_aciklamasi'] ?? null,
        'teslim_tarihi'     => $data['teslim_tarihi'] ?? null,
        'ambar_bilgisi'     => $data['ambar_bilgisi'] ?? null,
        'odeme_tipi'        => $data['odeme_tipi'] ?? 'NAKIT',
        'vade_gun'          => $data['vade_gun'] ?? null,
        'ara_toplam'        => $araToplam,
        'kdv_toplam'        => $kdvToplam,
        'genel_toplam'      => $genelToplam,
        'note'              => $data['note'] ?? null,
    ]);
    $orderId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare(
        'INSERT INTO order_items
            (order_id, product_id, koli_adedi, adet, birim_fiyat, iskonto_1, iskonto_2, iskonto_3, net_fiyat, net_tutar)
         VALUES
            (:order_id, :product_id, :koli_adedi, :adet, :birim_fiyat, :iskonto_1, :iskonto_2, :iskonto_3, :net_fiyat, :net_tutar)'
    );

    foreach ($kalemler as $kalem) {
        $kalem['order_id'] = $orderId;
        $itemStmt->execute($kalem);

        // NOT: Stok düşümü dokümandaki karara göre ONAYLANDI durumuna geçince yapılabilir.
        // Bu örnekte sipariş "BEKLEMEDE" olarak açılır; onay akışı orders/update.php içinde ele alınmalıdır.
    }

    $pdo->commit();

    jsonResponse([
        'success' => true,
        'order_id' => $orderId,
        'siparis_no' => $siparisNo,
        'genel_toplam' => round($genelToplam, 2),
    ], 201);
} catch (InvalidArgumentException | RuntimeException $e) {
    $pdo->rollBack();
    jsonError($e->getMessage(), 422);
} catch (Throwable $e) {
    $pdo->rollBack();
    jsonError('Sipariş oluşturulurken beklenmeyen bir hata oluştu.', 500);
}
