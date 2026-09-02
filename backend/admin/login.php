<?php
require_once __DIR__ . '/../includes/admin_auth.php';

if (adminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kullaniciAdi = trim($_POST['kullanici_adi'] ?? '');
    $sifre = $_POST['sifre'] ?? '';

    if ($kullaniciAdi === '' || $sifre === '') {
        $error = 'Kullanıcı adı ve şifre zorunludur.';
    } else {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare(
            'SELECT id, ad, soyad, kullanici_adi, sifre_hash, rol, durum
             FROM employees WHERE kullanici_adi = :ka AND rol IN ("ADMIN", "YONETICI") LIMIT 1'
        );
        $stmt->execute(['ka' => $kullaniciAdi]);
        $employee = $stmt->fetch();

        if (!$employee || !password_verify($sifre, $employee['sifre_hash'])) {
            $error = 'Kullanıcı adı veya şifre hatalı.';
        } elseif ($employee['durum'] !== 'AKTIF') {
            $error = 'Hesabınız pasif durumda.';
        } else {
            $_SESSION['admin_employee_id'] = $employee['id'];
            $_SESSION['admin_ad'] = $employee['ad'];
            $_SESSION['admin_soyad'] = $employee['soyad'];
            $_SESSION['admin_rol'] = $employee['rol'];

            $pdo->prepare('UPDATE employees SET son_giris = NOW() WHERE id = :id')
                ->execute(['id' => $employee['id']]);

            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap — ERP-APP Yönetim Paneli</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="login-page">
    <div class="login-box">
        <div class="brand">
            <div class="brand-logo">S</div>
            <div class="brand-text">SIBERBRIDGE<span>ERP SYSTEM</span></div>
        </div>
        <h1>Yönetim Paneli</h1>
        <p class="subtitle">Devam etmek için hesabınızla giriş yapın.</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label>Kullanıcı Adı</label>
                <input type="text" name="kullanici_adi" autocomplete="username" required>
            </div>
            <div class="form-group">
                <label>Şifre</label>
                <input type="password" name="sifre" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Giriş Yap</button>
        </form>
    </div>
</body>
</html>
