<?php
require_once __DIR__ . "/../model/ClienteModel.php";
require_once __DIR__ . "/../model/UserModel.php";
require_once __DIR__ . "/../app/services/ClienteService.php";
require_once __DIR__ . "/../app/services/MovimientoService.php";
require_once __DIR__ . "/../app/services/InformeService.php";
require_once __DIR__ . "/../app/services/Flash.php";

/**
 * Controlador de clientes. Solo se encarga de HTTP: leer el request, llamar al
 * servicio de negocio correspondiente y redirigir / mostrar la vista.
 * Toda la lógica (validaciones, saldo, reglas) vive en app/services y app/validators.
 */
class ClienteController
{
    private const BASE = "/mi_garita/index.php?url=";

    public function __construct()
    {
        // Verificar sesión
        if (!isset($_SESSION['id'])) {
            header("Location: " . self::BASE . "login/index");
            exit;
        }
    }

    /**
     * Redirige a una URL limpia. El aviso (ok / error / monto) viaja por la
     * sesión con Flash, nunca en la URL.
     */
    private function redirigir(string $ruta, array $aviso = []): void
    {
        if ($aviso) {
            Flash::guardar($aviso);
        }
        header("Location: " . self::BASE . $ruta);
        exit;
    }

    /** Las acciones que modifican datos solo se aceptan por POST. */
    private function exigirPost(string $volverA): void
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            $this->redirigir($volverA);
        }
    }

    /** Devuelve (y borra) los datos de un formulario guardados en la sesión tras un error. */
    private function tomarFormulario(string $clave): array
    {
        $datos = $_SESSION[$clave] ?? [];
        unset($_SESSION[$clave]);

        return is_array($datos) ? $datos : [];
    }

    private function servicio(): ClienteService
    {
        return new ClienteService(new ClienteModel());
    }

    public function index()
    {
        $clienteModel = new ClienteModel();
        // La búsqueda se hace con buscarAjax (POST); nada se busca por la URL.
        $clientes = $clienteModel->getAllConSaldo();

        // Calcular total de todas las deudas
        $totalGeneral = ClienteService::totalGeneral($clientes);

        require __DIR__ . "/../view/cliente/index.php";
    }

    public function crear()
    {
        $clienteModel = new ClienteModel();
        $tipos = $clienteModel->getTiposCliente();
        $datosPrevios = $this->tomarFormulario('form_cliente');
        require __DIR__ . "/../view/cliente/crear.php";
    }

    public function guardar()
    {
        $error = $this->servicio()->crear($_POST);

        if ($error !== null) {
            // Lo escrito se guarda en la sesión (no en la URL) para no perderlo.
            $_SESSION['form_cliente'] = ClienteValidator::normalizar($_POST);
            $this->redirigir("cliente/crear", ['error' => $error]);
        }

        $this->redirigir("cliente/index", ['ok' => 'creado']);
    }

    public function editar($id)
    {
        $clienteModel = new ClienteModel();
        $cliente = $clienteModel->getById($id);

        if (!$cliente) {
            $this->redirigir("cliente/index", ['error' => 'notfound']);
        }

        $tipos = $clienteModel->getTiposCliente();
        $previos = $this->tomarFormulario('form_cliente');
        $datosPrevios = ($previos['id'] ?? null) === (string)$id ? $previos : [];
        $saldo = $clienteModel->getSaldo($id);
        $historial = $clienteModel->getUltimosMovimientos(4, (int)$id);

        require __DIR__ . "/../view/cliente/editar.php";
    }

    public function actualizar($id)
    {
        $error = $this->servicio()->actualizar($id, $_POST);

        if ($error !== null) {
            $_SESSION['form_cliente'] = ['id' => (string)$id] + ClienteValidator::normalizar($_POST);
            $this->redirigir("cliente/editar/$id", ['error' => $error]);
        }

        $this->redirigir("cliente/index", ['ok' => 'editado']);
    }

    public function eliminar($id)
    {
        $this->exigirPost("cliente/index");

        $resultado = $this->servicio()->eliminar($id);

        if ($resultado['ok']) {
            $this->redirigir("cliente/index", ['ok' => 'eliminado']);
        }

        if ($resultado['error'] === 'saldo') {
            $this->redirigir("cliente/index", ['error' => 'saldo', 'monto' => $resultado['saldo']]);
        }

        $this->redirigir("cliente/index", ['error' => 'notfound']);
    }

    public function buscarAjax()
    {
        $texto = (string)($_POST["q"] ?? "");

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
            $this->redirigir("cliente/index", ['error' => 'notfound']);
        }

        $saldo = $clienteModel->getSaldo($id);
        $historial = $clienteModel->getUltimosMovimientos(6, (int)$id);

        require __DIR__ . "/../view/cliente/deuda.php";
    }

    public function eliminarAjax()
    {
        header("Content-Type: application/json; charset=utf-8");

        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") {
            http_response_code(405);
            echo json_encode(["ok" => false, "msg" => "Método no permitido."]);
            return;
        }

        $resultado = $this->servicio()->eliminar($_POST['id'] ?? null);

        if ($resultado['ok']) {
            echo json_encode(["ok" => true]);
            return;
        }

        if ($resultado['error'] === 'notfound') {
            http_response_code(404);
            echo json_encode(["ok" => false, "msg" => "Cliente no encontrado."]);
            return;
        }

        http_response_code(422);
        echo json_encode([
            "ok" => false,
            "msg" => "No se puede eliminar porque el cliente tiene un saldo pendiente de $ " . $resultado['saldo']
        ]);
    }

    public function guardarMovimiento($idCliente)
    {
        // Guardar los valores para no perderlos si hay un error
        $_SESSION["mov_data"] = MovimientoValidator::normalizar($_POST);

        $servicio = new MovimientoService(new ClienteModel(), new UserModel(), new EmailService());
        $error = $servicio->registrar($idCliente, $_SESSION['id'], $_POST);

        if ($error !== null) {
            $this->redirigir("cliente/deuda/$idCliente", ['error' => $error]);
        }

        // Limpiar variables temporales
        unset($_SESSION["mov_data"]);

        $this->redirigir("cliente/index", ['ok' => 'movimiento']);
    }

    public function seleccionarInforme($idCliente)
    {
        $clienteModel = new ClienteModel();
        $cliente = $clienteModel->getById($idCliente);

        if (!$cliente) {
            $this->redirigir("cliente/index", ['error' => 'notfound']);
        }

        $saldo = $clienteModel->getSaldo($idCliente);
        $historial = $clienteModel->getUltimosMovimientos(4, (int)$idCliente);

        require __DIR__ . "/../view/informes/seleccionar_informe.php";
    }

    public function generarInforme($idCliente)
    {
        // Las fechas del informe llegan por POST, no en la URL
        $this->exigirPost("cliente/seleccionarInforme/" . (int)$idCliente);

        require_once __DIR__ . "/../vendor/autoload.php";

        $clienteModel = new ClienteModel();

        // Obtener datos del cliente
        $cliente = $clienteModel->getById($idCliente);

        if (!$cliente) {
            $this->redirigir("cliente/index", ['error' => 'notfound']);
        }

        // Determinar tipo de informe y rango de fechas
        $rango = InformeService::rango((string)($_POST['tipo'] ?? 'completo'), $_POST['desde'] ?? null, $_POST['hasta'] ?? null);
        $desde = $rango['desde'];
        $hasta = $rango['hasta'];
        $tituloInforme = $rango['titulo'];

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

        $dompdf->stream(InformeService::nombreArchivo($cliente), ["Attachment" => true]);
    }
}
