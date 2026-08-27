<?php
/**
 * Admin panel oturum yönetimi (session tabanlı — mobil API'den farklı olarak
 * burada PHP session kullanılır, çünkü tarayıcı üzerinden erişilir).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

function adminLoggedIn(): bool
{
    return isset($_SESSION['admin_employee_id']);
}

function requireAdminLogin(): void
{
    if (!adminLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdminRole(array $allowedRoles): void
{
    requireAdminLogin();
    if (!in_array($_SESSION['admin_rol'], $allowedRoles, true)) {
        http_response_code(403);
        die('Bu sayfaya erişim yetkiniz yok.');
    }
}

function currentAdminName(): string
{
    return ($_SESSION['admin_ad'] ?? '') . ' ' . ($_SESSION['admin_soyad'] ?? '');
}

function currentAdminRole(): string
{
    return $_SESSION['admin_rol'] ?? '';
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatTL(float $value): string
{
    return number_format($value, 2, ',', '.') . ' ₺';
}

function formatTarih(?string $value): string
{
    if (!$value) return '-';
    $ts = strtotime($value);
    return $ts ? date('d.m.Y', $ts) : $value;
}
