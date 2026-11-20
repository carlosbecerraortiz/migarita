<?php
/**
 * Verifica que la cobertura de líneas sea >= al umbral (por defecto 60%).
 * PHPUnit no trae un "--fail-under" como pytest-cov, así que se lee el reporte
 * Clover que genera la suite y se falla (exit 1) si no se alcanza el umbral.
 *
 * Uso: php tests/verificar-cobertura.php [umbral]
 */

$umbral = (float)($argv[1] ?? 60);
$archivo = __DIR__ . '/../coverage/clover.xml';

if (!is_file($archivo)) {
    fwrite(STDERR, "No existe coverage/clover.xml. Ejecuta primero: composer test:coverage\n");
    exit(1);
}

$xml = simplexml_load_file($archivo);
$metricas = $xml->project->metrics;
$total = (int)$metricas['statements'];
$cubiertas = (int)$metricas['coveredstatements'];
$porcentaje = $total > 0 ? ($cubiertas / $total) * 100 : 0;

printf("\nCobertura de líneas: %.2f%% (%d de %d) — umbral mínimo: %.0f%%\n", $porcentaje, $cubiertas, $total, $umbral);

// Colores ANSI (fondo verde / rojo) para que se vea claro en la demo.
$verde = "\033[30;42m";
$rojo = "\033[37;41m";
$fin = "\033[0m";

if ($porcentaje < $umbral) {
    fwrite(STDERR, "{$rojo} FALLA: la cobertura está por debajo del umbral. {$fin}\n");
    exit(1);
}

echo "{$verde} OK: se cumple el umbral de cobertura. {$fin}\n";
