<?php
/**
 * Reglas de validación de un usuario del sistema (extraídas de UserController).
 *
 * Clase PURA: los chequeos de duplicados (cédula, correo, usuario) se reciben
 * como una función $existe(campo, valor) para no depender de la base de datos.
 */
class UsuarioValidator
{
    public const PATRON_NOMBRE = '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/';
    public const PATRON_NUMERICO = '/^[0-9]+$/';
    public const PASSWORD_MIN = 6;
    public const ROLES = ['usuario', 'superusuario'];

    public static function normalizar(array $input): array
    {
        $datos = [
            'cedula'   => trim((string)($input['cedula'] ?? '')),
            'nombre'   => trim((string)($input['nombre'] ?? '')),
            'apellido' => trim((string)($input['apellido'] ?? '')),
            'correo'   => trim((string)($input['correo'] ?? '')),
            'usuario'  => trim((string)($input['usuario'] ?? '')),
            'password' => (string)($input['password'] ?? ''),
        ];

        if (array_key_exists('rol', $input)) {
            $datos['rol'] = trim((string)$input['rol']);
        }

        return $datos;
    }

    public static function rolValido(string $rol): bool
    {
        return in_array($rol, self::ROLES, true);
    }

    /**
     * @param array         $d                   Datos normalizados.
     * @param callable|null $existe              fn(string $campo, string $valor): bool
     * @param bool          $esCreacion          Al crear, la contraseña y el rol son obligatorios.
     *                                           Al editar, la contraseña es opcional.
     */
    public static function validar(array $d, ?callable $existe = null, bool $esCreacion = true): ?string
    {
        $existe = $existe ?? fn() => false;

        if ($d['cedula'] === '') {
            return 'cedula_vacia';
        }
        if (!preg_match(self::PATRON_NUMERICO, $d['cedula'])) {
            return 'cedula_invalida';
        }
        if ($existe('cedula', $d['cedula'])) {
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

        if ($d['correo'] === '') {
            return 'correo_vacio';
        }
        if (!filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) {
            return 'correo_invalido';
        }
        if ($existe('correo', $d['correo'])) {
            return 'correo_duplicado';
        }

        if ($d['usuario'] === '') {
            return 'usuario_vacio';
        }
        if ($existe('usuario', $d['usuario'])) {
            return 'usuario_duplicado';
        }

        if ($esCreacion) {
            if ($d['password'] === '') {
                return 'password_vacio';
            }
            if (strlen($d['password']) < self::PASSWORD_MIN) {
                return 'password_corto';
            }

            $rol = $d['rol'] ?? '';
            if ($rol === '') {
                return 'rol_vacio';
            }
            if (!self::rolValido($rol)) {
                return 'rol_invalido';
            }
        } elseif ($d['password'] !== '' && strlen($d['password']) < self::PASSWORD_MIN) {
            // Al editar, la contraseña solo se valida si el usuario escribió una nueva.
            return 'password_corto';
        }

        return null;
    }
}
