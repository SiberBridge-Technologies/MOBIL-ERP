<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();
requireRole($currentUser, ['ADMIN', 'YONETICI']);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    jsonError('Geçersiz çalışan id.', 422);
}

$pdo = getDbConnection();

$stmt = $pdo->prepare(
    'SELECT id, ad, soyad, kullanici_adi, telefon, email, rol, durum, son_giris, olusturulma_tarihi
     FROM employees WHERE id = :id'
);
$stmt->execute(['id' => $id]);
$employee = $stmt->fetch();

if (!$employee) {
    jsonError('Çalışan bulunamadı.', 404);
}

$custStmt = $pdo->prepare(
    'SELECT c.id, c.cari_kodu, c.firma_adi
     FROM employee_customers ec
     JOIN customers c ON c.id = ec.customer_id
     WHERE ec.employee_id = :id'
);
$custStmt->execute(['id' => $id]);

jsonResponse([
    'success' => true,
    'data' => $employee,
    'atanmis_cariler' => $custStmt->fetchAll(),
]);
