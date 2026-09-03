<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();
requireRole($currentUser, ['ADMIN', 'YONETICI']);

$data = getRequestBody();
requireFields($data, ['id']);

$pdo = getDbConnection();
$pdo->prepare('DELETE FROM categories WHERE id = :id')->execute(['id' => (int) $data['id']]);

jsonResponse(['success' => true, 'message' => 'Kategori silindi.']);
