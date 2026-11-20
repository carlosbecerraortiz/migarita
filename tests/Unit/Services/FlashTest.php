<?php

namespace Tests\Unit\Services;

use Flash;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas UNITARIAS de los avisos de un solo uso (flash) en la sesión.
 */
final class FlashTest extends TestCase
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
    public function el_aviso_se_entrega_una_sola_vez(): void
    {
        // Arrange
        Flash::guardar(['ok' => 'creado']);

        // Act
        $primera = Flash::tomar();
        $segunda = Flash::tomar();

        // Assert
        $this->assertSame(['ok' => 'creado'], $primera);
        $this->assertSame([], $segunda, 'Al recargar la página el aviso ya no vuelve.');
    }

    #[Test]
    public function solo_acepta_las_claves_permitidas(): void
    {
        // Un dato personal nunca debe viajar como aviso
        Flash::guardar(['error' => 'saldo', 'monto' => 4500, 'cedula' => '1065', 'password' => 'x']);

        $this->assertSame(['error' => 'saldo', 'monto' => '4500'], Flash::tomar());
    }

    #[Test]
    public function un_aviso_nuevo_reemplaza_por_completo_al_anterior(): void
    {
        // No debe quedar un "monto" viejo pegado a un error distinto
        Flash::guardar(['error' => 'saldo', 'monto' => '100']);
        Flash::guardar(['error' => 'notfound']);

        $this->assertSame(['error' => 'notfound'], Flash::tomar());
    }

    #[Test]
    public function sin_aviso_pendiente_devuelve_vacio_aunque_la_sesion_este_danada(): void
    {
        $this->assertSame([], Flash::tomar());

        $_SESSION['mg_flash'] = 'valor inválido';
        $this->assertSame([], Flash::tomar());
    }
}
