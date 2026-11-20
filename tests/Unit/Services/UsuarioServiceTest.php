<?php

namespace Tests\Unit\Services;

use AuthService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UserModel;
use UsuarioService;

/**
 * Pruebas UNITARIAS con MOCKS de las reglas de permisos por rol.
 */
final class UsuarioServiceTest extends TestCase
{
    private UserModel&MockObject $usuarios;
    private UsuarioService $servicio;

    private const NUEVO = [
        'cedula' => '555', 'nombre' => 'Sofía', 'apellido' => 'Ríos', 'correo' => 'sofia@migarita.co',
        'usuario' => 'sofia', 'password' => 'clave123', 'rol' => 'usuario',
    ];

    protected function setUp(): void
    {
        $this->usuarios = $this->createMock(UserModel::class);
        $this->servicio = new UsuarioService($this->usuarios);
    }

    #[Test]
    public function un_usuario_normal_no_puede_crear_usuarios(): void
    {
        $this->usuarios->expects($this->never())->method('insert');

        $this->assertSame('sin_permisos', $this->servicio->crear(self::NUEVO, 'usuario'));
    }

    #[Test]
    public function sin_sesion_tampoco_puede_crear_usuarios(): void
    {
        $this->assertSame('sin_permisos', $this->servicio->crear(self::NUEVO, null));
    }

    #[Test]
    public function un_superusuario_crea_el_usuario_con_la_contrasena_hasheada(): void
    {
        // Arrange: no hay duplicados
        $this->usuarios->method('getByCedula')->willReturn(false);
        $this->usuarios->method('getByCorreo')->willReturn(false);
        $this->usuarios->method('getByUsuario')->willReturn(false);

        // Assert (mock): NUNCA se guarda la contraseña en texto plano
        $this->usuarios->expects($this->once())
            ->method('insert')
            ->with($this->callback(function (array $d) {
                $this->assertTrue(password_verify('clave123', $d['password_hash']), 'Se guarda con bcrypt.');
                $this->assertFalse(AuthService::esHashAntiguo($d['password_hash']));
                $this->assertArrayNotHasKey('password', $d);
                $this->assertSame('usuario', $d['rol']);
                return true;
            }));

        // Act + Assert
        $this->assertNull($this->servicio->crear(self::NUEVO, 'superusuario'));
    }

    #[Test]
    public function crear_rechaza_un_nombre_de_usuario_repetido(): void
    {
        $this->usuarios->method('getByCedula')->willReturn(false);
        $this->usuarios->method('getByCorreo')->willReturn(false);
        $this->usuarios->method('getByUsuario')->with('sofia')->willReturn(['id' => 4]);
        $this->usuarios->expects($this->never())->method('insert');

        $this->assertSame('usuario_duplicado', $this->servicio->crear(self::NUEVO, 'superusuario'));
    }

    #[Test]
    public function un_usuario_normal_no_puede_editar_a_otro_usuario(): void
    {
        $this->usuarios->expects($this->never())->method('update');

        $this->assertSame('sin_permisos', $this->servicio->actualizar(5, self::NUEVO, 2, 'usuario'));
    }

    #[Test]
    public function editar_con_un_correo_invalido_no_modifica_nada(): void
    {
        // Arrange
        $this->usuarios->method('getByCedula')->willReturn(false);
        $this->usuarios->expects($this->never())->method('update');

        // Act
        $error = $this->servicio->actualizar(5, ['correo' => 'sin-arroba'] + self::NUEVO, 1, 'superusuario');

        // Assert
        $this->assertSame('correo_invalido', $error);
    }

    #[Test]
    public function un_usuario_normal_puede_editar_su_propio_perfil_sin_cambiar_su_rol(): void
    {
        // Arrange: intenta auto-ascenderse enviando rol=superusuario
        $this->usuarios->method('getByCedula')->willReturn(['id' => 2]);
        $this->usuarios->method('getByCorreo')->willReturn(false);
        $this->usuarios->method('getByUsuario')->willReturn(false);

        // Assert: se actualiza, pero SIN el campo rol
        $this->usuarios->expects($this->once())
            ->method('update')
            ->with(2, $this->callback(fn(array $d) => !array_key_exists('rol', $d)));

        // Act
        $error = $this->servicio->actualizar(2, ['rol' => 'superusuario'] + self::NUEVO, 2, 'usuario');

        $this->assertNull($error);
    }

    #[Test]
    public function editar_sin_contrasena_nueva_no_cambia_la_contrasena(): void
    {
        $this->usuarios->expects($this->once())
            ->method('update')
            ->with(5, $this->callback(fn(array $d) => !array_key_exists('password_hash', $d)));

        $this->servicio->actualizar(5, ['password' => ''] + self::NUEVO, 1, 'superusuario');
    }

    #[Test]
    public function un_superusuario_no_puede_quitarse_su_propio_rol(): void
    {
        // Arrange: el superusuario 1 se edita a sí mismo y pone rol "usuario"
        $this->usuarios->method('getById')->with(1)->willReturn(['id' => 1, 'rol' => 'superusuario']);
        $this->usuarios->expects($this->never())->method('update');

        // Act
        $error = $this->servicio->actualizar(1, ['rol' => 'usuario'] + self::NUEVO, 1, 'superusuario');

        // Assert
        $this->assertSame('no_auto_cambiar_rol', $error);
    }

    #[Test]
    public function un_superusuario_puede_cambiar_el_rol_de_otro_usuario(): void
    {
        $this->usuarios->expects($this->once())
            ->method('update')
            ->with(5, $this->callback(fn(array $d) => $d['rol'] === 'superusuario'));

        $error = $this->servicio->actualizar(5, ['rol' => 'superusuario'] + self::NUEVO, 1, 'superusuario');

        $this->assertNull($error);
    }

    #[Test]
    public function un_rol_inexistente_se_rechaza_al_editar(): void
    {
        $this->usuarios->expects($this->never())->method('update');

        $error = $this->servicio->actualizar(5, ['rol' => 'dios'] + self::NUEVO, 1, 'superusuario');

        $this->assertSame('rol_invalido', $error);
    }

    #[Test]
    public function solo_el_superusuario_puede_eliminar(): void
    {
        $this->usuarios->expects($this->never())->method('delete');

        $this->assertSame(['error' => 'sin_permisos'], $this->servicio->eliminar(5, 2, 'usuario'));
    }

    #[Test]
    public function nadie_puede_eliminarse_a_si_mismo(): void
    {
        $this->usuarios->expects($this->never())->method('delete');

        $this->assertSame(['error' => 'auto_eliminar'], $this->servicio->eliminar(1, 1, 'superusuario'));
    }

    #[Test]
    public function eliminar_sin_id_devuelve_error(): void
    {
        $this->assertSame(['error' => 'id_no_recibido'], $this->servicio->eliminar(null, 1, 'superusuario'));
    }

    #[Test]
    public function el_superusuario_elimina_a_otro_usuario(): void
    {
        $this->usuarios->expects($this->once())->method('delete')->with(5);

        $this->assertSame(['ok' => true], $this->servicio->eliminar(5, 1, 'superusuario'));
    }
}
