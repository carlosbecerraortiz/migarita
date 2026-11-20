<?php

namespace Tests\Support;

use Database;
use PDO;

/**
 * Crea bases de datos de PRUEBA (SQLite) con el esquema de MiGarita.
 * Nunca toca la base real de MySQL.
 */
final class BaseDatosPrueba
{
    public const PASSWORD_ADMIN = 'admin123';
    public const PASSWORD_CAJERO = 'cajero123';

    /** BD en memoria: cada prueba arranca limpia (principio F.I.R.S.T.: Independiente). */
    public static function enMemoria(): PDO
    {
        $pdo = Database::crearConexion('sqlite::memory:');
        self::crearEsquema($pdo);

        return $pdo;
    }

    /** BD en un archivo temporal (para el servidor de las pruebas funcionales). */
    public static function enArchivo(string $ruta): PDO
    {
        if (is_file($ruta)) {
            unlink($ruta);
        }

        $pdo = Database::crearConexion('sqlite:' . $ruta);
        self::crearEsquema($pdo);

        return $pdo;
    }

    public static function crearEsquema(PDO $pdo): void
    {
        $pdo->exec(file_get_contents(__DIR__ . '/schema.sqlite.sql'));
    }

    /** Inserta un superusuario "admin" y un usuario normal "cajero". */
    public static function sembrarUsuarios(PDO $pdo): void
    {
        $sql = "INSERT INTO usuarios (id, cedula, nombre, apellido, correo, usuario, password_hash, rol)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([1, '1000', 'Ana', 'Admin', 'admin@migarita.test', 'admin', hash('sha256', self::PASSWORD_ADMIN), 'superusuario']);
        $stmt->execute([2, '2000', 'Carlos', 'Cajero', 'cajero@migarita.test', 'cajero', hash('sha256', self::PASSWORD_CAJERO), 'usuario']);
    }

    /** Inserta un cliente y devuelve su id. */
    public static function crearCliente(PDO $pdo, array $datos = []): int
    {
        $datos += [
            'cedula'          => '123456',
            'nombre'          => 'Laura',
            'apellido'        => 'Gómez',
            'telefono'        => '3001234567',
            'correo'          => 'laura@correo.test',
            'id_tipo_cliente' => 1,
        ];

        $stmt = $pdo->prepare(
            "INSERT INTO clientes (cedula, nombre, apellido, telefono, correo, id_tipo_cliente)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $datos['cedula'], $datos['nombre'], $datos['apellido'],
            $datos['telefono'], $datos['correo'], $datos['id_tipo_cliente'],
        ]);

        return (int)$pdo->lastInsertId();
    }

    /** Inserta un movimiento directamente (monto positivo = fiado, negativo = abono). */
    public static function crearMovimiento(PDO $pdo, int $idCliente, float $monto, ?string $fecha = null, ?int $metodo = null): void
    {
        $stmt = $pdo->prepare(
            "INSERT INTO deudas (id_cliente, id_usuario, descripcion, monto, fecha, id_metodo_pago)
             VALUES (?, 1, 'movimiento de prueba', ?, ?, ?)"
        );
        $stmt->execute([$idCliente, $monto, $fecha ?? date('Y-m-d H:i:s'), $metodo]);
    }
}
