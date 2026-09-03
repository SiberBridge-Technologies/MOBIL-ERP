<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();
requireRole($currentUser, ['ADMIN', 'YONETICI']);

$data = getRequestBody();
requireFields($data, ['id']);

$allowedFields = [
    'firma_adi', 'yetkili_kisi', 'telefon', 'email', 'adres', 'sehir', 'ilce',
    'vergi_dairesi', 'vergi_no', 'not_metni', 'durum',
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
$sql = 'UPDATE customers SET ' . implode(', ', $setParts) . ' WHERE id = :id';
$pdo->prepare($sql)->execute($params);

jsonResponse(['success' => true, 'message' => 'Cari güncellendi.']);
