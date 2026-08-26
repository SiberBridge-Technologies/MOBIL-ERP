<?php
/**
 * Ortak yardımcı fonksiyonlar
 */

function setJsonHeaders(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonError(string $message, int $statusCode = 400): void
{
    jsonResponse(['success' => false, 'message' => $message], $statusCode);
}

function getRequestBody(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Zorunlu alanları kontrol eder, eksikse hata döner ve script'i sonlandırır.
 */
function requireFields(array $data, array $fields): void
{
    $missing = [];
    foreach ($fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            $missing[] = $field;
        }
    }
    if (!empty($missing)) {
        jsonError('Eksik alanlar: ' . implode(', ', $missing), 422);
    }
}

function generateToken(): string
{
    return bin2hex(random_bytes(32));
}

function generateOrderNumber(): string
{
    return 'SIP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}
