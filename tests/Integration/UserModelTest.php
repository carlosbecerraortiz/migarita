<?php

namespace Tests\Integration;

use BitacoraModel;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\BaseDatosPrueba;
use UserModel;

/**
 * Pruebas de INTEGRACIÓN de los modelos de usuarios y bitácora contra SQLite.
 */
final class UserModelTest extends TestCase
{
    private PDO $pdo;
    private UserModel $modelo;

    protected function setUp(): void
    {
        $this->pdo = BaseDatosPrueba::enMemoria();
        BaseDatosPrueba::sembrarUsuarios($this->pdo);
        $this->modelo = new UserModel($this->pdo);
    }

    private function nuevoUsuario(array $cambios = []): array
    {
        return array_merge([
            'cedula' => '3000', 'nombre' => 'Sofía', 'apellido' => 'Ríos', 'correo' => 'sofia@migarita.test',
            'usuario' => 'sofia', 'password_hash' => hash('sha256', 'clave123'),
        ], $cambios);
    }

    #[Test]
    public function get_all_lista_los_usuarios_en_orden_de_id(): void
    {
        $usuarios = $this->modelo->getAll();

        $this->assertSame(['admin', 'cajero'], array_column($usuarios, 'usuario'));
    }

    #[Test]
    public function insert_sin_rol_crea_un_usuario_normal_por_defecto(): void
    {
        $this->modelo->insert($this->nuevoUsuario());

        $sofia = $this->modelo->getByUsuario('sofia');
        $this->assertSame('usuario', $sofia['rol']);
        $this->assertSame(hash('sha256', 'clave123'), $sofia['password_hash']);
    }

    #[Test]
    public function se_puede_consultar_por_id_cedula_y_correo(): void
    {
        $this->assertSame('admin', $this->modelo->getById(1)['usuario']);
        $this->assertSame('cajero', $this->modelo->getByCedula('2000')['usuario']);
        $this->assertSame('admin', $this->modelo->getByCorreo('admin@migarita.test')['usuario']);
        $this->assertFalse($this->modelo->getByUsuario('nadie'));
    }

    #[Test]
    public function la_bd_no_permite_un_nombre_de_usuario_repetido(): void
    {
        $this->expectException(PDOException::class);
        $this->modelo->insert($this->nuevoUsuario(['usuario' => 'admin']));
    }

    #[Test]
    public function la_bd_no_permite_un_rol_fuera_del_enum(): void
    {
        $this->expectException(PDOException::class);
        $this->modelo->insert($this->nuevoUsuario(['rol' => 'hacker']));
    }

    #[Test]
    public function update_cambia_los_datos_y_el_rol(): void
    {
        $this->modelo->update(2, ['nombre' => 'Carla', 'rol' => 'superusuario']);

        $cajero = $this->modelo->getById(2);
        $this->assertSame('Carla', $cajero['nombre']);
        $this->assertSame('superusuario', $cajero['rol']);
    }

    #[Test]
    public function buscar_encuentra_por_cualquier_campo_visible(): void
    {
        $this->assertCount(1, $this->modelo->buscar('caj'), 'por usuario');
        $this->assertCount(1, $this->modelo->buscar('superusu'), 'por rol');
        $this->assertCount(2, $this->modelo->buscar('@migarita.test'), 'por correo');
        $this->assertArrayNotHasKey('password_hash', $this->modelo->buscar('admin')[0], 'No expone el hash.');
    }

    #[Test]
    public function delete_elimina_el_usuario(): void
    {
        $this->modelo->delete(2);

        $this->assertFalse($this->modelo->getById(2));
    }

    #[Test]
    public function la_bitacora_registra_cada_intento_de_inicio_de_sesion(): void
    {
        // Arrange
        $bitacora = new BitacoraModel($this->pdo);

        // Act
        $bitacora->registrarLogin(1, '192.168.1.10', 'Mozilla/5.0 (Windows NT 10.0)', 'Windows', 1);
        $this->modelo->registrarLogin(null, '192.168.1.11', 'curl/8', 'Desconocido', 0);

        // Assert
        $filas = $this->pdo->query('SELECT * FROM bitacora_login ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $filas);
        $this->assertEquals(1, $filas[0]['id_usuario']);
        $this->assertEquals(1, $filas[0]['exito']);
        $this->assertNull($filas[1]['id_usuario']);
        $this->assertEquals(0, $filas[1]['exito']);
        $this->assertNotEmpty($filas[0]['fecha']);
    }
}
