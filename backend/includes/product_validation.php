<?php
require_once __DIR__ . '/order_creation.php';
function normalizeProductPricing(array $data): array {
    $unitPrice = orderNumberValue($data['liste_fiyati'] ?? 0, 'Adet fiyatı', 0, 999999999.99);
    $pack = (int)orderNumberValue($data['koli_ici_adet'] ?? 1, 'Koli içi adet', 1, 1000000, true);
    $standActive = (int)orderNumberValue($data['stand_aktif'] ?? 0, 'Stand aktif', 0, 1, true);
    $standUnits = $standActive ? (int)orderNumberValue($data['stand_ici_adet'] ?? 0, 'Stand içi adet', 1, 1000000, true) : null;
    $data['liste_fiyati'] = round($unitPrice, 2);
    $data['koli_ici_adet'] = $pack;
    $data['koli_fiyati'] = round($unitPrice * $pack, 2);
    $data['stand_aktif'] = $standActive;
    $data['stand_ici_adet'] = $standUnits;
    $data['stand_fiyati'] = $standActive ? round($unitPrice * $standUnits, 2) : null;
    return $data;
}
function validateProduct(array $data): void {
    foreach (['urun_kodu','urun_adi'] as $field) if (isset($data[$field]) && (!is_string($data[$field]) || trim($data[$field]) === '')) throw new DomainException('Ürün adı ve kodu boş olamaz.');
    foreach (['liste_fiyati','dip_fiyat','hacim_m3'] as $field) if (isset($data[$field])) orderNumberValue($data[$field],$field,0,999999999.99);
    if (isset($data['koli_ici_adet'])) orderNumberValue($data['koli_ici_adet'],'Koli içi adet',1,1000000,true);
    if (isset($data['stok'])) orderNumberValue($data['stok'],'Stok',0,2147483647,true);
    if (isset($data['kdv_orani'])) orderNumberValue($data['kdv_orani'],'KDV',0,100);
    if (isset($data['aktif'])) orderNumberValue($data['aktif'],'Aktif',0,1,true);
    if (isset($data['stand_aktif'])) orderNumberValue($data['stand_aktif'],'Stand aktif',0,1,true);
    if (!empty($data['stand_aktif'])) orderNumberValue($data['stand_ici_adet'] ?? 0,'Stand içi adet',1,1000000,true);
    if (isset($data['dip_fiyat'],$data['liste_fiyati']) && (float)$data['dip_fiyat'] > (float)$data['liste_fiyati']) throw new DomainException('Dip adet fiyatı adet fiyatından büyük olamaz.');
}
