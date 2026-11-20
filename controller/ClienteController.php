<?php
require_once __DIR__ . "/../model/ClienteModel.php";

class ClienteController
{
    private function volverGuardar($error, $c, $n, $a, $t, $co, $tipo)
    {
        header("Location: /mi_garita/index.php?url=cliente/crear&error=$error&cedula=$c&nombre=$n&apellido=$a&telefono=$t&correo=$co&id_tipo_cliente=$tipo");
        exit;
    }

    public function __construct()
    {
        // Verificar sesión
        if (!isset($_SESSION['id'])) {
            header("Location: /mi_garita/index.php?url=login/index");
            exit;
        }
    }

    public function index()
    {
        $clienteModel = new ClienteModel();

        // Capturar búsqueda
        $buscar = $_GET['buscar'] ?? '';

        if ($buscar !== '') {
            $clientes = $clienteModel->buscar($buscar);
        } else {
            $clientes = $clienteModel->getAllConSaldo();
        }

        // Calcular total de todas las deudas
        $totalGeneral = 0;
        foreach ($clientes as $c) {
            $totalGeneral += $c['saldo'];
        }

        require __DIR__ . "/../view/cliente/index.php";
    }

    public function crear()
    {
        $clienteModel = new ClienteModel();
        $tipos = $clienteModel->getTiposCliente();
        require __DIR__ . "/../view/cliente/crear.php";
    }

    public function guardar()
    {
        $cedula   = trim($_POST["cedula"]);
        $nombre   = trim($_POST["nombre"]);
        $apellido = trim($_POST["apellido"]);
        $telefono = trim($_POST["telefono"]);
        $correo   = trim($_POST["correo"]);
        $tipo_cliente = $_POST["id_tipo_cliente"] ?? "";

        // === VALIDACIONES ===
        if ($cedula === "") {
            $this->volverGuardar("cedula_vacia", $cedula, $nombre, $apellido, $telefono, $correo, $tipo_cliente);
        }

        if (!preg_match('/^[0-9]+$/', $cedula)) {
            $this->volverGuardar("cedula_invalida", $cedula, $nombre, $apellido, $telefono, $correo, $tipo_cliente);
        }

        $clienteModel = new ClienteModel();
        if ($clienteModel->getByCedula($cedula)) {
            $this->volverGuardar("cedula_duplicada", $cedula, $nombre, $apellido, $telefono, $correo, $tipo_cliente);
        }

        if ($nombre === "") {
            $this->volverGuardar("nombre_vacio", $cedula, $nombre, $apellido, $telefono, $correo, $tipo_cliente);
        }

        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/', $nombre)) {
            $this->volverGuardar("nombre_invalido", $cedula, $nombre, $apellido, $telefono, $correo, $tipo_cliente);
        }

