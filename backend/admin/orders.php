<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$pdo = getDbConnection();
$durumFiltre = $_GET['durum'] ?? '';

$sql = 'SELECT o.*, c.firma_adi, e.ad AS calisan_ad, e.soyad AS calisan_soyad
        FROM orders o
        JOIN customers c ON c.id = o.customer_id
        JOIN employees e ON e.id = o.employee_id';
$params = [];
if ($durumFiltre !== '') {
    $sql .= ' WHERE o.durum = :durum';
    $params['durum'] = $durumFiltre;
}
$sql .= ' ORDER BY o.olusturulma_tarihi DESC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$durumlar = ['TASLAK', 'BEKLEMEDE', 'ONAYLANDI', 'TAMAMLANDI', 'IPTAL'];

$pageTitle = 'Siparişler';
$activePage = 'orders';
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="toolbar">
    <div style="display:flex; gap:8px;">
        <a href="orders.php" class="btn <?= $durumFiltre === '' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Tümü</a>
        <?php foreach ($durumlar as $d): ?>
        <a href="orders.php?durum=<?= $d ?>" class="btn <?= $durumFiltre === $d ? 'btn-primary' : 'btn-secondary' ?> btn-sm"><?= $d ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Sipariş No</th>
                <th>Cari</th>
                <th>Çalışan</th>
                <th>Tutar</th>
                <th>Durum</th>
                <th>Tarih</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= e($o['siparis_no']) ?></td>
                <td><?= e($o['firma_adi']) ?></td>
                <td><?= e($o['calisan_ad'] . ' ' . $o['calisan_soyad']) ?></td>
                <td><?= formatTL((float) $o['genel_toplam']) ?></td>
                <td><span class="pill <?= durumPillClass($o['durum']) ?>"><?= e($o['durum']) ?></span></td>
                <td><?= formatTarih($o['olusturulma_tarihi']) ?></td>
                <td><a href="order-detail.php?id=<?= (int) $o['id'] ?>" class="btn btn-secondary btn-sm">Detay</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
            <tr><td colspan="7" style="text-align:center; color:var(--text-muted); padding:32px;">Sipariş bulunamadı.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
function durumPillClass(string $durum): string
{
    $map = [
        'TASLAK' => 'pill-muted', 'BEKLEMEDE' => 'pill-warning',
        'ONAYLANDI' => 'pill-success', 'TAMAMLANDI' => 'pill-success', 'IPTAL' => 'pill-danger',
    ];
    return $map[$durum] ?? 'pill-muted';
}
require __DIR__ . '/../includes/admin_footer.php';
