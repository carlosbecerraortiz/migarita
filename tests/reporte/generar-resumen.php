<?php
/**
 * Genera coverage/resumen.html: un resumen de cobertura EN ESPAÑOL y fácil de leer,
 * a partir de los archivos que produce PHPUnit:
 *   - coverage/clover.xml  (líneas y métodos cubiertos por archivo)
 *   - coverage/junit.xml   (resultado de las pruebas por nivel)
 *
 * También agrega a cada página del reporte detallado de PHPUnit (coverage/html)
 * una barra superior con un enlace de regreso a este resumen.
 *
 * Uso: php tests/reporte/generar-resumen.php   (lo ejecuta `composer test:coverage`)
 */

date_default_timezone_set('America/Bogota');

$raiz = dirname(__DIR__, 2);
$dirCobertura = $raiz . '/coverage';
$archivoClover = $dirCobertura . '/clover.xml';
$archivoJunit = $dirCobertura . '/junit.xml';
$umbral = 60;

if (!is_file($archivoClover)) {
    fwrite(STDERR, "No existe coverage/clover.xml. Ejecuta primero: composer test:coverage\n");
    exit(1);
}

// ------------------------------------------------------------------ Datos

/** Qué hace cada archivo, en palabras simples (para quien lee el reporte). */
$descripciones = [
    'ClienteValidator.php'    => 'Valida los datos de un cliente: cédula, nombre, teléfono, correo y tipo.',
    'UsuarioValidator.php'    => 'Valida los datos de un usuario del sistema: contraseña, rol y duplicados.',
    'MovimientoValidator.php' => 'Reglas de fiados y abonos: monto mayor que 0, abono no mayor que la deuda, signo del monto.',
    'ClienteService.php'      => 'Crear, editar y eliminar clientes. No permite eliminar a quien tiene saldo.',
    'MovimientoService.php'   => 'Registra fiados y abonos y avisa al cliente por correo.',
    'UsuarioService.php'      => 'Permisos por rol: quién puede crear, editar o eliminar usuarios.',
    'AuthService.php'         => 'Inicio de sesión y registro de cada intento en la bitácora.',
    'InformeService.php'      => 'Rangos de fechas y nombre de los informes PDF.',
    'EmailService.php'        => 'Arma y envía el correo de notificación al cliente.',
    'Database.php'            => 'Conexión a la base de datos (MySQL en la app, SQLite en las pruebas).',
    'ClienteModel.php'        => 'Consultas SQL de clientes, movimientos y saldos.',
    'UserModel.php'           => 'Consultas SQL de usuarios.',
    'BitacoraModel.php'       => 'Guarda los intentos de inicio de sesión.',
];

/** Capas del proyecto, en el orden en que se muestran. */
$capas = [
    'app/validators' => ['Validadores', 'Reglas de los formularios. Funciones puras, sin base de datos.'],
    'app/services'   => ['Servicios de negocio', 'La lógica de la garita: saldos, permisos, login, correo e informes.'],
    'model'          => ['Modelos (acceso a datos)', 'Consultas SQL contra la base de datos.'],
];

$clover = simplexml_load_file($archivoClover);
$proyecto = $clover->project->metrics;

$total = [
    'lineas'            => (int)$proyecto['statements'],
    'lineasCubiertas'   => (int)$proyecto['coveredstatements'],
    'metodos'           => (int)$proyecto['methods'],
    'metodosCubiertos'  => (int)$proyecto['coveredmethods'],
    'archivos'          => (int)$proyecto['files'],
];

$archivosPorCapa = array_fill_keys(array_keys($capas), []);

