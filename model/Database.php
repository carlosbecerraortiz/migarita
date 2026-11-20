<?php
/**
 * Conexión a la base de datos.
 *
 * Design for Testability: la conexión PDO se puede INYECTAR por el constructor.
 * - En producción no se pasa nada y se conecta a MySQL (XAMPP) como siempre.
 * - En las pruebas se inyecta una base SQLite en memoria.
 * - Si existe la variable de entorno MIGARITA_DB_DSN (p. ej. "sqlite:C:/tmp/test.db"),
 *   se usa esa base; así las pruebas funcionales levantan la app contra una BD de prueba.
 */

/** Error al conectar con la base de datos (p. ej. MySQL apagado). */
class ConexionException extends RuntimeException
{
}

class Database
{
    private $host = "localhost";
    private $db = "migarita";
    private $user = "root";
    private $pass = "";

    protected $conn;

    public function __construct(?PDO $conn = null)
    {
        if ($conn !== null) {
            $this->conn = $conn;
            return;
        }

        $dsn = getenv('MIGARITA_DB_DSN');
        if ($dsn) {
            $this->conn = self::crearConexion($dsn);
            return;
        }

        $this->conn = self::conectarMysql(
            getenv('MIGARITA_DB_HOST') ?: $this->host,
            getenv('MIGARITA_DB_PORT') ?: null,
            $this->db,
            $this->user,
            $this->pass
        );
    }

    /**
     * Conexión a MySQL (XAMPP). Si falla lanza una excepción en vez de hacer die(),
     * así el error se puede probar; index.php la captura y responde HTTP 500.
     *
     * @throws ConexionException "Error en la conexión: ..."
     */
    public static function conectarMysql(string $host, ?string $puerto, string $bd, string $user, string $pass): PDO
    {
        $dsn = "mysql:host=$host;" . ($puerto ? "port=$puerto;" : "") . "dbname=$bd;charset=utf8";

        try {
            return self::crearConexion($dsn, $user, $pass);
        } catch (PDOException $e) {
            throw new ConexionException("Error en la conexión: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Crea una conexión a partir de un DSN. Si es SQLite, registra las funciones
     * de MySQL que usan los modelos (NOW) y activa las llaves foráneas.
     */
    public static function crearConexion(string $dsn, ?string $user = null, ?string $pass = null): PDO
    {
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $pdo->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'), 0);
            $pdo->exec('PRAGMA foreign_keys = ON');
        }

        return $pdo;
    }

    public function getConexion(): PDO
    {
        return $this->conn;
    }
}
