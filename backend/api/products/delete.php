<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();
requireRole($currentUser, ['ADMIN', 'YONETICI']);

$data = getRequestBody();
requireFields($data, ['id']);

// Soft delete: aktif = 0 (veri bütünlüğü için gerçek silme yapılmaz)
$pdo = getDbConnection();
$pdo->prepare('UPDATE products SET aktif = 0 WHERE id = :id')
    ->execute(['id' => (int) $data['id']]);

jsonResponse(['success' => true, 'message' => 'Ürün pasif hale getirildi.']);
