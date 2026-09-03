<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();
requireRole($currentUser, ['ADMIN', 'YONETICI']);

$pdo = getDbConnection();
$stmt = $pdo->query(
    'SELECT id, ad, soyad, kullanici_adi, telefon, email, rol, durum, son_giris, olusturulma_tarihi
     FROM employees ORDER BY ad ASC'
);

jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
