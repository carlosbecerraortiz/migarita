<?php
/**
 * Protección CSRF (Cross-Site Request Forgery).
 *
 * Cada sesión tiene un token secreto y aleatorio. Todo formulario y toda
 * petición AJAX que modifique datos debe enviarlo (campo "_csrf" o cabecera
 * "X-CSRF-Token"). Una página de otro sitio no puede conocer el token, así que
 * no puede hacer que el navegador del usuario envíe acciones en su nombre.
 */
final class Csrf
{
    private const CLAVE = 'mg_csrf';
    public const CAMPO = '_csrf';
    public const CABECERA = 'HTTP_X_CSRF_TOKEN';

    /** Token de la sesión actual (se crea la primera vez). */
    public static function token(): string
    {
        if (empty($_SESSION[self::CLAVE]) || !is_string($_SESSION[self::CLAVE])) {
            $_SESSION[self::CLAVE] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CLAVE];
    }

    /** Compara en tiempo constante el token recibido con el de la sesión. */
    public static function esValido($recibido): bool
    {
        $esperado = $_SESSION[self::CLAVE] ?? null;

        return is_string($recibido) && $recibido !== ''
            && is_string($esperado) && $esperado !== ''
            && hash_equals($esperado, $recibido);
    }

    /** Token enviado en la petición: primero el campo del formulario, luego la cabecera. */
    public static function tokenRecibido(array $post, array $server): ?string
    {
        $token = $post[self::CAMPO] ?? $server[self::CABECERA] ?? null;

        return is_string($token) ? $token : null;
    }

    /** Campo oculto para incluir dentro de cada <form method="POST">. */
    public static function campo(): string
    {
        return '<input type="hidden" name="' . self::CAMPO . '" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
