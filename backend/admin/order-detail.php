<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$pdo = getDbConnection();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT o.*, c.firma_adi, c.telefon, e.ad AS calisan_ad, e.soyad AS calisan_soyad
     FROM orders o
     JOIN customers c ON c.id = o.customer_id
     JOIN employees e ON e.id = o.employee_id
     WHERE o.id = :id'
);
$stmt->execute(['id' => $id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

$error = null;
$success = null;

// Durum güncelleme (stok düşümü ONAYLANDI'da yapılır — dokümandaki karar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['yeni_durum'])) {
    $yeniDurum = $_POST['yeni_durum'];
    try {
        $pdo->beginTransaction();

        if ($yeniDurum === 'ONAYLANDI' && $order['durum'] !== 'ONAYLANDI') {
            $itemsStmt = $pdo->prepare('SELECT product_id, adet FROM order_items WHERE order_id = :id');
            $itemsStmt->execute(['id' => $id]);
            foreach ($itemsStmt->fetchAll() as $item) {
                $upd = $pdo->prepare('UPDATE products SET stok = stok - :adet WHERE id = :pid AND stok >= :adet2');
                $upd->execute(['adet' => $item['adet'], 'pid' => $item['product_id'], 'adet2' => $item['adet']]);
                if ($upd->rowCount() === 0) {
                    throw new RuntimeException('Yetersiz stok (ürün id: ' . $item['product_id'] . '). Onay iptal edildi.');
                }
                $pdo->prepare(
                    'INSERT INTO stock_movements (product_id, hareket_tipi, miktar, referans_order_id, aciklama)
                     VALUES (:pid, "SIPARIS", :miktar, :oid, "Sipariş onayı ile stok düşümü")'
                )->execute(['pid' => $item['product_id'], 'miktar' => -$item['adet'], 'oid' => $id]);
            }
        }

        $pdo->prepare('UPDATE orders SET durum = :durum WHERE id = :id')
            ->execute(['durum' => $yeniDurum, 'id' => $id]);

        $pdo->commit();
        $success = 'Sipariş durumu güncellendi.';

        $stmt2 = $pdo->prepare(
            'SELECT o.*, c.firma_adi, c.telefon, e.ad AS calisan_ad, e.soyad AS calisan_soyad
             FROM orders o JOIN customers c ON c.id = o.customer_id JOIN employees e ON e.id = o.employee_id
             WHERE o.id = :id'
        );
        $stmt2->execute(['id' => $id]);
        $order = $stmt2->fetch();
    } catch (Throwable $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

$itemsStmt = $pdo->prepare(
    'SELECT oi.*, p.urun_kodu, p.urun_adi
     FROM order_items oi JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = :id'
);
$itemsStmt->execute(['id' => $id]);
$items = $itemsStmt->fetchAll();

$pageTitle = 'Sipariş Detay — ' . $order['siparis_no'];
$activePage = 'orders';
require __DIR__ . '/../includes/admin_header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:24px;">
    <div class="card">
        <h3 style="margin-top:0;">Sipariş Kalemleri</h3>
        <table>
            <thead>
                <tr><th>Ürün Kodu</th><th>Ürün Adı</th><th>Koli</th><th>Adet</th><th>Net Fiyat</th><th>Net Tutar</th></tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                <tr>
                    <td><?= e($it['urun_kodu']) ?></td>
                    <td><?= e($it['urun_adi']) ?></td>
                    <td><?= (int) $it['koli_adedi'] ?></td>
                    <td><?= (int) $it['adet'] ?></td>
                    <td><?= formatTL((float) $it['net_fiyat']) ?></td>
                    <td><?= formatTL((float) $it['net_tutar']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="text-align:right; margin-top:16px; font-size:15px;">
            <div>Ara Toplam: <strong><?= formatTL((float) $order['ara_toplam']) ?></strong></div>
            <div>KDV Toplam: <strong><?= formatTL((float) $order['kdv_toplam']) ?></strong></div>
            <div style="font-size:18px; margin-top:4px;">Genel Toplam: <strong><?= formatTL((float) $order['genel_toplam']) ?></strong></div>
        </div>
    </div>

    <div>
        <div class="card">
            <h3 style="margin-top:0;">Sipariş Bilgileri</h3>
            <p><strong>Cari:</strong> <?= e($order['firma_adi']) ?></p>
            <p><strong>Çalışan:</strong> <?= e($order['calisan_ad'] . ' ' . $order['calisan_soyad']) ?></p>
            <p><strong>Evrak Açıklaması:</strong> <?= e($order['evrak_aciklamasi']) ?: '-' ?></p>
            <p><strong>Teslim Tarihi:</strong> <?= formatTarih($order['teslim_tarihi']) ?></p>
            <p><strong>Ambar:</strong> <?= e($order['ambar_bilgisi']) ?: '-' ?></p>
            <p><strong>Ödeme Tipi:</strong> <?= e($order['odeme_tipi']) ?></p>
            <p><strong>Oluşturulma:</strong> <?= formatTarih($order['olusturulma_tarihi']) ?></p>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Durum Güncelle</h3>
            <p>Mevcut durum: <span class="pill pill-warning"><?= e($order['durum']) ?></span></p>
            <form method="POST" action="order-detail.php?id=<?= $id ?>">
                <div class="form-group">
                    <select name="yeni_durum">
                        <?php foreach (['TASLAK','BEKLEMEDE','ONAYLANDI','TAMAMLANDI','IPTAL'] as $d): ?>
                        <option value="<?= $d ?>" <?= $order['durum'] === $d ? 'selected' : '' ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Durumu Güncelle</button>
            </form>
            <p style="font-size:12px; color:var(--text-muted); margin-top:12px;">
                Not: "ONAYLANDI" durumuna geçişte stok otomatik olarak düşer.
            </p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
