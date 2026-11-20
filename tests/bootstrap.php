<?php
/**
 * Arranque de la suite de pruebas.
 * Carga Composer y las clases de la app (la app no usa autoload propio).
 */

date_default_timezone_set('America/Bogota');

// Las pruebas NUNCA envían correos reales.
putenv('MIGARITA_MAIL_ENABLED=0');

$raiz = dirname(__DIR__);

require_once $raiz . '/vendor/autoload.php';

require_once $raiz . '/model/Database.php';
require_once $raiz . '/model/ClienteModel.php';
require_once $raiz . '/model/UserModel.php';
require_once $raiz . '/model/BitacoraModel.php';

require_once $raiz . '/app/validators/ClienteValidator.php';
require_once $raiz . '/app/validators/UsuarioValidator.php';
require_once $raiz . '/app/validators/MovimientoValidator.php';

require_once $raiz . '/app/services/EmailService.php';
require_once $raiz . '/app/services/AuthService.php';
require_once $raiz . '/app/services/ClienteService.php';
require_once $raiz . '/app/services/MovimientoService.php';
require_once $raiz . '/app/services/UsuarioService.php';
require_once $raiz . '/app/services/InformeService.php';
require_once $raiz . '/app/services/PanelService.php';
require_once $raiz . '/app/services/Flash.php';
require_once $raiz . '/app/services/Csrf.php';

require_once __DIR__ . '/Support/BaseDatosPrueba.php';
require_once __DIR__ . '/Support/ServidorPrueba.php';
