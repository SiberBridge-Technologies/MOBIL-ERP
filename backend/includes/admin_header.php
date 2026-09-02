<?php
/** @var string $pageTitle */
$activePage = $activePage ?? '';
$initials = '';
if (function_exists('currentAdminName')) {
    $parts = array_filter(explode(' ', trim(currentAdminName())));
    foreach (array_slice($parts, 0, 2) as $p) { $initials .= mb_substr($p, 0, 1); }
    $initials = mb_strtoupper($initials);
}
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
        <div class="brand">
            <div class="brand-logo">S</div>
            <div class="brand-text">SIBERBRIDGE<span>ERP SYSTEM</span></div>
        </div>

        <div class="menu-title">Yönetim</div>
        <nav>
            <a href="index.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">⌂ Genel Bakış</a>
            <a href="products.php" class="<?= $activePage === 'products' ? 'active' : '' ?>">▣ Ürünler</a>
            <a href="categories.php" class="sub <?= $activePage === 'categories' ? 'active' : '' ?>">↳ Kategoriler</a>
            <a href="customers.php" class="<?= $activePage === 'customers' ? 'active' : '' ?>">♙ Cariler</a>
            <a href="orders.php" class="<?= $activePage === 'orders' ? 'active' : '' ?>">▤ Siparişler</a>
            <?php if (currentAdminRole() === 'ADMIN'): ?>
            <a href="employees.php" class="<?= $activePage === 'employees' ? 'active' : '' ?>">♧ Çalışanlar</a>
            <?php endif; ?>
            <a href="settings.php" class="<?= $activePage === 'settings' ? 'active' : '' ?>">⚙ Ayarlar</a>
        </nav>

        <div class="sidebar-bottom">
            <div class="system-box">
                <div class="system-title">Sistem Durumu</div>
                <div class="system-status"><span class="online"></span> Sistemler aktif</div>
            </div>
        </div>
    </aside>

    <div class="main-content">
        <header class="topbar">
            <div class="page-title"><?= e($pageTitle ?? '') ?></div>
            <div class="user-info">
                <span><span class="avatar"><?= e($initials ?: '?') ?></span><strong><?= e(currentAdminName()) ?></strong> · <?= e(currentAdminRole()) ?></span>
                <a href="logout.php" class="logout-link">Çıkış Yap</a>
            </div>
        </header>
        <div class="content">
            <?php if (!empty($pageSubtitle)): ?>
            <div class="page-intro">
                <h2><?= e($pageTitle ?? '') ?></h2>
                <p><?= e($pageSubtitle) ?></p>
            </div>
            <?php endif; ?>
