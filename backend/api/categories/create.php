<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();
requireRole($currentUser, ['ADMIN', 'YONETICI']);

$data = getRequestBody();
requireFields($data, ['ad']);

$pdo = getDbConnection();
$stmt = $pdo->prepare('INSERT INTO categories (ad, ust_kategori_id) VALUES (:ad, :ust)');
$stmt->execute([
    'ad' => $data['ad'],
    'ust' => $data['ust_kategori_id'] ?? null,
]);

jsonResponse(['success' => true, 'id' => (int) $pdo->lastInsertId()], 201);
