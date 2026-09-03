<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();
requireRole($currentUser, ['ADMIN']);

$data = getRequestBody();
requireFields($data, ['id']);

$pdo = getDbConnection();
$employeeId = (int) $data['id'];

$allowedFields = ['ad', 'soyad', 'telefon', 'email', 'rol', 'durum'];
$setParts = [];
$params = ['id' => $employeeId];

foreach ($allowedFields as $field) {
    if (array_key_exists($field, $data)) {
        $setParts[] = "$field = :$field";
        $params[$field] = $data[$field];
    }
}

// Şifre resetleme
if (!empty($data['yeni_sifre'])) {
    $setParts[] = 'sifre_hash = :sifre_hash';
    $params['sifre_hash'] = password_hash($data['yeni_sifre'], PASSWORD_DEFAULT);
}

if (!empty($setParts)) {
    $sql = 'UPDATE employees SET ' . implode(', ', $setParts) . ' WHERE id = :id';
    $pdo->prepare($sql)->execute($params);
}

// Cari atamalarını tamamen yeniden ayarla (gönderilmişse)
if (isset($data['cari_ids']) && is_array($data['cari_ids'])) {
    $pdo->prepare('DELETE FROM employee_customers WHERE employee_id = :id')->execute(['id' => $employeeId]);
    $assignStmt = $pdo->prepare('INSERT IGNORE INTO employee_customers (employee_id, customer_id) VALUES (:eid, :cid)');
    foreach ($data['cari_ids'] as $cid) {
        $assignStmt->execute(['eid' => $employeeId, 'cid' => (int) $cid]);
    }
}

jsonResponse(['success' => true, 'message' => 'Çalışan güncellendi.']);
