<?php

namespace Tests\Unit\Validators;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use UsuarioValidator;

/**
 * Pruebas UNITARIAS de las reglas de validación de usuarios del sistema.
 */
final class UsuarioValidatorTest extends TestCase
{
    private function usuarioValido(array $cambios = []): array
    {
        return array_merge([
            'cedula'   => '1098765432',
            'nombre'   => 'Andrés',
            'apellido' => 'Muñoz',
            'correo'   => 'andres@migarita.co',
            'usuario'  => 'amunoz',
            'password' => 'secreta1',
            'rol'      => 'usuario',
        ], $cambios);
    }

    #[Test]
    public function un_usuario_nuevo_con_datos_correctos_es_valido(): void
    {
        $this->assertNull(UsuarioValidator::validar($this->usuarioValido()));
    }

    public static function datosInvalidosAlCrear(): array
    {
        return [
            'cédula vacía'                 => [['cedula' => ''], 'cedula_vacia'],
            'cédula con letras'            => [['cedula' => 'X123'], 'cedula_invalida'],
            'nombre vacío'                 => [['nombre' => ''], 'nombre_vacio'],
            'nombre con números'           => [['nombre' => 'R2D2'], 'nombre_invalido'],
            'apellido vacío'               => [['apellido' => ''], 'apellido_vacio'],
            'apellido con símbolos'        => [['apellido' => 'Muñoz#'], 'apellido_invalido'],
            'correo vacío'                 => [['correo' => ''], 'correo_vacio'],
            'correo inválido'              => [['correo' => 'andres@'], 'correo_invalido'],
            'usuario vacío'                => [['usuario' => ''], 'usuario_vacio'],
            'contraseña vacía'             => [['password' => ''], 'password_vacio'],
            'contraseña de 5 (límite)'     => [['password' => '12345'], 'password_corto'],
            'rol vacío'                    => [['rol' => ''], 'rol_vacio'],
            'rol no permitido'             => [['rol' => 'admin'], 'rol_invalido'],
            'rol en mayúsculas'            => [['rol' => 'SUPERUSUARIO'], 'rol_invalido'],
        ];
    }

    #[Test]
    #[DataProvider('datosInvalidosAlCrear')]
    public function al_crear_rechaza_datos_invalidos(array $cambios, string $errorEsperado): void
    {
        $this->assertSame($errorEsperado, UsuarioValidator::validar($this->usuarioValido($cambios)));
    }

    #[Test]
    public function una_contrasena_de_exactamente_6_caracteres_es_valida(): void
    {
        // Valor límite inferior aceptado
        $this->assertNull(UsuarioValidator::validar($this->usuarioValido(['password' => '123456'])));
    }

    #[Test]
    public function al_crear_falta_el_rol_si_no_se_envia(): void
    {
        $datos = $this->usuarioValido();
        unset($datos['rol']);

        $this->assertSame('rol_vacio', UsuarioValidator::validar($datos));
    }

    #[Test]
    public function al_editar_la_contrasena_es_opcional(): void
    {
        $datos = $this->usuarioValido(['password' => '']);
        unset($datos['rol']);

        $this->assertNull(UsuarioValidator::validar($datos, null, false));
    }

    #[Test]
    public function al_editar_una_contrasena_nueva_corta_se_rechaza(): void
    {
        $datos = $this->usuarioValido(['password' => 'abc']);

        $this->assertSame('password_corto', UsuarioValidator::validar($datos, null, false));
    }

    public static function duplicados(): array
    {
        return [
            'cédula repetida'  => ['cedula', 'cedula_duplicada'],
            'correo repetido'  => ['correo', 'correo_duplicado'],
            'usuario repetido' => ['usuario', 'usuario_duplicado'],
        ];
    }

    #[Test]
    #[DataProvider('duplicados')]
    public function detecta_cada_campo_duplicado(string $campoRepetido, string $errorEsperado): void
    {
        // Arrange: stub que solo marca como existente el campo indicado
        $existe = fn(string $campo) => $campo === $campoRepetido;

        // Act
        $error = UsuarioValidator::validar($this->usuarioValido(), $existe);

        // Assert
        $this->assertSame($errorEsperado, $error);
    }

    #[Test]
    public function rol_valido_solo_acepta_usuario_y_superusuario(): void
    {
        $this->assertTrue(UsuarioValidator::rolValido('usuario'));
        $this->assertTrue(UsuarioValidator::rolValido('superusuario'));
        $this->assertFalse(UsuarioValidator::rolValido('administrador'));
        $this->assertFalse(UsuarioValidator::rolValido(''));
    }

    #[Test]
    public function normalizar_no_recorta_la_contrasena_pero_si_los_demas_campos(): void
    {
        $datos = UsuarioValidator::normalizar([
            'cedula' => ' 1 ', 'nombre' => ' Ana ', 'usuario' => ' ana ', 'password' => ' clave ', 'rol' => ' usuario ',
        ]);

        $this->assertSame('1', $datos['cedula']);
        $this->assertSame('ana', $datos['usuario']);
        $this->assertSame(' clave ', $datos['password'], 'Los espacios pueden ser parte de la contraseña.');
        $this->assertSame('usuario', $datos['rol']);
    }

    #[Test]
    public function normalizar_no_inventa_un_rol_si_no_viene_en_el_formulario(): void
    {
        $this->assertArrayNotHasKey('rol', UsuarioValidator::normalizar(['cedula' => '1']));
    }
}
