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

$data = getRequestBody();
requireFields($data, ['id']);

$allowedFields = [
    'urun_adi', 'category_id', 'aciklama', 'barkod', 'liste_fiyati', 'koli_fiyati',
    'koli_ici_adet', 'kdv_orani', 'hacim_m3', 'stok', 'birim', 'gorsel_url', 'aktif',
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
$sql = 'UPDATE products SET ' . implode(', ', $setParts) . ' WHERE id = :id';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

jsonResponse(['success' => true, 'message' => 'Ürün güncellendi.']);
