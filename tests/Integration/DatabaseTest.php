<?php

namespace Tests\Integration;

use ConexionException;
use Database;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas de INTEGRACIÓN de la clase Database (cómo se obtiene la conexión).
 * Nunca se conecta a la base MySQL real: el caso de MySQL apunta a un puerto cerrado.
 */
final class DatabaseTest extends TestCase
{
    private const VARIABLES = ['MIGARITA_DB_DSN', 'MIGARITA_DB_HOST', 'MIGARITA_DB_PORT'];

    protected function tearDown(): void
    {
        // Dejar el entorno limpio para las demás pruebas (independencia, F.I.R.S.T.)
        foreach (self::VARIABLES as $variable) {
            putenv($variable);
        }
    }

    #[Test]
    public function usa_la_conexion_inyectada_cuando_se_le_pasa_una(): void
    {
        // Arrange
        $pdo = new PDO('sqlite::memory:');

        // Act
        $db = new Database($pdo);

        // Assert
        $this->assertSame($pdo, $db->getConexion());
    }

    #[Test]
    public function con_la_variable_migarita_db_dsn_usa_esa_base_de_datos(): void
    {
        // Arrange: así es como las pruebas funcionales apuntan la app a SQLite
        putenv('MIGARITA_DB_DSN=sqlite::memory:');

        // Act
        $conexion = (new Database())->getConexion();

        // Assert
        $this->assertSame('sqlite', $conexion->getAttribute(PDO::ATTR_DRIVER_NAME));
        $this->assertSame(PDO::ERRMODE_EXCEPTION, $conexion->getAttribute(PDO::ATTR_ERRMODE));
    }

    #[Test]
    public function en_sqlite_registra_now_y_activa_las_llaves_foraneas(): void
    {
        // Act
        $pdo = Database::crearConexion('sqlite::memory:');

        // Assert: NOW() es de MySQL; los modelos lo usan en los INSERT
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $pdo->query('SELECT NOW()')->fetchColumn()
        );
        $this->assertEquals(1, $pdo->query('PRAGMA foreign_keys')->fetchColumn());
    }

    #[Test]
    public function si_mysql_no_responde_lanza_un_error_de_conexion_claro(): void
    {
        // Arrange: MySQL "apagado" (puerto 1 de la propia máquina, siempre cerrado)
        putenv('MIGARITA_DB_HOST=127.0.0.1');
        putenv('MIGARITA_DB_PORT=1');

        // Assert
        $this->expectException(ConexionException::class);
        $this->expectExceptionMessage('Error en la conexión');

        // Act
        new Database();
    }
}
