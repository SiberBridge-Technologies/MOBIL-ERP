<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();

try {
    $currentUser = requireAuth();

    $pdo = getDbConnection();

    $search = trim($_GET['q'] ?? '');

    $page = max(
        1,
        (int) ($_GET['page'] ?? 1)
    );

    $limit = min(
        100,
        max(1, (int) ($_GET['limit'] ?? 20))
    );

    $offset = ($page - 1) * $limit;

    $where = 'WHERE aktif = 1';

    $countParams = [];
    $queryParams = [];

    if ($search !== '') {
        $where .= '
            AND (
                urun_kodu LIKE :search_code
                OR urun_adi LIKE :search_name
                OR barkod LIKE :search_barcode
            )
        ';

        $searchValue = '%' . $search . '%';

        $countParams[':search_code'] = $searchValue;
        $countParams[':search_name'] = $searchValue;
        $countParams[':search_barcode'] = $searchValue;

        $queryParams[':search_code'] = $searchValue;
        $queryParams[':search_name'] = $searchValue;
        $queryParams[':search_barcode'] = $searchValue;
    }

    $countSql = "
        SELECT COUNT(*)
        FROM products
        $where
    ";

    $countStmt = $pdo->prepare($countSql);

    foreach ($countParams as $key => $value) {
        $countStmt->bindValue(
            $key,
            $value,
            PDO::PARAM_STR
        );
    }

    $countStmt->execute();

    $total = (int) $countStmt->fetchColumn();

    $sql = "
        SELECT
            id,
            urun_kodu,
            urun_adi,
            aciklama,
            barkod,
            liste_fiyati,
            koli_fiyati,
            dip_fiyat,
            koli_ici_adet,
            kdv_orani,
            hacim_m3,
            stok,
            birim,
            gorsel_url
        FROM products
        $where
        ORDER BY urun_adi ASC
        LIMIT :limit
        OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);

    foreach ($queryParams as $key => $value) {
        $stmt->bindValue(
            $key,
            $value,
            PDO::PARAM_STR
        );
    }

    $stmt->bindValue(
        ':limit',
        $limit,
        PDO::PARAM_INT
    );

    $stmt->bindValue(
        ':offset',
        $offset,
        PDO::PARAM_INT
    );

    $stmt->execute();

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse([
        'success' => true,
        'data' => $products,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => $total > 0
                ? (int) ceil($total / $limit)
                : 0,
        ],
    ]);

} catch (Throwable $e) {

    error_log(
        'products/list.php ERROR: ' .
        $e->getMessage() .
        ' | File: ' .
        $e->getFile() .
        ' | Line: ' .
        $e->getLine()
    );

    http_response_code(500);

    jsonResponse([
        'success' => false,
        'message' => 'Ürünler alınırken sunucu hatası oluştu.',
        'error' => $e->getMessage(),
    ]);
}