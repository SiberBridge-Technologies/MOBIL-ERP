<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    jsonError('Geçersiz cari id.', 422);
}

if (!employeeCanAccessCustomer((int) $currentUser['employee_id'], $id, $currentUser['rol'])) {
    jsonError('Bu cariye erişim yetkiniz yok.', 403);
}

$pdo = getDbConnection();

$stmt = $pdo->prepare('SELECT * FROM customers WHERE id = :id');
$stmt->execute(['id' => $id]);
$customer = $stmt->fetch();

if (!$customer) {
    jsonError('Cari bulunamadı.', 404);
}

// Figma "Cari Sayfası" ekranı: son 15 sipariş
$ordersStmt = $pdo->prepare(
    'SELECT id, siparis_no, evrak_aciklamasi, teslim_tarihi, genel_toplam, durum, olusturulma_tarihi
     FROM orders
     WHERE customer_id = :id
     ORDER BY olusturulma_tarihi DESC
     LIMIT 15'
);
$ordersStmt->execute(['id' => $id]);

jsonResponse([
    'success' => true,
    'data' => $customer,
    'son_siparisler' => $ordersStmt->fetchAll(),
]);
