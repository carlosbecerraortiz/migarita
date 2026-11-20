<?php
require_once __DIR__ . "/../model/UserModel.php";

class UserController
{
    private function volverGuardar($error, $cedula, $nombre, $apellido, $correo, $usuario, $rol = "")
    {
        header("Location: /mi_garita/index.php?url=user/crear&error=$error&cedula=$cedula&nombre=$nombre&apellido=$apellido&correo=$correo&usuario=$usuario&rol=$rol");
        exit;
    }

    public function __construct()
    {
        if (!isset($_SESSION['id'])) {
            header("Location: /mi_garita/index.php?url=login/index");
            exit;
        }
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
        if ($_SESSION['rol'] !== 'superusuario') {
            header("Location: /mi_garita/index.php?url=user/index&error=sin_permisos");
            exit;
        }

        require __DIR__ . "/../view/user/crear.php";
    }

    public function guardar()
    {
        if ($_SESSION['rol'] !== 'superusuario') {
            header("Location: /mi_garita/index.php?url=user/index&error=sin_permisos");
            exit;
        }

        $cedula   = trim($_POST["cedula"] ?? "");
        $nombre   = trim($_POST["nombre"] ?? "");
        $apellido = trim($_POST["apellido"] ?? "");
        $correo   = trim($_POST["correo"] ?? "");
        $usuario  = trim($_POST["usuario"] ?? "");
        $password = $_POST["password"] ?? "";
        $rol      = trim($_POST["rol"] ?? "");

        $userModel = new UserModel();

        // === VALIDACIONES ===
        if ($cedula === "") {
            $this->volverGuardar("cedula_vacia", $cedula, $nombre, $apellido, $correo, $usuario, $rol);
        }

        if (!preg_match('/^[0-9]+$/', $cedula)) {
            $this->volverGuardar("cedula_invalida", $cedula, $nombre, $apellido, $correo, $usuario, $rol);
        }

        if ($userModel->getByCedula($cedula)) {
            $this->volverGuardar("cedula_duplicada", $cedula, $nombre, $apellido, $correo, $usuario, $rol);
        }

        if ($nombre === "") {
            $this->volverGuardar("nombre_vacio", $cedula, $nombre, $apellido, $correo, $usuario, $rol);
        }

        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/', $nombre)) {
            $this->volverGuardar("nombre_invalido", $cedula, $nombre, $apellido, $correo, $usuario, $rol);
        }

