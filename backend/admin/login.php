<?php
require_once __DIR__ . '/../includes/admin_auth.php';

if (adminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kullaniciAdi = trim($_POST['kullanici_adi'] ?? '');
    $sifre = $_POST['sifre'] ?? '';

    if ($kullaniciAdi === '' || $sifre === '') {
        $error = 'Kullanıcı adı ve şifre zorunludur.';
    } else {
        $pdo = getDbConnection();

        $stmt = $pdo->prepare(
            'SELECT id, ad, soyad, kullanici_adi, sifre_hash, rol, durum
             FROM employees
             WHERE kullanici_adi = :ka
             AND rol IN ("ADMIN", "YONETICI")
             LIMIT 1'
        );

        $stmt->execute(['ka' => $kullaniciAdi]);
        $employee = $stmt->fetch();

        if (!$employee || !password_verify($sifre, $employee['sifre_hash'])) {
            $error = 'Kullanıcı adı veya şifre hatalı.';
        } elseif ($employee['durum'] !== 'AKTIF') {
            $error = 'Hesabınız pasif durumda.';
        } else {
            $_SESSION['admin_employee_id'] = $employee['id'];
            $_SESSION['admin_ad'] = $employee['ad'];
            $_SESSION['admin_soyad'] = $employee['soyad'];
            $_SESSION['admin_rol'] = $employee['rol'];

            $pdo->prepare(
                'UPDATE employees SET son_giris = NOW() WHERE id = :id'
            )->execute([
                'id' => $employee['id']
            ]);

            header('Location: index.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>SIBERBRIDGE — Yönetim Paneli</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            width: 100%;
            height: 100%;
        }

        body {
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Arial,
                sans-serif;

            background: #f5f7fa;
            color: #111827;
            overflow: hidden;
        }

        .login-page {
            width: 100%;
            height: 100vh;

            display: flex;

            background: #ffffff;
        }

        /* =========================
           SOL LOGIN ALANI
        ========================= */

        .login-section {
            width: 50%;
            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 50px;

            background: #ffffff;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
        }

        .brand {
            display: flex;
            align-items: center;

            margin-bottom: 55px;
        }

        .brand-logo {
            width: 48px;
            height: 48px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin-right: 13px;

            border-radius: 12px;

            background: #111827;
            color: #ffffff;

            font-size: 23px;
            font-weight: 800;

            letter-spacing: -1px;

            box-shadow:
                0 8px 25px rgba(17, 24, 39, 0.15);
        }

        .brand-text {
            color: #111827;

            font-size: 18px;
            font-weight: 800;

            letter-spacing: 1.8px;
        }

        .brand-text span {
            display: block;

            margin-top: 3px;

            color: #9ca3af;

            font-size: 9px;
            font-weight: 600;

            letter-spacing: 2.5px;
        }

        .login-title {
            margin-bottom: 10px;

            color: #111827;

            font-size: 34px;
            font-weight: 750;

            letter-spacing: -1px;
        }

        .login-subtitle {
            margin-bottom: 36px;

            color: #6b7280;

            font-size: 15px;
            line-height: 1.6;
        }

        /* =========================
           HATA MESAJI
        ========================= */

        .alert {
            display: flex;
            align-items: center;

            margin-bottom: 22px;
            padding: 13px 15px;

            border-radius: 10px;

            font-size: 13px;
            line-height: 1.5;
        }

        .alert-error {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
        }

        /* =========================
           FORM
        ========================= */

        .form-group {
            margin-bottom: 21px;
        }

        .form-group label {
            display: block;

            margin-bottom: 9px;

            color: #374151;

            font-size: 13px;
            font-weight: 650;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            height: 52px;

            padding: 0 16px;

            border: 1px solid #d1d5db;
            border-radius: 10px;

            outline: none;

            background: #ffffff;
            color: #111827;

            font-size: 14px;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        .form-input:focus {
            border-color: #111827;

            box-shadow:
                0 0 0 3px rgba(17, 24, 39, 0.07);
        }

        .form-input::placeholder {
            color: #9ca3af;
        }

        .password-input {
            padding-right: 52px;
        }

        .password-toggle {
            position: absolute;

            top: 50%;
            right: 15px;

            transform: translateY(-50%);

            border: 0;

            background: transparent;

            color: #6b7280;

            cursor: pointer;

            font-size: 13px;
            font-weight: 600;
        }

        .password-toggle:hover {
            color: #111827;
        }

        /* =========================
           BUTTON
        ========================= */

        .login-button {
            width: 100%;
            height: 52px;

            margin-top: 9px;

            border: 0;
            border-radius: 10px;

            background: #111827;
            color: #ffffff;

            cursor: pointer;

            font-size: 14px;
            font-weight: 700;

            letter-spacing: 0.2px;

            transition:
                transform 0.2s ease,
                background 0.2s ease,
                box-shadow 0.2s ease;
        }

        .login-button:hover {
            background: #1f2937;

            box-shadow:
                0 8px 25px rgba(17, 24, 39, 0.16);

            transform: translateY(-1px);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-footer {
            margin-top: 30px;

            text-align: center;

            color: #9ca3af;

            font-size: 11px;
        }

        /* =========================
           SAĞ DÜNYA ALANI
        ========================= */

        .visual-section {
            position: relative;

            width: 50%;
            min-height: 100vh;

            overflow: hidden;

            background: #030712;
        }

        .earth-image {
            position: absolute;

            inset: 0;

            width: 100%;
            height: 100%;

            object-fit: cover;

            object-position: center;

            /*
             * NASA Black Marble / Earth at Night
             */
            background-image: url(
                "https://www.nasa.gov/wp-content/uploads/2023/03/earth-at-night.jpg"
            );

            background-size: cover;
            background-position: center;

            filter:
                brightness(0.78)
                contrast(1.08);

            transform: scale(1.03);
        }

        /*
         * Görselin üzerine koyu gradient
         */
        .visual-overlay {
            position: absolute;

            inset: 0;

            background:
                linear-gradient(
                    90deg,
                    rgba(3, 7, 18, 0.72) 0%,
                    rgba(3, 7, 18, 0.20) 55%,
                    rgba(3, 7, 18, 0.60) 100%
                );

            pointer-events: none;
        }

        .visual-content {
            position: relative;
            z-index: 2;

            width: 100%;
            height: 100%;

            display: flex;
            flex-direction: column;

            align-items: center;
            justify-content: center;

            padding: 50px;

            text-align: center;

            color: #ffffff;
        }

        .visual-logo {
            width: 68px;
            height: 68px;

            display: flex;
            align-items: center;
            justify-content: center;

            margin-bottom: 25px;

            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 18px;

            background: rgba(255, 255, 255, 0.10);

            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);

            font-size: 30px;
            font-weight: 800;

            box-shadow:
                0 20px 50px rgba(0, 0, 0, 0.25);
        }

        .visual-brand {
            margin-bottom: 10px;

            font-size: 14px;
            font-weight: 700;

            letter-spacing: 5px;

            opacity: 0.88;
        }

        .visual-title {
            font-size: clamp(38px, 4vw, 62px);
            font-weight: 800;

            letter-spacing: -2px;

            line-height: 1.05;

            text-shadow:
                0 8px 30px rgba(0, 0, 0, 0.35);
        }

        .visual-description {
            max-width: 430px;

            margin-top: 22px;

            color: rgba(255, 255, 255, 0.72);

            font-size: 14px;
            line-height: 1.7;
        }

        .visual-line {
            width: 60px;
            height: 2px;

            margin-top: 28px;

            background: rgba(255, 255, 255, 0.75);
        }

        .visual-bottom {
            position: absolute;

            bottom: 35px;
            left: 50%;

            transform: translateX(-50%);

            z-index: 3;

            color: rgba(255, 255, 255, 0.45);

            font-size: 10px;

            letter-spacing: 1.5px;

            white-space: nowrap;
        }

        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 900px) {

            body {
                overflow: auto;
            }

            .login-page {
                min-height: 100vh;
            }

            .login-section {
                width: 100%;
                min-height: 100vh;

                padding: 35px 25px;
            }

            .visual-section {
                display: none;
            }

            .login-container {
                max-width: 430px;
            }

            .brand {
                margin-bottom: 45px;
            }

            .login-title {
                font-size: 30px;
            }
        }

        @media (max-width: 480px) {

            .login-section {
                padding: 28px 20px;
            }

            .brand {
                margin-bottom: 40px;
            }

            .brand-logo {
                width: 44px;
                height: 44px;

                font-size: 21px;
            }

            .brand-text {
                font-size: 16px;
            }

            .login-title {
                font-size: 28px;
            }
        }
    </style>
</head>

<body>

<div class="login-page">

    <!-- SOL TARAF -->
    <section class="login-section">

        <div class="login-container">

            <div class="brand">

                <div class="brand-logo">
                    S
                </div>

                <div class="brand-text">
                    SIBERBRIDGE
                    <span>ERP SYSTEM</span>
                </div>

            </div>

            <h1 class="login-title">
                Yönetim Paneli
            </h1>

            <p class="login-subtitle">
                Devam etmek için yönetici hesabınızla giriş yapın.
            </p>

            <?php if ($error): ?>

                <div class="alert alert-error">
                    <?= e($error) ?>
                </div>

            <?php endif; ?>

            <form
                method="POST"
                action="login.php"
                autocomplete="on"
            >

                <div class="form-group">

                    <label for="kullanici_adi">
                        Kullanıcı Adı
                    </label>

                    <input
                        id="kullanici_adi"
                        class="form-input"
                        type="text"
                        name="kullanici_adi"
                        autocomplete="username"
                        placeholder="Kullanıcı adınızı girin"
                        required
                        autofocus
                    >

                </div>

                <div class="form-group">

                    <label for="sifre">
                        Şifre
                    </label>

                    <div class="input-wrapper">

                        <input
                            id="sifre"
                            class="form-input password-input"
                            type="password"
                            name="sifre"
                            autocomplete="current-password"
                            placeholder="Şifrenizi girin"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            onclick="togglePassword()"
                            id="passwordToggle"
                        >
                            Göster
                        </button>

                    </div>

                </div>

                <button
                    type="submit"
                    class="login-button"
                >
                    Giriş Yap
                </button>

            </form>

            <div class="login-footer">
                SIBERBRIDGE ERP SYSTEM
            </div>

        </div>

    </section>


    <!-- SAĞ TARAF -->
    <section class="visual-section">

        <div class="earth-image"></div>

        <div class="visual-overlay"></div>

        <div class="visual-content">

            <div class="visual-logo">
                S
            </div>

            <div class="visual-brand">
                SIBERBRIDGE
            </div>

            <div class="visual-title">
                YÖNETİM<br>
                PANELİ
            </div>

            <div class="visual-line"></div>

            <p class="visual-description">
                İş süreçlerinizi tek merkezden yönetin.
                SiberBridge ERP ile operasyonlarınızı
                daha hızlı, güvenli ve verimli hale getirin.
            </p>

        </div>

        <div class="visual-bottom">
            ENTERPRISE RESOURCE PLANNING
        </div>

    </section>

</div>


<script>
    function togglePassword() {

        const passwordInput =
            document.getElementById('sifre');

        const toggleButton =
            document.getElementById('passwordToggle');

        if (passwordInput.type === 'password') {

            passwordInput.type = 'text';

            toggleButton.textContent = 'Gizle';

        } else {

            passwordInput.type = 'password';

            toggleButton.textContent = 'Göster';
        }
    }
</script>

</body>
</html>