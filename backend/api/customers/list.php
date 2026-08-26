<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();

$pdo = getDbConnection();
$search = trim($_GET['q'] ?? '');

$isAdmin = in_array($currentUser['rol'], ['ADMIN', 'YONETICI'], true);

$sql = 'SELECT c.* FROM customers c';
$params = [];

if (!$isAdmin) {
    // Çalışan yalnızca yetkili olduğu carileri görebilir (bkz. dokümandaki employee_customers ilişkisi)
    $sql .= ' JOIN employee_customers ec ON ec.customer_id = c.id AND ec.employee_id = :employee_id';
    $params['employee_id'] = $currentUser['employee_id'];
}

$sql .= ' WHERE c.durum = "AKTIF"';

if ($search !== '') {
    $sql .= ' AND (c.firma_adi LIKE :search OR c.cari_kodu LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$sql .= ' ORDER BY c.firma_adi ASC LIMIT 50';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
