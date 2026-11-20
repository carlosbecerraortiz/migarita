<?php
require_once __DIR__ . "/../model/ClienteModel.php";
require_once __DIR__ . "/../model/UserModel.php";
require_once __DIR__ . "/../app/services/ClienteService.php";
require_once __DIR__ . "/../app/services/MovimientoService.php";
require_once __DIR__ . "/../app/services/InformeService.php";

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

    private function redirigir(string $ruta): void
    {
        header("Location: " . self::BASE . $ruta);
        exit;
    }

    private function servicio(): ClienteService
    {
        return new ClienteService(new ClienteModel());
    }

    public function index()
    {
        $clienteModel = new ClienteModel();

        // Capturar búsqueda
        $buscar = $_GET['buscar'] ?? '';

        if ($buscar !== '') {
            $clientes = $clienteModel->buscarConSaldo($buscar);
        } else {
            $clientes = $clienteModel->getAllConSaldo();
        }

        // Calcular total de todas las deudas
        $totalGeneral = ClienteService::totalGeneral($clientes);

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
        $error = $this->servicio()->crear($_POST);

        if ($error !== null) {
            // Se devuelven los datos al formulario para no perder lo escrito.
            $datos = ClienteValidator::normalizar($_POST);
            $this->redirigir("cliente/crear&" . http_build_query(['error' => $error] + $datos));
        }

        $this->redirigir("cliente/index&ok=creado");
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
        $error = $this->servicio()->actualizar($id, $_POST);

        if ($error !== null) {
            $this->redirigir("cliente/editar/$id&error=$error");
        }

        $this->redirigir("cliente/index&ok=editado");
    }

    public function eliminar($id)
    {
        $resultado = $this->servicio()->eliminar($id);

        if ($resultado['ok']) {
            $this->redirigir("cliente/index&ok=eliminado");
        }

        if ($resultado['error'] === 'saldo') {
            $this->redirigir("cliente/index&error=saldo&monto=" . $resultado['saldo']);
        }

        $this->redirigir("cliente/index&error=notfound");
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
            $this->redirigir("cliente/index&error=notfound");
        }

        $saldo = $clienteModel->getSaldo($id);
        $historial = $clienteModel->getUltimosMovimientos(6, (int)$id);

        require __DIR__ . "/../view/cliente/deuda.php";
    }

    public function eliminarAjax()
    {
        header("Content-Type: application/json; charset=utf-8");

        $resultado = $this->servicio()->eliminar($_GET['id'] ?? null);

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
            $this->redirigir("cliente/deuda/$idCliente&error=$error");
        }

        // Limpiar variables temporales
        unset($_SESSION["mov_data"]);

        $this->redirigir("cliente/index&ok=movimiento");
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
            $this->redirigir("cliente/index&error=notfound");
        }

        // Determinar tipo de informe y rango de fechas
        $rango = InformeService::rango($_GET['tipo'] ?? 'completo', $_GET['desde'] ?? null, $_GET['hasta'] ?? null);
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
