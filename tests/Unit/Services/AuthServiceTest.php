<?php

namespace Tests\Unit\Services;

use AuthService;
use BitacoraModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UserModel;

/**
 * Pruebas UNITARIAS con MOCKS de la autenticación.
 * UserModel es un STUB (devuelve el usuario) y BitacoraModel es un SPY
 * (verificamos que cada intento quede registrado con el resultado correcto).
 */
final class AuthServiceTest extends TestCase
{
    private UserModel&MockObject $usuarios;
    private BitacoraModel&MockObject $bitacora;
    private AuthService $auth;

    private const UA_WINDOWS = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120';

    protected function setUp(): void
    {
        $this->usuarios = $this->createMock(UserModel::class);
        $this->bitacora = $this->createMock(BitacoraModel::class);
        $this->auth = new AuthService($this->usuarios, $this->bitacora);
    }

    private function usuarioAdmin(): array
    {
        return ['id' => 1, 'usuario' => 'admin', 'nombre' => 'Ana', 'rol' => 'superusuario',
                'password_hash' => hash('sha256', 'admin123')];
    }

    #[Test]
    public function con_credenciales_correctas_inicia_sesion_y_registra_exito(): void
    {
        // Arrange
        $this->usuarios->method('getByUsuario')->with('admin')->willReturn($this->usuarioAdmin());

        // Assert (spy): intento exitoso (1) del usuario 1 desde Windows
        $this->bitacora->expects($this->once())
            ->method('registrarLogin')
            ->with(1, '10.0.0.5', self::UA_WINDOWS, 'Windows', 1);

        // Act
        $resultado = $this->auth->autenticar('admin', 'admin123', '10.0.0.5', self::UA_WINDOWS);

        // Assert
        $this->assertTrue($resultado['ok']);
        $this->assertSame('superusuario', $resultado['usuario']['rol']);
    }

    #[Test]
    public function con_contrasena_incorrecta_falla_y_registra_intento_fallido(): void
    {
        $this->usuarios->method('getByUsuario')->willReturn($this->usuarioAdmin());
        $this->bitacora->expects($this->once())
            ->method('registrarLogin')
            ->with(1, '10.0.0.5', self::UA_WINDOWS, 'Windows', 0);

        $resultado = $this->auth->autenticar('admin', 'clave-mala', '10.0.0.5', self::UA_WINDOWS);

        $this->assertSame(['ok' => false, 'error' => 'contrasena'], $resultado);
    }

    #[Test]
    public function la_contrasena_distingue_mayusculas_y_minusculas(): void
    {
        $this->usuarios->method('getByUsuario')->willReturn($this->usuarioAdmin());

        $resultado = $this->auth->autenticar('admin', 'ADMIN123', '::1', self::UA_WINDOWS);

        $this->assertFalse($resultado['ok']);
    }

    #[Test]
    public function con_usuario_inexistente_falla_y_registra_intento_sin_id(): void
    {
        $this->usuarios->method('getByUsuario')->willReturn(false);
        $this->bitacora->expects($this->once())
            ->method('registrarLogin')
            ->with(null, '10.0.0.5', self::UA_WINDOWS, 'Windows', 0);

        $resultado = $this->auth->autenticar('fantasma', 'x', '10.0.0.5', self::UA_WINDOWS);

        $this->assertSame(['ok' => false, 'error' => 'usuario'], $resultado);
    }

    #[Test]
    public function el_hash_de_la_contrasena_es_sha256(): void
    {
        $this->assertSame(
            '240be518fabd2724ddb6f04eeb1da5967448d7e831c08c8fa822809f74c720a9',
            AuthService::hashPassword('admin123')
        );
    }

    public static function userAgents(): array
    {
        return [
            'Windows'            => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120', 'Windows'],
            'macOS'              => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 14_1) AppleWebKit/605.1.15 Safari/605', 'Mac OS'],
            'Linux escritorio'   => ['Mozilla/5.0 (X11; Linux x86_64) Firefox/121.0', 'Linux'],
            // Casos borde que antes fallaban (el UA de Android contiene "Linux"
            // y el de iPhone contiene "Mac OS X"):
            'Android'            => ['Mozilla/5.0 (Linux; Android 14; SM-A546E) Chrome/120 Mobile', 'Android'],
            'iPhone'             => ['Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) Safari/604.1', 'iOS'],
            'iPad'               => ['Mozilla/5.0 (iPad; CPU OS 17_1 like Mac OS X) Safari/604.1', 'iOS'],
            'Desconocido'        => ['curl/8.4.0', 'Desconocido'],
            'Vacío'              => ['', 'Desconocido'],
        ];
    }

    #[Test]
    #[DataProvider('userAgents')]
    public function detecta_el_sistema_operativo_del_navegador(string $userAgent, string $esperado): void
    {
        $this->assertSame($esperado, AuthService::detectarSO($userAgent));
    }
}
