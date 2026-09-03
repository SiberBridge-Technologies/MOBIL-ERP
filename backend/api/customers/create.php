<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();
requireRole($currentUser, ['ADMIN', 'YONETICI']);

$data = getRequestBody();
requireFields($data, ['cari_kodu', 'firma_adi']);

$pdo = getDbConnection();
$stmt = $pdo->prepare(
    'INSERT INTO customers
        (cari_kodu, firma_adi, yetkili_kisi, telefon, email, adres, sehir, ilce, vergi_dairesi, vergi_no, not_metni)
     VALUES
        (:cari_kodu, :firma_adi, :yetkili_kisi, :telefon, :email, :adres, :sehir, :ilce, :vergi_dairesi, :vergi_no, :not_metni)'
);

try {
    $stmt->execute([
        'cari_kodu'      => $data['cari_kodu'],
        'firma_adi'      => $data['firma_adi'],
        'yetkili_kisi'   => $data['yetkili_kisi'] ?? null,
        'telefon'        => $data['telefon'] ?? null,
        'email'          => $data['email'] ?? null,
        'adres'          => $data['adres'] ?? null,
        'sehir'          => $data['sehir'] ?? null,
        'ilce'           => $data['ilce'] ?? null,
        'vergi_dairesi'  => $data['vergi_dairesi'] ?? null,
        'vergi_no'       => $data['vergi_no'] ?? null,
        'not_metni'      => $data['not_metni'] ?? null,
    ]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        jsonError('Bu cari kodu zaten kayıtlı.', 409);
    }
    jsonError('Cari eklenirken hata oluştu.', 500);
}

jsonResponse(['success' => true, 'id' => (int) $pdo->lastInsertId()], 201);
