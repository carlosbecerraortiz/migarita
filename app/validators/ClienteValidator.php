<?php
/**
 * Reglas de validación de un cliente (extraídas de ClienteController).
 *
 * Es una clase PURA: no toca la base de datos ni hace redirecciones.
 * La verificación de cédula duplicada se recibe como función ($cedulaDuplicada)
 * para no acoplar el validador a la base de datos.
 *
 * Devuelve el código del PRIMER error encontrado (el mismo que usan las vistas
 * en ?error=...) o null si los datos son válidos.
 */
class ClienteValidator
{
    public const PATRON_NOMBRE = '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/';
    public const PATRON_NUMERICO = '/^[0-9]+$/';

    /** Quita espacios al inicio/fin de los campos de texto del formulario. */
    public static function normalizar(array $input): array
    {
        return [
            'cedula'          => trim((string)($input['cedula'] ?? '')),
            'nombre'          => trim((string)($input['nombre'] ?? '')),
            'apellido'        => trim((string)($input['apellido'] ?? '')),
            'telefono'        => trim((string)($input['telefono'] ?? '')),
            'correo'          => trim((string)($input['correo'] ?? '')),
            'id_tipo_cliente' => $input['id_tipo_cliente'] ?? '',
        ];
    }

    /**
     * @param array         $d               Datos ya normalizados.
     * @param callable|null $cedulaDuplicada fn(string $cedula): bool
     */
    public static function validar(array $d, ?callable $cedulaDuplicada = null): ?string
    {
        if ($d['cedula'] === '') {
            return 'cedula_vacia';
        }
        if (!preg_match(self::PATRON_NUMERICO, $d['cedula'])) {
            return 'cedula_invalida';
        }
        if ($cedulaDuplicada !== null && $cedulaDuplicada($d['cedula'])) {
            return 'cedula_duplicada';
        }

        if ($d['nombre'] === '') {
            return 'nombre_vacio';
        }
        if (!preg_match(self::PATRON_NOMBRE, $d['nombre'])) {
            return 'nombre_invalido';
        }

        if ($d['apellido'] === '') {
            return 'apellido_vacio';
        }
        if (!preg_match(self::PATRON_NOMBRE, $d['apellido'])) {
            return 'apellido_invalido';
        }

        if ($d['telefono'] === '') {
            return 'telefono_vacio';
        }
        if (!preg_match(self::PATRON_NUMERICO, $d['telefono'])) {
            return 'telefono_invalido';
        }

        if ($d['correo'] === '') {
            return 'correo_vacio';
        }
        if (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) {
            return 'correo_invalido';
        }

        if (empty($d['id_tipo_cliente'])) {
            return 'tipo_cliente_vacio';
        }

        return null;
    }
}
