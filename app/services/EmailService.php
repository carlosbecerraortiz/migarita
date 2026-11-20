<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envío de correos de notificación al cliente.
 *
 * Design for Testability:
 *  - $config se puede inyectar (si no, se lee app/config/email_config.php).
 *  - $mailerFactory crea el objeto PHPMailer; en las pruebas se inyecta una
 *    fábrica que devuelve un MOCK, así nunca se conecta a un servidor SMTP real.
 *  - Si no hay configuración, o MIGARITA_MAIL_ENABLED=0, no se envía nada.
 */
class EmailService
{
    private $config;
    private $mailerFactory;

    public function __construct(?array $config = null, ?callable $mailerFactory = null)
    {
        if ($config === null) {
            $archivo = __DIR__ . '/../config/email_config.php';
            $config = is_file($archivo) ? require $archivo : null;
        }

        $this->config = $config;
        $this->mailerFactory = $mailerFactory ?? fn() => new PHPMailer(true);
    }

    public function estaHabilitado(): bool
    {
        return is_array($this->config)
            && !empty($this->config['host'])
            && getenv('MIGARITA_MAIL_ENABLED') !== '0';
    }

    public function enviarNotificacionMovimiento($datosCliente, $datosMovimiento, $usuarioNombre)
    {
        if (!$this->estaHabilitado()) {
            return false;
        }

        $mail = ($this->mailerFactory)();

        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host       = $this->config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['username'];
            $mail->Password   = $this->config['password'];
            $mail->SMTPSecure = $this->config['smtp_secure'];
            $mail->Port       = $this->config['port'];
            $mail->CharSet    = 'UTF-8';

            // Remitente y destinatario
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $mail->addAddress($datosCliente['correo'], $datosCliente['nombre'] . ' ' . $datosCliente['apellido']);

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Notificación de Movimiento - MiGarita';
            
            // Generar el cuerpo del correo
            $mail->Body = $this->generarPlantillaHTML($datosCliente, $datosMovimiento, $usuarioNombre);
            
            // Versión en texto plano (por si el cliente no soporta HTML)
            $mail->AltBody = $this->generarPlantillaTexto($datosCliente, $datosMovimiento, $usuarioNombre);

            // Enviar
            $mail->send();
            return true;

        } catch (Exception $e) {
            // Log del error
            error_log("Error al enviar correo: {$mail->ErrorInfo}");
            return false;
        }
    }
  
    private function generarPlantillaHTML($datosCliente, $datosMovimiento, $usuarioNombre)
    {
        // Los datos escritos por usuarios se escapan: el correo es HTML y un nombre o
        // descripción con etiquetas no debe poder insertar enlaces o contenido.
        $html = fn($texto) => htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');

        $nombreCliente = $html($datosCliente['nombre'] . ' ' . $datosCliente['apellido']);
        $usuarioNombre = $html($usuarioNombre);
        $tipoMovimiento = $datosMovimiento['tipo'] === 'fiado' ? 'FIADO' : 'ABONO';
        $fecha = date('d/m/Y', strtotime($datosMovimiento['fecha']));
        $hora = date('h:i A', strtotime($datosMovimiento['fecha']));
        $monto = number_format(abs($datosMovimiento['monto']), 0, ',', '.');
        $descripcion = $html($datosMovimiento['descripcion']);

        // Color según tipo de movimiento
        $color = $datosMovimiento['tipo'] === 'fiado' ? '#d9534f' : '#5cb85c';
        $colorClaro = $datosMovimiento['tipo'] === 'fiado' ? '#f8d7da' : '#d4edda';

        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 20px;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #ffffff;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                }
                .header {
                    background-color: {$color};
                    color: white;
                    padding: 30px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                }
                .content {
                    padding: 30px;
                }
                .greeting {
                    font-size: 16px;
                    color: #333;
                    margin-bottom: 20px;
                    line-height: 1.6;
                }
                .details {
                    background-color: {$colorClaro};
                    border-left: 4px solid {$color};
                    padding: 20px;
                    margin: 20px 0;
                }
                .detail-row {
                    display: flex;
                    padding: 10px 0;
                    border-bottom: 1px solid #ddd;
                }
                .detail-row:last-child {
                    border-bottom: none;
                }
                .detail-label {
                    font-weight: bold;
                    color: #555;
                    width: 180px;
                }
                .detail-value {
                    color: #333;
                    flex: 1;
                }
                .monto {
                    font-size: 20px;
                    font-weight: bold;
                    color: {$color};
                }
                .footer {
                    background-color: #f8f9fa;
                    padding: 20px;
                    text-align: center;
                    color: #666;
                    font-size: 12px;
                }
                .logo {
                    font-size: 28px;
                    font-weight: bold;
                    color: white;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>🏪 MiGarita</div>
                    <h1>Notificación de {$tipoMovimiento}</h1>
                </div>
                
                <div class='content'>
                    <div class='greeting'>
                        ¡Hola <strong>{$nombreCliente}</strong>! realizaste un <strong>{$tipoMovimiento}</strong> en MiGarita. 
                        Aquí están los datos de tu movimiento:
                    </div>
                    
                    <div class='details'>
                        <div class='detail-row'>
                            <div class='detail-label'>📅 Fecha:</div>
                            <div class='detail-value'>{$fecha}</div>
                        </div>
                        <div class='detail-row'>
                            <div class='detail-label'>🕐 Hora:</div>
                            <div class='detail-value'>{$hora}</div>
                        </div>
                        <div class='detail-row'>
                            <div class='detail-label'>👤 Usuario que atendió:</div>
                            <div class='detail-value'>{$usuarioNombre}</div>
                        </div>
                        <div class='detail-row'>
                            <div class='detail-label'>📝 Descripción:</div>
                            <div class='detail-value'>{$descripcion}</div>
                        </div>
                        <div class='detail-row'>
                            <div class='detail-label'>💰 Monto:</div>
                            <div class='detail-value monto'>$ {$monto}</div>
                        </div>
                    </div>
                    
                    <p style='color: #666; font-size: 14px; margin-top: 20px;'>
                        Si tienes alguna pregunta sobre este movimiento, no dudes en contactarnos.
                    </p>
                </div>
                
                <div class='footer'>
                    <p>© MiGarita. Todos los derechos reservados.</p>
                    <p>Este es un correo automático, por favor no responder.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function generarPlantillaTexto($datosCliente, $datosMovimiento, $usuarioNombre)
    {
        $nombreCliente = $datosCliente['nombre'] . ' ' . $datosCliente['apellido'];
        $tipoMovimiento = $datosMovimiento['tipo'] === 'fiado' ? 'FIADO' : 'ABONO';
        $fecha = date('d/m/Y', strtotime($datosMovimiento['fecha']));
        $hora = date('h:i A', strtotime($datosMovimiento['fecha']));
        $monto = number_format(abs($datosMovimiento['monto']), 0, ',', '.');
        $descripcion = $datosMovimiento['descripcion'];

        return "
¡Hola {$nombreCliente}! realizaste un {$tipoMovimiento} en MiGarita. Aquí están los datos de tu movimiento:

Fecha: {$fecha}
Hora: {$hora}
Usuario que atendió: {$usuarioNombre}
Descripción: {$descripcion}
Monto: $ {$monto}

Si tienes alguna pregunta sobre este movimiento, no dudes en contactarnos.

© MiGarita. Todos los derechos reservados.
Este es un correo automático, por favor no responder.
        ";
    }
}