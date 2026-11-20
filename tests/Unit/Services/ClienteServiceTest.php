<?php

namespace Tests\Unit\Services;

use ClienteModel;
use ClienteService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas UNITARIAS con MOCKS del servicio de clientes.
 * El ClienteModel (acceso a BD) se reemplaza por un mock inyectado.
 */
final class ClienteServiceTest extends TestCase
{
    private ClienteModel&MockObject $clientes;
    private ClienteService $servicio;

    private const FORMULARIO = [
        'cedula' => '123456', 'nombre' => 'Laura', 'apellido' => 'Gómez',
        'telefono' => '3001234567', 'correo' => 'laura@correo.test', 'id_tipo_cliente' => '1',
    ];

    protected function setUp(): void
    {
        $this->clientes = $this->createMock(ClienteModel::class);
        $this->servicio = new ClienteService($this->clientes);
    }

    #[Test]
    public function crear_inserta_el_cliente_con_los_datos_limpios(): void
    {
        // Arrange
        $this->clientes->method('getByCedula')->willReturn(false);
        $formulario = ['nombre' => '  Laura  '] + self::FORMULARIO;

        // Assert (mock): se inserta con el nombre ya sin espacios
        $this->clientes->expects($this->once())
            ->method('insert')
            ->with($this->callback(fn(array $d) => $d['nombre'] === 'Laura' && $d['cedula'] === '123456'));

        // Act
        $error = $this->servicio->crear($formulario);

        // Assert
        $this->assertNull($error);
    }

    #[Test]
    public function crear_rechaza_una_cedula_que_ya_existe(): void
    {
        // Arrange: stub — la BD ya tiene esa cédula
        $this->clientes->method('getByCedula')->with('123456')->willReturn(['id' => 99]);
        $this->clientes->expects($this->never())->method('insert');

        // Act
        $error = $this->servicio->crear(self::FORMULARIO);

        // Assert
        $this->assertSame('cedula_duplicada', $error);
    }

    #[Test]
    public function actualizar_permite_conservar_la_misma_cedula_del_cliente(): void
    {
        // Arrange: la cédula existe, pero es del MISMO cliente (id 5)
        $this->clientes->method('getByCedula')->willReturn(['id' => 5]);
        $this->clientes->expects($this->once())->method('update')->with(5, $this->callback(fn($d) => is_array($d) && $d['cedula'] === '123456'));

        // Act
        $error = $this->servicio->actualizar(5, self::FORMULARIO);

        // Assert
        $this->assertNull($error);
    }

    #[Test]
    public function actualizar_rechaza_la_cedula_de_otro_cliente(): void
    {
        $this->clientes->method('getByCedula')->willReturn(['id' => 8]);
        $this->clientes->expects($this->never())->method('update');

        $this->assertSame('cedula_duplicada', $this->servicio->actualizar(5, self::FORMULARIO));
    }

    #[Test]
    public function actualizar_con_datos_invalidos_no_modifica_la_bd(): void
    {
        $this->clientes->expects($this->never())->method('update');

        $error = $this->servicio->actualizar(5, ['correo' => 'no-es-correo'] + self::FORMULARIO);

        $this->assertSame('correo_invalido', $error);
    }

    #[Test]
    public function no_se_puede_eliminar_un_cliente_con_saldo_pendiente(): void
    {
        // Arrange: el cliente existe y debe 15.000
        $this->clientes->method('getById')->willReturn(['id' => 3]);
        $this->clientes->method('getSaldo')->willReturn('15000.00');

        // Assert: delete NUNCA debe llamarse
        $this->clientes->expects($this->never())->method('delete');

        // Act
        $resultado = $this->servicio->eliminar(3);

        // Assert
        $this->assertFalse($resultado['ok']);
        $this->assertSame('saldo', $resultado['error']);
        $this->assertSame('15000.00', $resultado['saldo']);
    }

    #[Test]
    public function no_se_puede_eliminar_si_el_cliente_tiene_saldo_a_favor(): void
    {
        // Caso borde: saldo negativo (pagó de más). También es != 0.
        $this->clientes->method('getById')->willReturn(['id' => 3]);
        $this->clientes->method('getSaldo')->willReturn('-500');
        $this->clientes->expects($this->never())->method('delete');

        $this->assertSame('saldo', $this->servicio->eliminar(3)['error']);
    }

    #[Test]
    public function se_elimina_un_cliente_con_saldo_en_cero(): void
    {
        $this->clientes->method('getById')->willReturn(['id' => 3]);
        $this->clientes->method('getSaldo')->willReturn('0.00');
        $this->clientes->expects($this->once())->method('delete')->with(3);

        $this->assertSame(['ok' => true], $this->servicio->eliminar(3));
    }

    #[Test]
    public function eliminar_un_cliente_inexistente_devuelve_notfound(): void
    {
        $this->clientes->method('getById')->willReturn(false);
        $this->clientes->expects($this->never())->method('getSaldo');
        $this->clientes->expects($this->never())->method('delete');

        $this->assertSame(['ok' => false, 'error' => 'notfound'], $this->servicio->eliminar(404));
    }

    #[Test]
    public function el_total_general_suma_los_saldos_de_todos_los_clientes(): void
    {
        $clientes = [['saldo' => '10000.00'], ['saldo' => '2500.50'], ['saldo' => '-500'], ['saldo' => '0']];

        $this->assertSame(12000.5, ClienteService::totalGeneral($clientes));
    }

    #[Test]
    public function el_total_general_de_una_lista_vacia_es_cero(): void
    {
        $this->assertSame(0.0, ClienteService::totalGeneral([]));
    }
}
