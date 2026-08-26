<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();

$pdo = getDbConnection();

// Figma "Ürün Arama" ekranı: ürün kodu veya ismine göre arama
$search = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = min(100, max(1, (int) ($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

$where = 'WHERE aktif = 1';
$params = [];

if ($search !== '') {
    $where .= ' AND (urun_kodu LIKE :search OR urun_adi LIKE :search OR barkod LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$stmt = $pdo->prepare(
    "SELECT id, urun_kodu, urun_adi, aciklama, barkod, liste_fiyati, koli_fiyati,
            koli_ici_adet, kdv_orani, hacim_m3, stok, birim, gorsel_url
     FROM products
     $where
     ORDER BY urun_adi ASC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue('limit', $limit, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();

jsonResponse([
    'success' => true,
    'data' => $stmt->fetchAll(),
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => (int) ceil($total / $limit),
    ],
]);
