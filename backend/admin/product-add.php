<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$pdo = getDbConnection();
$error = null;
$categories = $pdo->query('SELECT id, ad FROM categories ORDER BY ad ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $urunKodu = trim($_POST['urun_kodu'] ?? '');
    $urunAdi = trim($_POST['urun_adi'] ?? '');
    $listeFiyati = (float) ($_POST['liste_fiyati'] ?? 0);
    $koliFiyati = (float) ($_POST['koli_fiyati'] ?? 0);
    $dipFiyati = (float) ($_POST['dip_fiyat'] ?? 0);

    if ($urunKodu === '' || $urunAdi === '') {
        $error = 'Ürün kodu ve ürün adı zorunludur.';
    } elseif ($listeFiyati < 0 || $koliFiyati < 0 || $dipFiyati < 0) {
        $error = 'Fiyatlar negatif olamaz.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO products
                    (
                        urun_kodu,
                        urun_adi,
                        category_id,
                        aciklama,
                        barkod,
                        liste_fiyati,
                        koli_fiyati,
                        dip_fiyat,
                        koli_ici_adet,
                        kdv_orani,
                        hacim_m3,
                        stok,
                        birim
                    )
                 VALUES
                    (
                        :urun_kodu,
                        :urun_adi,
                        :category_id,
                        :aciklama,
                        :barkod,
                        :liste_fiyati,
                        :koli_fiyati,
                        :dip_fiyat,
                        :koli_ici_adet,
                        :kdv_orani,
                        :hacim_m3,
                        :stok,
                        :birim
                    )'
            );

            $stmt->execute([
                'urun_kodu'     => $urunKodu,
                'urun_adi'      => $urunAdi,
                'category_id'   => $_POST['category_id'] ?: null,
                'aciklama'      => $_POST['aciklama'] ?? null,
                'barkod'        => $_POST['barkod'] ?? null,
                'liste_fiyati'  => $listeFiyati,
                'koli_fiyati'   => $koliFiyati,
                'dip_fiyat'     => $dipFiyati,
                'koli_ici_adet' => max(1, (int) ($_POST['koli_ici_adet'] ?? 1)),
                'kdv_orani'     => max(0, (float) ($_POST['kdv_orani'] ?? 20)),
                'hacim_m3'      => max(0, (float) ($_POST['hacim_m3'] ?? 0)),
                'stok'          => max(0, (int) ($_POST['stok'] ?? 0)),
                'birim'         => $_POST['birim'] ?? 'ADET',
            ]);

            header('Location: products.php');
            exit;
        } catch (PDOException $e) {
            $error = $e->getCode() === '23000'
                ? 'Bu ürün kodu zaten kayıtlı.'
                : 'Ürün eklenirken hata oluştu.';
        }
    }
}

$pageTitle = 'Yeni Ürün';
$pageSubtitle = 'Ürün kataloğuna yeni bir kayıt ekleyin';
$activePage = 'products';
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="card" style="max-width:720px;">
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="product-add.php">

        <div class="form-row">
            <div class="form-group">
                <label>Ürün Kodu *</label>
                <input type="text" name="urun_kodu" required>
            </div>

            <div class="form-group">
                <label>Barkod</label>
                <input type="text" name="barkod">
            </div>
        </div>

        <div class="form-group">
            <label>Ürün Adı *</label>
            <input type="text" name="urun_adi" required>
        </div>

        <div class="form-group">
            <label>Kategori</label>
            <select name="category_id">
                <option value="">— Kategorisiz —</option>

                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>">
                        <?= e($cat['ad']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Açıklama</label>
            <textarea name="aciklama" rows="3"></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Liste Fiyatı (TL)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="liste_fiyati"
                    value="0"
                >
            </div>

            <div class="form-group">
                <label>Koli Fiyatı (TL)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="koli_fiyati"
                    value="0"
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
                value="0"
            >
            <small style="display:block;margin-top:5px;color:var(--text-muted);">
                İskontolar uygulandıktan sonra oluşabilecek minimum net birim fiyat.
            </small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Koli İçi Adet</label>
                <input
                    type="number"
                    min="1"
                    name="koli_ici_adet"
                    value="1"
                >
            </div>

            <div class="form-group">
                <label>KDV Oranı (%)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="kdv_orani"
                    value="20"
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
                    value="0"
                >
            </div>

            <div class="form-group">
                <label>Stok</label>
                <input
                    type="number"
                    min="0"
                    name="stok"
                    value="0"
                >
            </div>
        </div>

        <div class="form-group">
            <label>Birim</label>
            <select name="birim">
                <option value="ADET">Adet</option>
                <option value="KOLI">Koli</option>
                <option value="KG">Kg</option>
                <option value="LITRE">Litre</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">
            Ürünü Kaydet
        </button>

        <a href="products.php" class="btn btn-secondary">
            Vazgeç
        </a>
    </form>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>