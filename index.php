<?php
session_start();
date_default_timezone_set('America/Bogota');

require_once __DIR__ . "/app/services/Flash.php";

// Los avisos (ok / error / monto) llegan por la SESIÓN, nunca por la URL.
// Se ignoran los que vengan escritos en la URL y se entregan a las vistas
// en los mismos parámetros que ya leen ($_GET['ok'], $_GET['error']…).
foreach (Flash::PERMITIDAS as $clave) {
    unset($_GET[$clave]);
}
$_GET = Flash::tomar() + $_GET;

$url = isset($_GET['url']) ? $_GET['url'] : 'login/index';

$partes = explode('/', $url);
$controllerName = ucfirst($partes[0]) . "Controller";
$method = $partes[1] ?? 'index';
$param = $partes[2] ?? null;

$controllerFile = __DIR__ . "/controller/" . $controllerName . ".php";

// Solo se aceptan nombres con letras (evita rutas como "../algo").
if (!preg_match('/^[A-Za-z]+$/', $partes[0]) || !file_exists($controllerFile)) {
    http_response_code(404);
    die("No existe el controlador: " . htmlspecialchars($controllerName));
}

require_once $controllerFile;

$controller = new $controllerName();

// is_callable evita que se invoquen métodos privados desde la URL.
if (!preg_match('/^[A-Za-z]+$/', $method) || !is_callable([$controller, $method])) {
    http_response_code(404);
    die("No existe el método " . htmlspecialchars($method) . " en $controllerName");
}

try {
    ($param !== null) ? $controller->$method($param) : $controller->$method();
} catch (ConexionException $e) {
    // Base de datos no disponible (p. ej. MySQL apagado en XAMPP).
    http_response_code(500);
    die(htmlspecialchars($e->getMessage()));
}
