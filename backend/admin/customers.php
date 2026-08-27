<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$pdo = getDbConnection();
$search = trim($_GET['q'] ?? '');

$sql = 'SELECT * FROM customers WHERE durum = "AKTIF"';
$params = [];
if ($search !== '') {
    $sql .= ' AND (firma_adi LIKE :s OR cari_kodu LIKE :s)';
    $params['s'] = '%' . $search . '%';
}
$sql .= ' ORDER BY firma_adi ASC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

$pageTitle = 'Cariler';
$activePage = 'customers';
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="toolbar">
    <form method="GET" action="customers.php">
        <input type="text" name="q" class="search-input" placeholder="Firma adı veya cari kodu ile ara..." value="<?= e($search) ?>">
    </form>
    <a href="customer-add.php" class="btn btn-primary">+ Yeni Cari</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Cari Kodu</th>
                <th>Firma Adı</th>
                <th>Yetkili</th>
                <th>Telefon</th>
                <th>Şehir</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $c): ?>
            <tr>
                <td><?= e($c['cari_kodu']) ?></td>
                <td><?= e($c['firma_adi']) ?></td>
                <td><?= e($c['yetkili_kisi']) ?></td>
                <td><?= e($c['telefon']) ?></td>
                <td><?= e($c['sehir']) ?></td>
                <td><a href="customer-detail.php?id=<?= (int) $c['id'] ?>" class="btn btn-secondary btn-sm">Detay</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($customers)): ?>
            <tr><td colspan="6" style="text-align:center; color:var(--text-muted); padding:32px;">Cari bulunamadı.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
