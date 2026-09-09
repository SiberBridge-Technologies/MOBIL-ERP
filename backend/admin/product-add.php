<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();
require_once __DIR__ . '/../includes/product_validation.php';

$pdo = getDbConnection();
$error = null;
$categories = $pdo->query('SELECT id, ad FROM categories ORDER BY ad ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $urunKodu = trim($_POST['urun_kodu'] ?? '');
    $urunAdi = trim($_POST['urun_adi'] ?? '');
    $listeFiyati = (float) ($_POST['liste_fiyati'] ?? 0);
    $dipFiyati = (float) ($_POST['dip_fiyat'] ?? 0);

    if ($urunKodu === '' || $urunAdi === '') {
        $error = 'Ürün kodu ve ürün adı zorunludur.';
    } elseif ($listeFiyati < 0 || $dipFiyati < 0) {
        $error = 'Fiyatlar negatif olamaz.';
    } else {
        try {
            validateProduct($_POST);
            $pricing = normalizeProductPricing($_POST);
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
                        koli_ici_adet, stand_aktif, stand_ici_adet, stand_fiyati,
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
                        :koli_ici_adet, :stand_aktif, :stand_ici_adet, :stand_fiyati,
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
                'liste_fiyati'  => $pricing['liste_fiyati'],
                'koli_fiyati'   => $pricing['koli_fiyati'],
                'dip_fiyat'     => $dipFiyati,
                'koli_ici_adet' => $pricing['koli_ici_adet'],
                'stand_aktif' => $pricing['stand_aktif'],
                'stand_ici_adet' => $pricing['stand_ici_adet'],
                'stand_fiyati' => $pricing['stand_fiyati'],
                'kdv_orani'     => max(0, (float) ($_POST['kdv_orani'] ?? 20)),
                'hacim_m3'      => max(0, (float) ($_POST['hacim_m3'] ?? 0)),
                'stok'          => max(0, (int) ($_POST['stok'] ?? 0)),
                'birim'         => $_POST['birim'] ?? 'ADET',
            ]);

            header('Location: products.php');
            exit;
        } catch (Throwable $e) {
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

<div class="card product-form-card">
    <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="product-add.php">
            <?= csrfField() ?>

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
                <label>Adet Fiyatı (TL)</label>
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
                    readonly
                >
            </div>
        </div>

        <div class="form-group">
            <label>Dip Adet Fiyatı (TL)</label>
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

        <div class="form-row stand-row">
            <div class="form-group stand-toggle"><label><input type="checkbox" name="stand_aktif" value="1"> Stand Aktif</label></div>
            <div class="form-group"><label>Stand İçi Adet</label><input type="number" min="1" name="stand_ici_adet" value="1"></div>
            <div class="form-group"><label>Stand Fiyatı (TL)</label><input type="number" step="0.01" name="stand_fiyati" value="0" readonly></div>
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

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Ürünü Kaydet</button>
            <a href="products.php" class="btn btn-secondary">Vazgeç</a>
        </div>
    </form>
    <script>
    (()=>{const f=document.querySelector('form[action="product-add.php"]'),u=f.elements.liste_fiyati,p=f.elements.koli_ici_adet,k=f.elements.koli_fiyati,a=f.elements.stand_aktif,s=f.elements.stand_ici_adet,sf=f.elements.stand_fiyati;const calc=()=>{k.value=((+u.value||0)*(+p.value||0)).toFixed(2);sf.value=((+u.value||0)*(+s.value||0)).toFixed(2)};const toggle=()=>{s.closest('.form-group').hidden=!a.checked;sf.closest('.form-group').hidden=!a.checked;s.disabled=!a.checked;sf.disabled=!a.checked};[u,p,s].forEach(x=>x.addEventListener('input',calc));a.addEventListener('change',toggle);calc();toggle()})();
    </script>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
