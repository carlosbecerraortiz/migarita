<?php
require_once "model/UserModel.php";
require_once "model/BitacoraModel.php";

class LoginController
{
    public function index()
    {
        require __DIR__ . "/../view/login/login.php";
    }

    public function autenticar()
    {
        $userModel = new UserModel();
        $bitacora = new BitacoraModel();

        $usuario = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $user = $userModel->getByUsuario($usuario);

        $ip  = $_SERVER["REMOTE_ADDR"];
        $ua  = $_SERVER["HTTP_USER_AGENT"];
        $so  = $this->detectarSO($ua);

        if (!$user) {
            $bitacora->registrarLogin(
                null,
                $ip,
                $ua,
                $so,
                0
            );

            header("Location: /mi_garita/index.php?url=login/index&error=usuario");
            exit;
        }

        if (hash('sha256', $password) !== $user['password_hash']) {

            $bitacora->registrarLogin(
                $user["id"],
                $ip,
                $ua,
                $so,
                0
            );

            header("Location: /mi_garita/index.php?url=login/index&error=contrasena");
            exit;
        }

        session_start();
        $_SESSION['id']      = $user['id'];
        $_SESSION['usuario'] = $user['usuario'];
        $_SESSION['nombre']  = $user['nombre'];
        $_SESSION['rol']     = $user['rol']; // ← AGREGADO

        $bitacora->registrarLogin(
            $user["id"],
            $ip,
            $ua,
            $so,
            1
        );

        header("Location: /mi_garita/index.php?url=cliente/index");
        exit;
    }

    private function detectarSO($userAgent)
    {
        $sistemas = [
            'Windows' => 'Windows',
            'Mac' => 'Mac OS',
            'Linux' => 'Linux',
            'Android' => 'Android',
            'iPhone' => 'iOS'
        ];

        foreach ($sistemas as $key => $value) {
            if (stripos($userAgent, $key) !== false) {
                return $value;
            }
        }

        return "Desconocido";
    }
}