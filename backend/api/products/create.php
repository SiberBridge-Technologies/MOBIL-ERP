<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();
$currentUser = requireAuth();
requireRole($currentUser, ['ADMIN', 'YONETICI']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Sadece POST isteklerine izin verilir.', 405);
}

requireMethod(['POST']);
$data = getRequestBody();
requireFields($data, ['urun_kodu', 'urun_adi', 'liste_fiyati']);

require_once __DIR__ . '/../../includes/product_validation.php';
try { validateProduct($data); $data = normalizeProductPricing($data); } catch (DomainException $e) { jsonError($e->getMessage(), 422); }
$pdo = getDbConnection();

$stmt = $pdo->prepare(
    'INSERT INTO products
        (urun_kodu, urun_adi, category_id, aciklama, barkod, liste_fiyati, koli_fiyati,
         dip_fiyat, koli_ici_adet, stand_aktif, stand_ici_adet, stand_fiyati, kdv_orani, hacim_m3, stok, birim, gorsel_url)
     VALUES
        (:urun_kodu, :urun_adi, :category_id, :aciklama, :barkod, :liste_fiyati, :koli_fiyati,
         :dip_fiyat, :koli_ici_adet, :stand_aktif, :stand_ici_adet, :stand_fiyati, :kdv_orani, :hacim_m3, :stok, :birim, :gorsel_url)'
);

try {
    $stmt->execute([
        'urun_kodu'     => $data['urun_kodu'],
        'urun_adi'      => $data['urun_adi'],
        'category_id'   => $data['category_id'] ?? null,
        'aciklama'      => $data['aciklama'] ?? null,
        'barkod'        => $data['barkod'] ?? null,
        'liste_fiyati'  => $data['liste_fiyati'],
        'koli_fiyati'   => $data['koli_fiyati'] ?? 0,
        'dip_fiyat' => $data['dip_fiyat'] ?? 0,
        'koli_ici_adet' => $data['koli_ici_adet'] ?? 1,
        'stand_aktif' => $data['stand_aktif'],
        'stand_ici_adet' => $data['stand_ici_adet'],
        'stand_fiyati' => $data['stand_fiyati'],
        'kdv_orani'     => $data['kdv_orani'] ?? 20.00,
        'hacim_m3'      => $data['hacim_m3'] ?? 0,
        'stok'          => $data['stok'] ?? 0,
        'birim'         => $data['birim'] ?? 'ADET',
        'gorsel_url'    => $data['gorsel_url'] ?? null,
    ]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        jsonError('Bu ürün kodu zaten kayıtlı.', 409);
    }
    jsonError('Ürün eklenirken hata oluştu.', 500);
}

jsonResponse(['success' => true, 'id' => (int) $pdo->lastInsertId()], 201);
