<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$pdo = getDbConnection();

$toplamUrun = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE aktif = 1')->fetchColumn();
$toplamCari = (int) $pdo->query('SELECT COUNT(*) FROM customers WHERE durum = "AKTIF"')->fetchColumn();
$bekleyenSiparis = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE durum = "BEKLEMEDE"')->fetchColumn();
$aylikCiro = (float) $pdo->query(
    'SELECT COALESCE(SUM(genel_toplam),0) FROM orders
     WHERE durum IN ("ONAYLANDI","TAMAMLANDI") AND MONTH(olusturulma_tarihi) = MONTH(CURDATE())
     AND YEAR(olusturulma_tarihi) = YEAR(CURDATE())'
)->fetchColumn();

$sonSiparisler = $pdo->query(
    'SELECT o.id, o.siparis_no, o.genel_toplam, o.durum, o.olusturulma_tarihi, c.firma_adi
     FROM orders o JOIN customers c ON c.id = o.customer_id
     ORDER BY o.olusturulma_tarihi DESC LIMIT 10'
)->fetchAll();

$dusukStok = $pdo->query(
    'SELECT urun_kodu, urun_adi, stok FROM products WHERE aktif = 1 AND stok <= 10 ORDER BY stok ASC LIMIT 5'
)->fetchAll();

$pageTitle = 'Genel Bakış';
$activePage = 'dashboard';
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="stat-grid">
    <div class="stat-card">
        <div class="label">Toplam Aktif Ürün</div>
        <div class="value"><?= $toplamUrun ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Toplam Aktif Cari</div>
        <div class="value"><?= $toplamCari ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Bekleyen Sipariş</div>
        <div class="value"><?= $bekleyenSiparis ?></div>
    </div>
    <div class="stat-card">
        <div class="label">Bu Ay Ciro</div>
        <div class="value"><?= formatTL($aylikCiro) ?></div>
    </div>
</div>

<div class="card">
    <h3 style="margin-top:0;">Son Siparişler</h3>
    <table>
        <thead>
            <tr>
                <th>Sipariş No</th>
                <th>Cari</th>
                <th>Tutar</th>
                <th>Durum</th>
                <th>Tarih</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sonSiparisler as $o): ?>
            <tr>
                <td><?= e($o['siparis_no']) ?></td>
                <td><?= e($o['firma_adi']) ?></td>
                <td><?= formatTL((float) $o['genel_toplam']) ?></td>
                <td><?= renderDurumPill($o['durum']) ?></td>
                <td><?= formatTarih($o['olusturulma_tarihi']) ?></td>
                <td><a href="order-detail.php?id=<?= (int) $o['id'] ?>" class="btn btn-secondary btn-sm">Detay</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($sonSiparisler)): ?>
            <tr><td colspan="6" style="text-align:center; color:var(--text-muted);">Henüz sipariş bulunmuyor.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h3 style="margin-top:0;">Düşük Stoklu Ürünler</h3>
    <table>
        <thead>
            <tr><th>Ürün Kodu</th><th>Ürün Adı</th><th>Stok</th></tr>
        </thead>
        <tbody>
            <?php foreach ($dusukStok as $p): ?>
            <tr>
                <td><?= e($p['urun_kodu']) ?></td>
                <td><?= e($p['urun_adi']) ?></td>
                <td><span class="pill pill-danger"><?= (int) $p['stok'] ?> adet</span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($dusukStok)): ?>
            <tr><td colspan="3" style="text-align:center; color:var(--text-muted);">Düşük stoklu ürün yok.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
function renderDurumPill(string $durum): string
{
    $map = [
        'TASLAK'     => 'pill-muted',
        'BEKLEMEDE'  => 'pill-warning',
        'ONAYLANDI'  => 'pill-success',
        'TAMAMLANDI' => 'pill-success',
        'IPTAL'      => 'pill-danger',
    ];
    $class = $map[$durum] ?? 'pill-muted';
    return '<span class="pill ' . $class . '">' . e($durum) . '</span>';
}

require __DIR__ . '/../includes/admin_footer.php';
