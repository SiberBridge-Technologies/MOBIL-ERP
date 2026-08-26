<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();

$pdo = getDbConnection();
$isAdmin = in_array($currentUser['rol'], ['ADMIN', 'YONETICI'], true);

$sql = 'SELECT o.*, c.firma_adi FROM orders o JOIN customers c ON c.id = o.customer_id';
$params = [];

if (!$isAdmin) {
    $sql .= ' WHERE o.employee_id = :employee_id';
    $params['employee_id'] = $currentUser['employee_id'];
}

if (!empty($_GET['customer_id'])) {
    $sql .= ($isAdmin ? ' WHERE' : ' AND') . ' o.customer_id = :customer_id';
    $params['customer_id'] = (int) $_GET['customer_id'];
}

$sql .= ' ORDER BY o.olusturulma_tarihi DESC LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
