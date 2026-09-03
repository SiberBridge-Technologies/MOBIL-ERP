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
     ORDER BY o.olusturulma_tarihi DESC LIMIT 8'
)->fetchAll();

$dusukStok = $pdo->query(
    'SELECT urun_kodu, urun_adi, stok FROM products WHERE aktif = 1 AND stok <= 10 ORDER BY stok ASC LIMIT 6'
)->fetchAll();

$pageTitle = 'Genel Bakış';
$activePage = 'dashboard';
require __DIR__ . '/../includes/admin_header.php';

$gunler = ['Pazar','Pazartesi','Salı','Çarşamba','Perşembe','Cuma','Cumartesi'];
$aylar = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
$bugun = (int) date('j') . ' ' . $aylar[(int) date('n')] . ' ' . date('Y') . ' · ' . $gunler[(int) date('w')];
$adSadece = explode(' ', trim(currentAdminName()))[0] ?? '';
?>

<div class="page-intro" style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:10px;">
    <div>
        <h2>Hoş geldin, <?= e($adSadece) ?> 👋</h2>
        <p>İşletmenin güncel durumuna hızlıca göz at.</p>
    </div>
    <div class="pill pill-muted" style="padding:8px 13px; font-weight:500;"><?= e($bugun) ?></div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-top">
            <div class="label">Toplam Aktif Ürün</div>
            <div class="stat-icon blue">▣</div>
        </div>
        <div class="value"><?= $toplamUrun ?></div>
        <div class="footnote">Sistemde kayıtlı aktif ürün</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <div class="label">Toplam Aktif Cari</div>
            <div class="stat-icon green">♙</div>
        </div>
        <div class="value"><?= $toplamCari ?></div>
        <div class="footnote">Aktif müşteri ve cari hesap</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <div class="label">Bekleyen Sipariş</div>
            <div class="stat-icon orange">▤</div>
        </div>
        <div class="value"><?= $bekleyenSiparis ?></div>
        <div class="footnote">İşlem bekleyen siparişler</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <div class="label">Bu Ay Ciro</div>
            <div class="stat-icon zinc">₺</div>
        </div>
        <div class="value"><?= formatTL($aylikCiro) ?></div>
        <div class="footnote"><?= e($aylar[(int) date('n')]) ?> <?= date('Y') ?> toplam cirosu</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="panel">
        <div class="panel-header">
            <div>
                <div class="panel-title">Son Siparişler</div>
                <div class="panel-subtitle">Son oluşturulan siparişlerin özeti</div>
            </div>
            <a href="orders.php" class="view-all">Tümünü Gör →</a>
        </div>

        <?php if (empty($sonSiparisler)): ?>
        <div class="empty-block">
            <div class="empty-icon">▤</div>
            <strong>Henüz sipariş bulunmuyor.</strong>
            <span>Yeni siparişler burada görünecek.</span>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr><th>Sipariş No</th><th>Cari</th><th>Tutar</th><th>Durum</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($sonSiparisler as $o): ?>
                <tr>
                    <td><?= e($o['siparis_no']) ?></td>
                    <td><?= e($o['firma_adi']) ?></td>
                    <td><?= formatTL((float) $o['genel_toplam']) ?></td>
                    <td><?= renderDurumPill($o['durum']) ?></td>
                    <td><a href="order-detail.php?id=<?= (int) $o['id'] ?>" class="btn btn-secondary btn-sm">Detay</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <div class="panel-title">Düşük Stoklu Ürünler</div>
                <div class="panel-subtitle">Kritik seviyedeki ürünler (≤10 adet)</div>
            </div>
            <a href="products.php" class="view-all">Stokları Gör →</a>
        </div>

        <?php if (empty($dusukStok)): ?>
        <div class="empty-block">
            <div class="empty-icon">▥</div>
            <strong>Kritik stok bulunmuyor.</strong>
            <span>Stok seviyesi düşen ürünler burada listelenir.</span>
        </div>
        <?php else: ?>
        <table>
            <thead><tr><th>Ürün Kodu</th><th>Ürün Adı</th><th>Stok</th></tr></thead>
            <tbody>
                <?php foreach ($dusukStok as $p): ?>
                <tr>
                    <td><?= e($p['urun_kodu']) ?></td>
                    <td><?= e($p['urun_adi']) ?></td>
                    <td><span class="pill pill-danger"><?= (int) $p['stok'] ?> adet</span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
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
