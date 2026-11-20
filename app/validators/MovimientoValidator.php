<?php
/**
 * Reglas de negocio de un movimiento (fiado / abono), extraídas de
 * ClienteController::guardarMovimiento. Clase PURA.
 *
 * Reglas:
 *  - La descripción es obligatoria.
 *  - El monto debe ser un número mayor que 0.
 *  - El tipo solo puede ser "fiado" o "abono".
 *  - Un abono exige método de pago.
 *  - Un abono no puede ser mayor que el saldo pendiente del cliente.
 *  - En la BD los fiados se guardan POSITIVOS y los abonos NEGATIVOS,
 *    de modo que el saldo = SUMA(monto).
 */
class MovimientoValidator
{
    public const TIPO_FIADO = 'fiado';
    public const TIPO_ABONO = 'abono';

    public static function normalizar(array $input): array
    {
        return [
            'tipo'        => (string)($input['tipo'] ?? ''),
            'monto'       => trim((string)($input['monto'] ?? '')),
            'descripcion' => trim((string)($input['descripcion'] ?? '')),
            'metodo_pago' => $input['metodo_pago'] ?? null,
        ];
    }

    /** Validaciones de formulario que no dependen del saldo. */
    public static function validar(array $d): ?string
    {
        if ($d['descripcion'] === '') {
            return 'descripcion_vacia';
        }

        if ($d['monto'] === '' || floatval($d['monto']) <= 0) {
            return 'monto_invalido';
        }

        if ($d['tipo'] !== self::TIPO_FIADO && $d['tipo'] !== self::TIPO_ABONO) {
            return 'tipo_invalido';
        }

        if ($d['tipo'] === self::TIPO_ABONO && empty($d['metodo_pago'])) {
            return 'metodo_vacio';
        }

        return null;
    }

    /** Un abono no puede superar lo que el cliente debe. */
    public static function abonoSuperaSaldo(string $tipo, $monto, $saldoActual): bool
    {
        return $tipo === self::TIPO_ABONO && floatval($monto) > floatval($saldoActual);
    }

    /** Fiado => positivo (aumenta la deuda). Abono => negativo (la disminuye). */
    public static function montoConSigno(string $tipo, $monto): float
    {
        $valor = abs(floatval($monto));

        return $tipo === self::TIPO_ABONO ? -$valor : $valor;
    }
}
