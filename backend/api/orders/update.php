<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();
requireRole($currentUser, ['ADMIN', 'YONETICI']); // durum değişikliği sadece yönetici/admin

$data = getRequestBody();
requireFields($data, ['id', 'durum']);

$gecerliDurumlar = ['TASLAK', 'BEKLEMEDE', 'ONAYLANDI', 'TAMAMLANDI', 'IPTAL'];
if (!in_array($data['durum'], $gecerliDurumlar, true)) {
    jsonError('Geçersiz sipariş durumu.', 422);
}

$pdo = getDbConnection();
$orderId = (int) $data['id'];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT durum FROM orders WHERE id = :id FOR UPDATE');
    $stmt->execute(['id' => $orderId]);
    $mevcutDurum = $stmt->fetchColumn();

    if ($mevcutDurum === false) {
        throw new RuntimeException('Sipariş bulunamadı.');
    }

    // Stok düşümü: yalnızca BEKLEMEDE -> ONAYLANDI geçişinde bir kez yapılır
    if ($data['durum'] === 'ONAYLANDI' && $mevcutDurum !== 'ONAYLANDI') {
        $itemsStmt = $pdo->prepare('SELECT product_id, adet FROM order_items WHERE order_id = :id');
        $itemsStmt->execute(['id' => $orderId]);

        foreach ($itemsStmt->fetchAll() as $item) {
            $updateStock = $pdo->prepare(
                'UPDATE products SET stok = stok - :adet WHERE id = :pid AND stok >= :adet2'
            );
            $updateStock->execute([
                'adet'  => $item['adet'],
                'pid'   => $item['product_id'],
                'adet2' => $item['adet'],
            ]);

            if ($updateStock->rowCount() === 0) {
                throw new RuntimeException('Yetersiz stok, onay işlemi iptal edildi (ürün id: ' . $item['product_id'] . ').');
            }

            $pdo->prepare(
                'INSERT INTO stock_movements (product_id, hareket_tipi, miktar, referans_order_id, aciklama)
                 VALUES (:pid, "SIPARIS", :miktar, :order_id, "Sipariş onayı ile stok düşümü")'
            )->execute([
                'pid'      => $item['product_id'],
                'miktar'   => -$item['adet'],
                'order_id' => $orderId,
            ]);
        }
    }

    $pdo->prepare('UPDATE orders SET durum = :durum WHERE id = :id')
        ->execute(['durum' => $data['durum'], 'id' => $orderId]);

    $pdo->commit();
    jsonResponse(['success' => true, 'message' => 'Sipariş durumu güncellendi.']);
} catch (Throwable $e) {
    $pdo->rollBack();
    jsonError($e->getMessage(), 422);
}
