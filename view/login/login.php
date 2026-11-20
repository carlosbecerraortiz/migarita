<?php
require_once __DIR__ . '/../layout/helpers.php';

// Mismo mensaje para usuario inexistente y contraseña incorrecta:
// así nadie puede averiguar qué usuarios existen probando nombres.
$errores = [
    'usuario'         => 'Usuario o contraseña incorrectos.',
    'contrasena'      => 'Usuario o contraseña incorrectos.',
    'bloqueado'       => 'Demasiados intentos fallidos. Espera ' . AuthService::MINUTOS_BLOQUEO . ' minutos e inténtalo de nuevo.',
    'sesion_expirada' => 'Tu sesión se cerró por inactividad. Vuelve a ingresar.',
];
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#15803d">
    <meta name="csrf-token" content="<?= e(Csrf::token()) ?>">
    <title>Iniciar sesión · MiGarita</title>

    <link href="<?= asset('img/favicon1.png') ?>" rel="icon">
    <link href="<?= asset('img/apple-touch-icon1.png') ?>" rel="apple-touch-icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/app.css') ?>?v=8" rel="stylesheet">
    <script src="<?= asset('js/entrada.js') ?>?v=8"></script>
</head>
<body>
<main class="mg-login">

    <section class="mg-login-marca" aria-hidden="true">
        <div class="mg-marca">
            <img src="<?= asset('img/logo1.png') ?>" alt="">
            <span>MiGarita <small style="color:rgba(255,255,255,.75)">Cafetería UPC Aguachica</small></span>
        </div>

        <h1>La cartera de la cafetería, clara y al día.</h1>
        <p>Registra fiados y abonos en segundos y consulta cuánto debe cada cliente sin cuadernos.</p>

        <ul>
            <li><i class="bi bi-lightning-charge"></i> Fiados y abonos en dos toques</li>
            <li><i class="bi bi-envelope-check"></i> Aviso al cliente por correo en cada movimiento</li>
            <li><i class="bi bi-file-earmark-pdf"></i> Informes en PDF por cliente</li>
        </ul>
    </section>

    <section class="mg-login-form">
        <div class="caja">
            <div class="mg-login-movil">
                <span class="mg-marca"><img src="<?= asset('img/logo1.png') ?>" alt=""> MiGarita</span>
            </div>

            <h2>Bienvenido</h2>
            <p class="intro">Ingresa con tu usuario y contraseña.</p>

            <?php if (isset($errores[$error])): ?>
                <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-circle"></i><span><?= e($errores[$error]) ?></span></div>
            <?php endif; ?>

            <form class="needs-validation" method="POST" action="<?= url('login/autenticar') ?>" novalidate>
                <?= Csrf::campo() ?>
                <div class="mb-3">
                    <label for="yourUsername" class="form-label">Usuario</label>
                    <div class="mg-campo-icono">
                        <i class="bi bi-person"></i>
                        <input type="text" name="username" id="yourUsername" class="form-control form-control-lg"
                               autocomplete="username" autocapitalize="none" required autofocus>
                        <div class="invalid-feedback">Escribe tu usuario.</div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="yourPassword" class="form-label">Contraseña</label>
                    <div class="input-group input-group-lg has-validation">
                        <input type="password" name="password" id="yourPassword" class="form-control"
                               autocomplete="current-password" required>
                        <button type="button" class="input-group-text ver-clave" id="togglePassword" aria-label="Mostrar u ocultar contraseña">
                            <i class="bi bi-eye-slash" id="iconoPassword"></i>
                        </button>
                        <div class="invalid-feedback">Escribe tu contraseña.</div>
                    </div>
                </div>

                <button class="btn btn-primary btn-lg w-100" type="submit">Iniciar Sesión</button>
            </form>

            <p class="pie">Universidad Popular del Cesar · Seccional Aguachica</p>
        </div>
    </section>

</main>

<script src="<?= asset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/login.js') ?>?v=8"></script>
</body>
</html>
