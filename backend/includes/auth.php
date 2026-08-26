<?php
/**
 * Token tabanlı kimlik doğrulama
 * Kullanım: her korumalı API dosyasının başında requireAuth() çağrılır.
 */

require_once __DIR__ . '/functions.php';

function getBearerToken(): ?string
{
    $headers = null;

    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER['Authorization']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }

    if ($headers !== null && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * Geçerli tokenı doğrular ve ilişkili çalışan bilgisini döner.
 * Geçersizse 401 döner ve script'i sonlandırır.
 */
function requireAuth(): array
{
    $token = getBearerToken();

    if (!$token) {
        jsonError('Yetkilendirme token\'ı bulunamadı.', 401);
    }

    $pdo = getDbConnection();

    $stmt = $pdo->prepare(
        'SELECT at.employee_id, at.son_kullanim_tarihi, e.rol, e.durum, e.ad, e.soyad, e.kullanici_adi
         FROM auth_tokens at
         JOIN employees e ON e.id = at.employee_id
         WHERE at.token = :token'
    );
    $stmt->execute(['token' => $token]);
    $row = $stmt->fetch();

    if (!$row) {
        jsonError('Geçersiz token.', 401);
    }

    if (strtotime($row['son_kullanim_tarihi']) < time()) {
        jsonError('Oturum süresi doldu, tekrar giriş yapın.', 401);
    }

    if ($row['durum'] !== 'AKTIF') {
        jsonError('Hesabınız pasif durumda.', 403);
    }

    return $row;
}

/**
 * Sadece belirli rollere izin verir (örn. ADMIN, YONETICI)
 */
function requireRole(array $currentUser, array $allowedRoles): void
{
    if (!in_array($currentUser['rol'], $allowedRoles, true)) {
        jsonError('Bu işlem için yetkiniz yok.', 403);
    }
}

/**
 * Bir çalışanın belirli bir cariye erişim yetkisi olup olmadığını kontrol eder.
 * ADMIN/YONETICI tüm carilere erişebilir.
 */
function employeeCanAccessCustomer(int $employeeId, int $customerId, string $role): bool
{
    if (in_array($role, ['ADMIN', 'YONETICI'], true)) {
        return true;
    }

    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'SELECT 1 FROM employee_customers WHERE employee_id = :eid AND customer_id = :cid'
    );
    $stmt->execute(['eid' => $employeeId, 'cid' => $customerId]);
    return (bool) $stmt->fetchColumn();
}
