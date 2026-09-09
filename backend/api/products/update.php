<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();
requireRole($currentUser, ['ADMIN', 'YONETICI']);

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'], true)) {
    jsonError('Sadece POST/PUT isteklerine izin verilir.', 405);
}

requireMethod(['POST', 'PUT']);
$data = getRequestBody();
requireFields($data, ['id']);

$allowedFields = [
    'urun_adi', 'category_id', 'aciklama', 'barkod', 'liste_fiyati', 'koli_fiyati',
    'dip_fiyat', 'koli_ici_adet', 'stand_aktif', 'stand_ici_adet', 'stand_fiyati', 'kdv_orani', 'hacim_m3', 'stok', 'birim', 'gorsel_url', 'aktif',
];

$setParts = [];
$params = ['id' => (int) $data['id']];

foreach ($allowedFields as $field) {
    if (array_key_exists($field, $data)) {
        $setParts[] = "$field = :$field";
        $params[$field] = $data[$field];
    }
}

if (empty($setParts)) {
    jsonError('Güncellenecek alan bulunamadı.', 422);
}

$pdo = getDbConnection();
require_once __DIR__ . '/../../includes/product_validation.php';
$pdo->beginTransaction();
try {
$existing = $pdo->prepare('SELECT * FROM products WHERE id = ? FOR UPDATE'); $existing->execute([$params['id']]);
$product = $existing->fetch();
if (!$product) { $pdo->rollBack(); jsonError('Ürün bulunamadı.',404); }
validateProduct(array_replace($product,$data));
$normalized = normalizeProductPricing(array_replace($product,$data));
foreach (['liste_fiyati','koli_ici_adet','koli_fiyati','stand_aktif','stand_ici_adet','stand_fiyati'] as $field) {
    if (!in_array("$field = :$field", $setParts, true)) $setParts[] = "$field = :$field";
    $params[$field] = $normalized[$field];
}
$sql = 'UPDATE products SET ' . implode(', ', $setParts) . ' WHERE id = :id';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

if (isset($data['stok']) && (int)$data['stok'] !== (int)$product['stok']) {
    $pdo->prepare('INSERT INTO stock_movements (product_id, hareket_tipi, miktar, aciklama) VALUES (?, "DUZELTME", ?, "Ürün stok güncellemesi")')->execute([$params['id'],(int)$data['stok']-(int)$product['stok']]);
}
$pdo->commit();
} catch (DomainException $e) { $pdo->rollBack(); jsonError($e->getMessage(),422);
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); error_log($e->getMessage()); jsonError('Ürün güncellenemedi.',500); }
jsonResponse(['success' => true, 'message' => 'Ürün güncellendi.']);
