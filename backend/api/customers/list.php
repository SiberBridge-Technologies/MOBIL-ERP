<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();

try {
    $currentUser = requireAuth();

    $pdo = getDbConnection();

    $search = trim($_GET['q'] ?? '');

    $isAdmin = in_array(
        $currentUser['rol'] ?? '',
        ['ADMIN', 'YONETICI'],
        true
    );

    $sql = 'SELECT c.* FROM customers c';
    $params = [];

    if (!$isAdmin) {
        // Çalışan yalnızca yetkili olduğu carileri görebilir.
        $sql .= '
            JOIN employee_customers ec
                ON ec.customer_id = c.id
                AND ec.employee_id = :employee_id
        ';

        $params['employee_id'] = $currentUser['employee_id'] ?? null;
    }

    $sql .= ' WHERE c.durum = :durum';
    $params['durum'] = 'AKTIF';

    if ($search !== '') {
        $sql .= '
            AND (
                c.firma_adi LIKE :search_firma
                OR c.cari_kodu LIKE :search_kodu
            )
        ';

        $searchValue = '%' . $search . '%';

        $params['search_firma'] = $searchValue;
        $params['search_kodu'] = $searchValue;
    }

    $sql .= ' ORDER BY c.firma_adi ASC LIMIT 50';

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue(
            ':' . $key,
            $value
        );
    }

    $stmt->execute();

    jsonResponse([
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch (Throwable $e) {

    error_log(
        'customers/list.php ERROR: ' .
        $e->getMessage()
    );

    http_response_code(500);

    jsonResponse([
        'success' => false,
        'message' => 'Cari listesi alınırken sunucu hatası oluştu.',
        'error' => $e->getMessage()
    ]);
}