<?php

namespace Tests\Unit\Services;

use Csrf;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas UNITARIAS de la protección CSRF.
 */
final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    #[Test]
    public function el_token_es_aleatorio_largo_y_estable_durante_la_sesion(): void
    {
        $token = Csrf::token();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token, '32 bytes aleatorios en hexadecimal.');
        $this->assertSame($token, Csrf::token(), 'El mismo token para toda la sesión.');
    }

    #[Test]
    public function cada_sesion_tiene_un_token_distinto(): void
    {
        $primera = Csrf::token();
        $_SESSION = [];

        $this->assertNotSame($primera, Csrf::token());
    }

    #[Test]
    public function solo_es_valido_el_token_exacto_de_la_sesion(): void
    {
        $token = Csrf::token();

        $this->assertTrue(Csrf::esValido($token));
        $this->assertFalse(Csrf::esValido(strtoupper($token)));
        $this->assertFalse(Csrf::esValido(substr($token, 0, -1)));
        $this->assertFalse(Csrf::esValido(''));
        $this->assertFalse(Csrf::esValido(null));
        $this->assertFalse(Csrf::esValido(['arreglo']));
    }

    #[Test]
    public function sin_token_en_la_sesion_nada_es_valido(): void
    {
        // Caso borde: alguien envía un token pero la sesión no tiene ninguno
        $this->assertFalse(Csrf::esValido('cualquier-cosa'));
    }

    #[Test]
    public function toma_el_token_del_formulario_o_de_la_cabecera(): void
    {
        $this->assertSame('del-form', Csrf::tokenRecibido(['_csrf' => 'del-form'], ['HTTP_X_CSRF_TOKEN' => 'de-cabecera']));
        $this->assertSame('de-cabecera', Csrf::tokenRecibido([], ['HTTP_X_CSRF_TOKEN' => 'de-cabecera']));
        $this->assertNull(Csrf::tokenRecibido([], []));
        $this->assertNull(Csrf::tokenRecibido(['_csrf' => ['no-texto']], []));
    }

    #[Test]
    public function el_campo_oculto_lleva_el_token_escapado(): void
    {
        $campo = Csrf::campo();

        $this->assertSame('<input type="hidden" name="_csrf" value="' . Csrf::token() . '">', $campo);
    }
}
