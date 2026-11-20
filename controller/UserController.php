<?php
require_once __DIR__ . "/../model/UserModel.php";
require_once __DIR__ . "/../app/services/UsuarioService.php";
require_once __DIR__ . "/../app/services/Flash.php";

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

    /** Redirige a una URL limpia; el aviso viaja por la sesión (Flash), nunca en la URL. */
    private function redirigir(string $ruta, array $aviso = []): void
    {
        if ($aviso) {
            Flash::guardar($aviso);
        }
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
            $this->redirigir("user/index", ['error' => 'sin_permisos']);
        }

        $datosPrevios = $this->tomarFormulario('form_usuario');
        unset($datosPrevios['id']);

        require __DIR__ . "/../view/user/crear.php";
    }

    /** Datos del formulario de usuario listos para guardar en la sesión: nunca la contraseña. */
    private function datosSinClave(array $input): array
    {
        $datos = UsuarioValidator::normalizar($input);
        unset($datos['password']);

        return $datos;
    }

    /** Devuelve (y borra) los datos de un formulario guardados en la sesión tras un error. */
    private function tomarFormulario(string $clave): array
    {
        $datos = $_SESSION[$clave] ?? [];
        unset($_SESSION[$clave]);

        return is_array($datos) ? $datos : [];
    }

    public function guardar()
    {
        $error = $this->servicio()->crear($_POST, $_SESSION['rol'] ?? null);

        if ($error === 'sin_permisos') {
            $this->redirigir("user/index", ['error' => 'sin_permisos']);
        }

        if ($error !== null) {
            // Lo escrito (sin la contraseña) se guarda en la sesión, no en la URL.
            $_SESSION['form_usuario'] = $this->datosSinClave($_POST);
            $this->redirigir("user/crear", ['error' => $error]);
        }

        $this->redirigir("user/index", ['ok' => 'creado']);
    }

    public function editar($id)
    {
        if (!UsuarioService::puedeEditar($_SESSION['id'], $_SESSION['rol'] ?? null, $id)) {
            $this->redirigir("user/index", ['error' => 'sin_permisos']);
        }

        $userModel = new UserModel();
        $user = $userModel->getById($id);

        if (!$user) {
            $this->redirigir("user/index");
        }

        $previos = $this->tomarFormulario('form_usuario');
        $datosPrevios = ($previos['id'] ?? null) === (string)$id ? $previos : [];

        require __DIR__ . "/../view/user/editar.php";
    }

    public function actualizar($id)
    {
        $error = $this->servicio()->actualizar($id, $_POST, $_SESSION['id'], $_SESSION['rol'] ?? null);

        if ($error === 'sin_permisos') {
            $this->redirigir("user/index", ['error' => 'sin_permisos']);
        }

        if ($error !== null) {
            $_SESSION['form_usuario'] = ['id' => (string)$id] + $this->datosSinClave($_POST);
            $this->redirigir("user/editar/$id", ['error' => $error]);
        }

        $this->redirigir("user/index", ['ok' => 'editado']);
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

        if (!empty($resultado['ok'])) {
            Flash::guardar(['ok' => 'eliminado']);
        }

        echo json_encode($resultado);
    }

    public function buscarAjax()
    {
        $texto = (string)($_POST["q"] ?? "");

        $userModel = new UserModel();
        $usuarios = $userModel->buscar($texto);

        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($usuarios);
        exit;
    }
}
