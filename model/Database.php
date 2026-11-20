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

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db . ";charset=utf8",
                $this->user,
                $this->pass
            );

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            die("Error en la conexión: " . $e->getMessage());
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
