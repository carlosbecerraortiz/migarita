<?php
require_once __DIR__ . '/../../model/ClienteModel.php';

/**
 * Datos del panel de inicio: indicadores de la cartera, clientes que más deben
 * y últimos movimientos. Recibe el modelo por inyección de dependencias y la
 * fecha de "hoy" como parámetro para que las pruebas sean repetibles.
 */
class PanelService
{
    public const LIMITE_DEUDORES = 5;
    public const LIMITE_MOVIMIENTOS = 8;

    private ClienteModel $clientes;

    public function __construct(ClienteModel $clientes)
    {
        $this->clientes = $clientes;
    }

    public function resumen(?DateTimeImmutable $hoy = null): array
    {
        $hoy = $hoy ?? new DateTimeImmutable('today');
        $fecha = $hoy->format('Y-m-d');

        $lista = $this->clientes->getAllConSaldo();
        $totalesHoy = $this->clientes->getTotalesMovimientos($fecha, $fecha);

        return [
            'totalAdeudado'    => self::totalAdeudado($lista),
            'clientes'         => count($lista),
            'clientesConDeuda' => count(self::conDeuda($lista)),
            'hoy'              => [
                'fiado'    => (float)$totalesHoy['fiado'],
                'abonado'  => (float)$totalesHoy['abonado'],
                'cantidad' => (int)$totalesHoy['cantidad'],
            ],
            'deudores'         => self::mayoresDeudores($lista, self::LIMITE_DEUDORES),
            'ultimos'          => $this->clientes->getUltimosMovimientos(self::LIMITE_MOVIMIENTOS),
        ];
    }

    /**
     * Total que le deben a la garita. Solo suma saldos positivos: un cliente con
     * saldo a favor (negativo) no reduce lo que deben los demás.
     */
    public static function totalAdeudado(array $clientes): float
    {
        $total = 0.0;
        foreach (self::conDeuda($clientes) as $c) {
            $total += (float)$c['saldo'];
        }

        return $total;
    }

    /** Clientes con saldo pendiente (mayor que 0). */
    public static function conDeuda(array $clientes): array
    {
        return array_values(array_filter($clientes, fn($c) => (float)($c['saldo'] ?? 0) > 0));
    }

    /**
     * Los $limite clientes que más deben, de mayor a menor, con el porcentaje de
     * su deuda respecto al mayor deudor (para dibujar la barra).
     */
    public static function mayoresDeudores(array $clientes, int $limite): array
    {
        $deudores = self::conDeuda($clientes);
        usort($deudores, fn($a, $b) => (float)$b['saldo'] <=> (float)$a['saldo']);
        $deudores = array_slice($deudores, 0, max(0, $limite));

        $mayor = $deudores ? (float)$deudores[0]['saldo'] : 0.0;
        foreach ($deudores as &$d) {
            $d['porcentaje'] = $mayor > 0 ? round((float)$d['saldo'] / $mayor * 100, 1) : 0.0;
        }

        return $deudores;
    }
}
