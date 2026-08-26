<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    jsonError('Geçersiz sipariş id.', 422);
}

$pdo = getDbConnection();

$stmt = $pdo->prepare('SELECT o.*, c.firma_adi FROM orders o JOIN customers c ON c.id = o.customer_id WHERE o.id = :id');
$stmt->execute(['id' => $id]);
$order = $stmt->fetch();

if (!$order) {
    jsonError('Sipariş bulunamadı.', 404);
}

$isAdmin = in_array($currentUser['rol'], ['ADMIN', 'YONETICI'], true);
if (!$isAdmin && (int) $order['employee_id'] !== (int) $currentUser['employee_id']) {
    jsonError('Bu siparişe erişim yetkiniz yok.', 403);
}

$itemsStmt = $pdo->prepare(
    'SELECT oi.*, p.urun_kodu, p.urun_adi
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = :id'
);
$itemsStmt->execute(['id' => $id]);

jsonResponse([
    'success' => true,
    'data' => $order,
    'kalemler' => $itemsStmt->fetchAll(),
]);
