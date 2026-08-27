<?php
require_once __DIR__ . '/../includes/admin_auth.php';
requireAdminRole(['ADMIN']);

$pdo = getDbConnection();
$employees = $pdo->query(
    'SELECT id, ad, soyad, kullanici_adi, telefon, email, rol, durum, son_giris
     FROM employees ORDER BY ad ASC'
)->fetchAll();

$pageTitle = 'Çalışanlar';
$activePage = 'employees';
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="toolbar">
    <div></div>
    <a href="employee-add.php" class="btn btn-primary">+ Yeni Çalışan</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Ad Soyad</th>
                <th>Kullanıcı Adı</th>
                <th>Rol</th>
                <th>Durum</th>
                <th>Son Giriş</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($employees as $emp): ?>
            <tr>
                <td><?= e($emp['ad'] . ' ' . $emp['soyad']) ?></td>
                <td><?= e($emp['kullanici_adi']) ?></td>
                <td><?= e($emp['rol']) ?></td>
                <td>
                    <span class="pill <?= $emp['durum'] === 'AKTIF' ? 'pill-success' : 'pill-danger' ?>">
                        <?= e($emp['durum']) ?>
                    </span>
                </td>
                <td><?= $emp['son_giris'] ? formatTarih($emp['son_giris']) : 'Hiç giriş yapmadı' ?></td>
                <td><a href="employee-detail.php?id=<?= (int) $emp['id'] ?>" class="btn btn-secondary btn-sm">Detay</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
