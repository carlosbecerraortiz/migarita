<?php
/**
 * Avisos de un solo uso ("flash") guardados en la sesión.
 *
 * Después de una acción (crear, editar, eliminar, error de validación…) el
 * controlador guarda el aviso aquí y redirige a una URL LIMPIA. La página
 * siguiente lo toma una vez y se borra. Así ningún código de error, monto ni
 * dato del usuario viaja en la URL, el historial o los registros del servidor.
 */
final class Flash
{
    private const CLAVE = 'mg_flash';

    /** Únicas claves que se aceptan (las mismas que leen las vistas). */
    public const PERMITIDAS = ['ok', 'error', 'monto'];

    public static function guardar(array $aviso): void
    {
        $aviso = array_intersect_key($aviso, array_flip(self::PERMITIDAS));
        $aviso = array_map(fn($v) => (string)$v, $aviso);

        // Un aviso nuevo reemplaza por completo al anterior (no se mezclan datos viejos)
        $_SESSION[self::CLAVE] = $aviso;
    }

    /** Devuelve el aviso pendiente y lo borra (solo se muestra una vez). */
    public static function tomar(): array
    {
        $aviso = $_SESSION[self::CLAVE] ?? [];
        unset($_SESSION[self::CLAVE]);

        return is_array($aviso) ? $aviso : [];
    }
}
