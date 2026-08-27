<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminLogin();

$pdo = getDbConnection();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM customers WHERE id = :id');
$stmt->execute(['id' => $id]);
$customer = $stmt->fetch();

if (!$customer) {
    header('Location: customers.php');
    exit;
}

$success = null;
$error = null;

// Bilgi güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guncelle'])) {
    $stmt = $pdo->prepare(
        'UPDATE customers SET firma_adi=:firma_adi, yetkili_kisi=:yetkili_kisi, telefon=:telefon,
            email=:email, adres=:adres, sehir=:sehir, ilce=:ilce, vergi_dairesi=:vergi_dairesi,
            vergi_no=:vergi_no, not_metni=:not_metni WHERE id=:id'
    );
    $stmt->execute([
        'firma_adi'      => trim($_POST['firma_adi'] ?? ''),
        'yetkili_kisi'   => $_POST['yetkili_kisi'] ?? null,
        'telefon'        => $_POST['telefon'] ?? null,
        'email'          => $_POST['email'] ?? null,
        'adres'          => $_POST['adres'] ?? null,
        'sehir'          => $_POST['sehir'] ?? null,
        'ilce'           => $_POST['ilce'] ?? null,
        'vergi_dairesi'  => $_POST['vergi_dairesi'] ?? null,
        'vergi_no'       => $_POST['vergi_no'] ?? null,
        'not_metni'      => $_POST['not_metni'] ?? null,
        'id'             => $id,
    ]);
    $stmt2 = $pdo->prepare('SELECT * FROM customers WHERE id = :id');
    $stmt2->execute(['id' => $id]);
    $customer = $stmt2->fetch();
    $success = 'Cari bilgileri güncellendi.';
}

// Çalışan atama
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atama_ekle'])) {
    $employeeId = (int) $_POST['employee_id'];
    $pdo->prepare('INSERT IGNORE INTO employee_customers (employee_id, customer_id) VALUES (:eid, :cid)')
        ->execute(['eid' => $employeeId, 'cid' => $id]);
    $success = 'Çalışan atandı.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atama_sil'])) {
    $employeeId = (int) $_POST['employee_id'];
    $pdo->prepare('DELETE FROM employee_customers WHERE employee_id = :eid AND customer_id = :cid')
        ->execute(['eid' => $employeeId, 'cid' => $id]);
    $success = 'Çalışan ataması kaldırıldı.';
}

$siparisStmt = $pdo->prepare(
    'SELECT id, siparis_no, evrak_aciklamasi, genel_toplam, durum, olusturulma_tarihi
     FROM orders WHERE customer_id = :id ORDER BY olusturulma_tarihi DESC LIMIT 20'
);
$siparisStmt->execute(['id' => $id]);
$siparisler = $siparisStmt->fetchAll();

$atananlar = $pdo->prepare(
    'SELECT e.id, e.ad, e.soyad FROM employee_customers ec
     JOIN employees e ON e.id = ec.employee_id WHERE ec.customer_id = :id'
);
$atananlar->execute(['id' => $id]);
$atananCalisanlar = $atananlar->fetchAll();

$tumCalisanlar = $pdo->query(
    'SELECT id, ad, soyad FROM employees WHERE durum = "AKTIF" AND rol = "CALISAN" ORDER BY ad'
)->fetchAll();

$pageTitle = 'Cari Detay — ' . $customer['firma_adi'];
$activePage = 'customers';
require __DIR__ . '/../includes/admin_header.php';
?>

<?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px;">
    <div class="card">
        <h3 style="margin-top:0;">Cari Bilgileri</h3>
        <form method="POST" action="customer-detail.php?id=<?= $id ?>">
            <div class="form-group">
                <label>Firma Adı</label>
                <input type="text" name="firma_adi" value="<?= e($customer['firma_adi']) ?>">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Yetkili Kişi</label>
                    <input type="text" name="yetkili_kisi" value="<?= e($customer['yetkili_kisi']) ?>">
                </div>
                <div class="form-group">
                    <label>Telefon</label>
                    <input type="text" name="telefon" value="<?= e($customer['telefon']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>E-posta</label>
                <input type="email" name="email" value="<?= e($customer['email']) ?>">
            </div>
            <div class="form-group">
                <label>Adres</label>
                <textarea name="adres" rows="2"><?= e($customer['adres']) ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Şehir</label>
                    <input type="text" name="sehir" value="<?= e($customer['sehir']) ?>">
                </div>
                <div class="form-group">
                    <label>İlçe</label>
                    <input type="text" name="ilce" value="<?= e($customer['ilce']) ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Vergi Dairesi</label>
                    <input type="text" name="vergi_dairesi" value="<?= e($customer['vergi_dairesi']) ?>">
                </div>
                <div class="form-group">
                    <label>Vergi No</label>
                    <input type="text" name="vergi_no" value="<?= e($customer['vergi_no']) ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Not</label>
                <textarea name="not_metni" rows="2"><?= e($customer['not_metni']) ?></textarea>
            </div>
            <button type="submit" name="guncelle" class="btn btn-primary">Bilgileri Güncelle</button>
        </form>
    </div>

    <div>
        <div class="card">
            <h3 style="margin-top:0;">Yetkili Çalışanlar</h3>
            <?php foreach ($atananCalisanlar as $ac): ?>
            <form method="POST" action="customer-detail.php?id=<?= $id ?>" style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border);">
                <span><?= e($ac['ad'] . ' ' . $ac['soyad']) ?></span>
                <input type="hidden" name="employee_id" value="<?= (int) $ac['id'] ?>">
                <button type="submit" name="atama_sil" class="btn btn-danger btn-sm">Kaldır</button>
            </form>
            <?php endforeach; ?>
            <?php if (empty($atananCalisanlar)): ?>
            <p style="color:var(--text-muted); font-size:13px;">Bu cariye henüz çalışan atanmamış.</p>
            <?php endif; ?>

            <form method="POST" action="customer-detail.php?id=<?= $id ?>" style="margin-top:16px; display:flex; gap:8px;">
                <select name="employee_id" style="flex:1; padding:8px; border:1px solid var(--border); border-radius:8px;">
                    <?php foreach ($tumCalisanlar as $tc): ?>
                    <option value="<?= (int) $tc['id'] ?>"><?= e($tc['ad'] . ' ' . $tc['soyad']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="atama_ekle" class="btn btn-primary btn-sm">Ata</button>
            </form>
        </div>

        <div class="card">
            <h3 style="margin-top:0;">Siparişler</h3>
            <table>
                <thead><tr><th>Sipariş No</th><th>Tutar</th><th>Durum</th><th>Tarih</th></tr></thead>
                <tbody>
                    <?php foreach ($siparisler as $s): ?>
                    <tr>
                        <td><a href="order-detail.php?id=<?= (int) $s['id'] ?>"><?= e($s['siparis_no']) ?></a></td>
                        <td><?= formatTL((float) $s['genel_toplam']) ?></td>
                        <td><span class="pill pill-muted"><?= e($s['durum']) ?></span></td>
                        <td><?= formatTarih($s['olusturulma_tarihi']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($siparisler)): ?>
                    <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">Sipariş yok.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
