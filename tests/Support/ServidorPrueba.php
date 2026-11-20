<?php

namespace Tests\Support;

use PDO;
use RuntimeException;

/**
 * Levanta la app REAL con el servidor integrado de PHP (php -S) contra una base
 * SQLite temporal, y ofrece un cliente HTTP mínimo con manejo de cookie de sesión.
 *
 * Se usa en las pruebas funcionales (tests/Functional) y en el E2E (Playwright).
 */
final class ServidorPrueba
{
    /** @var resource|null */
    private $proceso = null;
    private array $tuberias = [];
    private string $baseUrl = '';
    private string $rutaBd;
    private ?string $cookieSesion = null;
    /** Token CSRF de la sesión, tomado de <meta name="csrf-token"> como lo haría el navegador. */
    private ?string $csrf = null;
    public PDO $bd;

    public function __construct()
    {
        $this->rutaBd = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'migarita_funcional_' . getmypid() . '.sqlite';
    }

    public function iniciar(): void
    {
        $this->bd = BaseDatosPrueba::enArchivo($this->rutaBd);
        BaseDatosPrueba::sembrarUsuarios($this->bd);

        $puerto = self::puertoLibre();
        $this->baseUrl = "http://127.0.0.1:$puerto";
        $raiz = dirname(__DIR__, 2);

        $comando = [
            PHP_BINARY,
            '-d', 'display_errors=stderr',
            '-d', 'session.save_path=' . sys_get_temp_dir(),
            '-S', "127.0.0.1:$puerto",
            '-t', $raiz,
            __DIR__ . DIRECTORY_SEPARATOR . 'router.php',
        ];

        $entorno = getenv() + [];
        $entorno['MIGARITA_DB_DSN'] = 'sqlite:' . $this->rutaBd;
        $entorno['MIGARITA_MAIL_ENABLED'] = '0';
        $entorno['XDEBUG_MODE'] = 'off';

        $descriptores = [
            0 => ['pipe', 'r'],
            1 => ['file', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'migarita_servidor.log', 'a'],
            2 => ['file', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'migarita_servidor.log', 'a'],
        ];

        $this->proceso = proc_open($comando, $descriptores, $this->tuberias, $raiz, $entorno, ['bypass_shell' => true]);

        if (!is_resource($this->proceso)) {
            throw new RuntimeException('No se pudo iniciar el servidor de pruebas.');
        }

        $this->esperarQueResponda($puerto);
    }

    public function detener(): void
    {
        if (is_resource($this->proceso)) {
            foreach ($this->tuberias as $t) {
                if (is_resource($t)) {
                    fclose($t);
                }
            }
            proc_terminate($this->proceso);
            proc_close($this->proceso);
            $this->proceso = null;
        }

        unset($this->bd);
        gc_collect_cycles();
        if (is_file($this->rutaBd)) {
            @unlink($this->rutaBd);
        }
    }

    public function cerrarSesion(): void
    {
        $this->cookieSesion = null;
        $this->csrf = null;
    }

    public function tokenCsrf(): ?string
    {
        return $this->csrf;
    }

    public function get(string $url): RespuestaHttp
    {
        return $this->enviar('GET', $url);
    }

    /** POST como el navegador: incluye el token CSRF de la sesión. */
    public function post(string $url, array $campos = []): RespuestaHttp
    {
        if ($this->csrf === null) {
            $this->get('/mi_garita/index.php?url=login/index'); // obtener sesión y token
        }

        return $this->enviar('POST', $url, $campos + ['_csrf' => $this->csrf]);
    }

    /** POST SIN token CSRF (para comprobar que se rechaza). */
    public function postSinCsrf(string $url, array $campos = [], array $cabecerasExtra = []): RespuestaHttp
    {
        return $this->enviar('POST', $url, $campos, $cabecerasExtra);
    }

    public function iniciarSesionComo(string $usuario, string $password): RespuestaHttp
    {
        return $this->post('/mi_garita/index.php?url=login/autenticar', [
            'username' => $usuario,
            'password' => $password,
        ]);
    }

    private function enviar(string $metodo, string $url, array $campos = [], array $cabecerasExtra = []): RespuestaHttp
    {
        $curl = curl_init($this->baseUrl . $url);
        $cabeceras = [];

        $opciones = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) PruebasFuncionales',
            CURLOPT_HEADERFUNCTION => function ($ch, string $linea) use (&$cabeceras) {
                if (strpos($linea, ':') !== false) {
                    [$nombre, $valor] = explode(':', $linea, 2);
                    $cabeceras[strtolower(trim($nombre))][] = trim($valor);
                }
                return strlen($linea);
            },
        ];

        if ($this->cookieSesion !== null) {
            $opciones[CURLOPT_COOKIE] = $this->cookieSesion;
        }

        if ($metodo === 'POST') {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($campos);
        }

        if ($cabecerasExtra) {
            $opciones[CURLOPT_HTTPHEADER] = $cabecerasExtra;
        }

        curl_setopt_array($curl, $opciones);
        $cuerpo = curl_exec($curl);

        if ($cuerpo === false) {
            throw new RuntimeException('Error HTTP: ' . curl_error($curl));
        }

        $estado = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        // Guardar la cookie de sesión más reciente (cambia al iniciar sesión).
        foreach ($cabeceras['set-cookie'] ?? [] as $cookie) {
            if (stripos($cookie, 'PHPSESSID=') === 0) {
                $this->cookieSesion = explode(';', $cookie)[0];
            }
        }

        // Recordar el token CSRF que imprime la página (igual que lo lee app.js)
        if (preg_match('/<meta name="csrf-token" content="([a-f0-9]+)"/', (string)$cuerpo, $m)) {
            $this->csrf = $m[1];
        }

        return new RespuestaHttp($estado, $cabeceras, $cuerpo);
    }

    private static function puertoLibre(): int
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $nombre = stream_socket_get_name($socket, false);
        fclose($socket);

        return (int)substr($nombre, strrpos($nombre, ':') + 1);
    }

    private function esperarQueResponda(int $puerto): void
    {
        $limite = microtime(true) + 10;

        while (microtime(true) < $limite) {
            $conexion = @fsockopen('127.0.0.1', $puerto, $errno, $errstr, 0.2);
            if ($conexion) {
                fclose($conexion);
                return;
            }
            usleep(100000);
        }

        $this->detener();
        throw new RuntimeException("El servidor de pruebas no respondió en el puerto $puerto.");
    }
}

/** Respuesta HTTP simple para las aserciones. */
final class RespuestaHttp
{
    public function __construct(
        public readonly int $estado,
        public readonly array $cabeceras,
        public readonly string $cuerpo
    ) {
    }

    public function cabecera(string $nombre): ?string
    {
        return $this->cabeceras[strtolower($nombre)][0] ?? null;
    }

    /** Valor de la cabecera Location (a dónde redirige la app). */
    public function redirigeA(): ?string
    {
        return $this->cabecera('location');
    }

    public function json(): mixed
    {
        return json_decode($this->cuerpo, true);
    }
}
