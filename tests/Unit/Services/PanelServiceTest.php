<?php

namespace Tests\Unit\Services;

use ClienteModel;
use DateTimeImmutable;
use PanelService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas UNITARIAS con MOCKS del panel de inicio.
 * El ClienteModel se simula: no hay base de datos.
 */
final class PanelServiceTest extends TestCase
{
    private ClienteModel&MockObject $clientes;
    private PanelService $servicio;

    protected function setUp(): void
    {
        $this->clientes = $this->createMock(ClienteModel::class);
        $this->servicio = new PanelService($this->clientes);
    }

    private static function cliente(int $id, float $saldo, string $nombre = 'Cliente'): array
    {
        return ['id' => $id, 'nombre' => $nombre, 'apellido' => 'X', 'tipo_cliente' => 'estudiante', 'saldo' => (string)$saldo];
    }

    #[Test]
    public function el_resumen_reune_los_indicadores_de_la_cartera(): void
    {
        // Arrange
        $this->clientes->method('getAllConSaldo')->willReturn([
            self::cliente(1, 7000, 'Ana'),
            self::cliente(2, 0, 'Beto'),
            self::cliente(3, 3000, 'Caro'),
        ]);
        $this->clientes->method('getUltimosMovimientos')->willReturn([['id' => 9]]);

        // Assert (mock): los totales de "hoy" se piden para la fecha inyectada
        $this->clientes->expects($this->once())
            ->method('getTotalesMovimientos')
            ->with('2026-11-20', '2026-11-20')
            ->willReturn(['fiado' => '5000.00', 'abonado' => '2000.00', 'cantidad' => '3']);

        // Act
        $r = $this->servicio->resumen(new DateTimeImmutable('2026-11-20'));

        // Assert
        $this->assertSame(10000.0, $r['totalAdeudado']);
        $this->assertSame(3, $r['clientes']);
        $this->assertSame(2, $r['clientesConDeuda']);
        $this->assertSame(['fiado' => 5000.0, 'abonado' => 2000.0, 'cantidad' => 3], $r['hoy']);
        $this->assertSame(['Ana', 'Caro'], array_column($r['deudores'], 'nombre'));
        $this->assertSame([['id' => 9]], $r['ultimos']);
    }

    #[Test]
    public function pide_solo_los_ultimos_8_movimientos(): void
    {
        $this->clientes->method('getAllConSaldo')->willReturn([]);
        $this->clientes->method('getTotalesMovimientos')->willReturn(['fiado' => 0, 'abonado' => 0, 'cantidad' => 0]);
        $this->clientes->expects($this->once())->method('getUltimosMovimientos')->with(8)->willReturn([]);

        $r = $this->servicio->resumen();

        $this->assertSame(0.0, $r['totalAdeudado']);
        $this->assertSame([], $r['deudores']);
    }

    #[Test]
    public function un_saldo_a_favor_no_resta_del_total_adeudado(): void
    {
        // Caso borde: un cliente pagó de más (saldo negativo)
        $clientes = [self::cliente(1, 5000), self::cliente(2, -1500)];

        $this->assertSame(5000.0, PanelService::totalAdeudado($clientes));
        $this->assertCount(1, PanelService::conDeuda($clientes));
    }

    #[Test]
    public function un_cliente_con_saldo_cero_no_cuenta_como_deudor(): void
    {
        $this->assertSame([], PanelService::conDeuda([self::cliente(1, 0)]));
    }

    #[Test]
    public function los_mayores_deudores_salen_de_mayor_a_menor_y_limitados(): void
    {
        // Arrange
        $clientes = [
            self::cliente(1, 1000, 'Uno'),
            self::cliente(2, 9000, 'Nueve'),
            self::cliente(3, 4500, 'Cuatro'),
            self::cliente(4, 0, 'Cero'),
        ];

        // Act
        $top = PanelService::mayoresDeudores($clientes, 2);

        // Assert: el mayor tiene la barra al 100 % y el resto es proporcional
        $this->assertSame(['Nueve', 'Cuatro'], array_column($top, 'nombre'));
        $this->assertSame([100.0, 50.0], array_column($top, 'porcentaje'));
    }

    #[Test]
    public function sin_deudores_el_ranking_esta_vacio(): void
    {
        $this->assertSame([], PanelService::mayoresDeudores([self::cliente(1, 0)], 5));
        $this->assertSame([], PanelService::mayoresDeudores([self::cliente(1, 500)], 0));
    }
}
