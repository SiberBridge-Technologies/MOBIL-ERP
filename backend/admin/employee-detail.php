<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminRole(['ADMIN']);

$pdo = getDbConnection();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM employees WHERE id = :id');
$stmt->execute(['id' => $id]);
$employee = $stmt->fetch();

if (!$employee) {
    header('Location: employees.php');
    exit;
}

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guncelle'])) {
    $setSql = 'ad=:ad, soyad=:soyad, telefon=:telefon, email=:email, rol=:rol, durum=:durum';
    $params = [
        'ad' => trim($_POST['ad'] ?? ''), 'soyad' => trim($_POST['soyad'] ?? ''),
        'telefon' => $_POST['telefon'] ?? null, 'email' => $_POST['email'] ?? null,
        'rol' => $_POST['rol'] ?? 'CALISAN', 'durum' => $_POST['durum'] ?? 'AKTIF',
        'id' => $id,
    ];

    if (!empty($_POST['yeni_sifre'])) {
        $setSql .= ', sifre_hash=:sifre_hash';
        $params['sifre_hash'] = password_hash($_POST['yeni_sifre'], PASSWORD_DEFAULT);
    }

    $pdo->prepare("UPDATE employees SET $setSql WHERE id = :id")->execute($params);

    $stmt2 = $pdo->prepare('SELECT * FROM employees WHERE id = :id');
    $stmt2->execute(['id' => $id]);
    $employee = $stmt2->fetch();
    $success = 'Çalışan bilgileri güncellendi.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cari_ekle'])) {
    $cid = (int) $_POST['customer_id'];
    $pdo->prepare('INSERT IGNORE INTO employee_customers (employee_id, customer_id) VALUES (:eid, :cid)')
        ->execute(['eid' => $id, 'cid' => $cid]);
    $success = 'Cari atandı.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cari_sil'])) {
    $cid = (int) $_POST['customer_id'];
    $pdo->prepare('DELETE FROM employee_customers WHERE employee_id = :eid AND customer_id = :cid')
        ->execute(['eid' => $id, 'cid' => $cid]);
    $success = 'Cari ataması kaldırıldı.';
}

$atananCariler = $pdo->prepare(
    'SELECT c.id, c.cari_kodu, c.firma_adi FROM employee_customers ec
     JOIN customers c ON c.id = ec.customer_id WHERE ec.employee_id = :id'
);
$atananCariler->execute(['id' => $id]);
$atananCariler = $atananCariler->fetchAll();

$tumCariler = $pdo->query('SELECT id, cari_kodu, firma_adi FROM customers WHERE durum = "AKTIF" ORDER BY firma_adi')->fetchAll();

$pageTitle = 'Çalışan Detay';
$pageSubtitle = $employee['ad'] . ' ' . $employee['soyad'] . ' (' . $employee['kullanici_adi'] . ')';
$activePage = 'employees';
require __DIR__ . '/../includes/admin_header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px;">
    <div class="card">
        <h3>Çalışan Bilgileri</h3>
        <form method="POST" action="employee-detail.php?id=<?= $id ?>">
            <div class="form-row">
                <div class="form-group">
                    <label>Ad</label>
                    <input type="text" name="ad" value="<?= e($employee['ad']) ?>">
                </div>
                <div class="form-group">
                    <label>Soyad</label>
                    <input type="text" name="soyad" value="<?= e($employee['soyad']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Kullanıcı Adı</label>
                <input type="text" value="<?= e($employee['kullanici_adi']) ?>" disabled>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Telefon</label>
                    <input type="text" name="telefon" value="<?= e($employee['telefon']) ?>">
                </div>
                <div class="form-group">
                    <label>E-posta</label>
                    <input type="email" name="email" value="<?= e($employee['email']) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Rol</label>
                    <select name="rol">
                        <?php foreach (['CALISAN','YONETICI','ADMIN'] as $r): ?>
                        <option value="<?= $r ?>" <?= $employee['rol'] === $r ? 'selected' : '' ?>><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Durum</label>
                    <select name="durum">
                        <option value="AKTIF" <?= $employee['durum'] === 'AKTIF' ? 'selected' : '' ?>>Aktif</option>
                        <option value="PASIF" <?= $employee['durum'] === 'PASIF' ? 'selected' : '' ?>>Pasif</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Yeni Şifre (opsiyonel)</label>
                <input type="password" name="yeni_sifre" placeholder="Değiştirmek için doldurun">
            </div>
            <button type="submit" name="guncelle" class="btn btn-primary">Kaydet</button>
        </form>
    </div>

    <div class="card">
        <h3>Yetkili Olduğu Cariler</h3>
        <?php foreach ($atananCariler as $ac): ?>
        <form method="POST" action="employee-detail.php?id=<?= $id ?>" style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border);">
            <span><?= e($ac['firma_adi']) ?> <small style="color:var(--muted-foreground);">(<?= e($ac['cari_kodu']) ?>)</small></span>
            <input type="hidden" name="customer_id" value="<?= (int) $ac['id'] ?>">
            <button type="submit" name="cari_sil" class="btn btn-danger btn-sm">Kaldır</button>
        </form>
        <?php endforeach; ?>
        <?php if (empty($atananCariler)): ?>
        <p style="color:var(--muted-foreground); font-size:13px;">Henüz cari atanmamış.</p>
        <?php endif; ?>

        <form method="POST" action="employee-detail.php?id=<?= $id ?>" style="margin-top:16px; display:flex; gap:8px;">
            <select name="customer_id" style="flex:1; padding:8px; border:1px solid var(--border); border-radius:8px;">
                <?php foreach ($tumCariler as $tc): ?>
                <option value="<?= (int) $tc['id'] ?>"><?= e($tc['firma_adi']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="cari_ekle" class="btn btn-primary btn-sm">Ata</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
