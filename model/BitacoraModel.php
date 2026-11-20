<?php
require_once __DIR__ . "/Database.php";

class BitacoraModel extends Database
{
    public function registrarLogin($idUsuario, $ip, $navegador, $so, $exito)
    {
        // La fecha la pone PHP (zona horaria de la app) para que coincida con los
        // cálculos de intentos recientes, sin importar la zona horaria de la BD.
        $sql = "INSERT INTO bitacora_login (id_usuario, ip, navegador, sistema_operativo, exito, fecha)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idUsuario, $ip, mb_substr((string)$navegador, 0, 250), $so, $exito, date('Y-m-d H:i:s')]);
    }

    /** Intentos fallidos desde una IP a partir de una fecha (para frenar ataques de fuerza bruta). */
    public function contarFallidosRecientes(string $ip, string $desde): int
    {
        $sql = "SELECT COUNT(*) FROM bitacora_login WHERE ip = ? AND exito = 0 AND fecha >= ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$ip, $desde]);
        return (int)$stmt->fetchColumn();
    }
}
