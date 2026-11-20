<?php

namespace Tests\Integration;

use AuthService;
use BitacoraModel;
use ClienteModel;
use ClienteService;
use EmailService;
use MovimientoService;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\BaseDatosPrueba;
use UserModel;
use UsuarioService;

/**
 * Pruebas de INTEGRACIÓN de casos de uso completos: servicio + modelos REALES
 * + BD de prueba. Solo el correo (servicio externo) se reemplaza por un mock.
 */
final class CasosDeUsoTest extends TestCase
{
    private PDO $pdo;
    private ClienteModel $clientes;
    private UserModel $usuarios;

    protected function setUp(): void
    {
        $this->pdo = BaseDatosPrueba::enMemoria();
        BaseDatosPrueba::sembrarUsuarios($this->pdo);
        $this->clientes = new ClienteModel($this->pdo);
        $this->usuarios = new UserModel($this->pdo);
    }

    private function movimientos(): MovimientoService
    {
        return new MovimientoService($this->clientes, $this->usuarios, $this->createStub(EmailService::class));
    }

    #[Test]
    public function flujo_completo_registrar_cliente_fiar_abonar_y_saldar(): void
    {
        // Arrange
        $servicioClientes = new ClienteService($this->clientes);
        $servicioMov = $this->movimientos();

        // Act 1: registrar cliente
        $error = $servicioClientes->crear([
            'cedula' => '1065999888', 'nombre' => 'Valentina', 'apellido' => 'Pérez',
            'telefono' => '3015556677', 'correo' => 'vale@correo.test', 'id_tipo_cliente' => '1',
        ]);
        $this->assertNull($error);
        $id = (int)$this->clientes->getByCedula('1065999888')['id'];

        // Act 2: fiar 12.000 y 3.000
        $this->assertNull($servicioMov->registrar($id, 2, ['tipo' => 'fiado', 'monto' => '12000', 'descripcion' => 'Almuerzo semana']));
        $this->assertNull($servicioMov->registrar($id, 2, ['tipo' => 'fiado', 'monto' => '3000', 'descripcion' => 'Jugo']));
        $this->assertEquals(15000, $this->clientes->getSaldo($id));

        // Act 3: intentar abonar más de lo que debe => rechazado, el saldo no cambia
        $this->assertSame('abono_mayor', $servicioMov->registrar($id, 2, [
            'tipo' => 'abono', 'monto' => '15001', 'descripcion' => 'Pago', 'metodo_pago' => '1',
        ]));
        $this->assertEquals(15000, $this->clientes->getSaldo($id));

        // Act 4: no se puede eliminar mientras deba
        $this->assertSame('saldo', $servicioClientes->eliminar($id)['error']);

        // Act 5: abonar todo por transferencia
        $this->assertNull($servicioMov->registrar($id, 2, [
            'tipo' => 'abono', 'monto' => '15000', 'descripcion' => 'Pago total', 'metodo_pago' => '2',
        ]));

        // Assert final: saldo en cero y ahora sí se puede eliminar
        $this->assertEquals(0, $this->clientes->getSaldo($id));
        $this->assertSame(['ok' => true], $servicioClientes->eliminar($id));
        $this->assertFalse($this->clientes->getById($id));
    }

    #[Test]
    public function un_movimiento_rechazado_no_deja_rastro_en_la_bd(): void
    {
        $id = BaseDatosPrueba::crearCliente($this->pdo);

        $error = $this->movimientos()->registrar($id, 2, ['tipo' => 'abono', 'monto' => '500', 'descripcion' => 'X', 'metodo_pago' => '1']);

        $this->assertSame('abono_mayor', $error);
        $this->assertSame(0, (int)$this->pdo->query('SELECT COUNT(*) FROM deudas')->fetchColumn());
    }

    #[Test]
    public function editar_un_cliente_con_la_cedula_de_otro_no_modifica_nada(): void
    {
        $servicio = new ClienteService($this->clientes);
        $laura = BaseDatosPrueba::crearCliente($this->pdo, ['cedula' => '111', 'nombre' => 'Laura']);
        BaseDatosPrueba::crearCliente($this->pdo, ['cedula' => '222', 'nombre' => 'Pedro']);

        $error = $servicio->actualizar($laura, [
            'cedula' => '222', 'nombre' => 'Laura', 'apellido' => 'Gómez',
            'telefono' => '300', 'correo' => 'l@c.co', 'id_tipo_cliente' => '1',
        ]);

        $this->assertSame('cedula_duplicada', $error);
        $this->assertSame('111', $this->clientes->getById($laura)['cedula']);
    }

    #[Test]
    public function un_superusuario_crea_un_usuario_que_luego_puede_iniciar_sesion(): void
    {
        // Arrange
        $servicio = new UsuarioService($this->usuarios);
        $auth = new AuthService($this->usuarios, new BitacoraModel($this->pdo));

        // Act
        $error = $servicio->crear([
            'cedula' => '4000', 'nombre' => 'Sofía', 'apellido' => 'Ríos', 'correo' => 'sofia@migarita.test',
            'usuario' => 'sofia', 'password' => 'clave123', 'rol' => 'usuario',
        ], 'superusuario');
        $login = $auth->autenticar('sofia', 'clave123', '127.0.0.1', 'Mozilla/5.0 (Linux; Android 14)');

        // Assert
        $this->assertNull($error);
        $this->assertTrue($login['ok']);
        $registro = $this->pdo->query('SELECT * FROM bitacora_login ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Android', $registro['sistema_operativo']);
        $this->assertEquals(1, $registro['exito']);
    }

    #[Test]
    public function un_usuario_no_puede_registrarse_con_el_correo_de_otro(): void
    {
        $servicio = new UsuarioService($this->usuarios);

        $error = $servicio->crear([
            'cedula' => '4000', 'nombre' => 'Sofía', 'apellido' => 'Ríos', 'correo' => 'cajero@migarita.test',
            'usuario' => 'sofia', 'password' => 'clave123', 'rol' => 'usuario',
        ], 'superusuario');

        $this->assertSame('correo_duplicado', $error);
        $this->assertCount(2, $this->usuarios->getAll());
    }

    #[Test]
    public function el_usuario_actualiza_su_contrasena_y_la_nueva_funciona(): void
    {
        $servicio = new UsuarioService($this->usuarios);
        $auth = new AuthService($this->usuarios, new BitacoraModel($this->pdo));

        $error = $servicio->actualizar(2, [
            'cedula' => '2000', 'nombre' => 'Carlos', 'apellido' => 'Cajero', 'correo' => 'cajero@migarita.test',
            'usuario' => 'cajero', 'password' => 'nuevaClave9',
        ], 2, 'usuario');

        $this->assertNull($error);
        $this->assertTrue($auth->autenticar('cajero', 'nuevaClave9', '::1', '')['ok']);
        $this->assertFalse($auth->autenticar('cajero', BaseDatosPrueba::PASSWORD_CAJERO, '::1', '')['ok']);
    }

    #[Test]
    public function la_conexion_inyectada_es_la_que_usa_el_modelo(): void
    {
        $this->assertSame($this->pdo, $this->clientes->getConexion());
    }
}