        if ($apellido === "") {
            $this->volverGuardar("apellido_vacio", $cedula, $nombre, $apellido, $correo, $usuario, $rol);
        }

        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/', $apellido)) {
            $this->volverGuardar("apellido_invalido", $cedula, $nombre, $apellido, $correo, $usuario, $rol);
        }

        if ($correo === "") {
            $this->volverGuardar("correo_vacio", $cedula, $nombre, $apellido, $correo, $usuario, $rol);
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->volverGuardar("correo_invalido", $cedula, $nombre, $apellido, $correo, $usuario, $rol);
        }

        if ($userModel->getByCorreo($correo)) {
            $this->volverGuardar("correo_duplicado", $cedula, $nombre, $apellido, $correo, $usuario, $rol);
        }

        if ($usuario === "") {
            $this->volverGuardar("usuario_vacio", $cedula, $nombre, $apellido, $correo, $usuario, $rol);
        }

        if ($userModel->getByUsuario($usuario)) {
            $this->volverGuardar("usuario_duplicado", $cedula, $nombre, $apellido, $correo, $usuario, $rol);
        }

        if ($password === "") {
            $this->volverGuardar("password_vacio", $cedula, $nombre, $apellido, $correo, $usuario, $rol);
        }

        if (strlen($password) < 6) {
            $this->volverGuardar("password_corto", $cedula, $nombre, $apellido, $correo, $usuario, $rol);
        }

        if ($rol === "") {
            $this->volverGuardar("rol_vacio", $cedula, $nombre, $apellido, $correo, $usuario, $rol);
        }

        if ($rol !== "usuario" && $rol !== "superusuario") {
            $this->volverGuardar("rol_invalido", $cedula, $nombre, $apellido, $correo, $usuario, $rol);
        }

        // === GUARDAR ===
        $data = [
            "cedula"        => $cedula,
            "nombre"        => $nombre,
            "apellido"      => $apellido,
            "correo"        => $correo,
            "usuario"       => $usuario,
            "password_hash" => hash('sha256', $password),
            "rol"           => $rol
        ];

        $userModel->insert($data);

        header("Location: /mi_garita/index.php?url=user/index&ok=creado");
        exit;
    }

    public function editar($id)
    {
        if ($_SESSION['id'] != $id && $_SESSION['rol'] !== 'superusuario') {
            header("Location: /mi_garita/index.php?url=user/index&error=sin_permisos");
            exit;
        }

        $userModel = new UserModel();
        $user = $userModel->getById($id);

        require __DIR__ . "/../view/user/editar.php";
    }

    public function actualizar($id)
    {
        if ($_SESSION['id'] != $id && $_SESSION['rol'] !== 'superusuario') {
            header("Location: /mi_garita/index.php?url=user/index&error=sin_permisos");
            exit;
        }

        $userModel = new UserModel();

        // === VALIDACIONES ===
        if (empty($_POST["cedula"])) {
            header("Location: /mi_garita/index.php?url=user/editar/$id&error=cedula_vacia");
            exit;
        }

        if (!ctype_digit($_POST["cedula"])) {
            header("Location: /mi_garita/index.php?url=user/editar/$id&error=cedula_invalida");
            exit;
        }

        $cedulaIngresada = $_POST["cedula"];
        $existe = $userModel->getByCedula($cedulaIngresada);

        if ($existe && $existe["id"] != $id) {
            header("Location: /mi_garita/index.php?url=user/editar/$id&error=cedula_duplicada");
            exit;
        }

        if (empty(trim($_POST["nombre"]))) {
            header("Location: /mi_garita/index.php?url=user/editar/$id&error=nombre_vacio");
            exit;
        }

        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/', $_POST["nombre"])) {
            header("Location: /mi_garita/index.php?url=user/editar/$id&error=nombre_invalido");
            exit;
        }

        if (empty(trim($_POST["apellido"]))) {
            header("Location: /mi_garita/index.php?url=user/editar/$id&error=apellido_vacio");
            exit;
        }

        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/', $_POST["apellido"])) {
            header("Location: /mi_garita/index.php?url=user/editar/$id&error=apellido_invalido");
            exit;
        }

        if (empty($_POST["correo"])) {
            header("Location: /mi_garita/index.php?url=user/editar/$id&error=correo_vacio");
            exit;
        }

        if (!filter_var($_POST["correo"], FILTER_VALIDATE_EMAIL)) {
            header("Location: /mi_garita/index.php?url=user/editar/$id&error=correo_invalido");
            exit;
        }

        $correoExiste = $userModel->getByCorreo($_POST["correo"]);
        if ($correoExiste && $correoExiste["id"] != $id) {
            header("Location: /mi_garita/index.php?url=user/editar/$id&error=correo_duplicado");
            exit;
        }

        if (empty($_POST["usuario"])) {
            header("Location: /mi_garita/index.php?url=user/editar/$id&error=usuario_vacio");
            exit;
        }

        $usuarioExiste = $userModel->getByUsuario($_POST["usuario"]);
        if ($usuarioExiste && $usuarioExiste["id"] != $id) {
            header("Location: /mi_garita/index.php?url=user/editar/$id&error=usuario_duplicado");
            exit;
        }

        if (!empty($_POST["password"]) && strlen($_POST["password"]) < 6) {
            header("Location: /mi_garita/index.php?url=user/editar/$id&error=password_corto");
            exit;
        }

        // DATOS LISTOS
        $data = [
            "cedula"   => $_POST["cedula"],
            "nombre"   => $_POST["nombre"],
            "apellido" => $_POST["apellido"],
            "correo"   => $_POST["correo"],
            "usuario"  => $_POST["usuario"]
        ];

        if (!empty($_POST["password"])) {
            $data["password_hash"] = hash('sha256', $_POST["password"]);
        }

        if ($_SESSION['rol'] === 'superusuario' && isset($_POST["rol"])) {
            $rol = trim($_POST["rol"]);
            
            if ($rol !== "usuario" && $rol !== "superusuario") {
                header("Location: /mi_garita/index.php?url=user/editar/$id&error=rol_invalido");
                exit;
            }
            
            if ($_SESSION['id'] == $id && $_SESSION['rol'] === 'superusuario') {
                $usuarioActual = $userModel->getById($id);
                if ($usuarioActual['rol'] === 'superusuario' && $rol !== 'superusuario') {
                    header("Location: /mi_garita/index.php?url=user/editar/$id&error=no_auto_cambiar_rol");
                    exit;
                }
            }
            
            $data["rol"] = $rol;
        }

        // ACTUALIZAR
        $userModel->update($id, $data);

        header("Location: /mi_garita/index.php?url=user/index&ok=editado");
        exit;
    }

    public function eliminarAjax()
    {
        if ($_SESSION['rol'] !== 'superusuario') {
            echo json_encode(["error" => "sin_permisos"]);
            return;
        }

        if (!isset($_POST['id'])) {
            echo json_encode(["error" => "id_no_recibido"]);
            return;
        }

        $id = $_POST['id'];

        if ($_SESSION['id'] == $id) {
            echo json_encode(["error" => "auto_eliminar"]);
            return;
        }

        // ELIMINAR
        $userModel = new UserModel();
        $userModel->delete($id);

        echo json_encode(["ok" => true]);
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