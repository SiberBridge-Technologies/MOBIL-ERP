<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$pdo = getDbConnection();
$search = trim($_GET['q'] ?? '');
$categoryFilter = (int) ($_GET['category_id'] ?? 0);

$sql = 'SELECT p.*, c.ad AS kategori_adi FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.aktif = 1';
$params = [];
if ($search !== '') {
    $sql .= ' AND (p.urun_kodu LIKE :s1 OR p.urun_adi LIKE :s2)';
    $params['s1'] = $params['s2'] = '%' . $search . '%';
}
if ($categoryFilter > 0) {
    $sql .= ' AND p.category_id = :cid';
    $params['cid'] = $categoryFilter;
}
$sql .= ' ORDER BY p.urun_adi ASC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query('SELECT id, ad FROM categories ORDER BY ad ASC')->fetchAll();

$pageTitle = 'Ürünler';
$activePage = 'products';
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="page-intro">
    <h2>Ürünler</h2>
    <p><?= count($products) ?> ürün listeleniyor</p>
</div>

<div class="toolbar">
    <form method="GET" action="products.php" style="display:flex; gap:10px; flex-wrap:wrap;">
        <input type="text" name="q" class="search-input" placeholder="Ürün kodu veya adı ile ara..." value="<?= e($search) ?>">
        <select name="category_id" onchange="this.form.submit()" style="padding:9px 12px; border:1px solid var(--input); border-radius:8px; font-size:13.5px; background:#fff;">
            <option value="0">Tüm Kategoriler</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= $categoryFilter === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['ad']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($search !== '' || $categoryFilter > 0): ?>
        <a href="products.php" class="btn btn-secondary btn-sm" style="align-self:center;">Filtreleri Temizle</a>
        <?php endif; ?>
    </form>
    <a href="product-add.php" class="btn btn-primary">+ Yeni Ürün</a>
</div>

<div class="card" style="padding:0;">
    <table>
        <thead>
            <tr>
                <th>Ürün Kodu</th>
                <th>Ürün Adı</th>
                <th>Kategori</th>
                        <th>Adet Fiyatı</th>
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
                <td><?= $p['kategori_adi'] ? e($p['kategori_adi']) : '<span style="color:var(--muted-foreground);">—</span>' ?></td>
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
            <tr><td colspan="7" style="text-align:center; color:var(--muted-foreground); padding:32px;">Ürün bulunamadı.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
