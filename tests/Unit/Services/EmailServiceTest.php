<?php

namespace Tests\Unit\Services;

use EmailService;
use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas UNITARIAS con MOCK de PHPMailer (la API externa de correo).
 * Se inyecta una fábrica que devuelve el mock: NUNCA se conecta a un servidor SMTP.
 */
final class EmailServiceTest extends TestCase
{
    private const CONFIG = [
        'host' => 'smtp.prueba.test', 'port' => 587, 'username' => 'bot@migarita.test',
        'password' => 'x', 'from_email' => 'bot@migarita.test', 'from_name' => 'MiGarita', 'smtp_secure' => 'tls',
    ];

    private const CLIENTE = ['nombre' => 'Laura', 'apellido' => 'Gómez', 'correo' => 'laura@correo.test'];

    private PHPMailer&MockObject $mailer;
    private EmailService $servicio;

    protected function setUp(): void
    {
        // El bootstrap desactiva el correo; aquí lo habilitamos porque el envío está simulado.
        putenv('MIGARITA_MAIL_ENABLED=1');

        $this->mailer = $this->createMock(PHPMailer::class);
        $this->servicio = new EmailService(self::CONFIG, fn() => $this->mailer);
    }

    protected function tearDown(): void
    {
        putenv('MIGARITA_MAIL_ENABLED=0');
    }

    #[Test]
    public function envia_el_correo_al_cliente_con_el_detalle_del_abono(): void
    {
        // Arrange (expectativas del mock)
        $this->mailer->expects($this->once())->method('addAddress')->with('laura@correo.test', 'Laura Gómez');
        $this->mailer->expects($this->once())->method('setFrom')->with('bot@migarita.test', 'MiGarita');
        $this->mailer->expects($this->once())->method('send')->willReturn(true);

        $movimiento = ['tipo' => 'abono', 'monto' => -25000.0, 'descripcion' => 'Pago quincena', 'fecha' => '2026-11-20 14:30:00'];

        // Act
        $enviado = $this->servicio->enviarNotificacionMovimiento(self::CLIENTE, $movimiento, 'Carlos Cajero');

        // Assert
        $this->assertTrue($enviado);
        $this->assertSame('Notificación de Movimiento - MiGarita', $this->mailer->Subject);
        $this->assertStringContainsString('ABONO', $this->mailer->Body);
        // El monto se muestra en positivo y con formato colombiano (punto de miles)
        $this->assertStringContainsString('$ 25.000', $this->mailer->Body);
        $this->assertStringNotContainsString('-25', $this->mailer->Body);
        $this->assertStringContainsString('20/11/2026', $this->mailer->AltBody);
        $this->assertStringContainsString('Carlos Cajero', $this->mailer->AltBody);
    }

    #[Test]
    public function el_correo_de_un_fiado_dice_fiado(): void
    {
        $this->mailer->method('send')->willReturn(true);

        $this->servicio->enviarNotificacionMovimiento(
            self::CLIENTE,
            ['tipo' => 'fiado', 'monto' => 1500.0, 'descripcion' => 'Gaseosa', 'fecha' => '2026-11-20 09:00:00'],
            'Carlos Cajero'
        );

        $this->assertStringContainsString('FIADO', $this->mailer->Body);
        $this->assertStringContainsString('$ 1.500', $this->mailer->AltBody);
    }

    #[Test]
    public function si_el_servidor_smtp_falla_devuelve_false_sin_romper_la_app(): void
    {
        // Arrange: PHPMailer lanza su excepción de error SMTP
        $this->mailer->method('send')->willThrowException(new MailerException('SMTP connect() failed'));
        $logAnterior = ini_set('error_log', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null');

        // Act
        $enviado = $this->servicio->enviarNotificacionMovimiento(
            self::CLIENTE,
            ['tipo' => 'fiado', 'monto' => 1000, 'descripcion' => 'X', 'fecha' => '2026-11-20 09:00:00'],
            'Carlos'
        );
        ini_set('error_log', $logAnterior === false ? '' : $logAnterior);

        // Assert
        $this->assertFalse($enviado);
    }

    #[Test]
    public function sin_configuracion_smtp_no_intenta_enviar(): void
    {
        // Arrange: servicio sin config (p. ej. un compañero que clonó el repo sin email_config.php)
        $fabricaLlamada = false;
        $servicio = new EmailService([], function () use (&$fabricaLlamada) {
            $fabricaLlamada = true;
            return $this->mailer;
        });

        // Act
        $enviado = $servicio->enviarNotificacionMovimiento(self::CLIENTE, [], 'X');

        // Assert
        $this->assertFalse($enviado);
        $this->assertFalse($fabricaLlamada);
    }

    #[Test]
    public function con_la_variable_de_entorno_en_cero_no_se_envian_correos(): void
    {
        putenv('MIGARITA_MAIL_ENABLED=0');
        $this->mailer->expects($this->never())->method('send');

        $this->assertFalse($this->servicio->estaHabilitado());
        $this->assertFalse($this->servicio->enviarNotificacionMovimiento(self::CLIENTE, [], 'X'));
    }
}
