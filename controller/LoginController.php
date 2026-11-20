<?php
require_once __DIR__ . "/../model/UserModel.php";
require_once __DIR__ . "/../model/BitacoraModel.php";
require_once __DIR__ . "/../app/services/AuthService.php";
require_once __DIR__ . "/../app/services/Flash.php";

class LoginController
{
    public function index()
    {
        // Si ya hay sesión, ir directo al panel
        if (isset($_SESSION['id'])) {
            header("Location: /mi_garita/index.php?url=inicio/index");
            exit;
        }

        require __DIR__ . "/../view/login/login.php";
    }

    public function autenticar()
    {
        $auth = new AuthService(new UserModel(), new BitacoraModel());

        $resultado = $auth->autenticar(
            (string)($_POST['username'] ?? ''),
            (string)($_POST['password'] ?? ''),
            $_SERVER["REMOTE_ADDR"] ?? '',
            $_SERVER["HTTP_USER_AGENT"] ?? ''
        );

        if (!$resultado['ok']) {
            Flash::guardar(['error' => $resultado['error']]);
            header("Location: /mi_garita/index.php?url=login/index");
            exit;
        }

        $user = $resultado['usuario'];

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_regenerate_id(true);

        $_SESSION['id']      = $user['id'];
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['nombre']  = $user['nombre'];
        $_SESSION['rol']     = $user['rol'];

        header("Location: /mi_garita/index.php?url=inicio/index");
        exit;
    }
}
