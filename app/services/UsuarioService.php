<?php
require_once __DIR__ . '/../../model/UserModel.php';
require_once __DIR__ . '/../validators/UsuarioValidator.php';
require_once __DIR__ . '/AuthService.php';

/**
 * Casos de uso de usuarios del sistema, con las reglas de permisos por rol:
 *  - Solo un superusuario puede crear usuarios, cambiar roles o eliminar.
 *  - Un usuario normal solo puede editar su propio perfil.
 *  - Un superusuario no puede quitarse a sí mismo el rol de superusuario.
 *  - Nadie puede eliminarse a sí mismo.
 */
class UsuarioService
{
    public const SUPERUSUARIO = 'superusuario';

    private UserModel $usuarios;

    public function __construct(UserModel $usuarios)
    {
        $this->usuarios = $usuarios;
    }

    public static function esSuperusuario(?string $rol): bool
    {
        return $rol === self::SUPERUSUARIO;
    }

    public static function puedeEditar($idSesion, ?string $rolSesion, $idObjetivo): bool
    {
        return $idSesion == $idObjetivo || self::esSuperusuario($rolSesion);
    }

    /** @return string|null Código de error, o null si se creó. */
    public function crear(array $input, ?string $rolSesion): ?string
    {
        if (!self::esSuperusuario($rolSesion)) {
            return 'sin_permisos';
        }

        $datos = UsuarioValidator::normalizar($input);
        $error = UsuarioValidator::validar($datos, $this->verificadorDuplicados(null), true);

        if ($error !== null) {
            return $error;
        }

        $this->usuarios->insert([
            'cedula'        => $datos['cedula'],
            'nombre'        => $datos['nombre'],
            'apellido'      => $datos['apellido'],
            'correo'        => $datos['correo'],
            'usuario'       => $datos['usuario'],
            'password_hash' => AuthService::hashPassword($datos['password']),
            'rol'           => $datos['rol'],
        ]);

        return null;
    }

    /** @return string|null Código de error, o null si se actualizó. */
    public function actualizar($id, array $input, $idSesion, ?string $rolSesion): ?string
    {
        if (!self::puedeEditar($idSesion, $rolSesion, $id)) {
            return 'sin_permisos';
        }

        $datos = UsuarioValidator::normalizar($input);
        $error = UsuarioValidator::validar($datos, $this->verificadorDuplicados($id), false);

        if ($error !== null) {
            return $error;
        }

        $cambios = [
            'cedula'   => $datos['cedula'],
            'nombre'   => $datos['nombre'],
            'apellido' => $datos['apellido'],
            'correo'   => $datos['correo'],
            'usuario'  => $datos['usuario'],
        ];

        if ($datos['password'] !== '') {
            $cambios['password_hash'] = AuthService::hashPassword($datos['password']);
        }

        // Solo un superusuario puede cambiar roles.
        if (self::esSuperusuario($rolSesion) && isset($datos['rol'])) {
            if (!UsuarioValidator::rolValido($datos['rol'])) {
                return 'rol_invalido';
            }

            if ($idSesion == $id) {
                $actual = $this->usuarios->getById($id);
                if (self::esSuperusuario($actual['rol'] ?? null) && !self::esSuperusuario($datos['rol'])) {
                    return 'no_auto_cambiar_rol';
                }
            }

            $cambios['rol'] = $datos['rol'];
        }

        $this->usuarios->update($id, $cambios);

        return null;
    }

    /** @return array{ok?: bool, error?: string} */
    public function eliminar($id, $idSesion, ?string $rolSesion): array
    {
        if (!self::esSuperusuario($rolSesion)) {
            return ['error' => 'sin_permisos'];
        }

        if ($id === null || $id === '') {
            return ['error' => 'id_no_recibido'];
        }

        if ($idSesion == $id) {
            return ['error' => 'auto_eliminar'];
        }

        $this->usuarios->delete($id);

        return ['ok' => true];
    }

    /**
     * Devuelve fn(campo, valor): bool que indica si el valor ya lo usa OTRO usuario.
     */
    private function verificadorDuplicados($idActual): callable
    {
        return function (string $campo, string $valor) use ($idActual): bool {
            switch ($campo) {
                case 'cedula':
                    $existe = $this->usuarios->getByCedula($valor);
                    break;
                case 'correo':
                    $existe = $this->usuarios->getByCorreo($valor);
                    break;
                case 'usuario':
                    $existe = $this->usuarios->getByUsuario($valor);
                    break;
                default:
                    return false;
            }

            if (!$existe) {
                return false;
            }

            return $idActual === null || $existe['id'] != $idActual;
        };
    }
}
