<?php

namespace Tests\Unit\Validators;

use ClienteValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas UNITARIAS de las reglas de validación de clientes.
 * Clase pura: no hay base de datos, por eso no se necesitan mocks aquí.
 */
final class ClienteValidatorTest extends TestCase
{
    private function clienteValido(array $cambios = []): array
    {
        return array_merge([
            'cedula'          => '1065123456',
            'nombre'          => 'María José',
            'apellido'        => 'Peña Núñez',
            'telefono'        => '3001234567',
            'correo'          => 'maria@upc.edu.co',
            'id_tipo_cliente' => '1',
        ], $cambios);
    }

    #[Test]
    public function un_cliente_con_todos_los_datos_correctos_es_valido(): void
    {
        // Arrange
        $datos = $this->clienteValido();

        // Act
        $error = ClienteValidator::validar($datos);

        // Assert
        $this->assertNull($error);
    }

    public static function datosInvalidos(): array
    {
        return [
            'cédula vacía'                   => [['cedula' => ''], 'cedula_vacia'],
            'cédula con letras'              => [['cedula' => '10A5'], 'cedula_invalida'],
            'cédula con puntos'              => [['cedula' => '1.065.123'], 'cedula_invalida'],
            'cédula negativa'                => [['cedula' => '-123'], 'cedula_invalida'],
            'nombre vacío'                   => [['nombre' => ''], 'nombre_vacio'],
            'nombre con números'             => [['nombre' => 'Juan2'], 'nombre_invalido'],
            'nombre con símbolos'            => [['nombre' => 'Juan!'], 'nombre_invalido'],
            'apellido vacío'                 => [['apellido' => ''], 'apellido_vacio'],
            'apellido con números'           => [['apellido' => 'Pérez 3'], 'apellido_invalido'],
            'teléfono vacío'                 => [['telefono' => ''], 'telefono_vacio'],
            'teléfono con guiones'           => [['telefono' => '300-123-4567'], 'telefono_invalido'],
            'teléfono con espacios'          => [['telefono' => '300 1234567'], 'telefono_invalido'],
            'correo vacío'                   => [['correo' => ''], 'correo_vacio'],
            'correo sin arroba'              => [['correo' => 'maria.upc.edu.co'], 'correo_invalido'],
            'correo sin dominio'             => [['correo' => 'maria@'], 'correo_invalido'],
            'tipo de cliente sin seleccionar' => [['id_tipo_cliente' => ''], 'tipo_cliente_vacio'],
        ];
    }

    #[Test]
    #[DataProvider('datosInvalidos')]
    public function rechaza_datos_invalidos_con_el_codigo_de_error_correcto(array $cambios, string $errorEsperado): void
    {
        // Arrange
        $datos = $this->clienteValido($cambios);

        // Act
        $error = ClienteValidator::validar($datos);

        // Assert
        $this->assertSame($errorEsperado, $error);
    }

    public static function valoresLimiteValidos(): array
    {
        return [
            'cédula de un solo dígito'      => [['cedula' => '7']],
            'nombre de una sola letra'      => [['nombre' => 'A']],
            'nombre con tildes y ñ'         => [['nombre' => 'Íñigo Ángel']],
            'teléfono de un solo dígito'    => [['telefono' => '0']],
            'tipo de cliente "externo" (5)' => [['id_tipo_cliente' => '5']],
        ];
    }

    #[Test]
    #[DataProvider('valoresLimiteValidos')]
    public function acepta_valores_limite_validos(array $cambios): void
    {
        $this->assertNull(ClienteValidator::validar($this->clienteValido($cambios)));
    }

    #[Test]
    public function la_cedula_duplicada_se_detecta_con_la_funcion_inyectada(): void
    {
        // Arrange: la función simula que la cédula ya existe en la BD
        $cedulaConsultada = null;
        $existe = function (string $cedula) use (&$cedulaConsultada) {
            $cedulaConsultada = $cedula;
            return true;
        };

        // Act
        $error = ClienteValidator::validar($this->clienteValido(), $existe);

        // Assert
        $this->assertSame('cedula_duplicada', $error);
        $this->assertSame('1065123456', $cedulaConsultada);
    }

    #[Test]
    public function no_consulta_duplicados_si_la_cedula_ya_es_invalida(): void
    {
        // Arrange
        $consultado = false;
        $existe = function () use (&$consultado) {
            $consultado = true;
            return false;
        };

        // Act
        $error = ClienteValidator::validar($this->clienteValido(['cedula' => 'abc']), $existe);

        // Assert
        $this->assertSame('cedula_invalida', $error);
        $this->assertFalse($consultado, 'No debe ir a la BD con una cédula inválida.');
    }

    #[Test]
    public function reporta_el_primer_error_segun_el_orden_del_formulario(): void
    {
        // Arrange: cédula y nombre inválidos a la vez
        $datos = $this->clienteValido(['cedula' => '', 'nombre' => '']);

        // Act + Assert
        $this->assertSame('cedula_vacia', ClienteValidator::validar($datos));
    }

    #[Test]
    public function normalizar_quita_espacios_al_inicio_y_al_final(): void
    {
        // Arrange
        $input = [
            'cedula' => '  123 ', 'nombre' => ' Ana ', 'apellido' => ' Ruiz',
            'telefono' => '300 ', 'correo' => ' a@b.co ', 'id_tipo_cliente' => '2',
        ];

        // Act
        $datos = ClienteValidator::normalizar($input);

        // Assert
        $this->assertSame('123', $datos['cedula']);
        $this->assertSame('Ana', $datos['nombre']);
        $this->assertSame('Ruiz', $datos['apellido']);
        $this->assertSame('300', $datos['telefono']);
        $this->assertSame('a@b.co', $datos['correo']);
    }

    #[Test]
    public function normalizar_tolera_campos_faltantes_y_luego_falla_por_cedula_vacia(): void
    {
        $datos = ClienteValidator::normalizar([]);

        $this->assertSame('cedula_vacia', ClienteValidator::validar($datos));
    }
}
