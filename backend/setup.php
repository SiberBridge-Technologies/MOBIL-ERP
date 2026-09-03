<?php
/**
 * İLK KURULUM SİHİRBAZI
 * Bu dosya sadece employees tablosu boşken çalışır. İlk ADMIN kullanıcısını
 * oluşturduktan sonra otomatik olarak devre dışı kalır (güvenlik için).
 */
require_once __DIR__ . '/config/config.php';

$pdo = getDbConnection();

// Tablolar kurulmuş mu kontrol et
try {
    $employeeCount = (int) $pdo->query('SELECT COUNT(*) FROM employees')->fetchColumn();
} catch (PDOException $e) {
    die('<h2>Veritabanı tabloları bulunamadı.</h2><p>Lütfen önce <code>database/schema.sql</code> dosyasını phpMyAdmin üzerinden içe aktarın, sonra bu sayfayı yenileyin.</p>');
}

if ($employeeCount > 0) {
    die('<h2>Kurulum zaten tamamlanmış.</h2><p>Sistemde zaten kayıtlı çalışan var. Güvenlik nedeniyle bu sayfa artık kullanılamaz. Giriş yapmak için <a href="admin/login.php">buraya tıklayın</a>.</p><p><strong>Önemli:</strong> Bu dosyayı (setup.php) sunucudan silmeniz önerilir.</p>');
}

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad = trim($_POST['ad'] ?? '');
    $soyad = trim($_POST['soyad'] ?? '');
    $kullaniciAdi = trim($_POST['kullanici_adi'] ?? '');
    $sifre = $_POST['sifre'] ?? '';
    $sifreTekrar = $_POST['sifre_tekrar'] ?? '';

    if ($ad === '' || $soyad === '' || $kullaniciAdi === '' || $sifre === '') {
        $error = 'Tüm alanlar zorunludur.';
    } elseif ($sifre !== $sifreTekrar) {
        $error = 'Şifreler eşleşmiyor.';
    } elseif (strlen($sifre) < 6) {
        $error = 'Şifre en az 6 karakter olmalı.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO employees (ad, soyad, kullanici_adi, sifre_hash, rol, durum)
             VALUES (:ad, :soyad, :kullanici_adi, :sifre_hash, "ADMIN", "AKTIF")'
        );
        $stmt->execute([
            'ad' => $ad,
            'soyad' => $soyad,
            'kullanici_adi' => $kullaniciAdi,
            'sifre_hash' => password_hash($sifre, PASSWORD_DEFAULT),
        ]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İlk Kurulum — ERP-APP</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
    <div class="login-box" style="width:420px;">
        <?php if ($success): ?>
            <h1>Kurulum Tamamlandı ✅</h1>
            <p class="subtitle">İlk admin kullanıcınız oluşturuldu. Artık giriş yapabilirsiniz.</p>
            <a href="admin/login.php" class="btn btn-primary" style="display:block; text-align:center;">Giriş Sayfasına Git</a>
            <p style="margin-top:16px; font-size:12px; color:var(--danger);">
                Güvenlik için bu dosyayı (setup.php) sunucudan silmeniz önerilir.
            </p>
        <?php else: ?>
            <h1>İlk Kurulum</h1>
            <p class="subtitle">Sistemde henüz kullanıcı yok. İlk yönetici (ADMIN) hesabını oluşturun.</p>

            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

            <form method="POST" action="setup.php">
                <div class="form-row">
                    <div class="form-group">
                        <label>Ad</label>
                        <input type="text" name="ad" required>
                    </div>
                    <div class="form-group">
                        <label>Soyad</label>
                        <input type="text" name="soyad" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Kullanıcı Adı</label>
                    <input type="text" name="kullanici_adi" required>
                </div>
                <div class="form-group">
                    <label>Şifre</label>
                    <input type="password" name="sifre" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Şifre (Tekrar)</label>
                    <input type="password" name="sifre_tekrar" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">Admin Hesabını Oluştur</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
