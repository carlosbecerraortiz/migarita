<?php

namespace Tests\Integration;

use ClienteModel;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\BaseDatosPrueba;

/**
 * Pruebas de INTEGRACIÓN del modelo de clientes contra una BD real (SQLite en
 * memoria con el mismo esquema de migarita.sql). Verifican SQL, persistencia,
 * la vista vista_saldos y las restricciones de la base de datos.
 */
final class ClienteModelTest extends TestCase
{
    private PDO $pdo;
    private ClienteModel $modelo;

    protected function setUp(): void
    {
        $this->pdo = BaseDatosPrueba::enMemoria();
        BaseDatosPrueba::sembrarUsuarios($this->pdo);
        $this->modelo = new ClienteModel($this->pdo);
    }

    private function datosCliente(array $cambios = []): array
    {
        return array_merge([
            'cedula' => '1065000111', 'nombre' => 'Laura', 'apellido' => 'Gómez',
            'telefono' => '3001234567', 'correo' => 'laura@correo.test', 'id_tipo_cliente' => 3,
        ], $cambios);
    }

    #[Test]
    public function insert_guarda_el_cliente_y_se_puede_consultar_por_cedula(): void
    {
        // Act
        $this->modelo->insert($this->datosCliente());
        $cliente = $this->modelo->getByCedula('1065000111');

        // Assert: los datos quedaron persistidos
        $this->assertNotFalse($cliente);
        $this->assertSame('Laura', $cliente['nombre']);
        $this->assertSame('3001234567', $cliente['telefono']);
        $this->assertEquals(3, $cliente['id_tipo_cliente']);
        $this->assertNotEmpty($cliente['fecha_registro']);
    }

    #[Test]
    public function la_bd_no_permite_dos_clientes_con_la_misma_cedula(): void
    {
        $this->modelo->insert($this->datosCliente());

        $this->expectException(PDOException::class);
        $this->modelo->insert($this->datosCliente(['correo' => 'otro@correo.test']));
    }

    #[Test]
    public function get_by_id_de_un_cliente_inexistente_devuelve_false(): void
    {
        $this->assertFalse($this->modelo->getById(9999));
    }

    #[Test]
    public function update_modifica_solo_el_cliente_indicado(): void
    {
        // Arrange
        $id1 = BaseDatosPrueba::crearCliente($this->pdo, ['cedula' => '1']);
        $id2 = BaseDatosPrueba::crearCliente($this->pdo, ['cedula' => '2', 'nombre' => 'Pedro']);

        // Act
        $this->modelo->update($id1, ['nombre' => 'Lucía', 'telefono' => '3110000000']);

        // Assert
        $this->assertSame('Lucía', $this->modelo->getById($id1)['nombre']);
        $this->assertSame('3110000000', $this->modelo->getById($id1)['telefono']);
        $this->assertSame('Pedro', $this->modelo->getById($id2)['nombre']);
    }

    #[Test]
    public function el_saldo_es_la_suma_de_fiados_menos_abonos(): void
    {
        // Arrange: fiados 10.000 + 5.000, abono 3.000 => debe 12.000
        $id = BaseDatosPrueba::crearCliente($this->pdo);
        BaseDatosPrueba::crearMovimiento($this->pdo, $id, 10000);
        BaseDatosPrueba::crearMovimiento($this->pdo, $id, 5000);
        BaseDatosPrueba::crearMovimiento($this->pdo, $id, -3000, null, 1);

        // Act
        $saldo = $this->modelo->getSaldo($id);

        // Assert
        $this->assertEquals(12000, $saldo);
    }

    #[Test]
    public function un_cliente_sin_movimientos_tiene_saldo_cero(): void
    {
        $id = BaseDatosPrueba::crearCliente($this->pdo);

        $this->assertEquals(0, $this->modelo->getSaldo($id));
    }

    #[Test]
    public function la_vista_de_saldos_lista_todos_los_clientes_con_su_saldo_y_tipo(): void
    {
        // Arrange
        $deudor = BaseDatosPrueba::crearCliente($this->pdo, ['cedula' => '10', 'id_tipo_cliente' => 3]);
        $alDia = BaseDatosPrueba::crearCliente($this->pdo, ['cedula' => '20', 'id_tipo_cliente' => 1]);
        BaseDatosPrueba::crearMovimiento($this->pdo, $deudor, 7000);

        // Act
        $lista = $this->modelo->getAllConSaldo();

        // Assert
        $this->assertCount(2, $lista);
        $porId = array_column($lista, null, 'id');
        $this->assertEquals(7000, $porId[$deudor]['saldo']);
        $this->assertSame('profesor', $porId[$deudor]['tipo_cliente']);
        $this->assertEquals(0, $porId[$alDia]['saldo']);
    }

