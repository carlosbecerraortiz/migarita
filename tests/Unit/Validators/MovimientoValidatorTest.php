<?php

namespace Tests\Unit\Validators;

use MovimientoValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas UNITARIAS de las reglas de negocio de fiados y abonos.
 */
final class MovimientoValidatorTest extends TestCase
{
    private function movimiento(array $cambios = []): array
    {
        return array_merge([
            'tipo'        => 'fiado',
            'monto'       => '5000',
            'descripcion' => 'Almuerzo',
            'metodo_pago' => null,
        ], $cambios);
    }

    #[Test]
    public function un_fiado_valido_no_necesita_metodo_de_pago(): void
    {
        $this->assertNull(MovimientoValidator::validar($this->movimiento()));
    }

    #[Test]
    public function un_abono_valido_con_metodo_de_pago_es_aceptado(): void
    {
        $datos = $this->movimiento(['tipo' => 'abono', 'metodo_pago' => '1']);

        $this->assertNull(MovimientoValidator::validar($datos));
    }

    public static function movimientosInvalidos(): array
    {
        return [
            'descripción vacía'            => [['descripcion' => ''], 'descripcion_vacia'],
            'monto vacío'                  => [['monto' => ''], 'monto_invalido'],
            'monto cero (límite)'          => [['monto' => '0'], 'monto_invalido'],
            'monto negativo'               => [['monto' => '-100'], 'monto_invalido'],
            'monto no numérico'            => [['monto' => 'mil'], 'monto_invalido'],
            'tipo desconocido'             => [['tipo' => 'prestamo'], 'tipo_invalido'],
            'tipo vacío'                   => [['tipo' => ''], 'tipo_invalido'],
            'tipo en mayúsculas'           => [['tipo' => 'FIADO'], 'tipo_invalido'],
            'abono sin método de pago'     => [['tipo' => 'abono', 'metodo_pago' => ''], 'metodo_vacio'],
            'abono con método nulo'        => [['tipo' => 'abono', 'metodo_pago' => null], 'metodo_vacio'],
        ];
    }

    #[Test]
    #[DataProvider('movimientosInvalidos')]
    public function rechaza_movimientos_invalidos(array $cambios, string $errorEsperado): void
    {
        $this->assertSame($errorEsperado, MovimientoValidator::validar($this->movimiento($cambios)));
    }

    #[Test]
    public function el_monto_minimo_positivo_es_valido(): void
    {
        // Valor límite: el primer valor por encima de 0
        $this->assertNull(MovimientoValidator::validar($this->movimiento(['monto' => '0.01'])));
    }

    #[Test]
    public function el_fiado_se_guarda_positivo_para_aumentar_la_deuda(): void
    {
        $this->assertSame(5000.0, MovimientoValidator::montoConSigno('fiado', '5000'));
    }

    #[Test]
    public function el_abono_se_guarda_negativo_para_disminuir_la_deuda(): void
    {
        $this->assertSame(-3000.0, MovimientoValidator::montoConSigno('abono', '3000'));
    }

    #[Test]
    public function el_signo_no_depende_de_lo_que_escriba_el_usuario(): void
    {
        // Aunque llegue con signo cambiado, el tipo manda.
        $this->assertSame(-3000.0, MovimientoValidator::montoConSigno('abono', '-3000'));
        $this->assertSame(5000.0, MovimientoValidator::montoConSigno('fiado', '-5000'));
    }

    public static function casosAbonoVsSaldo(): array
    {
        return [
            'abono menor que el saldo'           => ['abono', '4999', '5000', false],
            'abono IGUAL al saldo (límite)'      => ['abono', '5000', '5000', false],
            'abono un peso mayor (límite)'       => ['abono', '5001', '5000', true],
            'abono con saldo en cero'            => ['abono', '1', '0', true],
            'un fiado nunca supera el saldo'     => ['fiado', '999999', '0', false],
        ];
    }

    #[Test]
    #[DataProvider('casosAbonoVsSaldo')]
    public function un_abono_no_puede_ser_mayor_que_el_saldo(string $tipo, string $monto, string $saldo, bool $esperado): void
    {
        $this->assertSame($esperado, MovimientoValidator::abonoSuperaSaldo($tipo, $monto, $saldo));
    }

    #[Test]
    public function normalizar_quita_espacios_del_monto_y_la_descripcion(): void
    {
        $datos = MovimientoValidator::normalizar(['tipo' => 'fiado', 'monto' => ' 2500 ', 'descripcion' => '  Café ']);

        $this->assertSame('2500', $datos['monto']);
        $this->assertSame('Café', $datos['descripcion']);
        $this->assertNull($datos['metodo_pago']);
    }
}
