<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$pdo = getDbConnection();
$employeeId = (int) $_SESSION['admin_employee_id'];

$stmt = $pdo->prepare('SELECT * FROM employees WHERE id = :id');
$stmt->execute(['id' => $employeeId]);
$me = $stmt->fetch();

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mevcutSifre = $_POST['mevcut_sifre'] ?? '';
    $yeniSifre = $_POST['yeni_sifre'] ?? '';

    if (!password_verify($mevcutSifre, $me['sifre_hash'])) {
        $error = 'Mevcut şifre hatalı.';
    } elseif (strlen($yeniSifre) < 6) {
        $error = 'Yeni şifre en az 6 karakter olmalı.';
    } else {
        $pdo->prepare('UPDATE employees SET sifre_hash = :hash WHERE id = :id')
            ->execute(['hash' => password_hash($yeniSifre, PASSWORD_DEFAULT), 'id' => $employeeId]);
        $success = 'Şifreniz güncellendi.';
    }
}

$pageTitle = 'Ayarlar';
$activePage = 'settings';
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="card" style="max-width:480px;">
    <h3 style="margin-top:0;">Hesap Bilgileri</h3>
    <p><strong>Ad Soyad:</strong> <?= e($me['ad'] . ' ' . $me['soyad']) ?></p>
    <p><strong>Kullanıcı Adı:</strong> <?= e($me['kullanici_adi']) ?></p>
    <p><strong>Rol:</strong> <?= e($me['rol']) ?></p>
</div>

<div class="card" style="max-width:480px;">
    <h3 style="margin-top:0;">Şifre Değiştir</h3>
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="POST" action="settings.php">
        <div class="form-group">
            <label>Mevcut Şifre</label>
            <input type="password" name="mevcut_sifre" required>
        </div>
        <div class="form-group">
            <label>Yeni Şifre</label>
            <input type="password" name="yeni_sifre" required minlength="6">
        </div>
        <button type="submit" class="btn btn-primary">Şifreyi Güncelle</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
