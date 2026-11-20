<?php
require_once __DIR__ . "/../model/UserModel.php";
require_once __DIR__ . "/../app/services/UsuarioService.php";

/**
 * Controlador de usuarios del sistema. Las reglas de permisos y validaciones
 * viven en app/services/UsuarioService.php y app/validators/UsuarioValidator.php.
 */
class UserController
{
    private const BASE = "/mi_garita/index.php?url=";

    public function __construct()
    {
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

    private function servicio(): UsuarioService
    {
        return new UsuarioService(new UserModel());
    }

    public function index()
    {
        $userModel = new UserModel();
        $usuarios = $userModel->getAll();

        require __DIR__ . "/../view/user/index.php";
    }

    public function profile()
    {
        $userModel = new UserModel();
        $user = $userModel->getById($_SESSION['id']);

        require __DIR__ . "/../view/user/profile.php";
    }

    public function crear()
    {
        if (!UsuarioService::esSuperusuario($_SESSION['rol'] ?? null)) {
            $this->redirigir("user/index&error=sin_permisos");
        }

        require __DIR__ . "/../view/user/crear.php";
    }

    public function guardar()
    {
        $error = $this->servicio()->crear($_POST, $_SESSION['rol'] ?? null);

        if ($error === 'sin_permisos') {
            $this->redirigir("user/index&error=sin_permisos");
        }

        if ($error !== null) {
            // Se devuelven los datos (sin la contraseña) para no perder lo escrito.
            $datos = UsuarioValidator::normalizar($_POST);
            unset($datos['password']);
            $datos += ['rol' => ''];
            $this->redirigir("user/crear&" . http_build_query(['error' => $error] + $datos));
        }

        $this->redirigir("user/index&ok=creado");
    }

    public function editar($id)
    {
        if (!UsuarioService::puedeEditar($_SESSION['id'], $_SESSION['rol'] ?? null, $id)) {
            $this->redirigir("user/index&error=sin_permisos");
        }

        $userModel = new UserModel();
        $user = $userModel->getById($id);

        require __DIR__ . "/../view/user/editar.php";
    }

    public function actualizar($id)
    {
        $error = $this->servicio()->actualizar($id, $_POST, $_SESSION['id'], $_SESSION['rol'] ?? null);

        if ($error === 'sin_permisos') {
            $this->redirigir("user/index&error=sin_permisos");
        }

        if ($error !== null) {
            $this->redirigir("user/editar/$id&error=$error");
        }

        $this->redirigir("user/index&ok=editado");
    }

    public function eliminarAjax()
    {
        header("Content-Type: application/json; charset=utf-8");

        $resultado = $this->servicio()->eliminar($_POST['id'] ?? null, $_SESSION['id'], $_SESSION['rol'] ?? null);

        $codigos = [
            'sin_permisos'   => 403,
            'id_no_recibido' => 400,
            'auto_eliminar'  => 422,
        ];

        if (isset($resultado['error'])) {
            http_response_code($codigos[$resultado['error']] ?? 400);
        }

        echo json_encode($resultado);
    }

    public function buscarAjax()
    {
        $texto = $_GET["q"] ?? "";

        $userModel = new UserModel();
        $usuarios = $userModel->buscar($texto);

        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($usuarios);
        exit;
    }
}
