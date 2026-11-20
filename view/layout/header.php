<?php
/**
 * Apertura del layout de la app (páginas con sesión iniciada).
 *
 * Variables que puede definir la vista antes de incluirlo:
 *   $titulo  Título de la pestaña (ej. "Clientes")
 *   $seccion Sección activa del menú: "inicio" | "clientes" | "usuarios"
 */
if (!isset($_SESSION['id'])) {
    header("Location: /mi_garita/index.php?url=login/index");
    exit;
}

require_once __DIR__ . '/helpers.php';

$titulo = $titulo ?? 'MiGarita';
$seccion = $seccion ?? '';
$nombreSesion = $_SESSION['nombre'] ?? '';
$rolSesion = $_SESSION['rol'] ?? 'usuario';

$menu = [
    'inicio'   => ['Inicio', 'bi-grid-1x2', 'inicio/index'],
    'clientes' => ['Clientes', 'bi-people', 'cliente/index'],
    'usuarios' => ['Usuarios', 'bi-person-badge', 'user/index'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#15803d">
    <title><?= e($titulo) ?> · MiGarita</title>

    <link href="<?= asset('img/favicon1.png') ?>" rel="icon">
    <link href="<?= asset('img/apple-touch-icon1.png') ?>" rel="apple-touch-icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.css') ?>" rel="stylesheet">
    <link href="<?= asset('css/app.css') ?>?v=6" rel="stylesheet">
    <script>
        /* Si la página llega sin transición de vista, animar la entrada del contenido */
        (function (html) {
            if (!("onpagereveal" in window)) { html.classList.add("mg-entrada"); return; }
            addEventListener("pagereveal", function (e) { if (!e.viewTransition) html.classList.add("mg-entrada"); });
        })(document.documentElement);
    </script>
</head>
<body data-session-id="<?= (int)$_SESSION['id'] ?>" data-session-rol="<?= e($rolSesion) ?>">
<div class="mg-app">

    <!-- Menú lateral (computador) -->
    <aside class="mg-menu" aria-label="Menú principal">
        <a href="<?= url('inicio/index') ?>" class="mg-marca">
            <img src="<?= asset('img/logo1.png') ?>" alt="">
            <span>MiGarita <small>Cafetería UPC</small></span>
        </a>

        <ul class="mg-nav">
            <?php foreach ($menu as $clave => [$texto, $icono, $ruta]): ?>
                <li>
                    <a href="<?= url($ruta) ?>" class="<?= $seccion === $clave ? 'activo' : '' ?>"
                       <?= $seccion === $clave ? 'aria-current="page"' : '' ?>>
                        <i class="bi <?= $icono ?>"></i> <?= $texto ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="mg-sesion">
            <span class="mg-avatar"><?= e(iniciales($nombreSesion)) ?></span>
            <div class="datos">
                <div class="nombre"><?= e($nombreSesion) ?></div>
                <div class="rol"><?= $rolSesion === 'superusuario' ? 'Superusuario' : 'Usuario' ?></div>
            </div>
            <a href="/mi_garita/logout.php" class="salir" title="Cerrar sesión" aria-label="Cerrar sesión">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </aside>

    <!-- Barra superior (celular) -->
    <header class="mg-barra-movil">
        <a href="<?= url('inicio/index') ?>" class="mg-marca">
            <img src="<?= asset('img/logo1.png') ?>" alt=""> MiGarita
        </a>
        <span class="mg-avatar" title="<?= e($nombreSesion) ?>"><?= e(iniciales($nombreSesion)) ?></span>
    </header>

    <main class="mg-contenido" id="contenido">
