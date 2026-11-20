<?php
/**
 * Arranque seguro de cada petición (lo incluyen index.php y logout.php).
 *
 * - Oculta la versión de PHP y los errores en pantalla (se registran en el log).
 * - Cookie de sesión endurecida: HttpOnly (JavaScript no la lee), SameSite=Lax
 *   (otros sitios no la envían en formularios), Secure con HTTPS y solo para /mi_garita/.
 * - Modo estricto: PHP no acepta identificadores de sesión inventados.
 * - Cierre de sesión por inactividad.
 */

require_once __DIR__ . '/services/Flash.php';
require_once __DIR__ . '/services/Csrf.php';

/** Minutos sin actividad tras los cuales se cierra la sesión. */
const MIGARITA_MINUTOS_INACTIVIDAD = 60;

date_default_timezone_set('America/Bogota');

header_remove('X-Powered-By');

// Los errores se registran pero no se muestran (salvo con MIGARITA_DEBUG=1)
if (getenv('MIGARITA_DEBUG') !== '1') {
    ini_set('display_errors', '0');
}
ini_set('log_errors', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/mi_garita/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// Cierre por inactividad
if (isset($_SESSION['id'])) {
    $ahora = time();
    $ultima = $_SESSION['ultima_actividad'] ?? $ahora;

    if ($ahora - $ultima > MIGARITA_MINUTOS_INACTIVIDAD * 60) {
        session_unset();
        session_regenerate_id(true);
        Flash::guardar(['error' => 'sesion_expirada']);
        header('Location: /mi_garita/index.php?url=login/index');
        exit;
    }

    $_SESSION['ultima_actividad'] = $ahora;
}
