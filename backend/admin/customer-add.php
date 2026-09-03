<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$pdo = getDbConnection();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cariKodu = trim($_POST['cari_kodu'] ?? '');
    $firmaAdi = trim($_POST['firma_adi'] ?? '');

    if ($cariKodu === '' || $firmaAdi === '') {
        $error = 'Cari kodu ve firma adı zorunludur.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO customers
                    (cari_kodu, firma_adi, yetkili_kisi, telefon, email, adres, sehir, ilce, vergi_dairesi, vergi_no, not_metni)
                 VALUES
                    (:cari_kodu, :firma_adi, :yetkili_kisi, :telefon, :email, :adres, :sehir, :ilce, :vergi_dairesi, :vergi_no, :not_metni)'
            );
            $stmt->execute([
                'cari_kodu'      => $cariKodu,
                'firma_adi'      => $firmaAdi,
                'yetkili_kisi'   => $_POST['yetkili_kisi'] ?? null,
                'telefon'        => $_POST['telefon'] ?? null,
                'email'          => $_POST['email'] ?? null,
                'adres'          => $_POST['adres'] ?? null,
                'sehir'          => $_POST['sehir'] ?? null,
                'ilce'           => $_POST['ilce'] ?? null,
                'vergi_dairesi'  => $_POST['vergi_dairesi'] ?? null,
                'vergi_no'       => $_POST['vergi_no'] ?? null,
                'not_metni'      => $_POST['not_metni'] ?? null,
            ]);
            header('Location: customers.php');
            exit;
        } catch (PDOException $e) {
            $error = $e->getCode() === '23000' ? 'Bu cari kodu zaten kayıtlı.' : 'Cari eklenirken hata oluştu.';
        }
    }
}

$pageTitle = 'Yeni Cari';
$pageSubtitle = 'Sisteme yeni bir cari (müşteri) kaydı ekleyin';
$activePage = 'customers';
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="card" style="max-width:720px;">
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="POST" action="customer-add.php">
        <div class="form-row">
            <div class="form-group">
                <label>Cari Kodu *</label>
                <input type="text" name="cari_kodu" required>
            </div>
            <div class="form-group">
                <label>Firma Adı *</label>
                <input type="text" name="firma_adi" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Yetkili Kişi</label>
                <input type="text" name="yetkili_kisi">
            </div>
            <div class="form-group">
                <label>Telefon</label>
                <input type="text" name="telefon">
            </div>
        </div>

        <div class="form-group">
            <label>E-posta</label>
            <input type="email" name="email">
        </div>

        <div class="form-group">
            <label>Adres</label>
            <textarea name="adres" rows="2"></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Şehir</label>
                <input type="text" name="sehir">
            </div>
            <div class="form-group">
                <label>İlçe</label>
                <input type="text" name="ilce">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Vergi Dairesi</label>
                <input type="text" name="vergi_dairesi">
            </div>
            <div class="form-group">
                <label>Vergi No</label>
                <input type="text" name="vergi_no">
            </div>
        </div>

        <div class="form-group">
            <label>Not</label>
            <textarea name="not_metni" rows="2"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Cariyi Kaydet</button>
        <a href="customers.php" class="btn btn-secondary">Vazgeç</a>
    </form>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
