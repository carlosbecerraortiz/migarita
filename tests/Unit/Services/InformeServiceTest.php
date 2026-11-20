<?php

namespace Tests\Unit\Services;

use DateTimeImmutable;
use InformeService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas UNITARIAS de la lógica de informes.
 * La fecha de "hoy" se inyecta para que la prueba sea REPETIBLE (F.I.R.S.T.).
 */
final class InformeServiceTest extends TestCase
{
    private DateTimeImmutable $hoy;

    protected function setUp(): void
    {
        $this->hoy = new DateTimeImmutable('2026-11-20');
    }

    #[Test]
    public function el_informe_completo_no_tiene_rango_de_fechas(): void
    {
        $rango = InformeService::rango('completo', null, null, $this->hoy);

        $this->assertSame(['desde' => null, 'hasta' => null, 'titulo' => 'Informe Completo'], $rango);
    }

    #[Test]
    public function un_tipo_desconocido_genera_el_informe_completo(): void
    {
        $this->assertSame('Informe Completo', InformeService::rango('xyz', null, null, $this->hoy)['titulo']);
    }

    #[Test]
    public function el_informe_del_ultimo_mes_cubre_los_ultimos_30_dias(): void
    {
        $rango = InformeService::rango('mes', null, null, $this->hoy);

        $this->assertSame('2026-10-21', $rango['desde']);
        $this->assertSame('2026-11-20', $rango['hasta']);
        $this->assertSame('Informe del Último Mes', $rango['titulo']);
    }

    #[Test]
    public function el_ultimo_mes_cruza_correctamente_el_cambio_de_anio(): void
    {
        // Caso borde: 15 de enero -> 16 de diciembre del año anterior
        $rango = InformeService::rango('mes', null, null, new DateTimeImmutable('2027-01-15'));

        $this->assertSame('2026-12-16', $rango['desde']);
    }

    #[Test]
    public function el_informe_entre_fechas_usa_las_fechas_elegidas_y_las_muestra_en_formato_colombiano(): void
    {
        $rango = InformeService::rango('fechas', '2026-11-01', '2026-11-15', $this->hoy);

        $this->assertSame('2026-11-01', $rango['desde']);
        $this->assertSame('2026-11-15', $rango['hasta']);
        $this->assertSame('Informe del 01/11/2026 al 15/11/2026', $rango['titulo']);
    }

    #[Test]
    public function el_informe_entre_fechas_sin_fechas_usa_el_dia_de_hoy(): void
    {
        $rango = InformeService::rango('fechas', null, '', $this->hoy);

        $this->assertSame('2026-11-20', $rango['desde']);
        $this->assertSame('2026-11-20', $rango['hasta']);
    }

    #[Test]
    public function el_nombre_del_pdf_incluye_cliente_y_fecha(): void
    {
        $nombre = InformeService::nombreArchivo(['nombre' => 'Laura', 'apellido' => 'Gómez'], $this->hoy);

        $this->assertSame('Informe_Laura_Gómez_2026-11-20.pdf', $nombre);
    }

    #[Test]
    public function el_total_de_abonos_se_muestra_en_positivo(): void
    {
        $abonos = [['monto' => '-2000.00'], ['monto' => '-500.50']];

        $this->assertSame(2500.5, InformeService::total($abonos));
    }
}