foreach ($clover->xpath('//file') as $archivo) {
    $ruta = str_replace('\\', '/', (string)$archivo['name']);
    $relativa = ltrim(substr($ruta, strlen(str_replace('\\', '/', $raiz))), '/');

    $sinCubrir = [];
    foreach ($archivo->line as $linea) {
        if ((string)$linea['type'] === 'stmt' && (int)$linea['count'] === 0) {
            $sinCubrir[] = (int)$linea['num'];
        }
    }

    $m = $archivo->metrics;
    $datos = [
        'nombre'           => basename($relativa),
        'relativa'         => $relativa,
        'lineas'           => (int)$m['statements'],
        'lineasCubiertas'  => (int)$m['coveredstatements'],
        'metodos'          => (int)$m['methods'],
        'metodosCubiertos' => (int)$m['coveredmethods'],
        'sinCubrir'        => $sinCubrir,
    ];

    foreach (array_keys($capas) as $prefijo) {
        if (strpos($relativa, $prefijo . '/') === 0) {
            $archivosPorCapa[$prefijo][] = $datos;
            continue 2;
        }
    }
    $archivosPorCapa['otros'][] = $datos;
}

if (!empty($archivosPorCapa['otros'])) {
    $capas['otros'] = ['Otros', 'Archivos fuera de las capas anteriores.'];
}

// Resultado de las pruebas (junit.xml)
$nombresNivel = [
    'Unitarias'   => ['Unitarias', 'Validadores y servicios aislados con mocks (BD y correo simulados).'],
    'Integracion' => ['Integración', 'Modelos y casos de uso completos contra una BD SQLite de prueba.'],
    'Funcionales' => ['Funcionales', 'La app real por HTTP: login, clientes, movimientos, permisos y códigos de estado.'],
];
$niveles = [];
$pruebas = ['total' => 0, 'fallidas' => 0, 'aserciones' => 0, 'tiempo' => 0.0];

if (is_file($archivoJunit)) {
    $junit = simplexml_load_file($archivoJunit);
    foreach ($junit->xpath('/testsuites/testsuite/testsuite') as $suite) {
        $nombre = (string)$suite['name'];
        $fila = [
            'nombre'     => $nombresNivel[$nombre][0] ?? $nombre,
            'detalle'    => $nombresNivel[$nombre][1] ?? '',
            'pruebas'    => (int)$suite['tests'],
            'aserciones' => (int)$suite['assertions'],
            'fallidas'   => (int)$suite['failures'] + (int)$suite['errors'],
            'omitidas'   => (int)$suite['skipped'],
            'tiempo'     => (float)$suite['time'],
        ];
        $niveles[] = $fila;
        $pruebas['total'] += $fila['pruebas'];
        $pruebas['fallidas'] += $fila['fallidas'];
        $pruebas['aserciones'] += $fila['aserciones'];
        $pruebas['tiempo'] += $fila['tiempo'];
    }
}

// ------------------------------------------------------------------ Utilidades

function e($texto): string
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

function porcentaje(int $cubiertas, int $total): float
{
    return $total > 0 ? ($cubiertas / $total) * 100 : 100.0;
}

function formatoPct(float $valor): string
{
    $texto = abs($valor - round($valor)) < 0.005
        ? number_format($valor, 0, ',', '.')
        : number_format($valor, 2, ',', '.');

    return $texto . ' %';
}

/** alto (>= 90) | medio (50–89) | bajo (< 50): mismos límites que PHPUnit. */
function nivel(float $valor): string
{
    return $valor >= 90 ? 'alto' : ($valor >= 50 ? 'medio' : 'bajo');
}

function barra(float $valor, int $umbral): string
{
    $ancho = max(0, min(100, $valor));

    return '<div class="barra" role="img" aria-label="' . e(formatoPct($valor)) . '">'
        . '<span class="relleno ' . nivel($valor) . '" style="width:' . number_format($ancho, 2, '.', '') . '%"></span>'
        . '<span class="marca-umbral" style="left:' . $umbral . '%" title="Mínimo exigido: ' . $umbral . ' %"></span>'
        . '</div>';
}

function formatoTiempo(float $segundos): string
{
    return number_format($segundos, 2, ',', '.') . ' s';
}

// ------------------------------------------------------------------ HTML

