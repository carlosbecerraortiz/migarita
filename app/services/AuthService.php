<?php
require_once __DIR__ . '/../../model/UserModel.php';
require_once __DIR__ . '/../../model/BitacoraModel.php';

/**
 * Autenticación de usuarios y registro en la bitácora de accesos.
 * Cada intento (exitoso o fallido) queda registrado con IP, navegador y SO.
 */
class AuthService
{
    private UserModel $usuarios;
    private BitacoraModel $bitacora;

    public function __construct(UserModel $usuarios, BitacoraModel $bitacora)
    {
        $this->usuarios = $usuarios;
        $this->bitacora = $bitacora;
    }

    /** Hash que usa la app para las contraseñas (SHA-256, igual que en la BD actual). */
    public static function hashPassword(string $password): string
    {
        return hash('sha256', $password);
    }

    /**
     * @return array{ok: bool, error?: string, usuario?: array}
     *         error = "usuario" (no existe) | "contrasena" (clave incorrecta)
     */
    public function autenticar(string $usuario, string $password, string $ip, string $userAgent): array
    {
        $so = self::detectarSO($userAgent);
        $user = $this->usuarios->getByUsuario($usuario);

        if (!$user) {
            $this->bitacora->registrarLogin(null, $ip, $userAgent, $so, 0);
            return ['ok' => false, 'error' => 'usuario'];
        }

        if (!hash_equals((string)$user['password_hash'], self::hashPassword($password))) {
            $this->bitacora->registrarLogin($user['id'], $ip, $userAgent, $so, 0);
            return ['ok' => false, 'error' => 'contrasena'];
        }

        $this->bitacora->registrarLogin($user['id'], $ip, $userAgent, $so, 1);

        return ['ok' => true, 'usuario' => $user];
    }

    /** Detecta el sistema operativo a partir del User-Agent del navegador. */
    public static function detectarSO(string $userAgent): string
    {
        // El orden importa: un User-Agent de Android también contiene "Linux"
        // y uno de iPhone contiene "Mac OS X". Por eso los móviles van primero.
        // (Defecto real encontrado al escribir las pruebas: antes Android se
        // registraba como "Linux" y iPhone como "Mac OS".)
        $sistemas = [
            'Android' => 'Android',
            'iPhone'  => 'iOS',
            'iPad'    => 'iOS',
            'Windows' => 'Windows',
            'Mac'     => 'Mac OS',
            'Linux'   => 'Linux',
        ];

        foreach ($sistemas as $clave => $nombre) {
            if (stripos($userAgent, $clave) !== false) {
                return $nombre;
            }
        }

        return 'Desconocido';
    }
}