        if ($apellido === "") {
            $this->volverGuardar("apellido_vacio", $cedula, $nombre, $apellido, $telefono, $correo, $tipo_cliente);
        }

        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/', $apellido)) {
            $this->volverGuardar("apellido_invalido", $cedula, $nombre, $apellido, $telefono, $correo, $tipo_cliente);
        }

        if ($telefono === "") {
            $this->volverGuardar("telefono_vacio", $cedula, $nombre, $apellido, $telefono, $correo, $tipo_cliente);
        }

        if (!preg_match('/^[0-9]+$/', $telefono)) {
            $this->volverGuardar("telefono_invalido", $cedula, $nombre, $apellido, $telefono, $correo, $tipo_cliente);
        }

        if ($correo === "") {
            $this->volverGuardar("correo_vacio", $cedula, $nombre, $apellido, $telefono, $correo, $tipo_cliente);
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->volverGuardar("correo_invalido", $cedula, $nombre, $apellido, $telefono, $correo, $tipo_cliente);
        }

        if (empty($_POST["id_tipo_cliente"])) {
            $this->volverGuardar("tipo_cliente_vacio", $cedula, $nombre, $apellido, $telefono, $correo, $tipo_cliente);
        }

        // === SI TODO ESTÁ BIEN: GUARDAR ===
        $data = [
            "cedula" => $cedula,
            "nombre" => $nombre,
            "apellido" => $apellido,
            "telefono" => $telefono,
            "correo" => $correo,
            "id_tipo_cliente" => $_POST["id_tipo_cliente"]
        ];

        $clienteModel->insert($data);

        header("Location: /mi_garita/index.php?url=cliente/index&ok=creado");
        exit;
    }

    public function editar($id)
    {
        $clienteModel = new ClienteModel();
        $cliente = $clienteModel->getById($id);
        $tipos = $clienteModel->getTiposCliente();

        require __DIR__ . "/../view/cliente/editar.php";
    }

    public function actualizar($id)
    {
        $clienteModel = new ClienteModel();

        // === VALIDACIONES ===
        if (empty($_POST["cedula"])) {
            header("Location: /mi_garita/index.php?url=cliente/editar/$id&error=cedula_vacia");
            exit;
        }

        if (!ctype_digit($_POST["cedula"])) {
            header("Location: /mi_garita/index.php?url=cliente/editar/$id&error=cedula_invalida");
            exit;
        }

        $cedulaIngresada = $_POST["cedula"];
        $existe = $clienteModel->getByCedula($cedulaIngresada);

        if ($existe && $existe["id"] != $id) {
            header("Location: /mi_garita/index.php?url=cliente/editar/$id&error=cedula_duplicada");
            exit;
        }

        if (empty(trim($_POST["nombre"]))) {
            header("Location: /mi_garita/index.php?url=cliente/editar/$id&error=nombre_vacio");
            exit;
        }

        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/', $_POST["nombre"])) {
            header("Location: /mi_garita/index.php?url=cliente/editar/$id&error=nombre_invalido");
            exit;
        }

        if (empty(trim($_POST["apellido"]))) {
            header("Location: /mi_garita/index.php?url=cliente/editar/$id&error=apellido_vacio");
            exit;
        }

        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/', $_POST["apellido"])) {
            header("Location: /mi_garita/index.php?url=cliente/editar/$id&error=apellido_invalido");
            exit;
        }

        if (empty($_POST["telefono"])) {
            header("Location: /mi_garita/index.php?url=cliente/editar/$id&error=telefono_vacio");
            exit;
        }

        if (!ctype_digit($_POST["telefono"])) {
            header("Location: /mi_garita/index.php?url=cliente/editar/$id&error=telefono_invalido");
            exit;
        }

        if (empty($_POST["correo"])) {
            header("Location: /mi_garita/index.php?url=cliente/editar/$id&error=correo_vacio");
            exit;
        }

        if (!filter_var($_POST["correo"], FILTER_VALIDATE_EMAIL)) {
            header("Location: /mi_garita/index.php?url=cliente/editar/$id&error=correo_invalido");
            exit;
        }

        if (empty($_POST["id_tipo_cliente"])) {
            header("Location: /mi_garita/index.php?url=cliente/editar/$id&error=tipo_cliente_vacio");
            exit;
        }

        // DATOS LISTOS
        $data = [
            "cedula" => $_POST["cedula"],
            "nombre" => $_POST["nombre"],
            "apellido" => $_POST["apellido"],
            "telefono" => $_POST["telefono"],
            "correo" => $_POST["correo"],
            "id_tipo_cliente" => $_POST["id_tipo_cliente"]
        ];

        // ACTUALIZAR
        $clienteModel->update($id, $data);

        header("Location: /mi_garita/index.php?url=cliente/index&ok=editado");
        exit;
    }

    public function eliminar($id)
    {
        $clienteModel = new ClienteModel();

        // Obtener datos del cliente
        $cliente = $clienteModel->getById($id);

        if (!$cliente) {
            header("Location: /mi_garita/index.php?url=cliente/index&error=notfound");
            exit;
        }

        // Obtener saldo real
        $saldo = $clienteModel->getSaldo($id);

        // Si tiene saldo no se puede eliminar
        if ($saldo != 0) {
            header("Location: /mi_garita/index.php?url=cliente/index&error=saldo&monto=$saldo");
            exit;
        }

        // Eliminar si el saldo es 0
        $clienteModel->delete($id);

        header("Location: /mi_garita/index.php?url=cliente/index&ok=eliminado");
        exit;
    }

    public function buscarAjax()
    {
        $texto = $_GET["q"] ?? "";

        $clienteModel = new ClienteModel();
        $clientes = $clienteModel->buscarConSaldo($texto);

        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($clientes);
        exit;
    }

    public function deuda($id)
    {
        $clienteModel = new ClienteModel();
        $cliente = $clienteModel->getById($id);
        $metodos = $clienteModel->getMetodosPago();

        if (!$cliente) {
            header("Location: /mi_garita/index.php?url=cliente/index&error=notfound");
            exit;
        }

        require __DIR__ . "/../view/cliente/deuda.php";
    }


    public function eliminarAjax()
    {
        $id = $_GET['id'];

        $clienteModel = new ClienteModel();
        $cliente = $clienteModel->getById($id);

        if (!$cliente) {
            echo json_encode(["ok" => false, "msg" => "Cliente no encontrado."]);
            return;
        }

        // Validar saldo
        $saldo = $clienteModel->getSaldo($id);

        if ($saldo != 0) {
            echo json_encode([
                "ok" => false,
                "msg" => "No se puede eliminar porque el cliente tiene un saldo pendiente de $ " . $saldo
            ]);
            return;
        }

        // Eliminar
        $clienteModel->delete($id);

        echo json_encode(["ok" => true]);
    }

    public function guardarMovimiento($idCliente)
    {
        require_once __DIR__ . '/../app/services/EmailService.php';
        
        $tipo = $_POST["tipo"] ?? "";
        $monto = trim($_POST["monto"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $metodo_pago = $_POST["metodo_pago"] ?? null;

        // Guardar los valores
        $_SESSION["mov_data"] = [
            "tipo" => $tipo,
            "monto" => $monto,
            "descripcion" => $descripcion,
            "metodo_pago" => $metodo_pago
        ];

        // === VALIDACIONES ===
        if ($descripcion === "") {
            header("Location: /mi_garita/index.php?url=cliente/deuda/$idCliente&error=descripcion_vacia");
            exit;
        }

        if ($monto === "" || floatval($monto) <= 0) {
            header("Location: /mi_garita/index.php?url=cliente/deuda/$idCliente&error=monto_invalido");
            exit;
        }

        if ($tipo !== "fiado" && $tipo !== "abono") {
            header("Location: /mi_garita/index.php?url=cliente/deuda/$idCliente&error=tipo_invalido");
            exit;
        }

        if ($tipo === "abono" && empty($metodo_pago)) {
            header("Location: /mi_garita/index.php?url=cliente/deuda/$idCliente&error=metodo_vacio");
            exit;
        }

        $clienteModel = new ClienteModel();
        $saldoActual = $clienteModel->getSaldo($idCliente);

        if ($tipo === "abono" && floatval($monto) > $saldoActual) {
            header("Location: /mi_garita/index.php?url=cliente/deuda/$idCliente&error=abono_mayor");
            exit;
        }

        // Convertir signo
        if ($tipo === "abono") {
            $monto = -abs(floatval($monto));
        } else {
            $monto = abs(floatval($monto));
        }

        // Guardar movimiento
        $clienteModel->insertMovimiento(
            $idCliente,
            $_SESSION['id'],
            $descripcion,
            $monto,
            $metodo_pago
        );

        // === ENVIAR CORREO AL CLIENTE ===
        try {
            // Obtener datos del cliente
            $cliente = $clienteModel->getById($idCliente);
            
            // Obtener nombre del usuario que atendió
            require_once __DIR__ . '/../model/UserModel.php';
            $userModel = new UserModel();
            $usuario = $userModel->getById($_SESSION['id']);
            $usuarioNombre = $usuario['nombre'] . ' ' . $usuario['apellido'];
            
            // Preparar datos del movimiento para el correo
            $datosMovimiento = [
                'tipo' => $tipo,
                'monto' => $monto,
                'descripcion' => $descripcion,
                'fecha' => date('Y-m-d H:i:s')
            ];
            
            // Enviar correo
            $emailService = new EmailService();
            $emailService->enviarNotificacionMovimiento($cliente, $datosMovimiento, $usuarioNombre);
            
        } catch (Exception $e) {
            // Si falla el envío de correo, solo registrar el error pero continuar
            error_log("Error al enviar correo de notificación: " . $e->getMessage());
        }

        // Limpiar variables temporales
        unset($_SESSION["mov_data"]);

        header("Location: /mi_garita/index.php?url=cliente/index&ok=movimiento");
        exit;
    }

    public function seleccionarInforme($idCliente)
    {
        $clienteModel = new ClienteModel();
        $cliente = $clienteModel->getById($idCliente);

        require __DIR__ . "/../view/informes/seleccionar_informe.php";
    }

    public function generarInforme($idCliente)
    {
        require_once __DIR__ . "/../vendor/autoload.php";
        
        $clienteModel = new ClienteModel();
        
        // Obtener datos del cliente
        $cliente = $clienteModel->getById($idCliente);
        
        if (!$cliente) {
            header("Location: /mi_garita/index.php?url=cliente/index&error=notfound");
            exit;
        }
        
        // Determinar tipo de informe
        $tipoInforme = $_GET['tipo'] ?? 'completo';
        $desde = null;
        $hasta = null;
        $tituloInforme = "";
        
        switch ($tipoInforme) {
            case 'mes':
                // Últimos 30 días
                $desde = date("Y-m-d", strtotime("-30 days"));
                $hasta = date("Y-m-d");
                $tituloInforme = "Informe del Último Mes";
                break;
                
            case 'fechas':
                // Entre dos fechas específicas
                $desde = $_GET['desde'] ?? date("Y-m-d");
                $hasta = $_GET['hasta'] ?? date("Y-m-d");
                $tituloInforme = "Informe del " . date("d/m/Y", strtotime($desde)) . " al " . date("d/m/Y", strtotime($hasta));
                break;
                
            case 'completo':
            default:
                // Todos los movimientos
                $desde = null;
                $hasta = null;
                $tituloInforme = "Informe Completo";
                break;
        }
        
        // Obtener movimientos según el tipo
        $resultado = $clienteModel->getMovimientos($idCliente, $desde, $hasta);
        $fiados = $resultado['fiados'];
        $pagos = $resultado['pagos'];
        
        // Obtener saldo actual
        $saldo = $clienteModel->getSaldo($idCliente);
        
        ob_start();
        require __DIR__ . "/../view/informes/plantilla_pdf.php";
        $html = ob_get_clean();
        
        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled' => true,
            'chroot' => realpath(__DIR__ . '/../../')
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper("A4", "portrait");
        $dompdf->render();
        
        $nombreArchivo = "Informe_" . $cliente['nombre'] . "_" . $cliente['apellido'] . "_" . date("Y-m-d") . ".pdf";
        
        $dompdf->stream($nombreArchivo, ["Attachment" => true]);
    }
}
