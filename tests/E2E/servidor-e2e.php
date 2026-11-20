<?php
/**
 * Servidor para la prueba E2E con Playwright.
 * Crea una BD SQLite de prueba con datos semilla y levanta la app con `php -S`
 * (Playwright lo inicia y lo detiene automáticamente, ver playwright.config.js).
 *
 * Uso manual: php tests/E2E/servidor-e2e.php 8090
 */

require dirname(__DIR__, 2) . '/model/Database.php';
require dirname(__DIR__) . '/Support/BaseDatosPrueba.php';

use Tests\Support\BaseDatosPrueba;

$puerto = (int)($argv[1] ?? 8090);
$raiz = dirname(__DIR__, 2);
$rutaBd = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'migarita_e2e.sqlite';

$pdo = BaseDatosPrueba::enArchivo($rutaBd);
BaseDatosPrueba::sembrarUsuarios($pdo);
BaseDatosPrueba::crearCliente($pdo, [
    'cedula' => '1065123456', 'nombre' => 'Laura', 'apellido' => 'Gómez', 'correo' => 'laura@correo.test',
]);
$pdo = null;

putenv('MIGARITA_DB_DSN=sqlite:' . $rutaBd);
putenv('MIGARITA_MAIL_ENABLED=0');
putenv('XDEBUG_MODE=off');

$comando = sprintf(
    '%s -d display_errors=stderr -d session.save_path=%s -S 127.0.0.1:%d -t %s %s',
    escapeshellarg(PHP_BINARY),
    escapeshellarg(sys_get_temp_dir()),
    $puerto,
    escapeshellarg($raiz),
    escapeshellarg(dirname(__DIR__) . '/Support/router.php')
);

echo "MiGarita E2E en http://127.0.0.1:$puerto/mi_garita/\n";
passthru($comando);
