<?php
/**
 * Basit, bağımlılıksız .env okuyucu.
 * Composer/vendor gerektirmez — paylaşımlı ucuz hostinglerde bile
 * sorunsuz çalışır. vlucas/phpdotenv'in çok küçük bir alternatifidir.
 *
 * Kullanım:
 *   loadEnv(__DIR__ . '/.env');
 *   $host = env('DB_HOST', 'localhost');
 */

function loadEnv(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        // .env dosyası yoksa sessizce geç — env() fonksiyonu zaten
        // verilen varsayılan değerlere düşecek. Böylece geliştirici
        // ortamda .env unutulsa bile sistem çökmez, sadece varsayılanları kullanır.
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Yorum satırlarını atla
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Değer tırnak içindeyse tırnakları temizle ("value" veya 'value')
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        if ($key === '') {
            continue;
        }

        // Zaten sistemde tanımlıysa (örn. hosting panelinden env değişkeni
        // olarak ayarlanmışsa) .env dosyasındaki değer onu EZMEZ.
        if (getenv($key) === false && !isset($_ENV[$key])) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

/**
 * Bir ortam değişkenini okur, yoksa $default döner.
 */
function env(string $key, $default = null)
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false || $value === null) {
        return $default;
    }

    // "true"/"false"/"null" gibi metinleri gerçek tiplere çevir (kullanışlı
    // örn. APP_DEBUG=true değerinin PHP'de gerçek boolean true olması için).
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
        case 'empty':
        case '(empty)':
            return '';
    }

    return $value;
}
