<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$pdo = getDbConnection();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
$stmt->execute(['id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

$error = null;
$categories = $pdo->query(
    'SELECT id, ad FROM categories ORDER BY ad ASC'
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['sil'])) {
        $pdo->prepare(
            'UPDATE products SET aktif = 0 WHERE id = :id'
        )->execute(['id' => $id]);

        header('Location: products.php');
        exit;
    }

    $urunAdi = trim($_POST['urun_adi'] ?? '');
    $listeFiyati = (float) ($_POST['liste_fiyati'] ?? 0);
    $koliFiyati = (float) ($_POST['koli_fiyati'] ?? 0);
    $dipFiyati = (float) ($_POST['dip_fiyat'] ?? 0);

    if ($urunAdi === '') {
        $error = 'Ürün adı zorunludur.';
    } elseif (
        $listeFiyati < 0 ||
        $koliFiyati < 0 ||
        $dipFiyati < 0
    ) {
        $error = 'Fiyatlar negatif olamaz.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'UPDATE products SET
                    urun_adi = :urun_adi,
                    category_id = :category_id,
                    aciklama = :aciklama,
                    barkod = :barkod,
                    liste_fiyati = :liste_fiyati,
                    koli_fiyati = :koli_fiyati,
                    dip_fiyat = :dip_fiyat,
                    koli_ici_adet = :koli_ici_adet,
                    kdv_orani = :kdv_orani,
                    hacim_m3 = :hacim_m3,
                    stok = :stok,
                    birim = :birim
                 WHERE id = :id'
            );

            $stmt->execute([
                'urun_adi'      => $urunAdi,
                'category_id'   => $_POST['category_id'] ?: null,
                'aciklama'      => $_POST['aciklama'] ?? null,
                'barkod'        => $_POST['barkod'] ?? null,
                'liste_fiyati'  => $listeFiyati,
                'koli_fiyati'   => $koliFiyati,
                'dip_fiyat'     => $dipFiyati,
                'koli_ici_adet' => max(
                    1,
                    (int) ($_POST['koli_ici_adet'] ?? 1)
                ),
                'kdv_orani' => max(
                    0,
                    (float) ($_POST['kdv_orani'] ?? 20)
                ),
                'hacim_m3' => max(
                    0,
                    (float) ($_POST['hacim_m3'] ?? 0)
                ),
                'stok' => max(
                    0,
                    (int) ($_POST['stok'] ?? 0)
                ),
                'birim' => $_POST['birim'] ?? 'ADET',
                'id' => $id,
            ]);

            $stmt2 = $pdo->prepare(
                'SELECT * FROM products WHERE id = :id'
            );
            $stmt2->execute(['id' => $id]);

            $product = $stmt2->fetch();
            $success = true;

        } catch (PDOException $e) {
            $error = 'Ürün güncellenirken hata oluştu.';
        }
    }
}

$pageTitle = 'Ürün Düzenle';
$pageSubtitle = $product['urun_kodu'] . ' — ' . $product['urun_adi'];
$activePage = 'products';

require __DIR__ . '/../includes/admin_header.php';
?>

<div class="card" style="max-width:720px;">

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            Ürün güncellendi.
        </div>
    <?php endif; ?>

    <form method="POST" action="product-edit.php?id=<?= $id ?>">

        <div class="form-row">
            <div class="form-group">
                <label>Ürün Kodu</label>
                <input
                    type="text"
                    value="<?= e($product['urun_kodu']) ?>"
                    disabled
                >
            </div>

            <div class="form-group">
                <label>Barkod</label>
                <input
                    type="text"
                    name="barkod"
                    value="<?= e($product['barkod']) ?>"
                >
            </div>
        </div>

        <div class="form-group">
            <label>Ürün Adı *</label>
            <input
                type="text"
                name="urun_adi"
                value="<?= e($product['urun_adi']) ?>"
                required
            >
        </div>

        <div class="form-group">
            <label>Kategori</label>
            <select name="category_id">
                <option value="">— Kategorisiz —</option>

                <?php foreach ($categories as $cat): ?>
                    <option
                        value="<?= (int) $cat['id'] ?>"
                        <?= (int) $product['category_id'] === (int) $cat['id']
                            ? 'selected'
                            : '' ?>
                    >
                        <?= e($cat['ad']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Açıklama</label>
            <textarea
                name="aciklama"
                rows="3"
            ><?= e($product['aciklama']) ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Liste Fiyatı (TL)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="liste_fiyati"
                    value="<?= e((string) $product['liste_fiyati']) ?>"
                >
            </div>

            <div class="form-group">
                <label>Koli Fiyatı (TL)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="koli_fiyati"
                    value="<?= e((string) $product['koli_fiyati']) ?>"
                >
            </div>
        </div>

        <div class="form-group">
            <label>Dip Fiyat (TL)</label>
            <input
                type="number"
                step="0.01"
                min="0"
                name="dip_fiyat"
                value="<?= e((string) ($product['dip_fiyat'] ?? '0')) ?>"
            >
            <small style="display:block;margin-top:5px;color:var(--text-muted);">
                Net fiyatın altına inilemeyecek minimum birim fiyat.
            </small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Koli İçi Adet</label>
                <input
                    type="number"
                    min="1"
                    name="koli_ici_adet"
                    value="<?= e((string) $product['koli_ici_adet']) ?>"
                >
            </div>

            <div class="form-group">
                <label>KDV Oranı (%)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="kdv_orani"
                    value="<?= e((string) $product['kdv_orani']) ?>"
                >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Hacim (m3)</label>
                <input
                    type="number"
                    step="0.0001"
                    min="0"
                    name="hacim_m3"
                    value="<?= e((string) $product['hacim_m3']) ?>"
                >
            </div>

            <div class="form-group">
                <label>Stok</label>
                <input
                    type="number"
                    min="0"
                    name="stok"
                    value="<?= e((string) $product['stok']) ?>"
                >
            </div>
        </div>

        <div class="form-group">
            <label>Birim</label>
            <select name="birim">
                <?php foreach (['ADET', 'KOLI', 'KG', 'LITRE'] as $b): ?>
                    <option
                        value="<?= $b ?>"
                        <?= $product['birim'] === $b ? 'selected' : '' ?>
                    >
                        <?= $b ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">
            Değişiklikleri Kaydet
        </button>

        <a href="products.php" class="btn btn-secondary">
            Geri Dön
        </a>
    </form>

    <hr style="
        margin:24px 0;
        border:none;
        border-top:1px solid var(--border);
    ">

    <form
        method="POST"
        action="product-edit.php?id=<?= $id ?>"
        onsubmit="return confirm('Bu ürünü pasif hale getirmek istediğinize emin misiniz?');"
    >
        <button
            type="submit"
            name="sil"
            class="btn btn-danger"
        >
            Ürünü Pasifleştir
        </button>
    </form>

</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>