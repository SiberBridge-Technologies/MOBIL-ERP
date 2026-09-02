<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminRole(['ADMIN']);

$pdo = getDbConnection();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad = trim($_POST['ad'] ?? '');
    $soyad = trim($_POST['soyad'] ?? '');
    $kullaniciAdi = trim($_POST['kullanici_adi'] ?? '');
    $sifre = $_POST['sifre'] ?? '';

    if ($ad === '' || $soyad === '' || $kullaniciAdi === '' || $sifre === '') {
        $error = 'Ad, soyad, kullanıcı adı ve şifre zorunludur.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO employees (ad, soyad, kullanici_adi, sifre_hash, telefon, email, rol)
                 VALUES (:ad, :soyad, :kullanici_adi, :sifre_hash, :telefon, :email, :rol)'
            );
            $stmt->execute([
                'ad'            => $ad,
                'soyad'         => $soyad,
                'kullanici_adi' => $kullaniciAdi,
                'sifre_hash'    => password_hash($sifre, PASSWORD_DEFAULT),
                'telefon'       => $_POST['telefon'] ?? null,
                'email'         => $_POST['email'] ?? null,
                'rol'           => $_POST['rol'] ?? 'CALISAN',
            ]);
            header('Location: employees.php');
            exit;
        } catch (PDOException $e) {
            $error = $e->getCode() === '23000' ? 'Bu kullanıcı adı zaten alınmış.' : 'Çalışan eklenirken hata oluştu.';
        }
    }
}

$pageTitle = 'Yeni Çalışan';
$pageSubtitle = 'Mobil uygulamayı kullanacak yeni bir çalışan ekleyin';
$activePage = 'employees';
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="card" style="max-width:600px;">
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="POST" action="employee-add.php">
        <div class="form-row">
            <div class="form-group">
                <label>Ad *</label>
                <input type="text" name="ad" required>
            </div>
            <div class="form-group">
                <label>Soyad *</label>
                <input type="text" name="soyad" required>
            </div>
        </div>

        <div class="form-group">
            <label>Kullanıcı Adı *</label>
            <input type="text" name="kullanici_adi" required>
        </div>

        <div class="form-group">
            <label>Şifre *</label>
            <input type="password" name="sifre" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Telefon</label>
                <input type="text" name="telefon">
            </div>
            <div class="form-group">
                <label>E-posta</label>
                <input type="email" name="email">
            </div>
        </div>

        <div class="form-group">
            <label>Rol</label>
            <select name="rol">
                <option value="CALISAN">Çalışan</option>
                <option value="YONETICI">Yönetici</option>
                <option value="ADMIN">Admin</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Çalışanı Kaydet</button>
        <a href="employees.php" class="btn btn-secondary">Vazgeç</a>
    </form>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
