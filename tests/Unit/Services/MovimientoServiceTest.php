<?php

namespace Tests\Unit\Services;

use ClienteModel;
use EmailService;
use MovimientoService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use UserModel;

/**
 * Pruebas UNITARIAS con MOCKS del servicio de movimientos (fiados / abonos).
 *
 * Las tres dependencias se reemplazan por objetos simulados:
 *  - ClienteModel  -> STUB del saldo y SPY de insertMovimiento (no hay BD real).
 *  - UserModel     -> STUB del usuario que atiende.
 *  - EmailService  -> SPY / MOCK (no se envían correos reales).
 */
final class MovimientoServiceTest extends TestCase
{
    private ClienteModel&MockObject $clientes;
    private UserModel&MockObject $usuarios;
    private EmailService&MockObject $email;
    private MovimientoService $servicio;

    private const CLIENTE = ['id' => 7, 'nombre' => 'Laura', 'apellido' => 'Gómez', 'correo' => 'laura@correo.test'];
    private const CAJERO = ['id' => 2, 'nombre' => 'Carlos', 'apellido' => 'Cajero'];

    protected function setUp(): void
    {
        // Arrange común: dependencias simuladas inyectadas por el constructor
        $this->clientes = $this->createMock(ClienteModel::class);
        $this->usuarios = $this->createMock(UserModel::class);
        $this->email = $this->createMock(EmailService::class);

        $this->clientes->method('getById')->willReturn(self::CLIENTE);
        $this->usuarios->method('getById')->willReturn(self::CAJERO);

        $this->servicio = new MovimientoService($this->clientes, $this->usuarios, $this->email);
    }

    #[Test]
    public function un_fiado_se_guarda_con_monto_positivo(): void
    {
        // Arrange
        $this->clientes->method('getSaldo')->willReturn('0');

        // Assert (expectativa del mock): se guarda +5000 para el cliente 7, atendido por el usuario 2
        $this->clientes->expects($this->once())
            ->method('insertMovimiento')
            ->with(7, 2, 'Almuerzo', 5000.0, null);

        // Act
        $error = $this->servicio->registrar(7, 2, ['tipo' => 'fiado', 'monto' => '5000', 'descripcion' => 'Almuerzo']);

        // Assert
        $this->assertNull($error);
    }

    #[Test]
    public function un_abono_se_guarda_con_monto_negativo_y_su_metodo_de_pago(): void
    {
        // Arrange: el cliente debe 10.000
        $this->clientes->method('getSaldo')->willReturn('10000.00');

        $this->clientes->expects($this->once())
            ->method('insertMovimiento')
            ->with(7, 2, 'Pago parcial', -4000.0, '2');

        // Act
        $error = $this->servicio->registrar(7, 2, [
            'tipo' => 'abono', 'monto' => '4000', 'descripcion' => 'Pago parcial', 'metodo_pago' => '2',
        ]);

        // Assert
        $this->assertNull($error);
    }

    #[Test]
    public function un_abono_igual_al_saldo_deja_la_cuenta_en_cero_y_es_valido(): void
    {
        // Valor límite: pagar exactamente lo que se debe
        $this->clientes->method('getSaldo')->willReturn('8000');
        $this->clientes->expects($this->once())->method('insertMovimiento')
            ->with(7, 2, 'Pago total', -8000.0, '1');

        $error = $this->servicio->registrar(7, 2, [
            'tipo' => 'abono', 'monto' => '8000', 'descripcion' => 'Pago total', 'metodo_pago' => '1',
        ]);

        $this->assertNull($error);
    }

    #[Test]
    public function un_abono_mayor_que_el_saldo_se_rechaza_y_no_se_guarda_nada(): void
    {
        // Arrange: el cliente debe 8.000 y quiere abonar 8.001
        $this->clientes->method('getSaldo')->willReturn('8000');

        // Assert: NO se debe guardar ni enviar correo
        $this->clientes->expects($this->never())->method('insertMovimiento');
        $this->email->expects($this->never())->method('enviarNotificacionMovimiento');

        // Act
        $error = $this->servicio->registrar(7, 2, [
            'tipo' => 'abono', 'monto' => '8001', 'descripcion' => 'Pago', 'metodo_pago' => '1',
        ]);

        // Assert
        $this->assertSame('abono_mayor', $error);
    }

    #[Test]
    public function con_datos_invalidos_no_consulta_el_saldo_ni_guarda(): void
    {
        $this->clientes->expects($this->never())->method('getSaldo');
        $this->clientes->expects($this->never())->method('insertMovimiento');

        $error = $this->servicio->registrar(7, 2, ['tipo' => 'fiado', 'monto' => '0', 'descripcion' => 'X']);

        $this->assertSame('monto_invalido', $error);
    }

    #[Test]
    public function despues_de_guardar_notifica_al_cliente_por_correo_con_los_datos_correctos(): void
    {
        // Arrange
        $this->clientes->method('getSaldo')->willReturn('0');

        // Assert (SPY): el correo va al cliente, con el tipo, el monto con signo y quien atendió
        $this->email->expects($this->once())
            ->method('enviarNotificacionMovimiento')
            ->with(
                self::CLIENTE,
                $this->callback(function (array $mov) {
                    $this->assertSame('fiado', $mov['tipo']);
                    $this->assertSame(1500.0, $mov['monto']);
                    $this->assertSame('Gaseosa', $mov['descripcion']);
                    $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $mov['fecha']);
                    return true;
                }),
                'Carlos Cajero'
            )
            ->willReturn(true);

        // Act
        $this->servicio->registrar(7, 2, ['tipo' => 'fiado', 'monto' => '1500', 'descripcion' => 'Gaseosa']);
    }

    #[Test]
    public function si_el_correo_falla_el_movimiento_igual_queda_guardado(): void
    {
        // Arrange: el servicio de correo lanza una excepción (servidor SMTP caído)
        $this->clientes->method('getSaldo')->willReturn('0');
        $this->email->method('enviarNotificacionMovimiento')
            ->willThrowException(new RuntimeException('SMTP no disponible'));

        $this->clientes->expects($this->once())->method('insertMovimiento');

        // Silenciar el error_log esperado para no ensuciar la consola
        $logAnterior = ini_set('error_log', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null');

        // Act
        $error = $this->servicio->registrar(7, 2, ['tipo' => 'fiado', 'monto' => '1000', 'descripcion' => 'Pan']);

        ini_set('error_log', $logAnterior === false ? '' : $logAnterior);

        // Assert: la operación de negocio NO falla por culpa del correo
        $this->assertNull($error);
    }
}
