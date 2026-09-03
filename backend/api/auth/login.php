<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

setJsonHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Sadece POST isteklerine izin verilir.', 405);
}

$data = getRequestBody();
requireFields($data, ['kullanici_adi', 'sifre']);

$pdo = getDbConnection();

$stmt = $pdo->prepare(
    'SELECT id, ad, soyad, kullanici_adi, sifre_hash, rol, durum
     FROM employees WHERE kullanici_adi = :kullanici_adi LIMIT 1'
);
$stmt->execute(['kullanici_adi' => $data['kullanici_adi']]);
$employee = $stmt->fetch();

if (!$employee || !password_verify($data['sifre'], $employee['sifre_hash'])) {
    jsonError('Kullanıcı adı veya şifre hatalı.', 401);
}

if ($employee['durum'] !== 'AKTIF') {
    jsonError('Hesabınız pasif durumda. Yöneticinizle iletişime geçin.', 403);
}

// Yeni token oluştur
$token = generateToken();
$expiresAt = date('Y-m-d H:i:s', time() + TOKEN_LIFETIME);

$insert = $pdo->prepare(
    'INSERT INTO auth_tokens (employee_id, token, son_kullanim_tarihi) VALUES (:eid, :token, :exp)'
);
$insert->execute([
    'eid'   => $employee['id'],
    'token' => $token,
    'exp'   => $expiresAt,
]);

// Son giriş tarihini güncelle
$pdo->prepare('UPDATE employees SET son_giris = NOW() WHERE id = :id')
    ->execute(['id' => $employee['id']]);

jsonResponse([
    'success' => true,
    'token'   => $token,
    'expires_at' => $expiresAt,
    'user' => [
        'id'            => $employee['id'],
        'ad'            => $employee['ad'],
        'soyad'         => $employee['soyad'],
        'kullanici_adi' => $employee['kullanici_adi'],
        'rol'           => $employee['rol'],
    ],
]);
