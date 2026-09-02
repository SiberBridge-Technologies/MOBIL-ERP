<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminRole(['ADMIN', 'YONETICI']);

$pdo = getDbConnection();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ekle'])) {
    $ad = trim($_POST['ad'] ?? '');
    if ($ad === '') {
        $error = 'Kategori adı zorunludur.';
    } else {
        $pdo->prepare('INSERT INTO categories (ad, ust_kategori_id) VALUES (:ad, :ust)')
            ->execute(['ad' => $ad, 'ust' => $_POST['ust_kategori_id'] ?: null]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sil'])) {
    $pdo->prepare('DELETE FROM categories WHERE id = :id')->execute(['id' => (int) $_POST['id']]);
}

$categories = $pdo->query(
    'SELECT c.id, c.ad, p.ad AS ust_ad,
            (SELECT COUNT(*) FROM products WHERE category_id = c.id AND aktif = 1) AS urun_sayisi
     FROM categories c
     LEFT JOIN categories p ON p.id = c.ust_kategori_id
     ORDER BY c.ad ASC'
)->fetchAll();

$tumKategoriler = $pdo->query('SELECT id, ad FROM categories ORDER BY ad ASC')->fetchAll();

$pageTitle = 'Kategoriler';
$activePage = 'categories';
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="page-intro">
    <h2>Kategoriler</h2>
    <p><?= count($categories) ?> kategori tanımlı</p>
</div>

<div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px;">
    <div class="card">
        <h3>Kategori Listesi</h3>
        <table>
            <thead><tr><th>Kategori</th><th>Üst Kategori</th><th>Ürün Sayısı</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($categories as $c): ?>
                <tr>
                    <td><?= e($c['ad']) ?></td>
                    <td><?= e($c['ust_ad']) ?: '-' ?></td>
                    <td><?= (int) $c['urun_sayisi'] ?></td>
                    <td>
                        <form method="POST" action="categories.php" onsubmit="return confirm('Bu kategoriyi silmek istediğinize emin misiniz?');">
                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                            <button type="submit" name="sil" class="btn btn-danger btn-sm">Sil</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                <tr><td colspan="4" style="text-align:center; color:var(--muted-foreground); padding:24px;">Henüz kategori eklenmemiş.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Yeni Kategori</h3>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <form method="POST" action="categories.php">
            <div class="form-group">
                <label>Kategori Adı *</label>
                <input type="text" name="ad" required>
            </div>
            <div class="form-group">
                <label>Üst Kategori (opsiyonel)</label>
                <select name="ust_kategori_id">
                    <option value="">— Yok —</option>
                    <?php foreach ($tumKategoriler as $tk): ?>
                    <option value="<?= (int) $tk['id'] ?>"><?= e($tk['ad']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" name="ekle" class="btn btn-primary" style="width:100%;">Kategori Ekle</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
