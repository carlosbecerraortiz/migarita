<?php
require_once __DIR__ . "/Database.php";

class UserModel extends Database {

    protected $conn;

    public function __construct()
    {
        parent::__construct(); 
        $this->conn = parent::$conn ?? $this->conn;
    }

    public function getAll() {
        $sql = "SELECT * FROM usuarios ORDER BY id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $sql = "SELECT * FROM usuarios WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByUsuario($usuario) {
        $sql = "SELECT * FROM usuarios WHERE usuario = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByCedula($cedula) {
        $sql = "SELECT * FROM usuarios WHERE cedula = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$cedula]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByCorreo($correo) {
        $sql = "SELECT * FROM usuarios WHERE correo = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$correo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function buscar($texto)
    {
        $sql = "SELECT 
                    id,
                    cedula,
                    nombre,
                    apellido,
                    usuario,
                    correo,
                    rol
                FROM usuarios
                WHERE cedula LIKE ?
                   OR nombre LIKE ?
                   OR apellido LIKE ?
                   OR usuario LIKE ?
                   OR correo LIKE ?
                   OR rol LIKE ?
                ORDER BY id ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            "%$texto%", "%$texto%", "%$texto%", "%$texto%", "%$texto%", "%$texto%"
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function insert($data) {
        $sql = "INSERT INTO usuarios (cedula, nombre, apellido, correo, usuario, password_hash, rol, fecha_registro)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $data["cedula"],
            $data["nombre"],
            $data["apellido"],
            $data["correo"],
            $data["usuario"],
            $data["password_hash"],
            $data["rol"] ?? "usuario"
        ]);
    }

    public function update($id, $data) {
        $campos = [];
        $params = [];

        foreach ($data as $col => $value) {
            $campos[] = "$col = ?";
            $params[] = $value;
        }

        $params[] = $id;

        $sql = "UPDATE usuarios SET ".implode(",", $campos)." WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
    }

    public function delete($id) {
        $sql = "DELETE FROM usuarios WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
    }

    public function registrarLogin($idUsuario, $ip, $navegador, $so, $exito)
    {
        $sql = "INSERT INTO bitacora_login (id_usuario, ip, navegador, sistema_operativo, exito)
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idUsuario, $ip, $navegador, $so, $exito]);
    }
}