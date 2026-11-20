<?php
require_once __DIR__ . '/../../model/ClienteModel.php';
require_once __DIR__ . '/../../model/UserModel.php';
require_once __DIR__ . '/../validators/MovimientoValidator.php';
require_once __DIR__ . '/EmailService.php';

/**
 * Registro de movimientos (fiados y abonos) y notificación por correo.
 *
 * Dependencias inyectadas: ClienteModel (BD), UserModel (BD) y EmailService
 * (servidor SMTP externo). En las pruebas unitarias las tres se reemplazan por
 * mocks, de modo que no se toca la base de datos ni se envían correos reales.
 */
class MovimientoService
{
    private ClienteModel $clientes;
    private UserModel $usuarios;
    private EmailService $email;

    public function __construct(ClienteModel $clientes, UserModel $usuarios, EmailService $email)
    {
        $this->clientes = $clientes;
        $this->usuarios = $usuarios;
        $this->email = $email;
    }

    /**
     * @return string|null Código de error, o null si se registró el movimiento.
     */
    public function registrar($idCliente, $idUsuario, array $input): ?string
    {
        $datos = MovimientoValidator::normalizar($input);

        $error = MovimientoValidator::validar($datos);
        if ($error !== null) {
            return $error;
        }

        $saldoActual = $this->clientes->getSaldo($idCliente);

        if (MovimientoValidator::abonoSuperaSaldo($datos['tipo'], $datos['monto'], $saldoActual)) {
            return 'abono_mayor';
        }

        $monto = MovimientoValidator::montoConSigno($datos['tipo'], $datos['monto']);

        $this->clientes->insertMovimiento(
            $idCliente,
            $idUsuario,
            $datos['descripcion'],
            $monto,
            $datos['metodo_pago']
        );

        $this->notificar($idCliente, $idUsuario, $datos['tipo'], $monto, $datos['descripcion']);

        return null;
    }

    /**
     * Si el correo falla, el movimiento YA quedó guardado: solo se registra el error.
     */
    private function notificar($idCliente, $idUsuario, string $tipo, float $monto, string $descripcion): void
    {
        try {
            $cliente = $this->clientes->getById($idCliente);
            $usuario = $this->usuarios->getById($idUsuario);
            $usuarioNombre = $usuario['nombre'] . ' ' . $usuario['apellido'];

            $this->email->enviarNotificacionMovimiento($cliente, [
                'tipo'        => $tipo,
                'monto'       => $monto,
                'descripcion' => $descripcion,
                'fecha'       => date('Y-m-d H:i:s'),
            ], $usuarioNombre);
        } catch (Throwable $e) {
            error_log("Error al enviar correo de notificación: " . $e->getMessage());
        }
    }
}