$pctLineas = porcentaje($total['lineasCubiertas'], $total['lineas']);
$pctMetodos = porcentaje($total['metodosCubiertos'], $total['metodos']);
$cumple = $pctLineas >= $umbral;
$todoVerde = $pruebas['total'] > 0 && $pruebas['fallidas'] === 0;
$fecha = date('d/m/Y \a \l\a\s h:i a', (int)$clover['generated']);

ob_start();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cobertura MiGarita</title>
<style>
:root {
    --fondo: #f6f7f9; --superficie: #fff; --borde: #e3e6ea; --texto: #1f2933; --suave: #5f6b7a;
    --acento: #2563eb; --verde: #1f9d55; --verde-claro: #e7f6ee; --ambar: #b7791f; --ambar-claro: #fdf5e3;
    --rojo: #c53030; --rojo-claro: #fdecec; --pista: #e5e7eb; --sombra: 0 1px 2px rgba(16,24,40,.05);
}
@media (prefers-color-scheme: dark) {
    :root {
        --fondo: #0f141a; --superficie: #171e26; --borde: #2a333d; --texto: #e6eaef; --suave: #9aa6b2;
        --acento: #6ea2ff; --verde: #3ccf7f; --verde-claro: #12301f; --ambar: #e3b341; --ambar-claro: #33290f;
        --rojo: #f07171; --rojo-claro: #3a1717; --pista: #2a333d; --sombra: none;
    }
}
* { box-sizing: border-box; }
body { margin: 0; background: var(--fondo); color: var(--texto);
       font: 15px/1.55 "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif; }
.contenedor { max-width: 1120px; margin: 0 auto; padding: 32px 20px 48px; }
header h1 { margin: 0; font-size: 28px; letter-spacing: -.01em; }
header p { margin: 4px 0 0; color: var(--suave); }
h2 { font-size: 20px; margin: 40px 0 6px; }
.sub { color: var(--suave); margin: 0 0 16px; }
a { color: var(--acento); }

.tarjetas { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 24px; }
.tarjeta { background: var(--superficie); border: 1px solid var(--borde); border-radius: 14px; padding: 18px 20px; box-shadow: var(--sombra); }
.tarjeta .etiqueta { color: var(--suave); font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.tarjeta .valor { font-size: 34px; font-weight: 700; margin: 6px 0 2px; font-variant-numeric: tabular-nums; }
.tarjeta .detalle { color: var(--suave); font-size: 14px; }
.tarjeta.principal { border-width: 2px; }
.tarjeta.ok { border-color: var(--verde); }
.tarjeta.mal { border-color: var(--rojo); }
.valor.alto { color: var(--verde); } .valor.medio { color: var(--ambar); } .valor.bajo { color: var(--rojo); }

.insignia { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 999px; font-weight: 700; font-size: 14px; }
.insignia.ok { background: var(--verde-claro); color: var(--verde); }
.insignia.mal { background: var(--rojo-claro); color: var(--rojo); }

.barra { position: relative; height: 10px; background: var(--pista); border-radius: 999px; min-width: 120px; }
.relleno { position: absolute; inset: 0 auto 0 0; border-radius: 999px; }
.relleno.alto { background: var(--verde); } .relleno.medio { background: var(--ambar); } .relleno.bajo { background: var(--rojo); }
.marca-umbral { position: absolute; top: -4px; bottom: -4px; width: 2px; background: var(--texto); opacity: .55; }

.panel { background: var(--superficie); border: 1px solid var(--borde); border-radius: 14px; box-shadow: var(--sombra); overflow: hidden; margin-bottom: 18px; }
.panel-cabecera { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px 16px; padding: 14px 20px; border-bottom: 1px solid var(--borde); }
.panel-cabecera h3 { margin: 0; font-size: 17px; }
.panel-cabecera p { margin: 2px 0 0; color: var(--suave); font-size: 14px; }
.panel-cabecera .pct { font-size: 20px; font-weight: 700; font-variant-numeric: tabular-nums; }

table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.tabla-archivos th:nth-child(1) { width: 44%; }
.tabla-archivos th:nth-child(2) { width: 24%; }
.tabla-archivos th:nth-child(3) { width: 10%; }
.tabla-archivos th:nth-child(4) { width: 11%; }
.tabla-archivos th:nth-child(5) { width: 11%; }
.tabla-niveles th:nth-child(1) { width: 52%; }
th { text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: var(--suave); font-weight: 700; padding: 10px 20px; background: color-mix(in srgb, var(--pista) 35%, transparent); }
td { padding: 12px 20px; border-top: 1px solid var(--borde); vertical-align: middle; }
td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
td .archivo { font-weight: 600; }
td .que-hace { color: var(--suave); font-size: 13.5px; }
td .faltan { color: var(--rojo); font-size: 13px; margin-top: 2px; }
.pct-fila { font-weight: 700; }
.pct-fila.alto { color: var(--verde); } .pct-fila.medio { color: var(--ambar); } .pct-fila.bajo { color: var(--rojo); }
.estado-ok { color: var(--verde); font-weight: 700; } .estado-mal { color: var(--rojo); font-weight: 700; }

.guia { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px; }
.guia .tarjeta h4 { margin: 0 0 6px; font-size: 15px; }
.guia .tarjeta p { margin: 0; color: var(--suave); font-size: 14px; }
.leyenda { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.chip { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; padding: 3px 10px; border-radius: 999px; background: var(--fondo); border: 1px solid var(--borde); }
.punto { width: 10px; height: 10px; border-radius: 50%; }
.punto.alto { background: var(--verde); } .punto.medio { background: var(--ambar); } .punto.bajo { background: var(--rojo); }
.linea-umbral { width: 2px; height: 14px; background: var(--texto); opacity: .55; }

.boton { display: inline-block; margin-top: 8px; padding: 10px 16px; border-radius: 10px; background: var(--acento); color: #fff; text-decoration: none; font-weight: 600; }
footer { margin-top: 40px; color: var(--suave); font-size: 13px; }

@media (max-width: 720px) {
    .contenedor { padding: 20px 16px 40px; }
    .ocultar-movil { display: none; }
    td, th { padding: 10px 10px; }
    td.num { font-size: 13px; }
    .barra { min-width: 0; }
    .tabla-archivos th:nth-child(1) { width: 50%; }
    .tabla-archivos th:nth-child(2) { width: 26%; }
    .tabla-archivos th:nth-child(3) { width: 24%; }
    .tabla-niveles th:nth-child(1) { width: 46%; }
    .tabla-niveles th:nth-child(2) { width: 22%; }
    .tabla-niveles th:nth-child(5) { width: 32%; }
}
</style>
</head>
<body>
<div class="contenedor">

<header>
    <h1>Reporte de cobertura de pruebas · MiGarita</h1>
    <p>Generado el <?= e($fecha) ?> · PHP <?= e(PHP_VERSION) ?> · PHPUnit + Xdebug</p>
</header>

<section class="tarjetas" aria-label="Resumen">
    <div class="tarjeta principal <?= $cumple ? 'ok' : 'mal' ?>">
        <div class="etiqueta">Cobertura de líneas</div>
        <div class="valor <?= nivel($pctLineas) ?>"><?= e(formatoPct($pctLineas)) ?></div>
        <div class="detalle"><?= $total['lineasCubiertas'] ?> de <?= $total['lineas'] ?> líneas de código ejecutadas por las pruebas</div>
    </div>
    <div class="tarjeta">
        <div class="etiqueta">Mínimo exigido</div>
        <div class="valor"><?= $umbral ?> %</div>
        <div class="detalle">
            <?php if ($cumple): ?>
                <span class="insignia ok">✓ Cumple</span>
            <?php else: ?>
                <span class="insignia mal">✗ No cumple</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="tarjeta">
        <div class="etiqueta">Pruebas PHPUnit</div>
        <div class="valor <?= $todoVerde ? 'alto' : 'bajo' ?>"><?= $pruebas['total'] ?></div>
        <div class="detalle">
            <?= $todoVerde ? 'Todas pasaron' : e($pruebas['fallidas'] . ' fallaron') ?>
            · <?= $pruebas['aserciones'] ?> verificaciones · <?= e(formatoTiempo($pruebas['tiempo'])) ?>
        </div>
    </div>
    <div class="tarjeta">
        <div class="etiqueta">Métodos probados</div>
        <div class="valor <?= nivel($pctMetodos) ?>"><?= e(formatoPct($pctMetodos)) ?></div>
        <div class="detalle"><?= $total['metodosCubiertos'] ?> de <?= $total['metodos'] ?> métodos con todas sus líneas ejecutadas</div>
    </div>
</section>

<?php if ($niveles): ?>
<h2>Pruebas por nivel</h2>
<p class="sub">La suite sigue la pirámide de pruebas: muchas unitarias rápidas en la base y menos pruebas grandes arriba.</p>
<div class="panel">
    <table class="tabla-niveles">
        <thead>
            <tr>
                <th>Nivel</th>
                <th class="num">Pruebas</th>
                <th class="num ocultar-movil">Verificaciones</th>
                <th class="num ocultar-movil">Tiempo</th>
                <th class="num">Estado</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($niveles as $n): ?>
            <tr>
                <td><div class="archivo"><?= e($n['nombre']) ?></div><div class="que-hace"><?= e($n['detalle']) ?></div></td>
                <td class="num"><?= $n['pruebas'] ?></td>
                <td class="num ocultar-movil"><?= $n['aserciones'] ?></td>
                <td class="num ocultar-movil"><?= e(formatoTiempo($n['tiempo'])) ?></td>
                <td class="num"><?= $n['fallidas'] === 0
                    ? '<span class="estado-ok">✓ Pasaron</span>'
                    : '<span class="estado-mal">✗ ' . $n['fallidas'] . ' fallaron</span>' ?></td>
            </tr>
        <?php endforeach; ?>
            <tr>
                <td><div class="archivo">E2E en navegador (Playwright)</div><div class="que-hace">Flujo real en Chromium. Se ejecuta aparte con <code>npm run test:e2e</code>.</div></td>
                <td class="num">3</td>
                <td class="num ocultar-movil">—</td>
                <td class="num ocultar-movil">—</td>
                <td class="num" style="color:var(--suave)">aparte</td>
            </tr>
        </tbody>
    </table>
</div>
<?php endif; ?>

<h2>Cobertura por capa del proyecto</h2>
<p class="sub">Cada fila es un archivo de la lógica de la app. La raya vertical de cada barra marca el mínimo del <?= $umbral ?> %.</p>

<?php foreach ($capas as $prefijo => [$tituloCapa, $explicacionCapa]):
    $archivos = $archivosPorCapa[$prefijo] ?? [];
    if (!$archivos) { continue; }
    $lc = array_sum(array_column($archivos, 'lineasCubiertas'));
    $lt = array_sum(array_column($archivos, 'lineas'));
    $pctCapa = porcentaje($lc, $lt);
?>
<section class="panel">
    <div class="panel-cabecera">
        <div>
            <h3><?= e($tituloCapa) ?> <span style="color:var(--suave);font-weight:400;font-size:14px">· <?= e($prefijo) ?>/</span></h3>
            <p><?= e($explicacionCapa) ?></p>
        </div>
        <div class="pct pct-fila <?= nivel($pctCapa) ?>"><?= e(formatoPct($pctCapa)) ?></div>
    </div>
    <table class="tabla-archivos">
        <thead>
            <tr>
                <th>Archivo</th>
                <th>Líneas cubiertas</th>
                <th class="num">%</th>
                <th class="num ocultar-movil">Líneas</th>
                <th class="num ocultar-movil">Métodos</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($archivos as $a):
            $pctA = porcentaje($a['lineasCubiertas'], $a['lineas']);
        ?>
            <tr>
                <td>
                    <a class="archivo" href="html/<?= e($a['relativa']) ?>.html"><?= e($a['nombre']) ?></a>
                    <div class="que-hace"><?= e($descripciones[$a['nombre']] ?? '') ?></div>
                    <?php if ($a['sinCubrir']): ?>
                        <div class="faltan">Líneas sin probar: <?= e(implode(', ', $a['sinCubrir'])) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= barra($pctA, $umbral) ?></td>
                <td class="num"><span class="pct-fila <?= nivel($pctA) ?>"><?= e(formatoPct($pctA)) ?></span></td>
                <td class="num ocultar-movil"><?= $a['lineasCubiertas'] ?> / <?= $a['lineas'] ?></td>
                <td class="num ocultar-movil"><?= $a['metodosCubiertos'] ?> / <?= $a['metodos'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php endforeach; ?>

<h2>Cómo leer este reporte</h2>
<div class="guia">
    <div class="tarjeta">
        <h4>¿Qué es la cobertura?</h4>
        <p>El porcentaje de líneas de código que se ejecutaron al correr las pruebas. Una línea que nunca se ejecuta es una línea que nadie está vigilando.</p>
    </div>
    <div class="tarjeta">
        <h4>Colores</h4>
        <p>Usan los mismos límites que PHPUnit.</p>
        <div class="leyenda">
            <span class="chip"><span class="punto alto"></span>Alta · 90 % o más</span>
            <span class="chip"><span class="punto medio"></span>Media · 50 a 89 %</span>
            <span class="chip"><span class="punto bajo"></span>Baja · menos de 50 %</span>
            <span class="chip"><span class="linea-umbral"></span>Mínimo exigido (<?= $umbral ?> %)</span>
        </div>
    </div>
    <div class="tarjeta">
        <h4>Ver el código línea por línea</h4>
        <p>Haz clic en el nombre de un archivo. En verde las líneas ejecutadas por alguna prueba y en rojo las que no.</p>
    </div>
    <div class="tarjeta">
        <h4>¿100 % significa que no hay errores?</h4>
        <p>No. Significa que cada línea se ejecutó. Lo que demuestra que las pruebas vigilan las reglas de negocio es la <strong>prueba de fuego</strong>: romper una regla y ver la suite en rojo.</p>
    </div>
    <div class="tarjeta">
        <h4>¿Qué no se mide?</h4>
        <p>Los controladores y las vistas se ejecutan en otro proceso (<code>php -S</code>), por eso no entran en el porcentaje. Los recorren las pruebas funcionales y las E2E.</p>
    </div>
</div>

<p style="margin-top:28px">
    <a class="boton" href="html/index.html">Abrir el reporte detallado de PHPUnit →</a>
</p>

<footer>
    Cobertura medida sobre <code>app/</code> y <code>model/</code>. Se genera con <code>composer test:coverage</code>.
</footer>

</div>
</body>
</html>
<?php
$html = ob_get_clean();
file_put_contents($dirCobertura . '/resumen.html', $html);

// ------------------------------------------------------------------ Barra de regreso en el reporte detallado

$dirHtml = $dirCobertura . '/html';
if (is_dir($dirHtml)) {
    $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dirHtml, FilesystemIterator::SKIP_DOTS));
    foreach ($iterador as $archivo) {
        if ($archivo->getExtension() !== 'html') {
            continue;
        }

        $contenido = file_get_contents($archivo->getPathname());
        if (strpos($contenido, 'class="mg-barra"') !== false) {
            continue;
        }

        $profundidad = substr_count(str_replace('\\', '/', substr($archivo->getPathname(), strlen($dirHtml) + 1)), '/');
        $enlace = str_repeat('../', $profundidad + 1) . 'resumen.html';
        $barra = '<div class="mg-barra"><a href="' . $enlace . '">← Volver al resumen en español</a></div>';

        $contenido = preg_replace('/<body([^>]*)>/', '<body$1>' . $barra, $contenido, 1);
        file_put_contents($archivo->getPathname(), $contenido);
    }
}

echo "Resumen de cobertura: coverage/resumen.html\n";
