<?php
require_once __DIR__ . '/../../model/ClienteModel.php';
require_once __DIR__ . '/../validators/ClienteValidator.php';

/**
 * Casos de uso de clientes. Recibe el ClienteModel por INYECCIÓN DE DEPENDENCIAS,
 * así en las pruebas unitarias se reemplaza por un mock y en las de integración
 * por un modelo conectado a SQLite en memoria.
 */
class ClienteService
{
    private ClienteModel $clientes;

    public function __construct(ClienteModel $clientes)
    {
        $this->clientes = $clientes;
    }

    /** @return string|null Código de error, o null si se creó. */
    public function crear(array $input): ?string
    {
        $datos = ClienteValidator::normalizar($input);

        $error = ClienteValidator::validar(
            $datos,
            fn(string $cedula) => (bool)$this->clientes->getByCedula($cedula)
        );

        if ($error !== null) {
            return $error;
        }

        $this->clientes->insert($datos);

        return null;
    }

    /** @return string|null Código de error, o null si se actualizó. */
    public function actualizar($id, array $input): ?string
    {
        $datos = ClienteValidator::normalizar($input);

        $error = ClienteValidator::validar($datos, function (string $cedula) use ($id) {
            $existe = $this->clientes->getByCedula($cedula);
            // Es duplicada solo si la cédula pertenece a OTRO cliente.
            return $existe && $existe['id'] != $id;
        });

        if ($error !== null) {
            return $error;
        }

        $this->clientes->update($id, $datos);

        return null;
    }

    /**
     * Regla de negocio: un cliente solo se puede eliminar si su saldo es 0.
     *
     * @return array{ok: bool, error?: string, saldo?: mixed}
     */
    public function eliminar($id): array
    {
        $cliente = $this->clientes->getById($id);

        if (!$cliente) {
            return ['ok' => false, 'error' => 'notfound'];
        }

        $saldo = $this->clientes->getSaldo($id);

        if ($saldo != 0) {
            return ['ok' => false, 'error' => 'saldo', 'saldo' => $saldo];
        }

        $this->clientes->delete($id);

        return ['ok' => true];
    }

    /** Suma de los saldos de una lista de clientes (total general de la pantalla). */
    public static function totalGeneral(array $clientes): float
    {
        $total = 0.0;
        foreach ($clientes as $c) {
            $total += (float)($c['saldo'] ?? 0);
        }

        return $total;
    }
}
