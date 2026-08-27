<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
requireAuth();

$pdo = getDbConnection();
$categories = $pdo->query('SELECT id, ad, ust_kategori_id FROM categories ORDER BY ad ASC')->fetchAll();

jsonResponse(['success' => true, 'data' => $categories]);
