<?php
require_once __DIR__ . '/../../model/UserModel.php';
require_once __DIR__ . '/../../model/BitacoraModel.php';

/**
 * Autenticación de usuarios y registro en la bitácora de accesos.
 *
 * Seguridad:
 *  - Contraseñas con password_hash() (bcrypt, con sal aleatoria). Las cuentas
 *    con el hash antiguo (SHA-256 sin sal) se migran solas al iniciar sesión.
 *  - Freno de fuerza bruta: tras MAX_INTENTOS fallidos desde una IP en
 *    MINUTOS_BLOQUEO minutos, se rechazan los intentos de esa IP.
 *  - Tiempo de respuesta similar exista o no el usuario (no revela usuarios).
 */
class AuthService
{
    public const MAX_INTENTOS = 5;
    public const MINUTOS_BLOQUEO = 15;

    /** Hash de relleno para comparar cuando el usuario no existe (mismo costo de tiempo). */
    private const HASH_RELLENO = '$2y$10$YqYH2ux/.Vq5JNKaKhuENu3ynEZ6BZ.sH3KxV6NTPaCVFUd3vPz.C';

    private UserModel $usuarios;
    private BitacoraModel $bitacora;

    public function __construct(UserModel $usuarios, BitacoraModel $bitacora)
    {
        $this->usuarios = $usuarios;
        $this->bitacora = $bitacora;
    }

    /** Hash seguro para guardar una contraseña (bcrypt con sal aleatoria). */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /** ¿Es un hash del sistema anterior (SHA-256 sin sal, 64 caracteres hexadecimales)? */
    public static function esHashAntiguo(string $hash): bool
    {
        return (bool)preg_match('/^[a-f0-9]{64}$/', $hash);
    }

    /** Verifica una contraseña contra un hash nuevo (bcrypt) o antiguo (SHA-256). */
    public static function verificar(string $password, string $hash): bool
    {
        if (self::esHashAntiguo($hash)) {
            return hash_equals($hash, hash('sha256', $password));
        }

        return password_verify($password, $hash);
    }

    /**
     * @return array{ok: bool, error?: string, usuario?: array}
     *         error = "usuario" (no existe) | "contrasena" (clave incorrecta) | "bloqueado" (demasiados intentos)
     */
    public function autenticar(string $usuario, string $password, string $ip, string $userAgent): array
    {
        $so = self::detectarSO($userAgent);

        $desde = date('Y-m-d H:i:s', time() - self::MINUTOS_BLOQUEO * 60);
        if ($this->bitacora->contarFallidosRecientes($ip, $desde) >= self::MAX_INTENTOS) {
            $this->bitacora->registrarLogin(null, $ip, $userAgent, $so, 0);
            return ['ok' => false, 'error' => 'bloqueado'];
        }

        $user = $this->usuarios->getByUsuario($usuario);

        if (!$user) {
            password_verify($password, self::HASH_RELLENO); // mismo tiempo de respuesta
            $this->bitacora->registrarLogin(null, $ip, $userAgent, $so, 0);
            return ['ok' => false, 'error' => 'usuario'];
        }

        $hash = (string)$user['password_hash'];

        if (!self::verificar($password, $hash)) {
            $this->bitacora->registrarLogin($user['id'], $ip, $userAgent, $so, 0);
            return ['ok' => false, 'error' => 'contrasena'];
        }

        // Migración transparente: si el hash es antiguo o débil, se guarda uno nuevo
        if (self::esHashAntiguo($hash) || password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            $this->usuarios->update($user['id'], ['password_hash' => self::hashPassword($password)]);
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
