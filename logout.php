<?php
/**
 * Cerrar sesión. Solo por POST y con token CSRF, para que otra página no pueda
 * cerrar la sesión del usuario con un simple enlace.
 */
require_once __DIR__ . '/app/arranque.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST'
    || !Csrf::esValido(Csrf::tokenRecibido($_POST, $_SERVER))) {
    header('Location: /mi_garita/index.php?url=login/index');
    exit;
}

// Borrar los datos, la cookie y la sesión del servidor
$_SESSION = [];
$cookie = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires'  => time() - 3600,
    'path'     => $cookie['path'],
    'secure'   => $cookie['secure'],
    'httponly' => $cookie['httponly'],
    'samesite' => $cookie['samesite'],
]);
session_destroy();

header('Location: /mi_garita/index.php?url=login/index');
exit;
