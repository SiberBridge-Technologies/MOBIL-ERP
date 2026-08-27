<?php
/** @var string $pageTitle */
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'ERP-APP Yönetim Paneli') ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">Siberbridge ERP</div>
        <nav>
            <a href="index.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">Genel Bakış</a>
            <a href="products.php" class="<?= $activePage === 'products' ? 'active' : '' ?>">Ürünler</a>
            <a href="categories.php" class="<?= $activePage === 'categories' ? 'active' : '' ?>" style="padding-left:40px; font-size:13px;">↳ Kategoriler</a>
            <a href="customers.php" class="<?= $activePage === 'customers' ? 'active' : '' ?>">Cariler</a>
            <a href="orders.php" class="<?= $activePage === 'orders' ? 'active' : '' ?>">Siparişler</a>
            <?php if (currentAdminRole() === 'ADMIN'): ?>
            <a href="employees.php" class="<?= $activePage === 'employees' ? 'active' : '' ?>">Çalışanlar</a>
            <?php endif; ?>
            <a href="settings.php" class="<?= $activePage === 'settings' ? 'active' : '' ?>">Ayarlar</a>
        </nav>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <div class="page-title"><?= e($pageTitle ?? '') ?></div>
            <div class="user-info">
                <span><?= e(currentAdminName()) ?> · <?= e(currentAdminRole()) ?></span>
                <a href="logout.php" class="logout-link">Çıkış Yap</a>
            </div>
        </header>
        <div class="content">
