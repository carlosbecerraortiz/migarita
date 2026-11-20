<?php
require_once __DIR__ . "/Database.php";

class BitacoraModel extends Database
{
    public function registrarLogin($idUsuario, $ip, $navegador, $so, $exito)
    {
        $sql = "INSERT INTO bitacora_login (id_usuario, ip, navegador, sistema_operativo, exito)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idUsuario, $ip, $navegador, $so, $exito]);
    }
}
