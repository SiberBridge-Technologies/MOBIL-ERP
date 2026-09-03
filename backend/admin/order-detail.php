<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$pdo = getDbConnection();

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: orders.php');
    exit;
}

$error = null;
$success = null;

/*
|--------------------------------------------------------------------------
| Sipariş bilgilerini getir
|--------------------------------------------------------------------------
*/

function getOrder(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT
            o.*,
            c.firma_adi,
            c.cari_kodu,
            c.telefon,
            c.email,
            c.adres,
            c.sehir,
            c.ilce,
            c.vergi_dairesi,
            c.vergi_no,
            e.ad AS calisan_ad,
            e.soyad AS calisan_soyad
         FROM orders o
         INNER JOIN customers c
             ON c.id = o.customer_id
         INNER JOIN employees e
             ON e.id = o.employee_id
         WHERE o.id = :id
         LIMIT 1'
    );

    $stmt->execute([
        'id' => $id
    ]);

    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    return $order ?: null;
}

$order = getOrder($pdo, $id);

if (!$order) {
    header('Location: orders.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Durumlar
|--------------------------------------------------------------------------
*/

$durumEtiket = [
    'TASLAK'      => 'Taslak',
    'BEKLEMEDE'   => 'Beklemede',
    'ONAYLANDI'   => 'Onaylandı',
    'TAMAMLANDI'  => 'Tamamlandı',
    'IPTAL'       => 'İptal',
];

$durumRenk = [
    'TASLAK'      => 'pill-muted',
    'BEKLEMEDE'   => 'pill-warning',
    'ONAYLANDI'   => 'pill-success',
    'TAMAMLANDI'  => 'pill-success',
    'IPTAL'       => 'pill-danger',
];

$gecerliDurumlar = array_keys($durumEtiket);

/*
|--------------------------------------------------------------------------
| Durum güncelleme
|--------------------------------------------------------------------------
|
| ONAYLANDI:
|   Sipariş ürünlerinin stokları düşürülür.
|
| TAMAMLANDI:
|   teslim_edilme_tarihi otomatik olarak NOW() yapılır.
|
| Daha önce TAMAMLANDI olmuş bir sipariş tekrar güncellenirse
| teslim_edilme_tarihi korunur.
|
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['yeni_durum'])) {

    $yeniDurum = trim((string) $_POST['yeni_durum']);
    $eskiDurum = (string) $order['durum'];

    if (!in_array($yeniDurum, $gecerliDurumlar, true)) {

        $error = 'Geçersiz sipariş durumu seçildi.';

    } elseif ($yeniDurum === $eskiDurum) {

        $success = 'Sipariş durumu zaten bu durumda.';

    } else {

        try {

            $pdo->beginTransaction();

            /*
            |--------------------------------------------------------------------------
            | ONAYLANDI → stok düşümü
            |--------------------------------------------------------------------------
            */

            if (
                $yeniDurum === 'ONAYLANDI' &&
                $eskiDurum !== 'ONAYLANDI'
            ) {

                /*
                 * Sipariş daha önce ONAYLANDI olmuş mu?
                 *
                 * Stok hareketlerinden kontrol ediyoruz.
                 * Böylece örneğin:
                 *
                 * ONAYLANDI → BEKLEMEDE → ONAYLANDI
                 *
                 * yapılırsa stok ikinci kez düşmez.
                 */

                $stockCheckStmt = $pdo->prepare(
                    'SELECT COUNT(*)
                     FROM stock_movements
                     WHERE referans_order_id = :order_id
                       AND hareket_tipi = "SIPARIS"'
                );

                $stockCheckStmt->execute([
                    'order_id' => $id
                ]);

                $stockAlreadyDeducted =
                    (int) $stockCheckStmt->fetchColumn() > 0;

                if (!$stockAlreadyDeducted) {

                    $itemsStmt = $pdo->prepare(
                        'SELECT
                            product_id,
                            adet
                         FROM order_items
                         WHERE order_id = :order_id
                         ORDER BY id ASC'
                    );

                    $itemsStmt->execute([
                        'order_id' => $id
                    ]);

                    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($items as $item) {

                        $productId = (int) $item['product_id'];
                        $adet = (int) $item['adet'];

                        if ($productId <= 0 || $adet <= 0) {
                            throw new RuntimeException(
                                'Sipariş kalemlerinden birinde geçersiz ürün veya adet bulundu.'
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Stok düş
                        |--------------------------------------------------------------------------
                        */

                        $upd = $pdo->prepare(
                            'UPDATE products
                             SET stok = stok - :adet
                             WHERE id = :product_id
                               AND stok >= :minimum_stok'
                        );

                        $upd->execute([
                            'adet' => $adet,
                            'product_id' => $productId,
                            'minimum_stok' => $adet,
                        ]);

                        if ($upd->rowCount() === 0) {

                            throw new RuntimeException(
                                'Yetersiz stok nedeniyle sipariş onaylanamadı. Ürün ID: ' .
                                $productId
                            );
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Stok hareketi
                        |--------------------------------------------------------------------------
                        */

                        $movementStmt = $pdo->prepare(
                            'INSERT INTO stock_movements
                                (
                                    product_id,
                                    hareket_tipi,
                                    miktar,
                                    referans_order_id,
                                    aciklama
                                )
                             VALUES
                                (
                                    :product_id,
                                    "SIPARIS",
                                    :miktar,
                                    :order_id,
                                    :aciklama
                                )'
                        );

                        $movementStmt->execute([
                            'product_id' => $productId,
                            'miktar' => -$adet,
                            'order_id' => $id,
                            'aciklama' => 'Sipariş onayı ile stok düşümü',
                        ]);
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Durum ve teslim edilen tarih
            |--------------------------------------------------------------------------
            */

            if ($yeniDurum === 'TAMAMLANDI') {

                /*
                 * Sadece ilk kez TAMAMLANDI olduğunda tarih yazılır.
                 *
                 * Eğer teslim_edilme_tarihi zaten varsa korunur.
                 */

                $updateStmt = $pdo->prepare(
                    'UPDATE orders
                     SET
                        durum = :durum,
                        teslim_edilme_tarihi =
                            COALESCE(teslim_edilme_tarihi, NOW())
                     WHERE id = :id'
                );

                $updateStmt->execute([
                    'durum' => $yeniDurum,
                    'id' => $id,
                ]);

            } else {

                /*
                 * Diğer durumlarda teslim edilen tarih değiştirilmez.
                 */

                $updateStmt = $pdo->prepare(
                    'UPDATE orders
                     SET durum = :durum
                     WHERE id = :id'
                );

                $updateStmt->execute([
                    'durum' => $yeniDurum,
                    'id' => $id,
                ]);
            }

            $pdo->commit();

            $success = 'Sipariş durumu başarıyla güncellendi.';

            /*
            |--------------------------------------------------------------------------
            | Güncel siparişi tekrar getir
            |--------------------------------------------------------------------------
            */

            $order = getOrder($pdo, $id);

            if (!$order) {
                throw new RuntimeException(
                    'Sipariş güncellendi ancak güncel kayıt okunamadı.'
                );
            }

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'Sipariş durum güncelleme hatası: ' .
                $e->getMessage()
            );

            $error = $e->getMessage();
        }
    }
}

/*
|--------------------------------------------------------------------------
| Sipariş kalemleri
|--------------------------------------------------------------------------
*/

$itemsStmt = $pdo->prepare(
    'SELECT
        oi.*,
        p.urun_kodu,
        p.urun_adi,
        p.barkod,
        p.kdv_orani,
        p.birim
     FROM order_items oi
     INNER JOIN products p
         ON p.id = oi.product_id
     WHERE oi.order_id = :order_id
     ORDER BY oi.id ASC'
);

$itemsStmt->execute([
    'order_id' => $id
]);

$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Toplamlar
|--------------------------------------------------------------------------
*/

$brutToplam = 0.0;
$toplamIskonto = 0.0;

foreach ($items as $item) {

    $koliAdedi = (float) ($item['koli_adedi'] ?? 0);
    $birimFiyat = (float) ($item['birim_fiyat'] ?? 0);
    $netTutar = (float) ($item['net_tutar'] ?? 0);

    $brutTutar = $birimFiyat * $koliAdedi;

    $brutToplam += $brutTutar;
    $toplamIskonto += max(
        0,
        $brutTutar - $netTutar
    );
}

/*
|--------------------------------------------------------------------------
| Sayfa bilgileri
|--------------------------------------------------------------------------
*/

$pageTitle = 'Sipariş Detay';

$pageSubtitle =
    $order['siparis_no'] .
    ' — ' .
    $order['firma_adi'];

$activePage = 'orders';

require __DIR__ . '/../includes/admin_header.php';

?>

<?php if ($success): ?>

    <div class="alert alert-success">
        <?= e($success) ?>
    </div>

<?php endif; ?>


<?php if ($error): ?>

    <div class="alert alert-error">
        <?= e($error) ?>
    </div>

<?php endif; ?>


<!-- ========================================================= -->
<!-- ÜST AKSİYONLAR -->
<!-- ========================================================= -->

<div
    style="
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:12px;
        margin-bottom:24px;
        flex-wrap:wrap;
    "
>

    <div>

        <a
            href="orders.php"
            class="btn"
            style="text-decoration:none;"
        >
            ← Siparişlere Dön
        </a>

    </div>

    <div>

        <a
            href="../api/orders/pdf.php?id=<?= $id ?>"
            target="_blank"
            rel="noopener noreferrer"
            class="btn btn-primary"
            style="
                text-decoration:none;
                display:inline-block;
            "
        >
            📄 PDF Çıktısı
        </a>

    </div>

</div>


<!-- ========================================================= -->
<!-- ANA İÇERİK -->
<!-- ========================================================= -->

<div
    style="
        display:grid;
        grid-template-columns:minmax(0, 2fr) minmax(300px, 1fr);
        gap:24px;
        align-items:start;
    "
>


    <!-- ===================================================== -->
    <!-- SOL TARAF -->
    <!-- ===================================================== -->

    <div>

        <!-- Sipariş Kalemleri -->

        <div class="card">

            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                    gap:12px;
                    margin-bottom:16px;
                "
            >

                <h3 style="margin:0;">
                    Sipariş Kalemleri
                </h3>

                <span class="pill <?= $durumRenk[$order['durum']] ?? 'pill-muted' ?>">
                    <?= e($durumEtiket[$order['durum']] ?? $order['durum']) ?>
                </span>

            </div>


            <div style="overflow-x:auto;">

                <table>

                    <thead>

                        <tr>

                            <th>Ürün Kodu</th>

                            <th>Barkod</th>

                            <th>Ürün Adı</th>

                            <th>Koli</th>

                            <th>Adet</th>

                            <th>İsk1</th>

                            <th>İsk2</th>

                            <th>İsk3</th>

                            <th>Net Fiyat</th>

                            <th>Net Tutar</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (!$items): ?>

                            <tr>

                                <td
                                    colspan="10"
                                    style="
                                        text-align:center;
                                        padding:30px;
                                        color:var(--muted-foreground);
                                    "
                                >
                                    Siparişte ürün bulunmamaktadır.
                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($items as $it): ?>

                                <tr>

                                    <td>
                                        <?= e($it['urun_kodu']) ?>
                                    </td>

                                    <td>
                                        <?= e($it['barkod'] ?: '-') ?>
                                    </td>

                                    <td>
                                        <?= e($it['urun_adi']) ?>
                                    </td>

                                    <td>
                                        <?= (int) $it['koli_adedi'] ?>
                                    </td>

                                    <td>
                                        <?= (int) $it['adet'] ?>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float) $it['iskonto_1'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>%
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float) $it['iskonto_2'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>%
                                    </td>

                                    <td>
                                        <?= number_format(
                                            (float) $it['iskonto_3'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>%
                                    </td>

                                    <td>
                                        <?= formatTL(
                                            (float) $it['net_fiyat']
                                        ) ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= formatTL(
                                                (float) $it['net_tutar']
                                            ) ?>
                                        </strong>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>


            <!-- ================================================= -->
            <!-- TOPLAMLAR -->
            <!-- ================================================= -->

            <div
                style="
                    margin-top:20px;
                    margin-left:auto;
                    max-width:360px;
                    border-top:1px solid var(--border);
                    padding-top:14px;
                "
            >

                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        margin-bottom:8px;
                    "
                >
                    <span>Brüt Toplam</span>

                    <strong>
                        <?= formatTL($brutToplam) ?>
                    </strong>
                </div>


                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        margin-bottom:8px;
                    "
                >
                    <span>Toplam İskonto</span>

                    <strong>
                        <?= formatTL($toplamIskonto) ?>
                    </strong>
                </div>


                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        margin-bottom:8px;
                    "
                >
                    <span>Ara Toplam Net</span>

                    <strong>
                        <?= formatTL(
                            (float) $order['ara_toplam']
                        ) ?>
                    </strong>
                </div>


                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        margin-bottom:8px;
                    "
                >
                    <span>KDV</span>

                    <strong>
                        <?= formatTL(
                            (float) $order['kdv_toplam']
                        ) ?>
                    </strong>
                </div>


                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        margin-top:10px;
                        padding-top:12px;
                        border-top:2px solid var(--border);
                        font-size:18px;
                    "
                >

                    <span>
                        Genel Toplam
                    </span>

                    <strong>
                        <?= formatTL(
                            (float) $order['genel_toplam']
                        ) ?>
                    </strong>

                </div>

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- TARİHLER -->
        <!-- ===================================================== -->

        <div class="card">

            <h3>
                Tarih Bilgileri
            </h3>

            <div
                style="
                    display:grid;
                    grid-template-columns:repeat(3, 1fr);
                    gap:12px;
                "
            >

                <div
                    style="
                        border:1px solid var(--border);
                        padding:14px;
                        border-radius:8px;
                    "
                >

                    <div
                        style="
                            font-size:12px;
                            color:var(--muted-foreground);
                            margin-bottom:6px;
                        "
                    >
                        Sisteme Giriş Tarihi
                    </div>

                    <strong>
                        <?= formatTarih(
                            $order['olusturulma_tarihi']
                        ) ?>
                    </strong>

                </div>


                <div
                    style="
                        border:1px solid var(--border);
                        padding:14px;
                        border-radius:8px;
                    "
                >

                    <div
                        style="
                            font-size:12px;
                            color:var(--muted-foreground);
                            margin-bottom:6px;
                        "
                    >
                        Teslim Edilen Tarih
                    </div>

                    <strong>

                        <?php if (!empty($order['teslim_edilme_tarihi'])): ?>

                            <?= formatTarih(
                                $order['teslim_edilme_tarihi']
                            ) ?>

                        <?php else: ?>

                            -

                        <?php endif; ?>

                    </strong>

                </div>


                <div
                    style="
                        border:1px solid var(--border);
                        padding:14px;
                        border-radius:8px;
                    "
                >

                    <div
                        style="
                            font-size:12px;
                            color:var(--muted-foreground);
                            margin-bottom:6px;
                        "
                    >
                        Son Teslim Tarihi
                    </div>

                    <strong>

                        <?php if (!empty($order['teslim_tarihi'])): ?>

                            <?= formatTarih(
                                $order['teslim_tarihi']
                            ) ?>

                        <?php else: ?>

                            -

                        <?php endif; ?>

                    </strong>

                </div>

            </div>

        </div>


        <!-- ===================================================== -->
        <!-- NOT -->
        <!-- ===================================================== -->

        <?php if (!empty($order['note'])): ?>

            <div class="card">

                <h3>
                    Sipariş Notu
                </h3>

                <div
                    style="
                        white-space:pre-wrap;
                        line-height:1.6;
                    "
                >
                    <?= e($order['note']) ?>
                </div>

            </div>

        <?php endif; ?>

    </div>


    <!-- ===================================================== -->
    <!-- SAĞ TARAF -->
    <!-- ===================================================== -->

    <div>


        <!-- ===================================================== -->
        <!-- PDF -->
        <!-- ===================================================== -->

        <div class="card">

            <h3>
                Belge İşlemleri
            </h3>

            <p
                style="
                    color:var(--muted-foreground);
                    font-size:13px;
                    line-height:1.5;
                "
            >
                Siparişin profesyonel A4 formatındaki PDF belgesini
                oluşturabilirsiniz.
            </p>

            <a
                href="../api/orders/pdf.php?id=<?= $id ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="btn btn-primary"
                style="
                    width:100%;
                    display:block;
                    text-align:center;
                    text-decoration:none;
                    margin-top:12px;
                "
            >
                📄 PDF Çıktısı
            </a>

        </div>


        <!-- ===================================================== -->
        <!-- MÜŞTERİ -->
        <!-- ===================================================== -->

        <div class="card">

            <h3>
                Müşteri Bilgileri
            </h3>

            <p>
                <strong>Firma:</strong><br>
                <?= e($order['firma_adi']) ?>
            </p>

            <p>
                <strong>Cari Kodu:</strong><br>
                <?= e($order['cari_kodu'] ?: '-') ?>
            </p>

            <p>
                <strong>Telefon:</strong><br>
                <?= e($order['telefon'] ?: '-') ?>
            </p>

            <p>
                <strong>E-posta:</strong><br>
                <?= e($order['email'] ?: '-') ?>
            </p>

            <p>
                <strong>Adres:</strong><br>

                <?= e($order['adres'] ?: '-') ?>

                <?php if (!empty($order['ilce'])): ?>

                    <br><?= e($order['ilce']) ?>

                <?php endif; ?>

                <?php if (!empty($order['sehir'])): ?>

                    <br><?= e($order['sehir']) ?>

                <?php endif; ?>

            </p>

        </div>


        <!-- ===================================================== -->
        <!-- SİPARİŞ BİLGİLERİ -->
        <!-- ===================================================== -->

        <div class="card">

            <h3>
                Sipariş Bilgileri
            </h3>

            <p>
                <strong>Sipariş No:</strong><br>
                <?= e($order['siparis_no']) ?>
            </p>

            <p>
                <strong>Çalışan:</strong><br>

                <?= e(
                    trim(
                        ($order['calisan_ad'] ?? '') .
                        ' ' .
                        ($order['calisan_soyad'] ?? '')
                    )
                ) ?>

            </p>

            <p>
                <strong>Ödeme Tipi:</strong><br>

                <?= $order['odeme_tipi'] === 'VADELI'
                    ? 'Vadeli'
                    : 'Nakit'
                ?>

                <?php if (
                    $order['odeme_tipi'] === 'VADELI' &&
                    !empty($order['vade_gun'])
                ): ?>

                    (<?= (int) $order['vade_gun'] ?> gün)

                <?php endif; ?>

            </p>

            <p>
                <strong>Ambar:</strong><br>
                <?= e($order['ambar_bilgisi'] ?: '-') ?>
            </p>

            <p>
                <strong>Evrak Açıklaması:</strong><br>
                <?= e($order['evrak_aciklamasi'] ?: '-') ?>
            </p>

            <p>
                <strong>Sisteme Giriş:</strong><br>
                <?= formatTarih(
                    $order['olusturulma_tarihi']
                ) ?>
            </p>

        </div>


        <!-- ===================================================== -->
        <!-- DURUM -->
        <!-- ===================================================== -->

        <div class="card">

            <h3>
                Durum Güncelle
            </h3>

            <p>

                Mevcut durum:

                <span
                    class="pill <?= $durumRenk[$order['durum']] ?? 'pill-muted' ?>"
                >
                    <?= e(
                        $durumEtiket[$order['durum']]
                        ?? $order['durum']
                    ) ?>
                </span>

            </p>


            <form
                method="POST"
                action="order-detail.php?id=<?= $id ?>"
            >

                <div class="form-group">

                    <label>
                        Yeni Durum
                    </label>

                    <select name="yeni_durum" required>

                        <?php foreach ($durumEtiket as $d => $etiket): ?>

                            <option
                                value="<?= e($d) ?>"
                                <?= $order['durum'] === $d
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= e($etiket) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <button
                    type="submit"
                    class="btn btn-primary"
                    style="width:100%;"
                >
                    Durumu Güncelle
                </button>

            </form>


            <div
                style="
                    margin-top:14px;
                    padding:10px;
                    background:var(--muted);
                    border-radius:8px;
                    font-size:12px;
                    line-height:1.5;
                    color:var(--muted-foreground);
                "
            >

                <strong>Bilgi:</strong>

                <br>

                • <strong>Onaylandı</strong> durumuna geçişte
                stok otomatik olarak düşülür.

                <br>

                • <strong>Tamamlandı</strong> durumuna geçişte
                teslim edilen tarih ve saat otomatik olarak kaydedilir.

                <br>

                • Daha önce kaydedilmiş teslim tarihi
                tekrar değiştirilmez.

            </div>

        </div>

    </div>

</div>


<?php

require __DIR__ . '/../includes/admin_footer.php';

?>
```
