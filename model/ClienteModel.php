<?php
require_once __DIR__ . "/Database.php";

class ClienteModel extends Database {

    public function getAllConSaldo()
    {
        $sql = "SELECT * FROM vista_saldos ORDER BY id ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $sql = "SELECT * FROM clientes WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByCedula($cedula)
    {
        $sql = "SELECT * FROM clientes WHERE cedula = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$cedula]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function buscar($texto)
    {
        $sql = "SELECT * FROM clientes
                WHERE cedula  LIKE ? 
                   OR nombre  LIKE ?
                   OR apellido LIKE ?
                ORDER BY id ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute(["%$texto%", "%$texto%", "%$texto%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert($data)
    {
        $sql = "INSERT INTO clientes 
                (cedula, nombre, apellido, telefono, correo, id_tipo_cliente, fecha_registro)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $data["cedula"],
            $data["nombre"],
            $data["apellido"],
            $data["telefono"],
            $data["correo"],
            $data["id_tipo_cliente"]
        ]);
    }


    public function update($id, $data)
    {
        $campos = [];
        $params = [];

        foreach ($data as $col => $value) {
            $campos[] = "$col = ?";
            $params[] = $value;
        }

        $params[] = $id;

        $sql = "UPDATE clientes SET " . implode(",", $campos) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM clientes WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
    }

    public function getSaldo($idCliente)
    {
        $sql = "SELECT COALESCE(SUM(monto), 0) AS saldo
                FROM deudas
                WHERE id_cliente = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idCliente]);
        return $stmt->fetchColumn();
    }

    public function getMovimientos($idCliente, $desde = null, $hasta = null)
    {
        $sql = "SELECT d.*, 
                       u.nombre AS usuario_nombre,
                       u.apellido AS usuario_apellido,
                       mp.nombre AS metodo_pago
                FROM deudas d
                INNER JOIN usuarios u ON u.id = d.id_usuario
                LEFT JOIN metodos_pago mp ON mp.id = d.id_metodo_pago
                WHERE d.id_cliente = ?";

        $params = [$idCliente];

        if ($desde !== null && $hasta !== null) {
            $sql .= " AND DATE(d.fecha) BETWEEN ? AND ?";
            $params[] = $desde;
            $params[] = $hasta;
        }

        $sql .= " ORDER BY d.fecha ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $fiados = [];
        $pagos  = [];

        foreach ($todos as $m) {
            if ($m["monto"] > 0) {
                $fiados[] = $m;
            } else {
                // Convertir a positivo para mostrar en la tabla
                $m['monto_mostrar'] = abs($m['monto']);
                $pagos[] = $m;
            }
        }

        return [
            "fiados" => $fiados,
            "pagos"  => $pagos
        ];
    }


    public function insertMovimiento($idCliente, $idUsuario, $descripcion, $monto, $idMetodoPago = null)
    {
        $sql = "INSERT INTO deudas (id_cliente, id_usuario, descripcion, monto, id_metodo_pago, fecha)
            VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$idCliente, $idUsuario, $descripcion, $monto, $idMetodoPago]);
    }


    public function buscarConSaldo($texto)
    {
        $sql = "SELECT 
                    c.id,
                    c.cedula,
                    c.nombre,
                    c.apellido,
                    c.telefono,
                    tc.nombre AS tipo_cliente,
                    COALESCE(SUM(d.monto), 0) AS saldo
                FROM clientes c
                LEFT JOIN tipos_cliente tc ON tc.id = c.id_tipo_cliente
                LEFT JOIN deudas d ON d.id_cliente = c.id
                WHERE c.cedula LIKE ?
                   OR c.nombre LIKE ?
                   OR c.apellido LIKE ?
                GROUP BY c.id
                ORDER BY c.id ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute(["%$texto%", "%$texto%", "%$texto%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTiposCliente()
    {
        $sql = "SELECT * FROM tipos_cliente ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMetodosPago()
    {
        $sql = "SELECT * FROM metodos_pago ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Últimos movimientos (más recientes primero), de todos los clientes o de uno.
     * Incluye el nombre del cliente, quién lo registró y el método de pago.
     */
    public function getUltimosMovimientos(int $limite = 10, ?int $idCliente = null)
    {
        $limite = max(1, $limite);

        $sql = "SELECT d.id, d.id_cliente, d.descripcion, d.monto, d.fecha,
                       c.nombre AS cliente_nombre, c.apellido AS cliente_apellido,
                       u.nombre AS usuario_nombre,
                       mp.nombre AS metodo_pago
                FROM deudas d
                INNER JOIN clientes c ON c.id = d.id_cliente
                INNER JOIN usuarios u ON u.id = d.id_usuario
                LEFT JOIN metodos_pago mp ON mp.id = d.id_metodo_pago";

        $params = [];
        if ($idCliente !== null) {
            $sql .= " WHERE d.id_cliente = ?";
            $params[] = $idCliente;
        }

        // LIMIT va como entero (ya validado) porque MySQL no acepta un parámetro de texto ahí.
        $sql .= " ORDER BY d.fecha DESC, d.id DESC LIMIT " . $limite;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Totales de un rango de fechas (ambos días incluidos):
     * cuánto se fió, cuánto se abonó y cuántos movimientos hubo.
     */
    public function getTotalesMovimientos(string $desde, string $hasta)
    {
        $sql = "SELECT COALESCE(SUM(CASE WHEN monto > 0 THEN monto ELSE 0 END), 0) AS fiado,
                       COALESCE(SUM(CASE WHEN monto < 0 THEN -monto ELSE 0 END), 0) AS abonado,
                       COUNT(*) AS cantidad
                FROM deudas
                WHERE DATE(fecha) BETWEEN ? AND ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
