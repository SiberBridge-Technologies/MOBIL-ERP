<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

setJsonHeaders();

$token = getBearerToken();
if ($token) {
    $pdo = getDbConnection();
    $pdo->prepare('DELETE FROM auth_tokens WHERE token = :token')->execute(['token' => $token]);
}

jsonResponse(['success' => true, 'message' => 'Çıkış yapıldı.']);
