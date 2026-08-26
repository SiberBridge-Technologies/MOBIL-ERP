<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
requireAuth();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    jsonError('Geçersiz ürün id.', 422);
}

$pdo = getDbConnection();
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id AND aktif = 1');
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    jsonError('Ürün bulunamadı.', 404);
}

jsonResponse(['success' => true, 'data' => $product]);