    #[Test]
    public function buscar_encuentra_por_cedula_nombre_o_apellido_parcial(): void
    {
        BaseDatosPrueba::crearCliente($this->pdo, ['cedula' => '1065', 'nombre' => 'Laura', 'apellido' => 'Gómez']);
        BaseDatosPrueba::crearCliente($this->pdo, ['cedula' => '2077', 'nombre' => 'Pedro', 'apellido' => 'Lauren']);
        BaseDatosPrueba::crearCliente($this->pdo, ['cedula' => '3088', 'nombre' => 'Ana', 'apellido' => 'Ruiz']);

        $this->assertCount(2, $this->modelo->buscar('Laur'));
        $this->assertCount(1, $this->modelo->buscar('207'), 'Busca por cédula parcial.');
        $this->assertCount(0, $this->modelo->buscar('zzz'));
    }

    #[Test]
    public function buscar_con_saldo_devuelve_el_saldo_de_cada_coincidencia(): void
    {
        $id = BaseDatosPrueba::crearCliente($this->pdo, ['nombre' => 'Laura']);
        BaseDatosPrueba::crearMovimiento($this->pdo, $id, 4500);
        BaseDatosPrueba::crearMovimiento($this->pdo, $id, -1500, null, 2);

        $resultado = $this->modelo->buscarConSaldo('Laura');

        $this->assertCount(1, $resultado);
        $this->assertEquals(3000, $resultado[0]['saldo']);
        $this->assertSame('estudiante', $resultado[0]['tipo_cliente']);
    }

    #[Test]
    public function insert_movimiento_persiste_el_movimiento_con_usuario_y_metodo(): void
    {
        $id = BaseDatosPrueba::crearCliente($this->pdo);

        $this->modelo->insertMovimiento($id, 2, 'Pago parcial', -2000, 2);

        $fila = $this->pdo->query("SELECT * FROM deudas WHERE id_cliente = $id")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(-2000, $fila['monto']);
        $this->assertEquals(2, $fila['id_usuario']);
        $this->assertEquals(2, $fila['id_metodo_pago']);
        $this->assertSame('Pago parcial', $fila['descripcion']);
    }

    #[Test]
    public function la_bd_rechaza_un_movimiento_con_monto_cero(): void
    {
        $id = BaseDatosPrueba::crearCliente($this->pdo);

        $this->expectException(PDOException::class);
        $this->modelo->insertMovimiento($id, 1, 'Nada', 0);
    }

    #[Test]
    public function get_movimientos_separa_fiados_y_pagos_y_muestra_los_pagos_en_positivo(): void
    {
        // Arrange
        $id = BaseDatosPrueba::crearCliente($this->pdo);
        BaseDatosPrueba::crearMovimiento($this->pdo, $id, 8000, '2026-11-01 10:00:00');
        BaseDatosPrueba::crearMovimiento($this->pdo, $id, -3000, '2026-11-05 10:00:00', 1);

        // Act
        $movimientos = $this->modelo->getMovimientos($id);

        // Assert
        $this->assertCount(1, $movimientos['fiados']);
        $this->assertCount(1, $movimientos['pagos']);
        $this->assertEquals(3000, $movimientos['pagos'][0]['monto_mostrar']);
        $this->assertSame('efectivo', $movimientos['pagos'][0]['metodo_pago']);
        $this->assertSame('Ana', $movimientos['fiados'][0]['usuario_nombre']);
    }

    #[Test]
    public function get_movimientos_filtra_por_rango_de_fechas_inclusive(): void
    {
        // Arrange: movimientos el 31/10, 01/11, 15/11 y 16/11
        $id = BaseDatosPrueba::crearCliente($this->pdo);
        BaseDatosPrueba::crearMovimiento($this->pdo, $id, 100, '2026-10-31 23:59:59');
        BaseDatosPrueba::crearMovimiento($this->pdo, $id, 200, '2026-11-01 00:00:00');
        BaseDatosPrueba::crearMovimiento($this->pdo, $id, 300, '2026-11-15 18:00:00');
        BaseDatosPrueba::crearMovimiento($this->pdo, $id, 400, '2026-11-16 00:00:01');

        // Act: rango del 1 al 15 de noviembre
        $fiados = $this->modelo->getMovimientos($id, '2026-11-01', '2026-11-15')['fiados'];

        // Assert: incluye los días límite completos, excluye los de afuera
        $this->assertSame([200.0, 300.0], array_map(fn($m) => (float)$m['monto'], $fiados));
    }

    #[Test]
    public function al_eliminar_un_cliente_se_eliminan_sus_movimientos(): void
    {
        $id = BaseDatosPrueba::crearCliente($this->pdo);
        BaseDatosPrueba::crearMovimiento($this->pdo, $id, 1000);
        BaseDatosPrueba::crearMovimiento($this->pdo, $id, -1000, null, 1);

        $this->modelo->delete($id);

        $this->assertFalse($this->modelo->getById($id));
        $this->assertSame(0, (int)$this->pdo->query("SELECT COUNT(*) FROM deudas WHERE id_cliente = $id")->fetchColumn());
    }

    #[Test]
    public function los_catalogos_se_devuelven_ordenados_alfabeticamente(): void
    {
        $tipos = array_column($this->modelo->getTiposCliente(), 'nombre');
        $metodos = array_column($this->modelo->getMetodosPago(), 'nombre');

        $this->assertSame(['administrativo', 'egresado', 'estudiante', 'externo', 'profesor'], $tipos);
        $this->assertSame(['efectivo', 'transferencia'], $metodos);
    }
}
