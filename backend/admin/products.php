<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$pdo = getDbConnection();
$search = trim($_GET['q'] ?? '');

$sql = 'SELECT * FROM products WHERE aktif = 1';
$params = [];
if ($search !== '') {
    $sql .= ' AND (urun_kodu LIKE :s OR urun_adi LIKE :s)';
    $params['s'] = '%' . $search . '%';
}
$sql .= ' ORDER BY urun_adi ASC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Ürünler';
$activePage = 'products';
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="toolbar">
    <form method="GET" action="products.php">
        <input type="text" name="q" class="search-input" placeholder="Ürün kodu veya adı ile ara..." value="<?= e($search) ?>">
    </form>
    <a href="product-add.php" class="btn btn-primary">+ Yeni Ürün</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Ürün Kodu</th>
                <th>Ürün Adı</th>
                <th>Liste Fiyatı</th>
                <th>Koli Fiyatı</th>
                <th>Stok</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><?= e($p['urun_kodu']) ?></td>
                <td><?= e($p['urun_adi']) ?></td>
                <td><?= formatTL((float) $p['liste_fiyati']) ?></td>
                <td><?= formatTL((float) $p['koli_fiyati']) ?></td>
                <td>
                    <span class="pill <?= (int)$p['stok'] <= 10 ? 'pill-danger' : 'pill-success' ?>">
                        <?= (int) $p['stok'] ?> adet
                    </span>
                </td>
                <td><a href="product-edit.php?id=<?= (int) $p['id'] ?>" class="btn btn-secondary btn-sm">Düzenle</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
            <tr><td colspan="6" style="text-align:center; color:var(--text-muted); padding:32px;">Ürün bulunamadı.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
