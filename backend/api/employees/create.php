<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();
requireRole($currentUser, ['ADMIN']);

$data = getRequestBody();
requireFields($data, ['ad', 'soyad', 'kullanici_adi', 'sifre']);

$pdo = getDbConnection();
$sifreHash = password_hash($data['sifre'], PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    'INSERT INTO employees (ad, soyad, kullanici_adi, sifre_hash, telefon, email, rol)
     VALUES (:ad, :soyad, :kullanici_adi, :sifre_hash, :telefon, :email, :rol)'
);

try {
    $stmt->execute([
        'ad'            => $data['ad'],
        'soyad'         => $data['soyad'],
        'kullanici_adi' => $data['kullanici_adi'],
        'sifre_hash'    => $sifreHash,
        'telefon'       => $data['telefon'] ?? null,
        'email'         => $data['email'] ?? null,
        'rol'           => $data['rol'] ?? 'CALISAN',
    ]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        jsonError('Bu kullanıcı adı zaten alınmış.', 409);
    }
    jsonError('Çalışan eklenirken hata oluştu.', 500);
}

$employeeId = (int) $pdo->lastInsertId();

// Cari atamaları (opsiyonel)
if (!empty($data['cari_ids']) && is_array($data['cari_ids'])) {
    $assignStmt = $pdo->prepare(
        'INSERT IGNORE INTO employee_customers (employee_id, customer_id) VALUES (:eid, :cid)'
    );
    foreach ($data['cari_ids'] as $cid) {
        $assignStmt->execute(['eid' => $employeeId, 'cid' => (int) $cid]);
    }
}

jsonResponse(['success' => true, 'id' => $employeeId], 201);
