<?php
/**
 * Router para el servidor integrado de PHP (php -S) usado en las pruebas
 * funcionales y E2E. La app usa rutas absolutas "/mi_garita/...", así que aquí
 * se quita ese prefijo y se sirve el proyecto sin importar el nombre de la carpeta.
 */

$raiz = realpath(dirname(__DIR__, 2));
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

if (strpos($ruta, '/mi_garita') === 0) {
    $ruta = substr($ruta, strlen('/mi_garita'));
}
if ($ruta === '' || $ruta === '/') {
    $ruta = '/index.php';
}

// Solo se ejecutan los puntos de entrada reales de la app.
$entradas = ['/index.php', '/logout.php'];
if (in_array($ruta, $entradas, true)) {
    chdir($raiz);
    require $raiz . $ruta;
    return true;
}

// Archivos estáticos (CSS, JS, imágenes) solo dentro de /assets.
$archivo = realpath($raiz . $ruta);
if (
    $archivo === false
    || strpos($archivo, $raiz . DIRECTORY_SEPARATOR . 'assets') !== 0
    || !is_file($archivo)
) {
    http_response_code(404);
    echo 'No encontrado';
    return true;
}

$tipos = [
    'css' => 'text/css', 'js' => 'application/javascript', 'png' => 'image/png',
    'jpg' => 'image/jpeg', 'svg' => 'image/svg+xml', 'woff' => 'font/woff',
    'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'json' => 'application/json',
];
$extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
header('Content-Type: ' . ($tipos[$extension] ?? 'application/octet-stream'));
readfile($archivo);
return true;
