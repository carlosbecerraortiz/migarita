<?php
/**
 * Lógica de los informes PDF de un cliente (extraída de ClienteController::generarInforme).
 * Clase PURA: la fecha de "hoy" se puede inyectar para que las pruebas sean repetibles.
 */
class InformeService
{
    public const DIAS_ULTIMO_MES = 30;

    /**
     * Calcula el rango de fechas y el título del informe.
     *
     * @param string      $tipo  "completo" | "mes" | "fechas" (cualquier otro => completo)
     * @return array{desde: ?string, hasta: ?string, titulo: string}
     */
    public static function rango(string $tipo, ?string $desde = null, ?string $hasta = null, ?DateTimeImmutable $hoy = null): array
    {
        $hoy = $hoy ?? new DateTimeImmutable('today');

        switch ($tipo) {
            case 'mes':
                return [
                    'desde'  => $hoy->modify('-' . self::DIAS_ULTIMO_MES . ' days')->format('Y-m-d'),
                    'hasta'  => $hoy->format('Y-m-d'),
                    'titulo' => 'Informe del Último Mes',
                ];

            case 'fechas':
                $desde = $desde ?: $hoy->format('Y-m-d');
                $hasta = $hasta ?: $hoy->format('Y-m-d');

                return [
                    'desde'  => $desde,
                    'hasta'  => $hasta,
                    'titulo' => 'Informe del ' . date('d/m/Y', strtotime($desde)) . ' al ' . date('d/m/Y', strtotime($hasta)),
                ];

            case 'completo':
            default:
                return ['desde' => null, 'hasta' => null, 'titulo' => 'Informe Completo'];
        }
    }

    /** Nombre del archivo PDF que se descarga. */
    public static function nombreArchivo(array $cliente, ?DateTimeImmutable $hoy = null): string
    {
        $hoy = $hoy ?? new DateTimeImmutable('today');

        return 'Informe_' . $cliente['nombre'] . '_' . $cliente['apellido'] . '_' . $hoy->format('Y-m-d') . '.pdf';
    }

    /** Suma absoluta de una lista de movimientos (total de fiados o de abonos del informe). */
    public static function total(array $movimientos): float
    {
        $total = 0.0;
        foreach ($movimientos as $m) {
            $total += abs((float)$m['monto']);
        }

        return $total;
    }
}
